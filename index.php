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

foreach (['100', '101', '702', '204', '104'] as $pageNo) {
    $map = get($pageNo);
    $imageFormatter = new \marijnvdwerf\teletekst\ImageFormatter();
    $gd = $imageFormatter->formatPage($map->content);

    ob_start();
    imagegif($gd);
    $gif = ob_get_clean();
    echo sprintf('<img src="data:image/gif;base64,%s"/>', base64_encode($gif));
}



