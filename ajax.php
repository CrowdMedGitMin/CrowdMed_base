<?php
include_once 'sys/init.php';

// If the request did not come from AJAX, exit:
/*
if($_SERVER['HTTP_X_REQUESTED_WITH'] !='XMLHttpRequest'){
  exit;
}
*/
header('Content-Type: text/javascript; charset=UTF-8');

$data = array();
// Converting the IP to a number. This is a more effective way
// to store it in the database:
//$data['ip'] = sprintf('%u',ip2long($_SERVER['REMOTE_ADDR']));
// As the above is limited to IPv4 addresses, I decided to default 
// back to storing IPs in varchar(39)
$data['ip'] = $_GET['user'];
$callback = $_GET['callback'];

if($_GET['action'] == 'grab'){
  
  $sql = "SELECT s.id, s.suggestion, if (v.ip IS NULL,0,1) AS have_voted
    FROM suggestions AS s
    LEFT JOIN suggestions_votes AS v
    ON(
      s.id = v.suggestion_id
      AND v.day = CURRENT_DATE
      AND v.ip = ?
    )
    WHERE project_id = ?
    AND culture = ?
    ORDER BY s.rating DESC, s.id DESC";
  try {
    $stmt = $dbo->prepare($sql);
    $stmt->bind_param('sss', $data['ip'],$_GET['pid'],$_GET['culture']);
    $stmt->execute();
    $stmt->bind_result($sid, $ssuggestion, $havevoted);
    $str = array();
    while($stmt->fetch()){
      $suggestion = new Suggestion(array('id' => $sid, 'suggestion' => $ssuggestion, 'have_voted' => $havevoted));
      $str[] = $suggestion;
    }
    $stmt->close();
  } catch( Exception $e ){
    die( $e->getMessage() );
  }
  
  shuffle($str);
  foreach ($str as $string) {
    $result .= $string;
  }
  
  echo $callback . '(' . json_encode($result) . ');';
} 
else if($_GET['action'] == 'vote'){

  $data['v'] = (int)$_GET['vote'];
  $data['id'] = (int)$_GET['id'];
  
  if($data['v'] != -1 && $data['v'] != 1){
    exit;
  }

  // Checking to see whether such a suggest item id exists:
  $sql = "SELECT id FROM suggestions WHERE id = ?";
  try {
    $stmt = $dbo->prepare($sql);
    $stmt->bind_param('i', $data['id']);
    $stmt->execute();
    $stmt->store_result();
    if( !$stmt->num_rows ){
      exit;
    }
    $stmt->close();
  } catch( Exception $e ){
    die( $e->getMessage() );
  }

  // The id, ip and day fields are set as a primary key.
  // The query will fail if we try to insert a duplicate key,
  // which means that a visitor can vote only once per day.
  
  $sql = "INSERT INTO suggestions_votes (suggestion_id,ip,day,vote) VALUES (?, ?, CURRENT_DATE, ?)";
  $sql2 = "UPDATE suggestions SET 
      ".($data['v'] == 1 ? 'votes_up = votes_up + 1' : 'votes_down = votes_down + 1').",
      rating = rating + ?
    WHERE id = ?";
  try {
    $stmt = $dbo->prepare($sql);
    $stmt->bind_param('isi', $data['id'], $data['ip'], $data['v']);
    $stmt->execute();
    $stmt->close();
    $stmt = $dbo->prepare($sql2);
    $stmt->bind_param('ii', $data['v'], $data['id']);
    $stmt->execute();
    $stmt->close();
  } catch( Exception $e ){
    die( $e->getMessage() );
  }

}
else if($_GET['action'] == 'submit'){

  if(get_magic_quotes_gpc()){
    array_walk_recursive($_GET,create_function('&$v,$k','$v = stripslashes($v);'));
  }

  // Stripping the content  
  $_GET['content'] = htmlspecialchars(strip_tags($_GET['content']));
  
  if(mb_strlen($_GET['content'],'utf-8')<3){
    exit;
  }
  
  $sql = "INSERT INTO suggestions SET suggestion = ?, project_id = ?, culture = ?";
  try {
    $stmt = $dbo->prepare($sql);
    $stmt->bind_param('sss', $_GET['content'], $_GET['pid'], $_GET['culture']);
    $stmt->execute();
    $stmt->close();
  } catch( Exception $e ){
    die( $e->getMessage() );
  }
  
  // Outputting the HTML of the newly created suggestion in a JSON format.
  // We are using (string) to trigger the magic __toString() method of the object.
  
  echo $callback . '(' . json_encode(array(
    'html'  => (string)(new Suggestion(array(
      'id'      => $dbo->insert_id,
      'suggestion'  => $_GET['content']
    )))
  )) . ');';
}

?>