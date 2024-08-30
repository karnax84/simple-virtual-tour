<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../functions.php");
$settings = get_settings();
$api_key = $settings['autoenhance_key'];
$image_id = $_POST['image_id'];
$req = autoenhance_check_image($api_key,$image_id);
$preview_image_url = "";
$original_image_url = "";
if($req['status']=='processed') {
    $preview_image_url = autoenhance_preview_image($image_id);
    $original_image_url = autoenhance_original_image($image_id,'large');
}
ob_end_clean();
echo json_encode(array("req"=>$req,"preview_image_url"=>$preview_image_url,"original_image_url"=>$original_image_url));