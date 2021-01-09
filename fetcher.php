<?php

require_once "vendor/autoload.php";
require_once "lib/autoload.php";


use ItIsAllMail\DriverFactory;
use ItIsAllMail\Mailbox;


$config = yaml_parse_file(__DIR__ . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "config.yml");
$sources = yaml_parse_file(__DIR__ . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "sources.yml");

$driverFactory = new DriverFactory($config);

foreach ($sources as $source) {

    $driver = null;
    
    $driver = $driverFactory->getDriverForSource($source);

    $posts = $driver->getPosts($source);
    
    $m = new Mailbox($source["mailbox"] ?? $config["mailbox"]);
    $m->mergeMessages($posts);
}
