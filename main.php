<?php

require_once __DIR__.'/vendor/autoload.php';

$episode = isset($argv[1]) ? (int) $argv[1] : 779;

$logger = new Monolog\Logger('main');

$dl = new Downloader('http://www.mangareader.net/one-piece/%d/%d', __DIR__.'/output');
$dl->setLogger($logger)
        ->start($episode);
