#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use ParseServerMigration\Console\Command\MainCommand;
use ParseServerMigration\Console\Command\ExportCommand;
use ParseServerMigration\Console\Command\DeleteCommand;
use Parse\ParseClient;
use Aws\S3\S3Client;
use ParseServerMigration\Config;
use ParseServerMigration\Console\PictureRepository;
use ParseServerMigration\Console\Command\MigrateFromSaasCommand;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use MongoDB\Client;

//Init Parse client
ParseClient::initialize(Config::APP_ID, Config::REST_KEY, Config::MASTER_KEY);
ParseClient::setServerURL(Config::PARSE_URL,'parse');

//Init S3 client
$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => 'eu-west-1',
    'credentials' => [
        'key'    => 'AKIAJ3XMJG5C7ZNEFRMA',
        'secret' => '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'
    ]
]);

//Clients
$mongoDbClient = new Client(Config::MONGO_DB_CONNECTION);

//$cursor = $collection->find(['city' => 'JERSEY CITY', 'state' => 'NJ']);

$pictureRepository = new PictureRepository($s3Client, $mongoDbClient);


//Logger
$logger = new Logger('export');
$logger->pushHandler(new StreamHandler(__DIR__.Config::LOG_PATH, Logger::INFO));


//Init SF app
$delete = new DeleteCommand($pictureRepository, $logger);
$export = new ExportCommand($pictureRepository, $logger);
$migrate = new MigrateFromSaasCommand($pictureRepository, $logger);
$main = new MainCommand(array($delete, $export, $migrate), $logger);

$application = new Application('Parse exporter', '0.1');
$application->addCommands(array($main, $delete, $export, $migrate));
$application->setDefaultCommand($main->getName());

$application->run();
