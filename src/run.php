<?php

use Symfony\Component\Yaml\Yaml;
use Alorel\Dropbox\Operation\Files\Upload;
use Alorel\Dropbox\Options\Builder\UploadOptions;
use Alorel\Dropbox\Parameters\WriteMode;
use Guzzle\Http\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;

require_once(dirname(__FILE__) . "/../vendor/autoload.php");

const OAUTH_API_URL = "https://syrup.keboola.com/oauth";

$arguments = getopt("d::", array("data::"));
if (!isset($arguments["data"])) {
	print "Data folder not set.";
	exit(1);
}

$config = Yaml::parse(file_get_contents($arguments["data"] . "/config.yml"));

if (empty($config['parameters']['api_key'])) {
	if (!empty($config['parameters']['credentials'])) {
		$guzzle = new Guzzle;
		try {
			$token = getenv('KBC_TOKEN');
			if (empty($token)) {
				print 'KBC_TOKEN env variable not set.';
				exit(1);
			}

			$re = $guzzle->get(
				OAUTH_API_URL . "/credentials/wr-dropbox/{$config['parameters']['credentials']}",
				['X-StorageApi-Token' => $token]
			)->send();

			// TODO check for token in response!
			$apiKey = json_decode($re->json()['data'])->access_token;
		} catch(\Guzzle\Http\Exception\RequestException $e) {
			print "Failed retrieving token from OAuth API: " . $e->getMessage();
			exit(1);
		}
	} else {
		print "'api_key' or 'credentials' parameter is required";
		exit(2);
	}
} else {
	$apiKey = $config['parameters']['api_key'];
}

// $client = new Client($apiKey, "Keboola Dropbox Writer/0.1");



$path = empty($config['parameters']['path_prefix']) ? "" : $config['parameters']['path_prefix'];

$options = (!empty($config['parameters']['mode']) && $config['parameters']['mode'] == 'rewrite')
	? (new UploadOptions())->setWriteMode(WriteMode::overwrite())
	: (new UploadOptions())->setWriteMode(WriteMode::add())->setAutoRename(true);
$client2 = new Upload(false, $apiKey);

$dir = new RecursiveDirectoryIterator($arguments['data'] . "/in");
foreach (new RecursiveIteratorIterator($dir) as $filename => $file) {
	if ('.' != $file->getFilename() && '..' != $file->getFilename()
		&& '.manifest' != substr($file->getFilename(), -9)
	) {
		if (false !== strpos($filename, '/in/files/')
			&& file_exists($filename . '.manifest')
		) {
			$manifest = Yaml::parse(file_get_contents($filename . '.manifest'));
			if (!empty($manifest['name'])) {
				$name = $manifest['name'];
			} else {
				print "Failed getting file name from manifest. Using filename instead.";
				$name = $file->getFilename();
			}

			if (!empty($config['parameters']['file_id_suffix']) && !empty($manifest['id'])) {
				$name .= '_' . $manifest['id'];
			}

		} else {
			$name = $file->getFilename();
		}

		$dst =  '/' . $path . $name;

		try {
			$response = $client2->raw($dst, fopen($filename, 'r'), $options);
			print "{$name} uploaded" . PHP_EOL;
		} catch (ClientException $e) {
			$reason = "Unknown reason";
			if ($e->hasResponse()) {
				$reason = $e->getResponse()->getBody()->getContents();
			}
			print "Error uploading " . $name . " reason:" . $reason;
			exit(1);
		}
	}
}

exit(0);
