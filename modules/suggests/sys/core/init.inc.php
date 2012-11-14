<?php
include_once 'sys/config/db-cred.inc.php';

foreach( $C as $name => $val ){
	define($name, $val);
}


$dbo = mysqli_init();

$dbo->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$dbo->set_charset("utf8");

function __autoload($class){
	$filename = "sys/class/class." . $class . ".inc.php";
	if( file_exists($filename) ){
		include_once $filename;
	}
}

?>
