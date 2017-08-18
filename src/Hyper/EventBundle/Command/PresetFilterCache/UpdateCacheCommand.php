<?php
namespace Hyper\EventBundle\Command\PresetFilterCache;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Hyper\Domain\Filter\Filter;
use Hyper\Domain\Authentication\Authentication;
use Hyper\EventBundle\Service\PresetFilterParser\PresetFilterParser;
use Hyper\EventBundle\Service\FilterService\FilterService;
use Hyper\EventBundle\Service\Cached\User\UserFilterCached;
use Hyper\Domain\Setting\Setting;
use Hyper\EventBundle\Service\Cached\Card\LastCardPushToQueueCached;
use Hyper\EventBundle\Service\Cached\Setting\SettingCached;

class UpdateCacheCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('preset_filter_cache:update_cache')
            ->setDescription('Update Cache for preset filter')
            ->addOption(
                'preset_filter_id',
                null,
                InputOption::VALUE_REQUIRED,
                'Preset Filter ID is required!'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $presetFilterId = $input->getOption('preset_filter_id');
        $startTime = date('Y-m-d H:i:s');
        \Hyper\EventBundle\Service\FilterService\FilterService::logger($startTime, "The filter_id $presetFilterId is processing.");
        try {
            $filterRepo = $this->getContainer()->get('filter_repository');
            $presetFilter = $filterRepo->find($presetFilterId);

            if (!$presetFilter instanceof Filter) {
                throw new \Exception("The Filter $presetFilterId not found.");
            }
            $authRepo = $this->getContainer()->get('authentication_repository');
            $auth = $authRepo->find($presetFilter->getAuthenticationId());
            if (!$auth instanceof Authentication) {
                throw new \Exception("The Filter $presetFilterId with the auth not found.");
            }
            //check if have new event, we will recaculate card
            if(!$this->checkGenerateCache($presetFilter->getFilterData()) && $presetFilter->getLastUpdateCache()){
                throw new \Exception("The Filter $presetFilterId does not recaculate.");
            }
            $filterService = new FilterService(
                $this->getContainer()
            );
            $filterService = $filterService->execute($presetFilter);
            $serializer = $this->getContainer()->get('jms_serializer');
            $presetFilterCached = $serializer->toArray($presetFilter);
            $presetFilterCached['profile_count'] = $filterService->getProfileCount();
            $presetFilterCached['export_csv_path'] = $filterService->getExportCsvPath();
            $presetFilterCached['audience_csv_path'] = $filterService->getAudienceCsvPath();
            $presetFilterCached['email_csv_path'] = $filterService->getEmailCsvPath();
            $userFilterCached = new UserFilterCached($this->getContainer(), $auth->getId());
            $userFilterCached->hset($presetFilterCached['id'], json_encode($presetFilterCached));
            $presetFilter->setLastUpdateCache(time());
            $filterRepo->save($presetFilter);
            $filterRepo->completeTransaction();
        } catch(\Exception $e) {
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('error',$e->getMessage());
        }

        $endTime = date('Y-m-d H:i:s');
        \Hyper\EventBundle\Service\FilterService\FilterService::logger($endTime, "The filter_id $presetFilterId is done.");
        if ($this->checkLastCardPushToQueue($presetFilterId)) {
            $this->startHorizontalProcess();
        }
    }
    protected function checkGenerateCache($data){
        $appTitleIds = [];
        $now = time();
        $maxTime = 0;
        foreach ($data['audience'] as $value) {
            if(isset($value['history'])){
                $history = $value['history'];
                $appTitleIds[] = $history['in'];
                if($history['type'] == 'last_happened_at'){
                    list($day, $month, $year) = explode('/', $history['value'][0]);//get first element
                    $time =  mktime(0, 0, 0, $month, $day, $year);
                    $maxTime =$time > $maxTime ? $time : $maxTime;
                    continue;
                }
                if($history['type'] == 'install_time_duration'){
                    list($day, $month, $year) = explode('/', $history['value'][1]);//get second element
                    $time =  mktime(0, 0, 0, $month, $day, $year);
                    $maxTime =$time > $maxTime ? $time : $maxTime;
                    continue;
                }

            }
            if(isset($value['usage'])){
                $usage = $value['usage'];
                $appTitleIds[] = $usage['in'];
                if($usage['happened_at']['type'] == 'duration'){
                    list($day, $month, $year) = explode('/', $usage['happened_at']['value'][1]);
                    $time =  mktime(0, 0, 0, $month, $day, $year);
                    $maxTime = $time > $maxTime ? $time : $maxTime;
                }
                continue;
            }

        }
        $appTitleIds = array_unique($appTitleIds);
        $actionRepo = $this->getContainer()->get('application_platform_repository');
        $total = $actionRepo->countLastActivityByAppTitleId($appTitleIds);
        //check new event
        if($total == 0){
            return false;
        }
        if($maxTime < $now){
            return false;
        }
        return true;
    }

    protected function startHorizontalProcess()
    {
        $settingCached = new SettingCached($this->getContainer());
        $settingCached->hset(Setting::PRE_EVENT_HANDLING_TYPE_KEY, Setting::STATUS_START_VALUE);
        $settingCached->hset(Setting::EVENT_HANDLING_TYPE_KEY, Setting::STATUS_START_VALUE);
        $settingCached->hset(Setting::POST_EVENT_HANDLING_TYPE_KEY, Setting::STATUS_START_VALUE);
        $settingCached->hset(Setting::IDENTITY_CAPTURE_HANDLING_TYPE_KEY, Setting::STATUS_START_VALUE);
    }

    protected function checkLastCardPushToQueue($presetFilterId)
    {
        $lastCardPushToQueueCached = new LastCardPushToQueueCached($this->getContainer());;
        $value = $lastCardPushToQueueCached->get();
        if ($value) {
            $value = json_decode($value, true);
        }
        if (
            !empty($value['id']) && $value['id'] == $presetFilterId
        ) {
            return true;
        }

        return false;
    }
}