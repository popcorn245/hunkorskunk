<?php

class App{

	 /////////////////////////////////////
	// PROTECTED VARIABLES
	protected
		//Global Vars
		$action,
		$valid,

		//PDO Vars
		$pdo_host = "localhost",
		$pdo_usr  = "root",
		$pdo_pwd  = "",
		$pdo_db   = "popcorn_hunkorskunk",
		$pdo_insert,
		$PDO;

	private function clean($string){
		$detagged = strip_tags($string, $this->wysiwyg_accept);
		if(get_magic_quotes_gpc()) {
			$stripped = stripslashes($detagged);
			$escaped = $stripped;
		} else {
			$escaped = $detagged;
		}
		return $escaped;
	}

	private function connectIt(){
		// Set Defaults
		$host = $this->pdo_host;
		$db = $this->pdo_db;
		$usr = $this->pdo_usr;
		$pwd = $this->pdo_pwd;

		// Create PDO Connection
		try {
			$this->PDO = new PDO("mysql:host=".$host.";dbname=".$db, $usr, $pwd);
		}catch(PDOException $e){
			$this->logIt($e->getMessage(),"error");
			$this->echoIt("SQL Connection Error",false); // ERROR: No SQL Connection
		}
	}

	private function echoIt($data, $success){
		$resArray = array();

		if($success){
			$resArray['status'] = 'success';
		}else{
			$resArray['status'] = 'failure';
		}
		$resArray['data'] = $data;

		$this->PDO = null;

		exit(json_encode($resArray));
	}

	private function ipIt(){
	     $ipaddress = '';
	     if (getenv('HTTP_CLIENT_IP'))
	         $ipaddress = getenv('HTTP_CLIENT_IP');
	     else if(getenv('HTTP_X_FORWARDED_FOR'))
	         $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	     else if(getenv('HTTP_X_FORWARDED'))
	         $ipaddress = getenv('HTTP_X_FORWARDED');
	     else if(getenv('HTTP_FORWARDED_FOR'))
	         $ipaddress = getenv('HTTP_FORWARDED_FOR');
	     else if(getenv('HTTP_FORWARDED'))
	        $ipaddress = getenv('HTTP_FORWARDED');
	     else if(getenv('REMOTE_ADDR'))
	         $ipaddress = getenv('REMOTE_ADDR');
	     else
	         $ipaddress = 'UNKNOWN';

	     return $ipaddress; 
	}

	private function queryIt($sql, $single = false){
		if(stristr($sql, 'DELETE') || stristr($sql, 'DROP')){
			$this->logIt("Malicious SQL Attack!","attack");
			return false;
		}else if(strstr($sql, 'INSERT')){
			if($single){
				$query = $this->PDO->prepare($sql);
				$query->execute($single);
			}else{
				$query = $this->PDO->prepare($sql);
				$query->execute();
			}
			$this->pdo_insert = $this->PDO->lastInsertId();
			return true;
		}else if(strstr($sql, 'SELECT')){
			$query = $this->PDO->prepare($sql);
			$query->execute();
			$results = $query->fetchAll(PDO::FETCH_ASSOC);
			if(count($results) >= 1 && !$single){
				return $results;
			}else if(count($results) == 1 && $single){
				return $results[0];
			}else{
				return false;
			}
		}else if(strstr($sql, 'UPDATE')){
			if($single){
				$query = $this->PDO->prepare($sql);
				$query->execute($single);
			}else{
				$query = $this->PDO->prepare($sql);
				$query->execute();
			}
			return true;
		}else{
			return false;
		}
	}

	private function logIt($log = false, $type = false){
		// Set Vars
		$ip   = $this->ipIt();
		$date = date('m/d/Y h:i:s a');
		$user = session_id();

		if(!$type){
			$type = $this->action;
		}

		if(!$log){
			if($type == 'read'){
				$log = $user." downloaded their daily stack of hunks";
			}else if($type == 'rate'){
				$guy = $this->queryIt("SELECT * FROM guys WHERE guy_id = '$_POST[guy]'",true);
				$log = $user." rated ".$guy['guy_name']."'s photo ".$_POST['stars']." stars";
			}else if($type == 'view'){
				$guy = $this->queryIt("SELECT * FROM guys WHERE guy_id = '$_POST[guy]'",true);
				$log = $user." viewed ".$guy['guy_name']."'s photo";
			}
			$sql = "INSERT INTO logs (log_type, log_date, log_user, log_ip, log_text) values ('$type', '$date', '$user', '$ip', '$log')";
			return $this->queryIt($sql);
		}
	}

	function __construct(){
		session_start();
		date_default_timezone_set("America/Chicago");

		$postdata = file_get_contents("php://input");
		$request = json_decode($postdata);

		$this->action = $request->action;

		if($this->action){
			$this->connectIt();
			$this->logIt();

			if($this->action == 'rate'){
				$date = date('m/d/Y h:i:s a');
				$user = session_id();
				$bind = array(
					$this->clean($request->guy),
					$this->clean($request->stars),
					$this->clean($request->comment)
				);

				if($_SESSION['rated']){
					if(count($_SESSION['rated']) < 4){
						$_SESSION['rated'][] = $request->guy;
					}
				}else{
					$_SESSION['rated'] = array($request->guy);
				}

				if($this->queryIt("INSERT INTO ratings (rating_date, rating_guy, rating_user, rating_stars, rating_comment) VALUES ('$date', ?, '$user', ?, ?)",$bind)){
					$this->echoIt("Rating Added!",true);
				}else{
					$this->echoIt("Rating Failed!",false);
				}
				
			}else if($this->action == 'read'){
				$view  = $request->view;
				$chunk = array();
				if($view == 'guys'){
					$guys = $this->queryIt("SELECT guy_name AS name, guy_id AS guy, guy_picture AS picture FROM guys LIMIT 5");
					for($i=0; $i < count($guys); $i++){
						$guy = $guys[$i];
						$guy['stars'] = 0;
						$ratings = $this->queryIt("SELECT rating_date AS time, rating_user AS user, rating_stars AS stars, rating_comment AS comment FROM ratings WHERE rating_guy = '$guy[guy]'");
						$guy['ratings'] = $ratings;
						for($x=0; $x < count($ratings); $x++){
							$rating = $ratings[$x];
							$guy['stars'] += $rating['stars'];
						}
						$guy['stars'] = round($guy['stars'] / count($ratings));
						$guys[$i] = $guy;
					} 
					$chunk = $guys;
				}
				$this->echoIt($chunk,true);
			}else{
				$this->echoIt("Valid Action Required!",false); // ERROR: Valid Action Required
			}
			
		}
	}
}

$App = new App();

?>