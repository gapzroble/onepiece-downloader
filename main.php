<?php

require_once __DIR__.'/vendor/autoload.php';

$ep = isset($argv[1]) ? (int) $argv[1] : 779;

echo 'Downloading episodes starting from ', $ep, PHP_EOL;

do
{
    $result = download_episode($ep);
    if (!$result) break;
}
while ($ep++);

echo 'Done', PHP_EOL;

function download_episode($ep)
{
    echo ' + Downloading episode ', $ep, PHP_EOL;
    $page = 1;
    do {
        $result = ping_page($ep, $page);
        if (!$result) break;
    }
    while ($page++);
    echo ' + Done', PHP_EOL;
	return $page > 1;
}

function ping_page($ep, $page)
{
    $client = new GuzzleHttp\Client();
    $success = false;
    $tpl = 'http://www.mangareader.net/one-piece/%d/%d';
    $url = sprintf($tpl, $ep, $page);
    echo '  + page ', $page;
	try {
		$exists = $client->head($url);
		if ($exists->getStatusCode() == 200) {
			$img = get_page_img($url);
			if (!$img) {
				echo ' .. not yet', PHP_EOL;
				return false;
			}
			$dest = get_dest($ep, $page);
			$success = download_image($img, $dest);
		}
		echo ' .. OK', PHP_EOL;
		unset($client);
	} catch(Exception $e) {
		// assume not found
		echo ' .. end', PHP_EOL;
	}
    return $success;
}

function get_page_img($url)
{
    $client = new GuzzleHttp\Client();
    $html = $client->get($url);
    $a = explode('id="img"', $html);
	if (!isset($a[1])) return false;
    $b = explode('src="', $a[1]);
	if (!isset($b[1])) return false;
    list($c,) = explode('"', $b[1]);
    unset($client);
    return $c;
}

function download_image($url, $dest)
{
    $client = new GuzzleHttp\Client();
    $result = $client->get($url, ['save_to' => $dest, 'timeout' => 20]);
    unset($client);
    return $result->getStatusCode() == 200;
}

function get_dest($ep, $page, $ext = '.jpg')
{
    $folder = __DIR__.'/output';
    if (!file_exists($folder)) {
        mkdir($folder);
    }
    
    $ep = sprintf('%s/%s', $folder, $ep);
    if (!file_exists($ep)) {
        mkdir($ep);
    }
    
    return sprintf('%s/%s%s', $ep, sprintf('%02d', $page), $ext);
}
