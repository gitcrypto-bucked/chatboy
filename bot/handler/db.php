<?php
global $link, $sgo;

include_once  'config.php';
env();

$link = mysqli_connect(trim(getenv('DB_HOST')),getenv('DB_USERNAME'),getenv('DB_PASSWORD'), trim(getenv('DB_DATABASE'))  ) or die( mysqli_error($link) );
$sgo = mysqli_connect(trim(getenv('DB_HOST')),getenv('DB_USERNAME'),getenv('DB_PASSWORD'), 'sgo_portal'  ) or die( mysqli_error($link) );


?>