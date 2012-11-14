<?php
class DB_Connect {
	protected $db;
	
	protected function __construct($dbo = null){
		if(is_object($dbo)){
			$this->db = $dbo;
		} else {
			$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
			try{
				$this->db = new PDO( $dsn, DB_USER, DB_PASS );
				$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			} catch(Exception $e){
				echo "Connection Issue: Please check you internet connection and try again.";
				file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
				die();
			}
		}
	}
}
?>