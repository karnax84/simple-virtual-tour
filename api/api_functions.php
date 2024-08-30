<?php

function check_api_missing_params($params, $mandatory_params)
{
    $missing_params = "";
    foreach ($mandatory_params as $mandatory_param) {
        if (!isset($params[$mandatory_param])) {
            $missing_params .= $mandatory_param . ",";
        } else if (empty($params[$mandatory_param])) {
            $missing_params .= $mandatory_param . ",";
        }
    }
    $missing_params = rtrim($missing_params, ",");
    if (!empty($missing_params)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(array("message" => "missing parameters: $missing_params"));
        exit;
    }
}

function validate_api_key($api_key)
{
    if (!empty($api_key)) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $bearerToken = $matches[1];
                if (!hash_equals($api_key, $bearerToken)) {
                    http_response_code(401);
                    echo json_encode(array("message" => "Unauthorized: invalid API key"));
                    exit;
                }
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "Unauthorized: invalid Bearer authorization token"));
                exit;
            }
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized: missing Bearer authorization token"));
            exit;
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized: missing API key"));
        exit;
    }
}

function validate_token($token)
{
    if (empty($token)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(array("message" => "missing token"));
        exit;
    }
    $signer = new XSpat\Jwt\Cryptography\Algorithms\Hmac\HS256('12345678901234567890123456789012');
    $parser = new XSpat\Jwt\Parser($signer);
    try {
        $payload = $parser->parse($token);
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(array("message" => "invalid token"));
        exit;
    }
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(array("message" => "expired token"));
        exit;
    }
    return $payload;
}

function check_if_demo($id_user)
{
    $demo = false;
    if (file_exists("../config/demo.inc.php")) {
        require_once ("../config/demo.inc.php");
        $demo_developer_ip = DEMO_DEVELOPER_IP;
        $demo_server_ip = DEMO_SERVER_IP;
        $demo_user_id = DEMO_USER_ID;
        if (($_SERVER['SERVER_ADDR'] == $demo_server_ip) && ($_SERVER['REMOTE_ADDR'] != $demo_developer_ip) && ($id_user == $demo_user_id)) {
            $demo = true;
        }
    }
    return $demo;
}

function check_if_saas()
{
    if (array_key_exists('SERVER_ADDR', $_SERVER)) {
        $z0 = $_SERVER['SERVER_ADDR'];
        if (!filter_var($z0, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $z0 = gethostbyname($_SERVER['SERVER_NAME']);
        }
    } elseif (array_key_exists('LOCAL_ADDR', $_SERVER)) {
        $z0 = $_SERVER['LOCAL_ADDR'];
    } elseif (array_key_exists('SERVER_NAME', $_SERVER)) {
        $z0 = gethostbyname($_SERVER['SERVER_NAME']);
    } else {
        if (stristr(PHP_OS, 'WIN')) {
            $z0 = gethostbyname(php_uname('n'));
        } else {
            $b1 = shell_exec('/sbin/ifconfig eth0');
            preg_match('/addr:([\d\.]+)/', $b1, $e2);
            $z0 = $e2[1];
        }
    }
    $v3 = get_settings();
    $o5 = $z0 . 'RR' . $v3['purchase_code'];
    $v6 = password_verify($o5, $v3['license']);
    if (!$v6 && !empty($v3['license2'])) {
        $o5 = str_replace("www.", "", $_SERVER['SERVER_NAME']) . 'RR' . $v3['purchase_code'];
        $v6 = password_verify($o5, $v3['license2']);
    }
    $o5 = $z0 . 'RE' . $v3['purchase_code'];
    $w7 = password_verify($o5, $v3['license']);
    if (!$w7 && !empty($v3['license2'])) {
        $o5 = str_replace("www.", "", $_SERVER['SERVER_NAME']) . 'RE' . $v3['purchase_code'];
        $w7 = password_verify($o5, $v3['license2']);
    }
    $o5 = $z0 . 'E' . $v3['purchase_code'];
    $r8 = password_verify($o5, $v3['license']);
    if (!$r8 && !empty($v3['license2'])) {
        $o5 = str_replace("www.", "", $_SERVER['SERVER_NAME']) . 'E' . $v3['purchase_code'];
        $r8 = password_verify($o5, $v3['license2']);
    }
    if ($v6) {
        $saas = false;
    } else if (($r8) || ($w7)) {
        $saas = true;
    } else {
        $saas = false;
    }
    return $saas;
}

function fatal_handler()
{
    $errfile = "unknown file";
    $errstr = "shutdown";
    $errno = E_CORE_ERROR;
    $errline = 0;
    $error = error_get_last();
    if ($error !== NULL) {
        $errno = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr = $error["message"];
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message" => format_error($errno, $errstr, $errfile, $errline)));
        exit;
    }
}

function format_error($errno, $errstr, $errfile, $errline)
{
    $trace = print_r(debug_backtrace(false), true);
    $content = "Error: $errstr, Line: $errline";
    return $content;
}