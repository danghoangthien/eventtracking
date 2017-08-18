<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Hyper\EventBundle\Document\Person;
use Hyper\EventBundle\Document\Transaction;
use Hyper\EventBundle\Annotations\CsvMetaReader;

//Added 2015-08-11
use Symfony\Component\Filesystem\Filesystem;

class CsvImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('csv:import')
            ->setDescription('load csv parse to JSON and store to S3')
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'CSV file path to import'
            )
            // 2015-08-12 - Ding Dong : Added filename for process status
            ->addOption(
                'child_id',
                null,
                InputOption::VALUE_REQUIRED,
                'Child process ID; -1 - Mother process; 0> - Child process'
            )
            // 2015-08-10 - Ding Dong : Added this option to enable users of this command to specify the app_id
            ->addOption(
                'app_id',
                null,
                InputOption::VALUE_OPTIONAL,
                'App ID'
            )
            // 2015-09-02 - Ding Dong : Added this option to enable users of this command to specify the app_name
            ->addOption(
                'app_name',
                null,
                InputOption::VALUE_OPTIONAL,
                'App Name'
            )
            // 2015-08-10 - Ding Dong : Added this option to enable users of this command to specify the event_type
            ->addOption(
                'event_type',
                null,
                InputOption::VALUE_OPTIONAL,
                'Event Type'
            )
            // 2015-08-11 - Ding Dong : Added this option to capture the Provider ID; Prior it is "$providerId = 0";
            ->addOption(
                'provider_id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Provider ID'
            )
            // 2015-08-20 - Ding Dong : Sets the reference ID for consolidating the update status";
            ->addOption(
                'upload_id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Upload process unique ID'
            )
            // 2015-09-03 - Ding Dong : Sets the Attribution Type";
            ->addOption(
                'attribution_type',
                null,
                InputOption::VALUE_OPTIONAL,
                'Attribution Type'
            )
            // 2015-09-03 - Ding Dong : Sets the S3 Folder";
            ->addOption(
                'app_folder',
                null,
                InputOption::VALUE_OPTIONAL,
                'S3 Folder'
            )
            // 2015-09-03 - Ding Dong : Sets the Mobile OS Platform based on Application ID";
            ->addOption(
                'platform',
                null,
                InputOption::VALUE_OPTIONAL,
                'Mobile OS Platform'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get parameters passed
        echo "exe \n";
        $file             = $input->getOption('file');
        $app_id           = $input->getOption('app_id');
        $app_name         = $input->getOption('app_name');
        $event_type       = $input->getOption('event_type');
        $provider_id      = $input->getOption('provider_id');
        $child_id         = $input->getOption('child_id');
        $upload_id        = $input->getOption('upload_id');
        $attribution_type = $input->getOption('attribution_type');
        $app_folder       = $input->getOption('app_folder');
        $platform         = $input->getOption('platform');
        
        // Calls function parseCsvContent() to start processing
        $contents    = $this->parseCsvContent($file, $child_id, $app_id, $app_name, $event_type, $provider_id, $upload_id, $attribution_type, $app_folder, $platform);

    }

    protected function parseCsvContent($csvFile, $child_id, $app_id, $app_name, $event_type, $provider_id, $upload_id, $attribution_type, $app_folder, $platform)
    {
        echo "start parsing \n";
        $fs = new Filesystem();
        $process_start_datetime = time();

        if (-1 == $child_id) {
            echo "child_id = -1  \n";
            $progress_summary_log = "/tmp/upload_".$upload_id."_log.json";

            // Split the file by 10000 records
            $file_part_list = array();
            if (($handle = fopen($csvFile, "r")) !== false) {
                echo "read each csv line  \n";
                $i = -1;
                $split_num = 0;
                $header = array();

                while(($row = fgets($handle)) !== false) {
                    if($i == -1){
                        $header = $row;

                    } else if (0 == $i) {
                        $file_part_list[$split_num]['queue_id']        = $split_num;
                        $file_part_list[$split_num]['cmd_proc_id']     = 0;
                        $file_part_list[$split_num]['processed_count'] = 0;
                        $file_part_list[$split_num]['success_count']   = 0;
                        $file_part_list[$split_num]['failed_count']    = 0;
                        $file_part_list[$split_num]['csv_file']        = $csvFile.".".$split_num;
                        $file_part_list[$split_num]['status_file']     = "/tmp/file_part_".$upload_id."_".$split_num.".log.json";
                        $file_part_list[$split_num]['status']          = "";

                        $handle_split = fopen($file_part_list[$split_num]['csv_file'], 'a');

                        fwrite($handle_split, $header);
                        fwrite($handle_split, $row);
                    } else {

                        fwrite($handle_split, $row);
                    }
                    $i++;

                    if (10000 == $i) {
                        $i = 0;
                        $split_num++;

                        fclose($handle_split);
                    }
                }
                fclose($handle);
                echo "delete original csv file $csvFile \n";
                // 2015-08-17 - Ding Dong : Delete original CSV file
                //unlink($csvFile);
                $fs->remove($csvFile);

                $file_count = count($file_part_list);
                $children_max    = 15;
                $completed_child = 0;
                echo ":file_count $file_count \n";
                while ($completed_child < $file_count) {
                    $active_child = 0;
                    $completed_child = 0;

                    foreach($file_part_list as $file_part_key => $file_part) {
                        if ( 0 == $file_part['cmd_proc_id'] ) {
                            echo "execute sub process {$file_part['queue_id']} \n";
                            $cmd = "php /var/www/html/projects/event_tracking/app/console csv:import --file='".$file_part['csv_file']."' --child_id='".$file_part['queue_id']."' --app_id='".$app_id."' --app_name='".$app_name."' --event_type='".$event_type."' --provider_id='".$provider_id."' --upload_id='".$upload_id."' --attribution_type='".$attribution_type."' --app_folder='".$app_folder."' --platform='".$platform."'";
                            echo "$cmd";
                            exec($cmd);
                            //exec("php /var/www/html/projects/event_tracking/app/console csv:import --file='".$file_part['csv_file']."' --child_id='".$file_part['queue_id']."' --app_id='".$app_id."' --app_name='".$app_name."' --event_type='".$event_type."' --provider_id='".$provider_id."' --upload_id='".$upload_id."' --attribution_type='".$attribution_type."' --app_folder='".$app_folder."' --platform='".$platform."'> /dev/null 2>/dev/null &");
                            $active_child++;
                        } else if ( "running" == $file_part['status'] ) {
                            $active_child++;
                        } else if ( "done" == $file_part['status'] ) {
                            $completed_child++;
                        }

                        if ($active_child == $children_max) {
                            break;
                        }

                        if ($file_count == $completed_child) {
                            break 2;
                        }
                    }

                    sleep(60);

                    $is_moniting_on = 1;
                    while ($is_moniting_on) {
                        $total_processed = 0;
                        $total_success   = 0;
                        $total_failed    = 0;
                        $active_child    = 0;
                        $completed_child = 0;

                        // Check if the command is still active by checking the process ID
                        $proc_list = array();
                        exec("ps auxwww | grep php | grep ".$upload_id." | awk '{print $2}'", $proc_list);

                        foreach($file_part_list as $file_part_key => $file_part) {
                            $status_details = $this->getProcessLogData($file_part['status_file']);

                            if (null != $status_details) {
                                $file_part_list[$file_part_key]['queue_id']        = $status_details['queue_id'];
                                $file_part_list[$file_part_key]['cmd_proc_id']     = $status_details['cmd_proc_id'];
                                $file_part_list[$file_part_key]['processed_count'] = $status_details['processed_count'];
                                $file_part_list[$file_part_key]['success_count']   = $status_details['success_count'];
                                $file_part_list[$file_part_key]['failed_count']    = $status_details['failed_count'];
                                $file_part_list[$file_part_key]['csv_file']        = $status_details['csv_file'];
                                $file_part_list[$file_part_key]['status_file']     = $status_details['status_file'];
                                $file_part_list[$file_part_key]['status']          = $status_details['status'];

                                $total_processed += $file_part_list[$file_part_key]['processed_count'];
                                $total_success   += $file_part_list[$file_part_key]['success_count'];
                                $total_failed    += $file_part_list[$file_part_key]['failed_count'];

                                if ( "running" == $file_part_list[$file_part_key]['status'] ) {
                                    if (0 <count($proc_list)) {
                                        if (in_array($file_part_list[$file_part_key]['cmd_proc_id'], $proc_list)) {
                                            $active_child++;
                                        } else {
                                            // Double check. The process might have completed at this point but not captured in about for loop
                                            $status_details = $this->getProcessLogData($file_part['status_file']);
                                            if (null != $status_details) {
                                                if ("done" == $status_details['status']) {
                                                    $completed_child++;
                                                } else {
                                                    $file_part_list[$file_part_key]['status'] = "";
                                                    $file_part_list[$file_part_key]['cmd_proc_id'] = 0;
                                                }
                                            }
                                        }
                                    }
                                } else if ( "done" == $file_part_list[$file_part_key]['status'] ) {
                                    $completed_child++;
                                }
                            }
                        }

                        $progress_summary['total_records']          = $total_processed;
                        $progress_summary['total_processed']        = $total_success + $total_failed;
                        $progress_summary['total_success']          = $total_success;
                        $progress_summary['total_failed']           = $total_failed;
                        $progress_summary['process_start_datetime'] = $process_start_datetime;
                        $progress_summary['process_end_datetime']   = " --- ";
                        $progress_summary['timeDiff']               = " --- ";
                        $progress_summary['upload_id']              = $upload_id;
                        $progress_summary['status']                 = 2;
                        $progress_summary['status_msg']             = "Uploading to S3 in process.";

                        $fs->dumpFile($progress_summary_log, json_encode($progress_summary));

                        if ($file_count == $completed_child) {
                            break 2;
                        } else if (($active_child < $children_max) && (($active_child + $completed_child) < $file_count)) {
                            break;
                        } else {
                            $is_moniting_on = 1;
                        }

                        sleep(60);
                    }
                }
            }

            // 2015-08-06 - Ding Dong : Added block to consolidate process information for display
            // START
            $process_end_datetime = time();
            $timeDiff = $process_end_datetime - $process_start_datetime;

            $total_processed = 0;
            $total_success   = 0;
            $total_failed    = 0;

            foreach($file_part_list as $file_part_key => $file_part) {
                $status_details = $this->getProcessLogData($file_part['status_file']);

                if (null != $status_details) {
                    $file_part_list[$file_part_key]['queue_id']        = $status_details['queue_id'];
                    $file_part_list[$file_part_key]['cmd_proc_id']     = $status_details['cmd_proc_id'];
                    $file_part_list[$file_part_key]['processed_count'] = $status_details['processed_count'];
                    $file_part_list[$file_part_key]['success_count']   = $status_details['success_count'];
                    $file_part_list[$file_part_key]['failed_count']    = $status_details['failed_count'];
                    $file_part_list[$file_part_key]['csv_file']        = $status_details['csv_file'];
                    $file_part_list[$file_part_key]['status_file']     = $status_details['status_file'];
                    $file_part_list[$file_part_key]['status']          = $status_details['status'];

                    $total_processed += $file_part_list[$file_part_key]['processed_count'];
                    $total_success   += $file_part_list[$file_part_key]['success_count'];
                    $total_failed    += $file_part_list[$file_part_key]['failed_count'];
                    //echo "line 298";
                    //$fs->remove($file_part['status_file']);
                }
            }

            $progress_summary['total_records']          = $total_processed;
            $progress_summary['total_processed']        = $total_success + $total_failed;
            $progress_summary['total_success']          = $total_success;
            $progress_summary['total_failed']           = $total_failed;
            $progress_summary['process_start_datetime'] = $process_start_datetime;
            $progress_summary['process_end_datetime']   = $process_end_datetime;
            $progress_summary['timeDiff']               = $timeDiff;
            $progress_summary['upload_id']              = $upload_id;
            $progress_summary['status']                 = 3;
            $progress_summary['status_msg']             = "Done";

            $fs->dumpFile($progress_summary_log, json_encode($progress_summary));

        } else {
            echo "process_id != -1 \n";
            $content_lines = array();
            $success_count = 0;
            $fail_count = 0;

            $status_details['queue_id']        = $child_id;
            $status_details['cmd_proc_id']     = getmypid();
            $status_details['processed_count'] = 0;
            $status_details['success_count']   = $success_count;
            $status_details['failed_count']    = $fail_count;
            $status_details['csv_file']        = $csvFile;
            $status_details['status_file']     = "/tmp/file_part_".$upload_id."_".$child_id.".log.json";
            $status_details['status']          = "running";

            // 2015-08-11 - Ding Dong : Added to set the correct provider ID selected by user
            $providerId = $provider_id;

            //$content = array();
             echo "get storageController \n";
            $storageController = $this->getContainer()->get('hyper_event.csv_upload_controller');
            echo "storageController retreived: \n";
            $amazonBaseURL = $storageController->getAmazonBaseURL();
            $rootDir = $storageController->get('kernel')->getRootDir();
            echo "rootDir : $rootDir \n";
            $rawLogDir = $rootDir. '/../web/raw_event';

            if (($handle = fopen($csvFile, "r")) !== false) {
                $i = 0;
                $header = array();
                while(($row = fgetcsv($handle)) !== false) {
                    if($i == 0){
                        $header = $row;
                    } else {
                        $contentIndex = $i-1;
                        $content = array();

                        // 2015-08-12 - Ding Dong : All files will be mapped using $header; Replace code block above
                        foreach ($header as $index => $columnName) {
                            $content[strtolower($columnName)] = $row[$index];
                        }

                        $rawContent = json_encode($content);

                        // 2015-08-10 - Ding Dong : Condition block added to check if the field app_id and event_type is in
                        //                          the CSV record if not it will add the $app_id parameter passed to this
                        //                          parseCsvContent(). This method is used to minimize revisions on
                        //                          StorageControllerV4::storeEventS3() because there are other functions
                        //                          using it.
                        //                          NOTE : Did not add this prior to setting value to $rawContent
                        if (! isset($content['app_id'])) {
                            $content['app_id'] = $app_id;
                        }

                        if (! isset($content['event_type'])) {
                            $content['event_type'] = $event_type;
                        }

                        // 2015-08-11 - Ding Dong : Added Condition block below to check if provider_id is 2 (hasoffer).
                        //                          This is to translate hasoffer date field "created" into event_time
                        //                          so StorageController::storeEventS3() can process it.
                        //                          Custom (3) format is assumed to be the same as appsflyer
                        /* Continued 2015-10-21 Paul Francisco */
                        if ('hasoffer' == $providerId) 
                        {
                            $content['app_id']           = $app_id;
                            $content['app_name']         = $app_name;
                            $content['platform']         = $platform;
                            $content['event_type']       = $event_type;
                            $content['attribution_type'] = $attribution_type;
                            
                            $file = "/tmp/hasoffer_".$upload_id.".json";
                            $fs->dumpFile($file, json_encode($content));
                            echo "$file dumped \n";
                            var_dump(get_class($storageController));
                            $content = $storageController->mapToSchemaHasOffer($content);
                            echo "content ready \n";
                            $rawContent = json_encode($content);
                        }

                        // Added for appsflyer
                        if ('appsflyer' == $providerId) {
                            $file = "/tmp/appsflyer_".$upload_id.".json";
                            $fs->dumpFile($file, json_encode($content));
                            
                            $device_info = $storageController->getDeviceInfo($content['device_type']);
                            unset($content['device type']);

                            $content['device_model']     = $device_info['device_model'];
                            $content['device_brand']     = $device_info['device_brand'];

                            $content['platform']         = $platform;
                            $content['app_name']         = $app_name;
                            $content['app_id']           = $app_id;
                            $content['event_type']       = $event_type;
                            //$content['attribution_type'] = $attribution_type;
                            $content['attribution_type'] = ''; // Angie's latest requirement

                            $content = $storageController->mapToSchemaAppsflyer($content);
                            $rawContent = json_encode($content);
                        }

                        $result = $storageController->storeEventS3(
                            $rawContent,
                            $content,
                            $amazonBaseURL,
                            $rawLogDir,
                            $app_folder,
                            $provider_id
                        );
                        
                        // 2015-08-06 - Ding Dong : Added condition block added to check if the file was created in S3 bucket and indicate status
                        if (null != $result) {
                            // File creation successful
                            $content_lines[$contentIndex]["s3_path"] = $result;
                            $content_lines[$contentIndex]["status"] = "Success";
                            $success_count++;
                            
                            /* store log */
                            //storeLogEventToRedshift($providerId,$filePath,$content);
                            $metaData = array();
                            $metaData['s3_log_file'] = $result;
                            $redshift = $this->getContainer()->get('redshift_service');
                            if ('appsflyer' == $providerId ) {
                                $providerId = 1;
                            }
                            if ('hasoffer' == $providerId ) {
                                $providerId = 2;
                            }
                            $redshift->storeLogEventToRedshift($providerId,$content,$metaData);
                        } else {
                            // File creation failed
                            $content_lines[$contentIndex]["s3_path"] = "";
                            $content_lines[$contentIndex]["status"] = "Failed";
                            $fail_count++;
                        }
                    }

                    if (99 == (count($content_lines) % 100)) {
                        $status_details['processed_count'] = count($content_lines);
                        $status_details['success_count']   = $success_count;
                        $status_details['failed_count']    = $fail_count;

                        $this->createStatusFile($status_details['status_file'], $status_details);
                    }

                    $i++;
                }

                fclose($handle);
            }

            $status_details['processed_count'] = count($content_lines);
            $status_details['success_count']   = $success_count;
            $status_details['failed_count']    = $fail_count;
            $status_details['status']          = "done";

            $this->createStatusFile($status_details['status_file'], $status_details);
            echo "removing file";
            //$fs->remove($csvFile);
        }
    }

    protected function createStatusFile($status_file, $status_details) {
        echo "create $status_file";
        $fs = new Filesystem();
        $status_details_json = json_encode($status_details);
        $fs->dumpFile($status_file, $status_details_json);
        //chmod($status_file,0777);
        echo "chmod 0777 $status_file";
        return 1;
    }

    protected function getProcessLogData($process_log_file) {
        if (file_exists($process_log_file)) {
            if (($handle = fopen($process_log_file, "r")) !== false) {
                while(($line = fgets($handle)) !== false) {
                    $status_details = json_decode($line, 1);
                }
                fclose($handle);
                return $status_details;
            }
        }
        return null;
    }
}