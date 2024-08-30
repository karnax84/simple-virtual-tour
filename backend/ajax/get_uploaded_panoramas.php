<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
$id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
session_write_close();
$s3_params = check_s3_tour_enabled($id_virtualtour_sel);
$s3_enabled = false;
$s3_bucket_name = "";
if(!empty($s3_params)) {
    $s3Client = init_s3_client_no_wrapper($s3_params);
    if($s3Client==null) {
        $s3_enabled = false;
    } else {
        $s3_enabled = true;
    }
}
$where="";
$user_role = get_user_role($id_user);
switch($user_role) {
    case 'customer':
        $where = " AND v.id_user=$id_user ";
        break;
    case 'editor':
        $where = " AND v.id IN () ";
        $query = "SELECT GROUP_CONCAT(id_virtualtour) as ids FROM svt_assign_virtualtours WHERE id_user=$id_user;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $ids = $row['ids'];
                $where = " AND v.id IN ($ids) ";
            }
        }
        break;
}
$array_panoramas = array();
if($user_role=='administrator') {
    $query = "SELECT r.panorama_image,MIN(r.id) as id_room,MIN(v.id) as id_virtualtour,MIN(r.name) AS room_name,MIN(v.name) AS tour_name,IF(p.id_room IS NULL,0,1) as public
                FROM svt_rooms AS r JOIN svt_virtualtours AS v ON v.id = r.id_virtualtour
                LEFT JOIN svt_public_panoramas as p ON p.id_room=r.id
                WHERE v.aws_s3=".(($s3_enabled) ? 1 : 0)." AND r.type = 'image' AND r.panorama_image <> ''
                GROUP BY r.panorama_image;";
} else {
    $query = "SELECT r.panorama_image,MIN(r.id) as id_room,MIN(v.id) as id_virtualtour,MIN(r.name) AS room_name,MIN(v.name) AS tour_name,0 as public
                FROM svt_rooms AS r JOIN svt_virtualtours AS v ON v.id = r.id_virtualtour
                WHERE v.aws_s3=".(($s3_enabled) ? 1 : 0)." AND ((r.type = 'image' $where AND r.panorama_image <> '') OR (r.type = 'image' AND r.id IN (SELECT DISTINCT id_room FROM svt_public_panoramas) AND r.panorama_image <> ''))
                GROUP BY r.panorama_image;";
}
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $panorama_image = $row['panorama_image'];
            $tour_name = $row['tour_name'];
            $room_name = $row['room_name'];
            $id_virtualtour = $row['id_virtualtour'];
            $s3_params = check_s3_tour_enabled($id_virtualtour);
            $s3_enabled = false;
            $s3_bucket_name = "";
            $s3_url = "";
            if(!empty($s3_params)) {
                $s3_bucket_name = $s3_params['bucket'];
                $s3_region = $s3_params['region'];
                $s3_url = init_s3_client($s3_params);
                if($s3_url!==false) {
                    $s3_enabled = true;
                }
            }
            if($s3_enabled) {
                $thumb_url = "s3://$s3_bucket_name/viewer/panoramas/lowres/".$panorama_image;
                $file_url = "s3://$s3_bucket_name/viewer/panoramas/".$panorama_image;
            } else {
                $thumb_url = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'lowres'.DIRECTORY_SEPARATOR.$panorama_image;
                $file_url = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.$panorama_image;
            }
            if(file_exists($thumb_url)) {
                if($s3_enabled) {
                    $thumb_url = $s3_url."viewer/panoramas/lowres/".$panorama_image;
                } else {
                    $thumb_url = "../viewer/panoramas/lowres/".$panorama_image;
                }
            } else {
                if($s3_enabled) {
                    $thumb_url = $s3_url."viewer/panoramas/".$panorama_image;
                } else {
                    $thumb_url = "../viewer/panoramas/".$panorama_image;
                }
            }
            if(file_exists($file_url)) {
                if($s3_enabled) {
                    $file_url = $s3_url."viewer/panoramas/".$panorama_image;
                } else {
                    $file_url = "../viewer/panoramas/".$panorama_image;
                }
            }
            $array_panoramas[] = array(
                "id_virtualtour"=>$row['id_virtualtour'],
                "id_room"=>$row['id_room'],
                "public"=>$row['public'],
                "thumb_url"=>$thumb_url,
                "file_url"=>$file_url,
                "tour_name"=>$tour_name,
                "room_name"=>$room_name
            );
        }
    }
}
function comparePanoramas($a, $b) {
    if ($a['id_virtualtour'] == $b['id_virtualtour']) {
        return $a['id_room'] - $b['id_room'];
    }
    return $a['id_virtualtour'] - $b['id_virtualtour'];
}
usort($array_panoramas, 'comparePanoramas');
ob_end_clean();
echo json_encode($array_panoramas);