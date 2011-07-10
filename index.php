<?php
header('Content-type: text/html; charset=utf-8');
require_once 'config.php';


printSearchField();
printUploadForm();

if(isset($_GET['uploaded'])) {
	parseUploadedFile();
}
if(isset($_GET['search_string'])) {
	searchMovie($_GET['search_string']);
}


?>