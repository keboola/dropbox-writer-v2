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
use Alorel\Dropbox\Operation\Files\Upload;
use Alorel\Dropbox\Options\Builder\UploadOptions;
use Alorel\Dropbox\Parameters\WriteMode;
use Guzzle\Http\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;




class RunCommand extends Command
{
    protected function configure() {
        $this->setName('run');
        $this->setDescription('Runs the App');
        $this->addArgument('data directory', InputArgument::REQUIRED, 'Data directory');
    }
    protected function execute(InputInterface $input, OutputInterface $consoleOutput)
    {
        $dataDirectory = $input->getArgument('data directory');

        try {
            $this->runWriter($consoleOutput, $dataDirectory);
            return 0;
        } catch (UserException $e) {
            $consoleOutput->writeln($e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $consoleOutput->writeln("{$e->getMessage()}\n{$e->getTraceAsString()}");
            return 2;
        }
    }

    private function runWriter(OutputInterface $consoleOutput, $dataDirectory)
    {
        $configFilePath = "$dataDirectory/config.json";
        if (!file_exists($configFilePath)) {
            throw new \Exception("Config file not found at path $configFilePath");
        }
        $decode = new JsonDecode(true);
        $config = $decode->decode(file_get_contents($configFilePath), JsonEncoder::FORMAT);
        if (empty($config['authorization'])) {
            throw new UserException('Missing authorization data');
        }
        $tokenData = json_decode($config['authorization']['oauth_api']['credentials']['#data'], true);
        $token = $tokenData["access_token"];
        $dropboxClient = new Upload(false, $token);
        $options = (!empty($config['parameters']['mode']) && $config['parameters']['mode'] == 'rewrite')
        ? (new UploadOptions())->setWriteMode(WriteMode::overwrite())
        : (new UploadOptions())->setWriteMode(WriteMode::add())->setAutoRename(true);

        $inFiles = $this->prepareFilesToUpload("$dataDirectory/in/files", function($path) {return $this->tryParseNameFromManifest($path);});
        $inTables = $this->prepareFilesToUpload("$dataDirectory/in/tables", "basename");
        $allFiles = array_merge($inFiles, $inTables);
        $count = count($allFiles);
        $consoleOutput->writeln("Found $count items to upload:");
        $idx = 1;
        foreach ($allFiles as $filePath => $dst) {
            $consoleOutput->writeln("[$idx]. $dst upload started");
            $this->uploadFile($filePath, '/' . $dst, $dropboxClient, $options);
            $consoleOutput->writeln("[$idx]. $dst upload finished");
            $idx++;
        }
    }

    private function uploadFile($filePath, $dst, $client, $options) {
		try {
			$client->raw($dst, fopen($filePath, 'r'), $options);
		} catch (ClientException $e) {
			$reason = "Unknown reason";
			if ($e->hasResponse()) {
				$reason = $e->getResponse()->getBody()->getContents();
			}
			throw new UserException("Error uploading $dst. Reason: $reason");
		}
    }

    private function fetchDir($path) {
        $result = array();
        $scanned_directory = array_diff(scandir($path), array('..', '.'));
        $dirs = array_filter($scanned_directory, function($i) use($path){return is_dir("$path/$i");});
        $files = array_diff($scanned_directory, $dirs);
        foreach ($dirs as $key => $dir) {
            $result = array_merge($result, $this->fetchDir("$path/$dir"));
        }
        $filePaths = array_map(function($f) use($path) { return "$path/$f";}, $files);
        return array_merge($result, $filePaths);
    }

    private function tryParseNameFromManifest($filePath) {
        $name = basename($filePath);
        $manifestFile = $filePath . '.manifest';
        if (file_exists($manifestFile)) {
            $decode = new JsonDecode(true);
            $manifest = $decode->decode(file_get_contents($manifestFile), JsonEncoder::FORMAT);
            if (!empty($manifest['name'])) {
                $name = $manifest['name'];
            }
        }
        return $name;
    }

    private function prepareFilesToUpload($dirPath, $parseDestinationFn)
    {
        $allFiles = $this->fetchDir($dirPath);
        $files = array_filter($allFiles, function($f)
            {
                $ext = '.manifest';
                $extLen = strlen($ext);
                return strlen($f) < $extLen || $ext !== substr($f, - $extLen);
            }
        );
        $result = array();
        foreach ($files as $key => $file) {
            $destination = call_user_func($parseDestinationFn, $file);
            $result[$file] = $destination;
        }
        return $result;
    }

}