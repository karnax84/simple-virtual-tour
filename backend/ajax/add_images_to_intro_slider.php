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
$id_virtualtour = (int)$_SESSION['id_virtualtour_sel'];
session_write_close();
$images = $_POST['images'];
$priority = 0;
$query = "SELECT MAX(priority)+1 as priority FROM svt_intro_slider WHERE id_virtualtour=$id_virtualtour LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $priority = $row['priority'];
        if(empty($priority)) $priority=0;
    }
}
foreach ($images as $image) {
    $query = "INSERT INTO svt_intro_slider(id_virtualtour,image,priority) VALUES(?,?,?);";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('isi',  $id_virtualtour,$image,$priority);
        $result = $smt->execute();
        if ($result) {
            $priority++;
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error"));
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
}
ob_end_clean();
echo json_encode(array("status"=>"ok"));