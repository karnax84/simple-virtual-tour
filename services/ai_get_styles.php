<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
$settings = get_settings();
$api_key = $settings['ai_key'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://backend.blockadelabs.com/api/v1/skybox/families?api_key=".$api_key);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec($ch);
curl_close($ch);
ob_end_clean();
echo $server_output;