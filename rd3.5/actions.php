<?php
$actions = array(
	'getAmount'=>array(
		'object'=>'Market',
		'method'=>'getAmount'
	),
	'getStock'=>array(
		'object'=>'Market',
		'method'=>'getStock'
	),
	'getConcepts'=>array(
		'object'=>'Market',
		'method'=>'getConcepts'
	),
	'getConceptsSimple'=>array(
		'object'=>'Market',
		'method'=>'getConceptsSimple'
	),
	'getData'=>array(
		'object'=>'Market',
		'method'=>'getData'
	)
);

if(isset($_POST['action']) || isset($_GET['action'])){
	$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
	$mid = isset($_POST['m']) ? $_POST['m'] : $_GET['m'];
	$uid = isset($_POST['u']) ? $_POST['u'] : $_GET['u'];
	$culture = isset($_POST['c']) ? $_POST['c'] : $_GET['c'];
	if(isset($_POST['callback'])){
		$callback = $_POST['callback'];
 	} elseif(isset($_GET['callback'])){
		$callback = $_GET['callback'];
	} else {
		$callback = null;
	}
	if(isset($_POST['id'])){
		$id = (int) $_POST['id'];
	} elseif(isset($_GET['id'])){
		$id = (int) $_GET['id'];		
	} else {
		$id = null;
	}
}

if(isset($actions[$action])){
	$use_array = $actions[$action];
	$obj = new $use_array['object']($dbo,$mid,$uid,$culture);
	
	header('Content-Type: text/javascript; charset=UTF-8');
	echo $obj->$use_array['method']($id,$callback);
}


function __autoload($class){
	$filename = "class/class." . $class . ".inc.php";
	if(file_exists($filename)){
		include_once $filename;
	}
}
?>