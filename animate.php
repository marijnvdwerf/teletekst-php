<?php

require 'vendor/autoload.php';

function get($page)
{
    if (!file_exists('./tmp')) {
        mkdir('./tmp');
    }
    $path = './tmp/' . $page . '.json';

    if (!file_exists($path)) {
        $data = file_get_contents('http://teletekst-data.nos.nl/json/' . $page);
        file_put_contents($path, $data);
    } else {
        $data = file_get_contents($path);
    }

    return json_decode($data);
}


$pageNo = '702';

$page = get($pageNo);
$pages = [$page];
while (!empty($pages[count($pages) - 1]->nextSubPage)) {
    $pages[] = get($pages[count($pages) - 1]->nextSubPage);
}

$imageFormatter = new \marijnvdwerf\teletekst\ImageFormatter();
$frames = [];

foreach ($pages as $page) {
    $gd = $imageFormatter->formatPage($page->content);
    $tmpPath = './tmp/' . md5($page->content) . '.gif';
    imagegif($gd, $tmpPath);
    imagedestroy($gd);
    $frames[] = $tmpPath;
}

$arguments = ['-delay 120', '-loop 0'];

foreach ($frames as $path) {
    $arguments[] = escapeshellarg($path);
}

$outputPath = realpath(__DIR__) . '/tmp/' . $pageNo . '.gif';
$arguments[] = escapeshellarg($outputPath);
shell_exec('/usr/local/bin/convert ' . implode(' ', $arguments));


header('Content-Type: image/gif');
readfile($outputPath);



