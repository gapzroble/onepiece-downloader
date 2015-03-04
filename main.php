<?php

require_once __DIR__.'/vendor/autoload.php';

$url = 'http://www.mangareader.net/one-piece/%d/%d';
$episode = isset($argv[1]) ? (int) $argv[1] : 779;

$dl = new Downloader($url, __DIR__.'/output');
$dl->setLogger(new Monolog\Logger('main'))
        ->start($episode);
