<?php

use Psr\Log\NullLogger;
use TgScraper\Constants\Versions;

require_once __DIR__ . '/vendor/autoload.php';

$logger = new NullLogger();

$versions = (new ReflectionClass(Versions::class))
    ->getConstants();
unset($versions['LATEST']);

foreach ($versions as $version => $url) {
    $filename = strtolower($version);
    echo $filename . PHP_EOL;
    $generator = new \TgScraper\Generator($logger, url: $url);
    // fix for older bot API versions
    $rawVersion = str_split(str_replace('v', '', $filename));
    $realVersion = sprintf('%s.%s.%s', $rawVersion[0] ?? '1', $rawVersion[1] ?? '0', $rawVersion[2] ?? '0');
    $json = $generator->toJson();
    $jsonData = json_decode($json, true);
    $jsonData = ['version' => $realVersion] + $jsonData;
    $generator = \TgScraper\Generator::fromJson($logger, json_encode($jsonData));
    $json = $generator->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $postman = $generator->toPostman(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $yaml = $generator->toYaml();
    file_put_contents("files/json/$filename.json", $json);
    file_put_contents("files/postman/$filename.json", $postman);
    file_put_contents("files/yaml/$filename.yaml", $yaml);
}