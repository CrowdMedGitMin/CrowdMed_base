<?php
include_once 'sys/db_cred.php';

foreach( $C as $name => $val ){
  define($name, $val);
}


$dbo = mysqli_init();

$dbo->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

function __autoload($class){
  $filename = "sys/class." . strtolower($class) . ".php";
  if( file_exists($filename) ){
    include_once $filename;
  }
}

?>