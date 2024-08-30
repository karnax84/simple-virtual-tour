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
$id_poi = (int)$_POST['id_poi'];
if(!check_can_delete($_SESSION['id_user'],$_SESSION['id_virtualtour_sel'])) {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    die();
}
session_write_close();
$query = "DELETE FROM svt_pois WHERE id=$id_poi;";
$result = $mysqli->query($query);
if($result) {
    $mysqli->query("UPDATE svt_rooms SET id_poi_autoopen=NULL WHERE id_poi_autoopen=$id_poi;");
    $mysqli->query("ALTER TABLE svt_pois AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_poi_gallery AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_poi_embedded_gallery AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_poi_objects360 AUTO_INCREMENT = 1;");
    $query = "SELECT id,content FROM svt_pois WHERE content LIKE '%$id_poi%' AND type='grouped';";
    $result = $mysqli->query($query);
    if($result) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_poi_g = $row['id'];
            $content = $row['content'];
            $id_pois_grouped = explode(",",$content);
            $key = array_search($id_poi, $id_pois_grouped);
            if ($key!==false) {
                unset($id_pois_grouped[$key]);
            }
            $id_pois_grouped = implode(",",$id_pois_grouped);
            $mysqli->query("UPDATE svt_pois SET content='$id_pois_grouped' WHERE id=$id_poi_g;");
        }
    }
    include("../../services/clean_images.php");
    update_user_space_storage($id_user,false);
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}