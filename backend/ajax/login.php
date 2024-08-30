<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si_l']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once('../functions.php');
$username = strip_tags($_POST['username_svt']);
$password = strip_tags($_POST['password_svt']);
$remember_me = (int)$_POST['remember_svt'];
$autologin = (int)$_POST['autologin'];
$id_user = 0;
$query = "SELECT id FROM svt_users WHERE (username=? OR email=?) LIMIT 1;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ss', $username, $username);
    $result = $smt->execute();
    if ($result) {
        $result = get_result($smt);
        if (count($result) == 1) {
            $row = array_shift($result);
            $id_user = $row['id'];
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"incorrect_username"));
            exit;
        }
    }
}
$settings = get_settings();
$twofa_enabled = $settings['2fa_enable'];
$max_concurrent_sessions = (isset($settings['max_concurrent_sessions'])) ? $settings['max_concurrent_sessions'] : 0;
$query = "SELECT * FROM svt_users WHERE id=? AND password=MD5(?) LIMIT 1;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('is', $id_user, $password);
    $result = $smt->execute();
    if ($result) {
        $result = get_result($smt);
        if (count($result) == 1) {
            $row = array_shift($result);
            if($row['active']) {
                if($autologin==0 && $twofa_enabled && !empty($row['2fa_secretkey'])) {
                    $_SESSION['id_user_2fa'] = $id_user;
                    session_write_close();
                    ob_end_clean();
                    echo json_encode(array("status"=>"2fa"));
                } else {
                    $active_sessions = checkActiveSessions($id_user,$max_concurrent_sessions);
                    if($active_sessions < $max_concurrent_sessions) {
                        try {
                            $browser = parse_user_agent();
                            set_user_log($id_user,'login',(!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))." - ".$browser['browser']." ".$browser['version']." - ".$browser['platform'],date('Y-m-d H:i:s', time()));
                        } catch (Exception $e) {}
                        $_SESSION['id_user'] = $id_user;
                        if($max_concurrent_sessions>0) insertSession($id_user,session_id());
                        unset($_SESSION['lang']);
                        if($remember_me==1) {
                            $cookieExpiration = time() + (30 * 24 * 60 * 60);
                            setcookie("cc_backend_l", 1, $cookieExpiration, "/");
                            setcookie("cc_backend_u", encrypt_decrypt('encrypt',$username,'svt'), $cookieExpiration, "/");
                            setcookie("cc_backend_p", encrypt_decrypt('encrypt',$password,'svt'), $cookieExpiration, "/");
                        } else {
                            $cookieExpiration = time() - 3600;
                            setcookie('cc_backend_l', '', $cookieExpiration, "/");
                            setcookie('cc_backend_u', '', $cookieExpiration, "/");
                            setcookie('cc_backend_p', '', $cookieExpiration, "/");
                            unset($_COOKIE['cc_backend_u']);
                            unset($_COOKIE['cc_backend_p']);
                        }
                        session_write_close();
                        ob_end_clean();
                        echo json_encode(array("status"=>"ok","id"=>$row['id'],"role"=>$row['role'],"email"=>$row['email']));
                    } else {
                        session_write_close();
                        ob_end_clean();
                        echo json_encode(array("status"=>"max_concurrent_sessions","count"=>$active_sessions));
                    }
                }
            } else {
                ob_end_clean();
                $cookieExpiration = time() - 3600;
                setcookie('cc_backend_l', '', $cookieExpiration, "/");
                setcookie('cc_backend_u', '', $cookieExpiration, "/");
                setcookie('cc_backend_p', '', $cookieExpiration, "/");
                unset($_COOKIE['cc_backend_u']);
                unset($_COOKIE['cc_backend_p']);
                echo json_encode(array("status"=>"blocked"));
            }
        } else {
            ob_end_clean();
            $cookieExpiration = time() - 3600;
            setcookie('cc_backend_l', '', $cookieExpiration, "/");
            setcookie('cc_backend_u', '', $cookieExpiration, "/");
            setcookie('cc_backend_p', '', $cookieExpiration, "/");
            unset($_COOKIE['cc_backend_u']);
            unset($_COOKIE['cc_backend_p']);
            echo json_encode(array("status"=>"incorrect_password"));
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}