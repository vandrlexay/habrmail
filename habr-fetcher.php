<?php

require_once "vendor/autoload.php";
require_once "lib/autoload.php";

use HabrMail\HabrThread;
use HabrMail\Mailbox;

$html = "";

if ($argv[1] === "-l") {
    $html = file_get_contents("page.dump");
}
else {
    $html = file_get_contents($argv[1]);
}

$t = new HabrThread($html);

$m = new Mailbox("./maildir");
$m->mergeMessages($t->getPosts());
