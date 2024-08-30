<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ((!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
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
$user_info = get_user_info($id_user);
if(!empty($settings['timezone'])) {
    date_default_timezone_set($settings['timezone']);
}
$now = date('Y-m-d H:i:s');
session_write_close();
$plan_permissions = get_plan_permission($id_user);
$ai_generate_mode = $plan_permissions['ai_generate_mode'];
switch($ai_generate_mode) {
    case 'month':
        $n_ai_generate_month = $plan_permissions['n_ai_generate_month'];
        if($n_ai_generate_month!=-1) {
            $ai_generated = get_user_ai_generated($id_user,$ai_generate_mode);
            if($ai_generated>=$n_ai_generate_month) {
                die();
            }
        }
        break;
    case 'credit':
        $ai_credits = $user_info['ai_credits'];
        if($ai_credits>0) {
            $ai_generated = get_user_ai_generated($id_user,$ai_generate_mode);
            if($ai_generated>=$ai_credits) {
                die();
            }
        } else {
            die();
        }
        break;
}
$api_key = $settings['ai_key'];
$prompt = $_POST['prompt'];
$style = $_POST['style'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://backend.blockadelabs.com/api/v1/skybox");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('api_key' => $api_key, 'prompt' => $prompt, 'skybox_style_id' => $style)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec($ch);
curl_close($ch);
if(!empty($server_output)) {
    $response = json_decode($server_output,true);
    $pusher_channel = $response['pusher_channel'];
    $pusher_event = $response['pusher_event'];
    if(!empty($pusher_channel) && !empty($pusher_event)) {
        $response = str_replace("'","\'",$response);
        $result = $mysqli->query("INSERT INTO svt_ai_log(id_user, date_time, response) VALUES($id_user,'$now','$server_output');");
        $id_log = $mysqli->insert_id;
        ob_end_clean();
        echo json_encode(array('status'=>'ok','pusher_channel'=>$pusher_channel,'pusher_event'=>$pusher_event,"id_ai_log"=>$id_log));
    } else {
        ob_end_clean();
        echo json_encode(array('status'=>'error','output'=>$server_output));
    }
} else {
    ob_end_clean();
    echo json_encode(array('status'=>'error'));
}