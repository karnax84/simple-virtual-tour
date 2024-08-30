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
session_write_close();
$settings = get_settings();
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
$id = (int)$_POST['id'];
$table = strip_tags($_POST['table']);
$field = strip_tags($_POST['field']);
$value = $_POST['value'];
$lang = strip_tags($_POST['lang']);
switch($table) {
    case 'svt_virtualtours_lang':
        $id_field = "id_virtualtour";
        break;
    case 'svt_rooms_lang':
        $id_field = "id_room";
        break;
    case 'svt_rooms_alt_lang':
        $id_field = "id_room_alt";
        break;
    case 'svt_maps_lang':
        $id_field = "id_map";
        break;
    case 'svt_gallery_lang':
        $id_field = "id_gallery";
        break;
    case 'svt_presentations_lang':
        $id_field = "id_presentation";
        break;
    case 'svt_products_lang':
        $id_field = "id_product";
        break;
    case 'svt_markers_lang':
        $id_field = "id_marker";
        break;
    case 'svt_pois_lang':
        $id_field = "id_poi";
        break;
}
$query_c = "SELECT $id_field FROM $table WHERE $id_field=? AND language=? LIMIT 1;";
if($smt_c = $mysqli->prepare($query_c)) {
    $smt_c->bind_param('is',  $id,$lang);
    $result_c = $smt_c->execute();
    if ($result_c) {
        $result_c = get_result($smt_c);
        if (count($result_c) == 1) {
            $query = "UPDATE $table SET $field=? WHERE $id_field=? AND language=?;";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('sss', $value, $id,$lang);
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error"));
            }
        } else {
            $query = "INSERT INTO $table($id_field,language,$field) VALUES(?,?,?);";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('iss', $id,$lang,$value);
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error"));
            }
        }
        $result = $smt->execute();
        if($result) {
            ob_end_clean();
            echo json_encode(array("status"=>"ok"));
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error"));
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}