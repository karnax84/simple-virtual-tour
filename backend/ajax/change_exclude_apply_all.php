<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ((!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id = (int)$_POST['id'];
$p = strip_tags($_POST['p']);
$k = (int)$_POST['k'];
switch($p) {
    case 'marker':
        $mysqli->query("UPDATE svt_markers SET exclude_from_apply_all=$k WHERE id=$id;");
        break;
    case 'poi':
        $mysqli->query("UPDATE svt_pois SET exclude_from_apply_all=$k WHERE id=$id;");
        break;
}