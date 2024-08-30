<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
if(isset($_POST['version']) && !empty($_POST['version'])) {
    $version = strip_tags($_POST['version']);
    require_once("../../db/connection.php");
    require_once("../../services/check_update.php");
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8mb4';");
    $query = "UPDATE svt_settings SET `version`=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$version);
        $result = $smt->execute();
    }
}