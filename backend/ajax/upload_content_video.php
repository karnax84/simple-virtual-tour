<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ((!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once(dirname(__FILE__).'/../functions.php');
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
$s3_enabled = false;
$s3Client = null;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
session_write_close();
if(isset($_GET['e'])) {
    switch ($_GET['e']) {
        case 'mp4':
            $allowed_ext = array('mp4','webm');
            $allowed_ext_msg = "mp4, webm";
            break;
        case 'webm_mov':
            $allowed_ext = array('mov','webm');
            $allowed_ext_msg = "mov, webm";
            break;
    }
} else {
    $allowed_ext = array('mp4','webm');
    $allowed_ext_msg = "mp4, webm";
}
if(isset($_FILES) && !empty($_FILES['file']['name'])){
    $filename = $_FILES['file']['name'];
    $ext = explode('.',$filename);
    $ext = strtolower(end($ext));
    if(in_array($ext,$allowed_ext)){
        $name = "content_".time().".$ext";
        if($s3_enabled) {
            $path_dest = "s3://$s3_bucket_name/viewer/content/".$name;
        } else {
            $path_dest = dirname(__FILE__).'/../../viewer/content/'.$name;
        }
        $moved = move_uploaded_file($_FILES['file']['tmp_name'],$path_dest);
        if($moved) {
            if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/content/$name",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    ob_end_clean();
                    echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                    exit;
                }
            }
            ob_end_clean();
            echo "content/$name";
        } else {
            ob_end_clean();
            echo 'ERROR: code:'.$_FILES["file"]["error"];
        }
    }else{
        ob_end_clean();
        echo 'ERROR: '.sptrintf(_("Only %s files are supported."),$allowed_ext_msg);
    }
}else{
    ob_end_clean();
    echo 'ERROR: '._("File not provided.");
}
exit;