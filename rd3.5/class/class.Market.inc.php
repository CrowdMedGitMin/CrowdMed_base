<?php
class Market extends DB_Connect {
	protected $market;
	protected $b = 250;
	protected $user;
	protected $sampling;
	protected $stocks = array();
	protected $db;
	protected $culture;
	
	/*
	 * Creates the Market object for the specified market and the specified user
	 * @param $mid : Market identifier
	 * @param $userID : user identifier
	 */
	public function __construct($dbo = null, $mid, $userID, $culture = null) {
		parent::__construct($dbo);

		$this->culture = $culture;
		$this->market = $mid;
		
		$this->updateMarket();

		if(!($this->user = $this->userExists($userID))){
			$this->user = $this->createUser($userID);
		}
		$this->sampling = $userID;

	}
	
	public function __toString() {
		return "<pre>" . var_dump($this->stocks) . "</pre>";
	}
	
	/*
	 * Checks to see if the user exists in the database
	 * @param $userID : user identifier
	 * @return bool, true if userID exists, false otherwise
	 */
	protected function userExists($userID) {
		$sql = "SELECT id FROM traders WHERE sampling_id=? AND market_id=? LIMIT 1";
		try{
			$stmt = $this->db->prepare($sql);
			$stmt->bind_param('si',$userID,$this->market);
			$stmt->execute();
			$stmt->bind_result($rid);
			$stmt->fetch();
			$stmt->close();
		} catch(Exception $e){
			echo "Connection Issue: Please check you internet connection and try again.";
			file_put_contents('PDOErrors.txt', "36: " . $e->getMessage() . "\n\r", FILE_APPEND);
			die();
		}
		
		return $rid;
	}
	
	/*
	 * Updates the market from the database
	 */
	protected function updateMarket(){
		$sql = "SELECT * FROM concepts WHERE market_id=?";
		
		try{
			$stmt = $this->db->prepare($sql);
			$stmt->bind_param('i',$this->market);
			$stmt->execute();
			$meta = $stmt->result_metadata();
			$fields = $meta->fetch_fields();
			foreach ($fields as $field) {
				$result[$field->name] = "";
				$resultArray[$field->name] = &$result[$field->name];
			}
			call_user_func_array(array($stmt, 'bind_result'), $resultArray);
			//$stmt->bind_result($sid, $name, $shares, $color);
			while ($stmt->fetch()) {
				$sid = $resultArray['id'];
				foreach($resultArray as $key => $value) {
					$this->stocks[$sid][$key] = $value;
				}
				$this->stocks[$sid]['futures'] = $resultArray['shares'];
			}
			$stmt->close();
			
			
			if ($this->culture) {
				// Do stuff to grab languages for stocks
				$this->getTranslatedConcepts();
			}
		} catch(Exception $e){
			echo "Connection Issue: Please check you internet connection and try again.";
			file_put_contents('PDOErrors.txt', "57: " . $e->getMessage() . "\n\r", FILE_APPEND);
			die();
		}
	}
	
