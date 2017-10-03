<?php
namespace Keboola\DropboxWriterV2;

// use Keboola\Csv\CsvFile;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
// use Keboola\Db\Import\Exception as SnowflakeImportException;


class RunCommand extends Command
{
    protected function configure() {
        $this->setName('run');
        $this->setDescription('Runs the App');
        $this->addArgument('data directory', InputArgument::REQUIRED, 'Data directory');
    }

    protected function execute(InputInterface $input, OutputInterface $consoleOutput) {
        $dataDirectory = $input->getArgument('data directory');
        print "run test " . $dataDirectory;
        return 0;
    }
}