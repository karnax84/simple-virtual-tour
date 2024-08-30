<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ((!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']))!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$settings = get_settings();
$id_user = $_SESSION['id_user'];
$id_user_logged = $id_user;
$user_info = get_user_info($id_user);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
session_write_close();
if(!get_user_role($id_user)=='administrator') {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}
$id_user = (int)$_POST['id_svt'];
$username = strip_tags($_POST['username_svt']);
$email = strip_tags($_POST['email_svt']);
$role = strip_tags($_POST['role_svt']);
if($role=='super_admin') {
    $super_admin = 1;
    $role = 'administrator';
} else {
    $super_admin = 0;
}
$id_plan = (int)$_POST['plan_svt'];
$active = (int)$_POST['active_svt'];
$ai_credits = (int)$_POST['ai_credits'];
$autoenhance_credits = (int)$_POST['autoenhance_credits'];
$language = strip_tags($_POST['language_svt']);
$expire_plan_date_manual_date = $_POST['expire_plan_date_manual_date_svt'];
$expire_plan_date_manual_time = $_POST['expire_plan_date_manual_time_svt'];
$first_name = strip_tags($_POST['first_name']);
$last_name = strip_tags($_POST['last_name']);
$company = strip_tags($_POST['company']);
$tax_id = strip_tags($_POST['tax_id']);
$street = strip_tags($_POST['street']);
$city = strip_tags($_POST['city']);
$province = strip_tags($_POST['province']);
$postal_code = strip_tags($_POST['postal_code']);
$country = strip_tags($_POST['country']);
$tel = strip_tags($_POST['tel']);
if(empty($expire_plan_date_manual_date) && empty($expire_plan_date_manual_time)) {
    $expire_plan_date_manual = NULL;
} else if(!empty($expire_plan_date_manual_date) && empty($expire_plan_date_manual_time)) {
    $expire_plan_date_manual = "$expire_plan_date_manual_date 23:59:00";
} else if(empty($expire_plan_date_manual_date) && !empty($expire_plan_date_manual_time)) {
    $expire_plan_date_manual = NULL;
} else {
    $expire_plan_date_manual = "$expire_plan_date_manual_date $expire_plan_date_manual_time";
}
$query_check = "SELECT id FROM svt_users WHERE username=? AND id!=?;";
if($smt = $mysqli->prepare($query_check)) {
    $smt->bind_param('si', $username, $id_user);
    $result_check = $smt->execute();
    if ($result_check) {
        $result_check = get_result($smt);
        if (count($result_check) > 0) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Username already registered!")));
            exit;
        }
    }
}
$query_check = "SELECT id FROM svt_users WHERE email=? AND id!=?;";
if($smt = $mysqli->prepare($query_check)) {
    $smt->bind_param('si', $email, $id_user);
    $result_check = $smt->execute();
    if ($result_check) {
        $result_check = get_result($smt);
        if (count($result_check) > 0) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("E-mail already registered!")));
            exit;
        }
    }
}
$reload = 0;
$change_plan = false;
$change_role = false;
$query_check = "SELECT expire_plan_date_manual,id_plan,role FROM svt_users WHERE id=? LIMIT 1;";
if($smt = $mysqli->prepare($query_check)) {
    $smt->bind_param('i',  $id_user);
    $result_check = $smt->execute();
    if ($result_check) {
        $result_check = get_result($smt);
        if (count($result_check) == 1) {
            $row_check = array_shift($result_check);
            if(empty($row_check['expire_plan_date_manual'])) $row_check['expire_plan_date_manual']=NULL;
            if($row_check['expire_plan_date_manual']!=$expire_plan_date_manual) {
                $reload = 1;
            }
            if($row_check['id_plan']!=$id_plan) {
                $reload = 1;
                $change_plan = true;
            }
            if($row_check['role']!=$role) {
                $change_role = true;
            }
        }
    }
}
$query = "UPDATE svt_users SET username=?,email=?,role=?,super_admin=?,id_plan=?,active=?,language=?,expire_plan_date_manual=?,first_name=?,last_name=?,company=?,tax_id=?,street=?,city=?,province=?,postal_code=?,country=?,tel=?,ai_credits=?,autoenhance_credits=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sssiiissssssssssssiii', $username,$email,$role,$super_admin,$id_plan,$active,$language,$expire_plan_date_manual,$first_name,$last_name,$company,$tax_id,$street,$city,$province,$postal_code,$country,$tel,$ai_credits,$autoenhance_credits,$id_user);
    $result = $smt->execute();
    if($result) {
        update_plans_expires_date($id_user);
        if($change_plan) {
            $mysqli->query("UPDATE svt_users SET status_subscription_stripe=0,status_subscription_paypal=0,status_subscription_2checkout=0,id_subscription_stripe=NULL,id_subscription_paypal=NULL,id_subscription_2checkout=NULL WHERE id=$id_user;");
            $query = "SELECT u.id,u.username,u.email,p.name as plan FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE u.id=$id_user;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows>0) {
                    while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                        $username = $row['username'];
                        $email_u = $row['email'];
                        $plan = $row['plan'];
                        if(empty($plan)) $plan = _("None");
                        set_user_log($id_user_logged,'change_user_plan',json_encode(array("id"=>$id_plan,"id_user"=>$id_user,"user_name"=>$username,"name"=>$plan)),date('Y-m-d H:i:s', time()));
                        if($settings['notify_plan_changes']) {
                            $subject = $settings['mail_plan_changed_subject'];
                            $body = $settings['mail_plan_changed_body'];
                            $body = str_replace("%USER_NAME%",$username,$body);
                            $body = str_replace("%PLAN_NAME%",$plan,$body);
                            $body = str_replace('<p><br></p>','<br>',$body);
                            $body = str_replace('<p>','<p style="padding:0;margin:0;">',$body);
                            $subject_q = str_replace("'","\'",$subject);
                            $body_q = str_replace("'","\'",$body);
                            $mysqli->query("INSERT INTO svt_notifications(id_user,subject,body,notify_user,notified) VALUES($id_user,'$subject_q','$body_q',1,0);");
                        }
                    }
                }
            }
        }
        if($change_role) {
            if($role=='administrator' && $super_admin==1) {
                $role = 'suer administrator';
            } else if($role=='administrator' && $super_admin==0) {
                $role = 'administrator';
            }
            $role = ucfirst($role);
            set_user_log($id_user_logged,'change_user_role',json_encode(array("id"=>$id_plan,"id_user"=>$id_user,"user_name"=>$username,"role"=>$role)),date('Y-m-d H:i:s', time()));
        }
        ob_end_clean();
        echo json_encode(array("status"=>"ok","reload"=>$reload));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("An error has occurred, please try again later")));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("An error has occurred, please try again later")));
}