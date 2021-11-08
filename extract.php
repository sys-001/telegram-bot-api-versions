<?php

use Psr\Log\NullLogger;
use TgScraper\Common\Encoder;
use TgScraper\Constants\Versions;
use TgScraper\TgScraper;

require_once __DIR__ . '/vendor/autoload.php';

$logger = new NullLogger();

$versionReplacer = function (string $ver) {
    $this->version = $ver;
};

function rrmdir(string $directory): void
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileInfo) {
        $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileInfo->getRealPath());
    }
    rmdir($directory);
}

$versions = array_keys(
    (new ReflectionClass(Versions::class))
        ->getConstants()['URLS']
);
$versions = array_diff($versions, ['latest']);

foreach ($versions as $version) {
    $filename = 'v' . str_replace('.', '', $version);
    echo $filename . PHP_EOL;
    $generator = TgScraper::fromVersion($logger, $version);
    $versionReplacer->call($generator, $version); // fix for older bot API versions
    $custom = $generator->toArray();
    $postman = $generator->toPostman();
    $openapi = $generator->toOpenApi();
    $generator->toStubs('tmp');
    $zip = new PharData("files/stubs/$filename.zip");
    $zip->buildFromDirectory('tmp');
    rrmdir('tmp');
    file_put_contents("files/custom/json/$filename.json", Encoder::toJson($custom, readable: true));
    file_put_contents("files/custom/yaml/$filename.yaml", Encoder::toYaml($custom));
    file_put_contents("files/postman/$filename.json", Encoder::toJson($postman, readable: true));
    file_put_contents("files/openapi/json/$filename.json", Encoder::toJson($openapi, readable: true));
    file_put_contents("files/openapi/yaml/$filename.yaml", Encoder::toYaml($openapi));
}