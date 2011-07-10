<?php
header('Content-type: text/html; charset=utf-8');
require_once 'config.php';


printSearchForm();
printUploadForm();

if(isset($_GET['uploaded'])) {
	parseUploadedFile();
}
if(isset($_GET['search_string'])) {
	printSearchResult($_GET['search_type'], searchMovie($_GET['search_type'], $_GET['search_string']));
}
printActor($_GET['actor']);
printMovie(getMovie($_GET['movie']));

?>