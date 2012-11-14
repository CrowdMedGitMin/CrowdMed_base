<?php

function __autoload($class){
	$filename = "class/class." . $class . ".inc.php";
	if(file_exists($filename)){
		include_once $filename;
	}
}

ini_set('display_errors',1);
error_reporting(E_ALL & ~E_NOTICE);

$_GET['m'] = '5';
$_GET['id'] = '1';
if(!isset($_GET['m'])){
	// checks to see if the market variable is set correctly
	echo "Market not set";
} else if(!isset($_GET['id'])){
	// checks to see if the userID is set correctly
	echo "User data not set";
} else {
//	$mid = $_GET['m'];
	$mid = '5';
	$user = '1';
	$parent_url = isset($_GET['url']) ? $_GET['url'] : null;
	$culture = isset($_GET['c']) ? $_GET['c'] : null;
	$exchange = isset($_GET['exchange']) ? $_GET['exchange'] : 1;
	$wholes = isset($_GET['wholes']) ? 1 : 0;

	$market = new Market(null,$mid,$user, $culture);

	if($userStatus = $market->checkUserStatus($user)){
		$purchace = $userStatus;
	}
	$colors = "";
	$colors = $market->getColors();
	
	if ($culture) {
		$language = $market->getLanguage();
	} else {
		$language = array(
			'submit' 			=> 'SUBMIT PREDICTIONS', 
			'concepts' 			=> 'Concepts', 
			'concept-hover'		=> 'Hover over concept name for description', 
			'others' 			=> 'Others Think...', 
			'others-hover' 		=> 'This is the current probability of a concept being the most preferred, as estimated by other users', 
			'think' 			=> 'I Think...',
			'think-hover' 		=> 'Your estimated probability of a concept being most preferred',
			'payout' 			=> 'Estimated Payout',
			'payout-hover' 		=> 'This is the estimated bonus you will earn if this concept is actually most preferred. Payout amounts are dependent on current probability. For example, concepts with a low current probability will reward a higher payout if it is the preferred concept.',
			'whatothers' 		=> 'What Others Think:',
			'whati' 			=> 'What I Think:',
			'loading' 			=> 'Loading Error',
			'errormsg' 			=> 'There was an error loading the next page, please click the Retry Submission button.',
			'retry' 			=> 'Retry Submission',
			'rightclick' 		=> 'Right-click is not allowed',
			'printscreen' 		=> 'Print Screen is not allowed',
			'concept' 			=> 'Concept',
			'price' 			=> 'Price',
			'currency'			=> '$',
			'currencyDelimeter'	=> '.',
			'currencySpacing'	=> ','
		);
	}
	
	$nopay = false; $example = false;
	if (isset($_GET['layout']) && $_GET['layout'] == 'nopay') {
		$nopay = true;
	}
	if (isset($_GET['layout']) && $_GET['layout'] == 'example') {
		$nopay = true;
		$example = true;
	}
?>


<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link type="text/css" rel="stylesheet" href="css/redmond/jquery-ui-1.8.2.custom.css" />
	<link type="text/css" rel="stylesheet" href="css/visualize.jQuery.css" />
	<link type="text/css" rel="stylesheet" href="https://www.feedback.infosurv.com/surveys/1296310000/iCE - Standard (2)/iCE - Standard (2).css" />
	<link type="text/css" rel="stylesheet" href="css/icemarket.css" />
</head>
<body>
	<div id="dataEntry">
		<div class="graphs">
					<div class="graphHolder" id="graph4">
						<table class="visualize-hide">
							<tr>
								<th><?php echo $language['concept']; ?></th>
								<th><?php echo $language['price']; ?></th>
							</tr>
<?php
	$stocks = $market->toArray();
	foreach($stocks as $id => $stock){
		echo "<tr><th>" . $stock['name'] . "</th><td>" . $stock['price'] . "</td></tr>";
	}
?>
						</table>
					</div>
					<div class="graphHolder" id="graph5">
						<table class="visualize-hide">
							<tr>
								<th><?php echo $language['concept']; ?></th>
								<th><?php echo $language['price']; ?></th>
							</tr>
<?php
	foreach($stocks as $id => $stock){
		echo '<tr><th>' . $stock['name'] . '</th><td id="graphKey' . $id . '">.01</td></tr>';
	}
	echo '<tr><th>Remaining Percent</th><td id="graphKeyBase">100</td></tr>';
?>

						</table>
						<a id="getStock" class="submit-button" href="#"><?php echo $language['submit']; ?></a>
					</div>
					<br style="clear:both;" />
				</div>
		
		<table border="1" cellpadding="5" cellspacing="0" style="width:900px;" id="mainTable">
			<thead valign="top">
				<tr>
					<th style="width:245px; position:relative;" class="mouseeffects">
						<?php echo $language['concepts']; ?><br />
						<span style="font-size:0.76em;"><?php echo $language['concept-hover']; ?></span>
						<div class="tooltip">
							<strong><?php echo $language['concepts']; ?></strong>
							<hr />
							<?php echo $language['concept-hover']; ?>
						</div>
					</th>
					<th style="width:140px; position:relative;" class="mouseeffects">
						<?php echo $language['others']; ?>
						<div class="tooltip">
							<strong><?php echo $language['others']; ?></strong>
							<hr />
							<?php echo $language['others-hover']; ?>
						</div>
					</th>
					<th id="sliderCol" class="mouseeffects" style="width:270px;position:relative;">
						<?php echo $language['think']; ?>
						<div class="tooltip">
							<strong><?php echo $language['think']; ?></strong>
							<hr />
							<?php echo $language['think-hover']; ?>
						</div>
					</th>
				<?php if (!$nopay): ?>
					<th class="mouseeffects" style="width:155px;position:relative;">
						<?php echo $language['payout']; ?>
						<div class="tooltip">
							<strong><?php echo $language['payout']; ?></strong>
							<hr />
							<?php echo $language['payout-hover']; ?>
						</div>
					</th>
				<?php endif; ?>
				</tr>
			</thead>
			<tbody>
<?php
// puts a table row for each stock located in the specified market into an array
$array = array();
$order = 0;
foreach($stocks as $id => $stock){
	$stockprice = number_format($stock['price'],0);
	$str = <<<EOT
<tr id='R$id'>
	<td>
		<span id='D$id' class='concept'>{$stock['name']}</span>
	</td>
	<td style='text-align:center'><span id='P$id' class='cost'>$stockprice%</span></td>
	<td style='text-align:center;'>
		<div style="float:left;width:15%;">0%</div>
		<div id='S$id' name="$order" class='slider' style="float:left;margin-top:7px;width:64%;"></div>
		<div style='float:left;width:20%;'>100%</div>
	</td>
EOT;
	if (!$nopay) {
		if ($wholes != 0) {
			$str .= "<td id='A$id' style='text-align:center'>" . $language['currency'] . "0</td>";
		} else {
			$str .= "<td id='A$id' style='text-align:center'>" . $language['currency'] . "0{$language['currencyDelimeter']}00</td>";
		}
	}
	$str .= "</tr>";
	
	$array[] = $str;
	$order++;
}
// randomizes array order
for($X=0;$X<count($array);$X++){
	$Y = rand(1,count($array)-1);
	$element1 = $array[$X];
	$element2 = $array[$Y];
	$array[$X] = $element2;
	$array[$Y] = $element1;
}
// outouts table rows
foreach($array as $row){
	echo $row;
}

?>
			</tbody>
		</table>
	</div>
	<div id="error"></div>
	<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.8.2.custom.min.js"></script>
	<script type="text/javascript" src="js/jquery.ba-postmessage.min.js"></script>
	<!--[if IE]><script type="text/javascript" src="js/excanvas.compiled.js"></script><![endif]-->
	<script type="text/javascript" src="js/visualize.jQuery.min.js"></script>
	<script type="text/javascript">
		var marketID = "<?php echo $mid; ?>",
			userID = "<?php echo $user; ?>",
			culture = "<?php echo $culture; ?>",
			parent_url = "<?php echo $parent_url; ?>"
			ice_purchace = "<?php echo $purchace; ?>",
			colors = "<?php echo $colors; ?>",
			wholes = "<?php echo $wholes; ?>",
			language = {
				'whatothers': "<?php echo $language['whatothers']; ?>",
				'whati': "<?php echo $language['whati']; ?>",
				'loading': "<?php echo $language['loading']; ?>",
				'errormsg': "<?php echo $language['errormsg']; ?>",
				'retry': "<?php echo $language['retry']; ?>",
				'rightclick': "<?php echo $language['rightclick']; ?>",
				'printscreen': "<?php echo $language['printscreen']; ?>",
				'currency': "<?php echo $language['currency']; ?>",
				'currencyDelimeter': "<?php echo $language['currencyDelimeter']; ?>",
				'currencySpacing': "<?php echo $language['currencySpacing']; ?>",
				'exchange': "<?php echo $exchange; ?>"
			};
	</script>	
	<script type='text/javascript' src="js/icemarket.js"></script>
</body>
</html>
<?php
}// End display
?>