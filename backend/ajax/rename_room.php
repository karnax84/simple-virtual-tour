<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ((!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$id = (int)$_POST['id'];
$name = strip_tags($_POST['name']);
$query = "UPDATE svt_rooms SET name=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('si', $name,$id);
    $result = $smt->execute();
    if($result) {
        //ob_end_clean();
        echo json_encode(array("status"=>"ok"));
    } else {
        //ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    //ob_end_clean();
    echo json_encode(array("status"=>"error"));
}