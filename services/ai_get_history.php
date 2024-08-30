<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
if(isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
} else {
    die();
}
$settings = get_settings();
$api_key = $settings['ai_key'];
$array_history = array();
$query = "SELECT id,response FROM svt_ai_log WHERE id_user=$id_user AND deleted=0 ORDER BY id DESC;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_ai_log = $row['id'];
            $response = $row['response'];
            if(!empty($response)) {
                $response = json_decode($response,true);
                if(isset($response['status'])) {
                    if($response['status']=='complete') {
                        $thumb_url = $response['thumb_url'];
                        if(!empty($thumb_url)) {
                            $prompt = $response['prompt'];
                            $file_url = $response['file_url'];
                            $skybox_style_name = $response['skybox_style_name'];
                            $completed_at = $response['completed_at'];
                            $array_history[] = array(
                                "id"=>$id_ai_log,
                                "date"=>$completed_at,
                                "prompt"=>$prompt,
                                "style"=>$skybox_style_name,
                                "thumb_url"=>$thumb_url,
                                "file_url"=>$file_url
                            );
                        }
                    } else if($response['status']=='pending') {
                        $id_image = $response['id'];
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL,"https://backend.blockadelabs.com/api/v1/imagine/requests/$id_image?api_key=".$api_key);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $server_output = curl_exec($ch);
                        curl_close($ch);
                        $data = json_decode($server_output, true);
                        if ($data !== null && isset($data['request'])) {
                            $request = $data['request'];
                            $response = json_encode($request);
                            $query = "UPDATE svt_ai_log SET response=? WHERE id=?;";
                            if ($smt = $mysqli->prepare($query)) {
                                $smt->bind_param('si', $response, $id_ai_log);
                                $smt->execute();
                            }
                            $response = json_decode($response,true);
                            if(isset($response['status'])) {
                                if ($response['status'] == 'complete') {
                                    $thumb_url = $response['thumb_url'];
                                    if (!empty($thumb_url)) {
                                        $prompt = $response['prompt'];
                                        $file_url = $response['file_url'];
                                        $skybox_style_name = $response['skybox_style_name'];
                                        $completed_at = $response['completed_at'];
                                        $array_history[] = array(
                                            "id"=>$id_ai_log,
                                            "date" => $completed_at,
                                            "prompt" => $prompt,
                                            "style" => $skybox_style_name,
                                            "thumb_url" => $thumb_url,
                                            "file_url" => $file_url
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
ob_end_clean();
echo json_encode($array_history);