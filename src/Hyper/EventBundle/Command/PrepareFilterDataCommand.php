<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Hyper\Domain\Filter\Filter;
use Hyper\Domain\Authentication\Authentication;
use Hyper\EventBundle\Service\PresetFilterParser\PresetFilterParser;
use Hyper\EventBundle\Service\Cached\User\UserFilterCached;

class PrepareFilterDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('preset_filter:prepare_filter_data')
            ->setDescription('Update Filter Data for preset filter');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->prepareV3();
    }

    public function prepareV2()
    {
        try {
            $em = $this->getContainer()->get('doctrine')->getManager('pgsql');
            $filterRepo = $this->getContainer()->get('filter_repository');
            $listPresetFilter = $filterRepo->findAll();
            if (!$listPresetFilter) {
                throw new \Exception("No record to update.");
            }
            foreach($listPresetFilter as $filter) {
                $presetFilterId = $filter->getId();
                echo "\nThe filter_id $presetFilterId is processing. \n";
                $filterData = $filter->getFilterData();
                if (empty($filterData)) {
                    echo "Filter data is empty. \n";
                    continue;
                } else {
                    $filterDataBefore = serialize($filterData);
                    echo "Filter data before: $filterDataBefore \n";
                }
                $isParsed = false;
                $install_time_since = '';
                if (!empty($filterData['install_time_since'])) {
                    $filterData['history'][0]['install_time_since'] = $filterData['install_time_since'];
                    unset($filterData['install_time_since']);
                }
                $appTitleId = '';
                $appIds = [];
                if (!empty($filterData['app_title'])) {
                    $appTitleId = $filterData['app_title'][0];
                    $em = $this->getContainer()->get('doctrine')->getManager('pgsql');
                    $listAppFlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')
                        ->findByAppTitle($filterData['app_title']);
                    if (!empty($listAppFlatform)) {
                        foreach ($listAppFlatform as $appPlatform) {
                            $appIds[] = $appPlatform->getAppId();
                        }
                    }
                }
                if (!empty($filterData['last_happened_at'])) {
                    $filterData['history'][0]['last_happened_at'] = $filterData['last_happened_at'];
                    unset($filterData['last_happened_at']);
                    $isParsed = true;
                }
                if (!empty($filterData['last_happened_at'])) {
                    $filterData['history'][0]['last_happened_at'] = $filterData['last_happened_at'];
                    unset($filterData['last_happened_at']);
                    $isParsed = true;
                }
                if (!empty($filterData['history'][0])) {
                    $filterData['history'][0]['in'] = $appTitleId;
                    $filterData['history'][0]['app_id'] = $appIds;
                    $isParsed = true;
                }
                if (!empty($filterData['actions'])) {
                    foreach($filterData['actions'] as $key => $action) {
                        if (isset($filterData['actions'][$key]['frequent']['value'])) {
                            $value = $filterData['actions'][$key]['frequent']['value'];
                            unset($filterData['actions'][$key]['frequent']['value']);
                            $filterData['actions'][$key]['frequent']['value'][0] = $value;
                            $filterData['actions'][$key]['frequent']['value'][1] = '';
                            $isParsed = true;
                        }
                        if (!empty($appTitleId)) {
                            $filterData['actions'][$key]['in'] = $appTitleId;
                            $filterData['actions'][$key]['app_id'] = $appIds;
                            $isParsed = true;
                        }
                    }
                }
                $filterDataAfter = serialize($filterData);
                echo "Filter data after: $filterDataAfter \n";
                if ($isParsed) {
                    $filter->setFilterData($filterData);
                    $em->persist($filter);
                    $em->flush();
                    echo "\nThe filter_id $presetFilterId is done. \n";
                }
            }
        } catch(\Exception $e) {
            echo "Error: ".$e->getMessage() . "\n";
            echo "The filter_id $presetFilterId is fail. \n";

        }
    }

    public function prepareV3()
    {
        try {
            $em = $this->getContainer()->get('doctrine')->getManager('pgsql');
            $filterRepo = $this->getContainer()->get('filter_repository');
            $listPresetFilter = $filterRepo->findAll();
            if (!$listPresetFilter) {
                throw new \Exception("No record to update.");
            }
            foreach($listPresetFilter as $filter) {
                $presetFilterId = $filter->getId();
                echo "\nThe filter_id $presetFilterId is processing. \n";
                $filterData = $filter->getFilterData();
                $isParsed = false;
                if (empty($filterData)) {
                    echo "Filter data is empty. \n";
                    continue;
                } else {
                    $filterDataBefore = serialize($filterData);
                    echo "Filter data before: $filterDataBefore \n";
                }
                if (!empty($filterData['audience'])) {
                    foreach ($filterData['audience'] as $key => $audienceType) {
                        if (!empty($audienceType['history'])) {
                            $value = $audienceType['history']['value'];
                            unset($filterData['audience'][$key]['history']['value']);
                            $filterData['audience'][$key]['history']['value'][0] = $value;
                            $filterData['audience'][$key]['history']['value'][1] = '';
                            $isParsed = true;
                        } elseif (!empty($audienceType['usage'])) {
                            if (
                                !empty($audienceType['usage']['happened_at_from'])
                                && !empty($audienceType['usage']['happened_at_to'])
                            ) {
                                $filterData['audience'][$key]['usage']['happened_at']['type'] = 'duration';
                                $filterData['audience'][$key]['usage']['happened_at']['value'][0] = $audienceType['usage']['happened_at_from'];
                                $filterData['audience'][$key]['usage']['happened_at']['value'][1] = $audienceType['usage']['happened_at_to'];
                                unset($filterData['audience'][$key]['usage']['happened_at_from']);
                                unset($filterData['audience'][$key]['usage']['happened_at_to']);
                                $isParsed = true;
                            }
                        }
                    }
                }
                $filterDataAfter = serialize($filterData);
                echo "Filter data after: $filterDataAfter \n";
                if ($isParsed) {
                    $filter->setFilterData($filterData);
                    $em->persist($filter);
                    $em->flush();
                    echo "\nThe filter_id $presetFilterId is done. \n";
                }
            }
        } catch(\Exception $e) {
            echo "Error: ".$e->getMessage() . "\n";
            echo "The filter_id $presetFilterId is fail. \n";

        }
    }
}