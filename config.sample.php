<?php
error_reporting(E_ALL);
include 'main.php';
$db_host = 'localhost';
$db_user = 'pmd';
$db_password = 'pmd';
$db_db = 'pmd';

$mysqli = new mysqli($db_host, $db_user, $db_password, $db_db);

?>