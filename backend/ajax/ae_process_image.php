<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ((!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
$settings = get_settings();
$api_key = $settings['autoenhance_key'];
$id_room = $_POST['id_room'];
$image_id = $_POST['image_id'];
$enhance_type = $_POST['enhance_type'];
$sky_replacement = $_POST['sky_replacement'];
$cloud_type = $_POST['cloud_type'];
$privacy = $_POST['privacy'];
$contrast_boost = $_POST['contrast_boost'];
$brightness_boost = $_POST['brightness_boost'];
$saturation_level = $_POST['saturation_level'];
$sharpen_level = $_POST['sharpen_level'];
$denoise_level = $_POST['denoise_level'];
$clarity_level = $_POST['clarity_level'];
$sky_saturation_level = $_POST['sky_saturation_level'];
$vertical_correction = $_POST['vertical_correction'];
$lens_correction = $_POST['lens_correction'];
if(empty($image_id)) {
    $panorama_image = $_POST["panorama_image"];
    $s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
    $s3_enabled = false;
    $s3Client = null;
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_region = $s3_params['region'];
        $s3Client = init_s3_client_no_wrapper($s3_params);
        if($s3Client!==null) {
            $s3_enabled = true;
        }
    }
    if($s3_enabled) {
        $path_panorama_compressed = "s3://$s3_bucket_name/viewer/panoramas/$panorama_image";
        $path_panorama_original = "s3://$s3_bucket_name/viewer/panoramas/original/$panorama_image";
    } else {
        $path_panorama_compressed = dirname(__FILE__).'/../../viewer/panoramas/'.$panorama_image;
        $path_panorama_original = dirname(__FILE__).'/../../viewer/panoramas/original/'.$panorama_image;
    }
    if($s3_enabled) {
        $s3_path_original = 'viewer/panoramas/original/'.$panorama_image;
        $s3_path = 'viewer/panoramas/'.$panorama_image;
    } else {
        if(file_exists($path_panorama_original)) {
            $path_panorama = $path_panorama_original;
        } else {
            $path_panorama = $path_panorama_compressed;
        }
    }
    if($s3_enabled) {
        try {
            $exist_original = $s3Client->doesObjectExist($s3_bucket_name,$s3_path_original);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $exist_original = false;
        }
        if($exist_original) {
            try {
                $s3Client->getObject(array(
                    'Bucket' => $s3_bucket_name,
                    'Key'    => $s3_path_original,
                    'SaveAs' => dirname(__FILE__)."/../../services/export_tmp/$panorama_image"
                ));
            } catch (\Aws\S3\Exception\S3Exception $e) {}
        } else {
            try {
                $s3Client->getObject(array(
                    'Bucket' => $s3_bucket_name,
                    'Key'    => $s3_path,
                    'SaveAs' => dirname(__FILE__)."/../../services/export_tmp/$panorama_image"
                ));
            } catch (\Aws\S3\Exception\S3Exception $e) {}
        }
        $path_panorama = dirname(__FILE__)."/../../services/export_tmp/$panorama_image";
    }
    $req = autoenhance_new_image($api_key,$path_panorama,$enhance_type,$sky_replacement,$cloud_type,$privacy,$contrast_boost,$brightness_boost,$saturation_level,$sharpen_level,$denoise_level,$clarity_level,$sky_saturation_level,$vertical_correction,$lens_correction);
    $image_id = $req['image_id'];
    $s3PutObjectUrl = $req['s3PutObjectUrl'];
    $status = autoenhance_upload_image($api_key,$path_panorama,$s3PutObjectUrl);
    if($status) {
        ob_end_clean();
        echo json_encode(array("status"=>"ok","image_id"=>$image_id));
        $mysqli->query("INSERT INTO svt_autoenhance_log(id_user,id_room,date_time,id_image,processed,deleted) VALUES($id_user,$id_room,NOW(),'$image_id',0,0)");
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>$status['msg']));
    }
} else {
    $status = autoenhance_process_image($api_key,$image_id,$enhance_type,$sky_replacement,$cloud_type,$privacy,$contrast_boost,$brightness_boost,$saturation_level,$sharpen_level,$denoise_level,$clarity_level,$sky_saturation_level,$vertical_correction,$lens_correction);
    if($status) {
        ob_end_clean();
        echo json_encode(array("status"=>"ok","image_id"=>$image_id));
    }
}