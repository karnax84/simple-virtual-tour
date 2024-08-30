<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if ($_SESSION['svt_si'] != session_id()) {
    die();
}
require_once("../functions.php");
ob_start();
$type = $_POST['type'];
$id_user = $_SESSION['id_user'];
$settings = get_settings();
$user_info = get_user_info($id_user);
if(!isset($_SESSION['lang'])) {
    if(!empty($user_info['language'])) {
        $language = $user_info['language'];
    } else {
        $language = $settings['language'];
    }
} else {
    $language = $_SESSION['lang'];
}
session_write_close();
$folderPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . $type.'_tmp' . DIRECTORY_SEPARATOR;
$zipFiles = array();
$dir = opendir($folderPath);
if ($dir) {
    while (($file = readdir($dir)) !== false) {
        $filePath = $folderPath . $file;
        if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'zip') {
            $fileInfo = stat($filePath);
            $creationDate = $fileInfo['ctime'];
            $formattedDate = formatTime("dd MMM y HH:mm",$language,$creationDate);
            $fileSize = $fileInfo['size'];
            $formattedSize = '';
            if ($fileSize >= 1073741824) {
                $formattedSize = number_format($fileSize / 1073741824, 2) . ' GB';
            } elseif ($fileSize >= 1048576) {
                $formattedSize = number_format($fileSize / 1048576, 2) . ' MB';
            } else {
                $formattedSize = number_format($fileSize / 1024, 2) . ' KB';
            }
            $zipFiles[] = array(
                'name' => $file,
                'creationDate' => $creationDate,
                'formattedDate' => $formattedDate,
                'size' => $fileSize,
                'formattedSize' => $formattedSize,
            );
        }
    }
    closedir($dir);
}
ob_end_clean();
foreach ($zipFiles as $fileData) {
    echo '<tr>';
    switch($type) {
        case 'export':
            echo '<td style="text-align:center;"><button onclick="download_file(\'../services/export_tmp/'.$fileData['name'].'\')" title="'._("DOWNLOAD").'" class="btn btn-xs btn-primary"><i class="fa-solid fa-download"></i></button>&nbsp;&nbsp;<button onclick="delete_import_export_file(\'export\',\''.$fileData['name'].'\')" title="'._("DELETE").'" class="btn btn-xs btn-danger"><i class="fa-solid fa-trash"></i></button></td>';
            break;
        case 'import':
            echo '<td style="text-align:center;"><button onclick="download_file(\'../services/import_tmp/'.$fileData['name'].'\')" title="'._("DOWNLOAD").'" class="btn btn-xs btn-primary"><i class="fa-solid fa-download"></i></button>&nbsp;&nbsp;<button onclick="import_tour_check(\''.$fileData['name'].'\')" title="'._("IMPORT").'" class="btn btn-xs btn-warning"><i class="fa-solid fa-arrow-right"></i></button>&nbsp;&nbsp;<button onclick="delete_import_export_file(\'import\',\''.$fileData['name'].'\')" title="'._("DELETE").'" class="btn btn-xs btn-danger"><i class="fa-solid fa-trash"></i></button></td>';
            break;
    }
    echo '<td>'.htmlspecialchars($fileData['name']).'</td>';
    echo '<td><span style="display:none">'.$fileData['creationDate'].'</span>'.htmlspecialchars($fileData['formattedDate']).'</td>';
    echo '<td>'.$fileData['size'].'</td>';
    echo '<td>'.htmlspecialchars($fileData['formattedSize']).'</td>';
    echo '</tr>';
}