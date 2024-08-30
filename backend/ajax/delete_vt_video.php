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
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
session_write_close();
$id_video = (int)$_POST['id_video'];
$where = "";
switch(get_user_role($id_user)) {
    case 'customer':
        $where = " AND id_user=$id_user ";
        break;
    case 'editor':
        $where = " AND id IN () ";
        $query = "SELECT GROUP_CONCAT(id_virtualtour) as ids FROM svt_assign_virtualtours WHERE id_user=$id_user;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $ids = $row['ids'];
                $where = " AND id IN ($ids) ";
            }
        }
        break;
}
$code = "";
$query = "SELECT code FROM svt_virtualtours WHERE id=$id_virtualtour $where LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
    }
}
if(empty($code)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    die();
}
if($s3_enabled) {
    unlink("s3://$s3_bucket_name/video/".$id_virtualtour."_$id_video.mp4");
} else {
    unlink("../../video/".$id_virtualtour."_$id_video.mp4");
}
ob_end_clean();
echo json_encode(array("status"=>"ok"));