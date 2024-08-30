<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
require_once("../db/connection.php");
$result = $mysqli->query("SELECT c.table_name, c.column_name FROM information_schema.columns c LEFT JOIN information_schema.key_column_usage k ON c.table_schema = k.table_schema AND c.table_name = k.table_name AND c.column_name = k.column_name WHERE c.table_schema = '".DATABASE_NAME."' AND c.table_name LIKE 'svt_%' AND c.data_type = 'varchar' AND (c.column_default IS NULL OR c.column_default = 'NULL') AND k.column_name IS NULL");
if($result) {
    if ($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $table = $row['table_name'];
            $column = $row['column_name'];
            $alter_query = "ALTER TABLE $table MODIFY COLUMN $column TEXT;";
            $mysqli->query($alter_query);
        }
    }
} else { echo $mysqli->error; }
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8mb4';");
$result = $mysqli->query("SELECT DISTINCT table_name FROM information_schema.tables WHERE table_schema = '".DATABASE_NAME."' AND table_name LIKE 'svt_%' AND (table_collation != 'utf8mb4_unicode_ci');");
if($result) {
    if ($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $table_name = $row["table_name"];
            $alter_table_query = "ALTER TABLE `$table_name` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
            $mysqli->query($alter_table_query);
        }
    }
} else { echo $mysqli->error; }
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8mb4';");
$result = $mysqli->query("SELECT table_name,column_name FROM information_schema.columns WHERE table_schema = '".DATABASE_NAME."' AND table_name LIKE 'svt_%' AND data_type IN ('text','varchar','longtext') AND (character_set_name != 'utf8mb4');");
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $table_name = $row["table_name"];
            $column_name = $row['column_name'];
            $alter_column_query = "ALTER TABLE `$table_name` MODIFY `$column_name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
            $mysqli->query($alter_column_query);
        }
    }
} else { echo $mysqli->error; }
$mysqli->close();