	private function getTranslatedConcepts() {
		$sql = "SELECT concept_id, name, subname, color, image_sm, image_lg, format_sm, format_std, description FROM concept_translations WHERE market_id=? AND cultureCode =?";
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bind_param('is',$this->market, $this->culture);
			$stmt->execute();
			$meta = $stmt->result_metadata();
			$fields = $meta->fetch_fields();
			foreach ($fields as $field) {
				$result[$field->name] = "";
				$resultArray[$field->name] = &$result[$field->name];
			}
			call_user_func_array(array($stmt, 'bind_result'), $resultArray);
			//$stmt->bind_result($sid, $name, $shares, $color);
			while ($stmt->fetch()) {
				$sid = $resultArray['concept_id'];
				foreach($resultArray as $key => $value) {
					if ($value != "") {
						$this->stocks[$sid][$key] = $value;
					}
				}
			}
			$stmt->close();
		}	catch(Exception $e){
			echo "Connection Issue: Please check you internet connection and try again.";
			file_put_contents('PDOErrors.txt', "103: " . $e->getMessage() . "\n\r", FILE_APPEND);
			die();
		}
	}
	
	/*
	 *
	 *
	 * @return string of colors so javascript can split it into an array
	 */
	public function getColors(){
		$result = array();
		foreach($this->stocks as $id => $stock){
			$result[$id] = $stock['color'];
		}
		$result = implode($result,",");
		if(substr($result,0,1) == ","){
			return null;
		} else return $result;
	}
	

	
	/*
	 * Creates the user in the database
	 * @param $userID : user identifier
	 */
	protected function createUser($userID){
		$sql = "INSERT INTO traders (sampling_id, market_id) VALUES (?,?)";
		try{
			$stmt = $this->db->prepare($sql);
			$stmt->bind_param('si',$userID,$this->market);
			$stmt->execute();
			$r = $this->db->insert_id;
			$stmt->close();
		} catch(Exception $e){
			echo "Connection Issue: Please check you internet connection and try again.";
			file_put_contents('PDOErrors.txt', "110: " . $e->getMessage() . "\n\r", FILE_APPEND);
			die();
		}
		return $r;
	}
	
	/*
	 * Creates the user in the database
	 * @param $userID : user identifier
	 */
	public function checkUserStatus($userID){
		$sql = "SELECT purchased FROM traders WHERE sampling_id=? AND market_id=? LIMIT 1";
		try{
			$stmt = $this->db->prepare($sql);
			$stmt->bind_param('si',$userID,$this->market);
			$stmt->execute();
			$stmt->bind_result($purchased);
			$stmt->fetch();
			$stmt->close();
		} catch(Exception $e){
			echo "Connection Issue: Please check you internet connection and try again.";
			file_put_contents('PDOErrors.txt', "121: " . $e->getMessage() . "\n\r", FILE_APPEND);
			die();
		}		
		return $purchased;
	}
	
	
	
	/* 
	 * Returns an array of stock prices for all sotcks in the market
	 * @return an array, $array[<key>] = <value>; where <key> = stock ID, <value> = price
	 */
	public function toArray(){
		$array = array();
		foreach($this->stocks as $id => $stock){
			$array[$id]['price'] = round(100*(exp($stock['shares']/$this->b)/$this->getDom()),2);
			$array[$id]['name'] = $stock['name'];
		}
		return $array;
	}
	
	/* 
	 * returns the price of stock that has the specified ID
	 * @param $stockKey : stock identifier
	 * @param $future : used to control weither the user is looking at possible future prices
	 * @return a stock price
	 */
	public function getPrice($stockKey,$future = false){
		if(!array_key_exists($stockKey,$this->stocks)) return "Stock not found";
		if($future){
			$value = $this->futures[$stockKey];
			$dom = $this->getDom(true);
		} else {
			$value = $this->stocks[$stockKey];
			$dom = $this->getDom();
		}
		return round(100*(exp($value/$this->b)/$dom),2);
	}
	
	/*
	 * Gets the stock name when given a stock ID
	 * @param $stockKey : the stock ID
	 * @return the name of the specified stock
	 */
	public function getStockName($stockKey){
	/*	if(!array_key_exists($stockKey,$this->stocks)) return "Stock not found";
		else {
			$sql = "SELECT stockName FROM markets WHERE marketID=? AND stockID=?";
			$stmt = $this->db->prepare($sql);
			$stmt->bind_param('ii', $this->market, $stockKey);
			$stmt->execute();
			$stmt->bind_result($stockName);
			$stmt->fetch();
			$stmt->close();
			return $stockName;
		}	*/
	}
	
	/*
	 * Gets the stock IDs of the market
	 * @return the IDs of the stocks
	 */
	public function getStockIDs(){
		foreach($this->stocks as $key => $value){
			$data[] = $key;
		}
		return $data;
	}
	
	public function getStockCost($stockKey,$amount,$future = false){
		$newValue = 0;
		foreach((($future) ? $this->futures : $this->stocks) as $key => $stock){
			if($key == $stockKey){
				$newValue += exp(($stock+$amount)/$this->b);
			} else {
				$newValue += exp($stock/$this->b);
			}
		}
		return round(100*(($this->b*log($newValue)) - ($this->b*log( (($future) ? $this->getDom(true) : $this->getDom())))),2);
	}
	/*
	 * returns the status of the last transaction by the user
	 * @return true if a transaction was found, false if not
	 */
	protected function lastTransaction(){
	/*	$sql = "SELECT stockID,delta,purchasePrice,time FROM stockHistory WHERE id=? AND marketID=? ORDER BY time DESC LIMIT 1";
		$stmt = $this->db->prepare($sql);
		$stmt->bind_param('si', $this->user, $this->market);
		$stmt->execute();
		$r = $stmt->num_rows;
		$stmt->close();
		if ($r < 1) {
			return false;
		} else {
			return true;
		}*/
	}
	/*
	*/
	public function getAmount(){
		$stockArray = array();
		foreach($_POST as $key => $value){
			if(substr($key,0,3) == 'stk'){
				$stockArray[substr($key,3)] = $value;
			}
		}
		// Sorts the array with the highest value at top
		arsort($stockArray);
		$this->resetFutureMarket();
		foreach($stockArray as $key => $value){
			// Get the amount of stock purchasable with the  amount of money that user wants (% * 10)
			$amount = $this->getStockAmount($key,($value*10),true);
			// Sets the value into an array
			$result[$key] = ($amount < 0) ? 0 : ($amount/10);
			// Sets the future market with the amount as being used, so future prices take into account the earlier trades
			$this->setFutureMarket(array($key => (($amount < 0) ? 0 : $amount)));
		}
		// returns the array
		return json_encode($result);
	}
	/*
	*/
	protected function setFutureMarket($array){
		foreach($array as $sid => $change){
			$this->stocks[$sid]['futures'] = $this->stocks[$sid]['shares'] + $change;
		}
	}
	/*
	*/
	protected function resetFutureMarket(){
		foreach($this->stocks as $id => $stock){
			$this->stocks[$id]['futures'] = $this->stocks[$id]['shares'];
		}
	}
	/*
	 * Gets the amount of stock that a user can buy with the amount of money the user is willing to spend
	 * @param $stockKey : the stock's identifier
	 * @param $cost : the cost the user with willing to spend
	 * @param $future : used to control weither the user is looking at possible future prices
	 * @return the amount of stock the user can buy
	 */
	protected function getStockAmount($stockKey,$cost,$future = false){
		$base = (($future) ? $this->getDom(true) : $this->getDom());
		$stuff = 0;
		foreach($this->stocks as $id => $stock){
			$stock = $future ? $stock['futures'] : $stock['shares'];
			if($id == $stockKey){
				$q1 = $stock;
			} else {
				$stuff += exp($stock/$this->b);
			}
		}
		return (($this->b*(log(exp((($cost/100)/$this->b)+log($base))-$stuff)))-$q1);
	}
	/* 
	 * Gets the base data for the algorithm: C = b * ln( e^(q1/b) + e^(q2/b) ... )
	 * @param $future : used to control weither the user is looking at possible future prices
	 * @return the number ( e^(q1/b) + e^(q2/b) ... ), for all stocks (q1,q2,...) in the market
	 */
	protected function getDom($future = false){
		$stockDom = 0;
		foreach($this->stocks as $id => $stock){
			$stockval = $future ? $stock['futures'] : $stock['shares'];
			$stockDom += (exp($stockval/$this->b));
		}			
		return $stockDom;
	}
	/*
	 * Buys stock from the market
	 * @param $stockArray : an array of stocks to buy, key = stockID, value = quantity to buy
	 */
	public function getStock(){
		// grab stock values from the POST array
		foreach($_POST as $key => $value){
			if(substr($key,0,3) == 'stk'){
				$stockArray[substr($key,3)] = $value;
			}
		}
		// Sort the array by values, highest to lowest
		arsort($stockArray);
		try {
			$stmt = $this->db->prepare("UPDATE concepts SET shares = (shares + ?) WHERE market_id=? AND id=?");
			$stmt2 = $this->db->prepare("INSERT INTO trades (concept_id,trader_id,quantity,paid,prices) VALUES (?,?,?,?,?)");
		} catch (Exception $e) {
			echo "Connection Issue: Please check you internet connection and try again.";
			file_put_contents('PDOErrors.txt', "stmt failed: " . $e->getMessage() . "\n\r", FILE_APPEND);
			die();
		}
		foreach($stockArray as $key => $value){
			if($value != 0){
				$amount = ($value*10);
				$this->updateMarket();
				$quantity = $this->getStockAmount($key, $amount);
				try{
					$stmt->bind_param('sii', $quantity, $this->market, $key);
					$stmt->execute();
				} catch(Exception $e){
					echo "Connection Issue: Please check you internet connection and try again.";
					file_put_contents('PDOErrors.txt', "319: " . $e->getMessage() . "\n\r", FILE_APPEND);
					die();
				}
				$priceArray = serialize($this->toArray());
				try{
					$stmt2->bind_param('issis',$key, $this->user, $quantity, $amount, $priceArray);
					$stmt2->execute();
				} catch(Exception $e){
					echo "Connection Issue: Please check you internet connection and try again.";
					file_put_contents('PDOErrors.txt', "336: " . $e->getMessage() . "\n\r", FILE_APPEND);
					die();
				}
			}
		}
		$stmt->close();
		$stmt2->close();
		
		try{
			$stmt = $this->db->prepare("UPDATE traders SET purchased=1 WHERE id=?");
			$stmt->bind_param('s',$this->user);
			$stmt->execute();
		} catch(Exception $e){
			echo "Connection Issue: Please check you internet connection and try again.";
			file_put_contents('PDOErrors.txt', "350: " . $e->getMessage() . "\n\r", FILE_APPEND);
			die();
		}
		$stmt->close();
		return json_encode($this->toArray());
	}
	
	public function getConcepts($source = 0, $callback = null){
		// To make the actions page more generic, a $source value of 1 = true and 0 = false
		$concepts = array();
		
		foreach ($this->stocks as $rid => $stock) {
			$f = ($source==1) ? $stock['format_sm'] : $stock['format_std'];
			$concepts[] = array(
				'cid' => $rid, 
				'name' => $stock['name'], 
				'content' => $this->parseHTML($source,$stock,$f)
			);
		}

		if($callback){
			return $callback . '(' . json_encode($concepts) . ');';
		} else {
			return json_encode($concepts);
		}
		
	}

	/**
	 * A function to take a simple string of html tags and formats the concept 
	 * data into a string of html
	 *
	 * @param string $uid the identifier of the user
	 * @param int $m the market id
	 * @param boolean $s true for small image, false for large
	 * @param array $data the array of data to format
	 * @param string $format a simplified string of html to add content to
	 * @return string html content for a concept
	 */
	private function parseHTML($source,$data,$format){
		$array = explode('+%',$format);
		$result = "";
		$datadiv = false;
		foreach($array as $value){
			$len = strpos($value,' ');
			if(!$len){
				$len = strpos($value,'>');
			}
			$var = substr($value,1,$len-1);
			preg_match('/style="(.*)"/',$value,$out);
			switch($var){
				case 'h1':
					$result .= '<h1' . (isset($out[0]) ? " " . $out[0] : "") . '>'.$data['name'].'</h1>';
					break;
				case 'h3':
					$result .= '<h3' . (isset($out[0]) ? " " . $out[0] : "") . '>'.$data['subname'].'</h3>';
					break;
				case 'div':
					preg_match('/id="(.*)"/',$value,$out2);
					$result .= '<div' . (isset($out[0]) ? " " . $out[0] : "") . '>';
					if (isset($out2[0])) {
						$datadiv = true;
					}
					break;
				case 'img':
					$result .= '<img src="//presentation.infosurv.com/ice/rd3/picture.php?i=' . $this->sampling . '&m=' . $this->market . '&f=' . ($source ? $data['image_sm'] : $data['image_lg']) . '"' . (isset($out[0]) ? " " . $out[0] : "") . ' />';
					break;
				case '/div':
					if ($datadiv) {
						$result .= $data['description'];
						$datadiv = false;
					}
					$result .= '</div>';
					break;
				case '/h1':
					$result .= '</h1>';
					break;
				case '/h3':
					$result .= '</h3>';
					break;
			}

			if(strpos($value,"/")){}
		}
		return $result;
	}
	
	/**
	 *
	 *
	 * @return json 
	 */
	public function getData($type = null){

		$sql = "SELECT Trade.concept_id, Concept.name, Trade.quantity, Trade.paid, Trade.trade_time FROM trades AS Trade LEFT JOIN concepts AS Concept ON (Trade.concept_id = Concept.id) WHERE Concept.market_id=?";

		try{
			$stmt = $this->db->prepare($sql);
			$stmt->bind_param('i', $this->market);
			$stmt->execute();
			$stmt->bind_result($concept_id, $name, $quantity, $paid, $trade_time);
			while ($stmt->fetch()) {
				$c = $concept_id;
				$t = $trade_time;

				$math[$c]['quantity'][$t] = $quantity;
				$math[$c]['paid'][$t] = $paid;

				if ($type == 1) {
					$result['times'][$t][$c] = $paid/$quantity;
				} else {
					$result['times'][$t][$c] = array_sum($math[$c]['paid'])/array_sum($math[$c]['quantity']);
				}

				$return['concepts'][$c] = $name;
				$return['times'][$t] = 1;
			}
			$stmt->close();
		} catch(Exception $e){
			echo "Connection Issue: Please check you internet connection and try again.";
			file_put_contents('PDOErrors.txt', "getData(): " . $e->getMessage() . "\n\r", FILE_APPEND);
			die();
		}

		foreach($result['times'] as $time => $array){
			$diff = array_diff_key($return['concepts'], $array);
			foreach($diff as $key => $name){
				$result['times'][$time][$key] = "";
			}
		}
		
		ksort($return['times']);
		ksort($result['times']);
		
		foreach($result['times'] as $time => $array){
			foreach($array as $cid => $vwap){
				$return['vwap'][$cid][] = $vwap;
			}
		}
		
		return json_encode($return);
	}
	
	public function getLanguage() {
		$stmt = $this->db->prepare('SELECT K.keyword, L.translation FROM localizations AS L LEFT JOIN keywords AS K ON (L.keyword_id = K.id) WHERE L.cultureCode = ?');
		$stmt->bind_param('s', $this->culture);
		$stmt->execute();
		$stmt->bind_result($keyword, $translation);
		$result = array();
		while ($stmt->fetch()) {
			$result[$keyword] = $translation;
		}
		return $result;
	}
	
}
?>