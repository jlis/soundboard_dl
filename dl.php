<?php

require __DIR__.'/vendor/autoload.php';

if (!isset($argv[1])) {
    echo 'No board name given (i.e. "Southpark_Sounds_3").';
    die(1);
}

$savePath = __DIR__.'/sounds/';
$boardName = $argv[1];
$json = file_get_contents('http://www.soundboard.com/handler/gettrackjson.ashx?boardname='.$boardName);
$entries = json_decode($json, true);
if (empty($entries)) {
    echo sprintf('No sounds could be found for board "%s"', $boardName);
    die(1);
}

$rollingCurl = new \RollingCurl\RollingCurl();
$fileNameMap = [];
foreach ($entries as $entry) {
    $rollingCurl->get($entry['mp3']);
    $fileNameMap[$entry['mp3']] = $entry['title'];
}

$rollingCurl
    ->setCallback(
        function (\RollingCurl\Request $request, \RollingCurl\RollingCurl $rollingCurl) use ($fileNameMap, $savePath) {
            $fileName = $fileNameMap[$request->getUrl()];
            if (!isset($request->getResponseInfo()['content_type']) || 'audio/mpeg' !== $request->getResponseInfo(
                )['content_type']
            ) {
                echo sprintf('File "%s" is no audio/mpeg file.%s', $fileName, PHP_EOL);
            } else {
                $fileNameExt = $fileName.'.mp3';
                file_put_contents($savePath.$fileNameExt, $request->getResponseText());
                echo sprintf('Downloaded: %s%s', $fileNameExt, PHP_EOL);
            }
        }
    )
    ->setSimultaneousLimit(3)
    ->execute();