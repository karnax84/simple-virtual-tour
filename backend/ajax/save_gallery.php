<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ((!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$params = json_encode($_POST);
$params = str_replace("'","\'",$params);
unset($_POST['id_virtualtour']);
$gallery_params = json_encode($_POST);
$gallery_params = str_replace("'","\'",$gallery_params);
$mysqli->query("UPDATE svt_virtualtours SET gallery_params='$gallery_params' WHERE id=$id_virtualtour;");