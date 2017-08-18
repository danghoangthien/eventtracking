<?php
namespace Hyper\EventProcessingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Hyper\Domain\Setting\Setting;

class EventHandingControlCommand extends ContainerAwareCommand
{
    protected $settingRepo;
    protected $type;
    protected $status;
    protected $container;

    protected function configure()
    {
        $this->type = array(
            Setting::PRE_EVENT_HANDLING_TYPE_KEY
            , Setting::EVENT_HANDLING_TYPE_KEY
            , Setting::POST_EVENT_HANDLING_TYPE_KEY
            , Setting::IDENTITY_CAPTURE_HANDLING_TYPE_KEY

        );
        $this->status = array(Setting::STATUS_START_VALUE, Setting::STATUS_STOP_VALUE);
        $type = implode(', ', $this->type);
        $status = implode(', ', $this->status);
        $this
            ->setName('event_processing:event_handling:control')
            ->setDescription('Event Handling Control')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                "Type of Event Handling: {$type}"
            )
            ->addArgument(
                'status',
                InputArgument::REQUIRED,
                "Status of Event Handling : {$status}"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initService();
        $type = $input->getArgument('type');
        if (!in_array($type,$this->type)) {
            throw new \Exception("Missing {$type} key param in settings table.");
        }
        $status = $input->getArgument('status');
        if (!in_array($status, $this->status)) {
            throw new \Exception("Missing $status key param in settings table.");
        }
        try {
            $this->setSetting($type, $status);
            echo "{$type} is {$status}ing \n";
        } catch(\Exception $e) {

        }
    }

    protected function initService()
    {
        $this->settingRepo = $this->getContainer()->get('setting_repository');
    }

    public function setSetting($type, $status)
    {
        $setting = $this->settingRepo->findOneByKey($type);
        if (!$setting instanceof Setting) {
            $setting = new Setting();
        } else {
            $setting->setKey($type);
        }
        $setting->setKey($type);
        $setting->setValue($status);
        $this->settingRepo->save($setting);
        $this->settingRepo->completeTransaction();
    }

}