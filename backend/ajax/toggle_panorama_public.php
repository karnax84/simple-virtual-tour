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
session_write_close();
$id_room = (int)$_POST['id_room'];
if(get_user_role($id_user)=='administrator') {
    $query_check = "SELECT id_room FROM svt_public_panoramas WHERE id_room=$id_room;;";
    $result_check = $mysqli->query($query_check);
    if($result_check->num_rows==0) {
        $result = $mysqli->query("INSERT INTO svt_public_panoramas(id_room) VALUES($id_room);");
        $action = "+";
    } else {
        $result = $mysqli->query("DELETE FROM svt_public_panoramas WHERE id_room=$id_room;");
        $action = "-";
    }
    if($result) {
        ob_end_clean();
        echo json_encode(array("status"=>"ok","action"=>$action));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}