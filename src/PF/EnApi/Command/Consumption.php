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
            ->setName('en:consumption')
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
               InputArgument::OPTIONAL,
               'Show comsumption by hour, day, month or year?',
               'day'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $customerNo = $input->getArgument('customerNo');
        $pin = $input->getArgument('pin');

        $resolutions = array(
            'hour' => 1,
            'day' => 2,
            'month' => 3,
            'year' => 4
        );

        if(!array_key_exists($input->getOption('resolution'), $resolutions)) {
            throw new \Exception(sprintf('Unknown resolution %s', $resolution));
        }

        $resolution = $resolutions[$input->getOption('resolution')];

        $cc = new \PF\EnApi\Com\JsonApi($customerNo, $pin);
        $cc->setResolution($resolution);
        $response = $cc->getConsumption();

        foreach($response->HentForbrugResult->ReturnData as $entry) {
            print date('Y-m-d', substr($entry->Key, 6, 10)) . ' : ' . $entry->Value . ' kwh' . PHP_EOL;
        }
    }
}
