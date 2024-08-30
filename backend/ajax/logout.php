<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
require_once("../functions.php");
$lang = $_SESSION['lang'];
unset($_SESSION['id_user']);
unset($_SESSION['svt_si']);
unset($_SESSION['svt_si_l']);
$cookieExpiration = time() - 3600;
setcookie('cc_backend_l', '', $cookieExpiration, "/");
unset($_COOKIE['cc_backend_l']);
removeSession(session_id());
session_destroy();
session_start();
session_regenerate_id();
$_SESSION['lang'] = $lang;
session_write_close();