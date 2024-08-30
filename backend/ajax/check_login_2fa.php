<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si_l']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
require_once("../vendor/google2fa/vendor/autoload.php");
use PragmaRX\Google2FA\Google2FA;
$id_user = (int)$_SESSION['id_user_2fa'];
$user_info = get_user_info($id_user);
$secretKey = $user_info['2fa_secretkey'];
$code = $_POST['code'];
$settings = get_settings();
$max_concurrent_sessions = (isset($settings['max_concurrent_sessions'])) ? $settings['max_concurrent_sessions'] : 0;
$google2fa = new Google2FA();
if ($google2fa->verifyKey($secretKey, $code)) {
    $active_sessions = checkActiveSessions($id_user,$max_concurrent_sessions);
    if($active_sessions < $max_concurrent_sessions) {
        try {
            $browser = parse_user_agent();
            set_user_log($id_user,'login',$_SERVER['REMOTE_ADDR']." - ".$browser['browser']." ".$browser['version']." - ".$browser['platform'],date('Y-m-d H:i:s', time()));
        } catch (Exception $e) {}
        $_SESSION['id_user'] = $id_user;
        insertSession($id_user,session_id());
        unset($_SESSION['id_user_2fa']);
        unset($_SESSION['lang']);
        session_write_close();
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
    } else {
        session_write_close();
        ob_end_clean();
        echo json_encode(array("status"=>"max_concurrent_sessions","count"=>$active_sessions));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}