<?php
namespace PF\EnApi\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Consumption extends Command
{
    protected function configure()
    {
        $this
            ->setName('en:comsumption')
            ->setDescription('Get consumption values')
            ->addArgument(
                'customerNo',
                InputArgument::REQUIRED,
                'Your customer number'
            )
            ->addArgument(
                'pin',
                InputArgument::REQUIRED,
                'Your login pin code'
            )
            ->addOption(
               'resolution',
               null,
               InputOption::VALUE_NONE,
               'If set, the task will yell in uppercase letters'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $customerNo = $input->getArgument('customerNo');
        $pin = $input->getArgument('pin');

        $cc = new \PF\EnApi\Com\JsonApi($customerNo, $pin);

        foreach($cc->getConsumption() as $entry) {
            var_dump($entry);
        }
    }
}
