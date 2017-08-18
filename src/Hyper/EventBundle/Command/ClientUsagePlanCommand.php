<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hyper\EventBundle\Service\ClientUsagePlanService\ClientUsagePlanService;
use Hyper\EventBundle\Service\Cached\Client\ClientUsagePlanCached;

class ClientUsagePlanCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('client:client_usage_plan')
            ->setDescription('Update client usage plan');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientUsagePlanService = new ClientUsagePlanService(
            $this->getContainer()->get('client_repository')
            , $this->getContainer()->get('client_app_title_repository')
            , $this->getContainer()->get('action_repository')
            , $this->getContainer()->get('application_platform_repository')
            , new ClientUsagePlanCached($this->getContainer())
        );
        $clientUsagePlanService->handle();
    }


}