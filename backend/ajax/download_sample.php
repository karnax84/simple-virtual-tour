<?php
try {
    if (file_exists(dirname(__FILE__).'/../../sample_data/B_SIMPLE_VIRTUAL_TOUR/')) {
        deleteDir(dirname(__FILE__).'/../../sample_data/B_SIMPLE_VIRTUAL_TOUR/');
    }
} catch (Exception $e) {}
try {
    $url = 'https://simpledemo.it/svt_demo/sample/B_SIMPLE_VIRTUAL_TOUR_79.zip';
    $localFileSize = file_exists(dirname(__FILE__).'/../../sample_data/B_SIMPLE_VIRTUAL_TOUR.zip') ? filesize(dirname(__FILE__).'/../../sample_data/B_SIMPLE_VIRTUAL_TOUR.zip') : 0;
    $remoteFileSize = 0;
    $headers = get_headers($url, true);
    if ($headers && isset($headers['Content-Length'])) {
        $remoteFileSize = $headers['Content-Length'];
    }
    if ($localFileSize != $remoteFileSize) {
        if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
            $options = array('http' => array('timeout' => 120,"ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false)));
            $context = stream_context_create($options);
            $file = file_get_contents($url, false, $context);
            if(empty($file)) {
                $file = file_get_contents_curl($url);
            }
        } else {
            $file = file_get_contents_curl($url);
        }
        file_put_contents(dirname(__FILE__).'/../../sample_data/B_SIMPLE_VIRTUAL_TOUR.zip', $file);
    }
} catch (Exception $e) {}