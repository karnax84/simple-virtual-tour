<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ((!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once(dirname(__FILE__).'/ImageResizeException.php');
require_once(dirname(__FILE__).'/ImageResize.php');
use \Gumlet\ImageResize;
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
$_SESSION['tab_edit_room'] = 'preview';
session_write_close();
$virtual_tour = get_virtual_tour($id_virtualtour,$id_user);
$compress_jpg = $virtual_tour['compress_jpg'];
$max_width_compress = $virtual_tour['max_width_compress'];
$keep_original_panorama = $virtual_tour['keep_original_panorama'];
if($compress_jpg=="") $compress_jpg=90;
if($max_width_compress=="") $max_width_compress=0;
if($keep_original_panorama=="") $keep_original_panorama=1;
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
$s3Client = null;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
$settings = get_settings();
$api_key = $settings['autoenhance_key'];
$id_room = $_POST['id_room'];
$image_id = $_POST['image_id'];
$enable_autoenhance_room = $settings['enable_autoenhance_room'];
$plan_permissions = get_plan_permission($id_user);
if(!$enable_autoenhance_room || !$plan_permissions['enable_autoenhance_room']) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>"unauthorized"));
    exit;
}
$image_url = autoenhance_original_image($image_id,'big');
$panorama_image_new = 'pano_'.time().'.jpg';
if($s3_enabled) {
    $path_panorama_original_new = "s3://$s3_bucket_name/viewer/panoramas/original/$panorama_image_new";
    $path_panorama_new = "s3://$s3_bucket_name/viewer/panoramas/$panorama_image_new";
} else {
    $path_panorama_original_new = dirname(__FILE__).'/../../viewer/panoramas/original/'.$panorama_image_new;
    $path_panorama_new = dirname(__FILE__).'/../../viewer/panoramas/'.$panorama_image_new;
}
$headers = [
    'Cache-Control: no-cache, no-store, must-revalidate',
    'Pragma: no-cache',
    'Expires: 0',
];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $image_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$fileSizeKB = 0;
if ($httpCode == 200) {
    if(!empty($response)) {
        file_put_contents($path_panorama_original_new, $response);
        $fileSize = filesize($path_panorama_original_new);
        $fileSizeKB = $fileSize / 1024;
    }
} else {
    $error = curl_error($ch);
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>$error));
    exit;
}
if(file_exists($path_panorama_original_new) && $fileSizeKB > 100) {
    if($compress_jpg<100 || $max_width_compress>0) {
        try {
            $image = new ImageResize($path_panorama_original_new);
            $image->quality_jpg = $compress_jpg;
            $image->interlace = 1;
            if($max_width_compress>0) {
                $image->resizeToWidth($max_width_compress,false);
            }
            $image->gamma(false);
            $image->save($path_panorama_new);
        } catch (ImageResizeException $e) {}
    } else {
        copy($path_panorama_original_new,$path_panorama_new);
    }
    if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
        try {
            $s3Client->putObjectAcl([
                'Bucket' => $s3_bucket_name,
                'Key' => "viewer/panoramas/$panorama_image_new",
                'ACL' => 'public-read',
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {}
        try {
            $s3Client->putObjectAcl([
                'Bucket' => $s3_bucket_name,
                'Key' => "viewer/panoramas/original/$panorama_image_new",
                'ACL' => 'public-read',
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {}
    }
    if(!$keep_original_panorama) unlink($path_panorama_original_new);
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8mb4';");
    $mysqli->query("UPDATE svt_rooms SET panorama_image='$panorama_image_new',multires_status=0 WHERE id=$id_room;");
    $panorama_image_gt = $panorama_image_new;
    include("../../services/generate_thumb.php");
    include("../../services/generate_pano_mobile.php");
    generate_multires(false,$id_virtualtour);
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
    exit;
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>"error download image"));
    exit;
}