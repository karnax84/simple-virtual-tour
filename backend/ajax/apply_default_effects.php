<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ((!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
session_write_close();
$filters = [];
$filters['brightness'] = $_POST['brightness'];
$filters['contrast'] = $_POST['contrast'];
$filters['saturate'] = $_POST['saturate'];
$filters['grayscale'] = $_POST['grayscale'];
$filters = json_encode($filters);
$query_a = "UPDATE svt_rooms SET filters='$filters' WHERE id_virtualtour=$id_virtualtour;";
$result_a = $mysqli->query($query_a);
if($result_a) {
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>$mysqli->error));
}