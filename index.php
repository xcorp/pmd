<?php
header('Content-type: text/html; charset=utf-8');
require_once 'config.php';

if(isset($_GET['uploaded'])) {
	parseUploadedFile();
}

printMovie(getMovie('322259'));

?>