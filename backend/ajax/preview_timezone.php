<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
require_once("../functions.php");
$settings = get_settings();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedTimezone = $_POST["timezone"];
    date_default_timezone_set($selectedTimezone);
    echo formatTime("dd MMM y - HH:mm",$settings['language'],time());
}