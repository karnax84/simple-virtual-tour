<?php
ob_start();
$key = bin2hex(random_bytes(15));
ob_end_clean();
echo $key;