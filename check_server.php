<?php

$errno = 0;
$errstr = '';
$fp = @fsockopen('127.0.0.1', 8001, $errno, $errstr, 2);
var_dump($fp !== false);
echo "errno={$errno} errstr={$errstr}\n";
if ($fp) {
    fclose($fp);
}
