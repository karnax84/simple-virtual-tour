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
$id_measure = (int)$_POST['id_measure'];
if(!check_can_delete($_SESSION['id_user'],$_SESSION['id_virtualtour_sel'])) {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    die();
}
session_write_close();
$query = "DELETE FROM svt_measures WHERE id=$id_measure;";
$result = $mysqli->query($query);
if($result) {
    $mysqli->query("ALTER TABLE svt_measures AUTO_INCREMENT = 1;");
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}