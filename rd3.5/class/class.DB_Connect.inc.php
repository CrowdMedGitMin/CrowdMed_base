<?php
class DB_Connect {
	protected $db;
	
	protected function __construct($dbo = null){
		if(is_object($dbo)){
			$this->db = $dbo;
		} else {
			$this->db = mysqli_init();
			try{
				$this->db->real_connect('mx.internal', 'iceninja', 'cWUeDtUHc89VWfhZ', 'ice');
				$this->db->set_charset("utf8");
			} catch(Exception $e){
				echo "Connection Issue: Please check your internet connection and try again.";
				file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
				die();
			}
		}
	}
}
?>
