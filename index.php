<?php

require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// load config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initiate app
$configs =  [
	'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

//example LINE request message
/*
{
  "events": [
      {
        "replyToken": "nHuyWiB7yP5Zw52FIkcQobQuGDXCTA",
        "type": "message",
        "timestamp": 1462629479859,
        "source": {
             "type": "user",
             "userId": "U206d25c2ea6bd87c17655609a1c37cb8"
         },
         "message": {
             "id": "325708",
             "type": "text",
             "text": "Hello, world"
          }
      }
  ]
}
//grup table (id, group, groupid, state, poin, firstdate, lastdate)

*/


/* ROUTES */
$app->get('/', function ($request, $response) {
	return "Lanjutkan!";
});
$app->get('/test', function ($request, $response) {
	//mysqlconnect
	$servername = "localhost";
	$username = "herryfau_bot";
	$password = "HFR_78itb";
	$dbname = "herryfau_linebot";

		 
	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);

	// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	} 
	$namagrup = 'choly';
	$groupid = 'ffhfhghkjkjkjkjkjkkkljkjkljkjlkj';
	//query SQL
	$sql = "INSERT INTO `grup` ( `group`, `groupid`, `state`, `poin` ) VALUES ( '".$namagrup."', '".$groupid."', 1, 0);";
	$result = $conn->query($sql);
	if (!$result) {
		$text="gagal buat nama group".$namagrup."group id= ".$groupid."karena: ".mysqli_error($conn)." ";
	} else {
		$text= "Grup kamu telah ditambahkan";
	}
	$conn->close();
	return $text;
});


$app->post('/', function ($request, $response)
{
	// get request body and line signature header
	$body 	   = file_get_contents('php://input');
	$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

	// log body and signature
	file_put_contents('php://stderr', 'Body: '.$body);

	// is LINE_SIGNATURE exists in request header?
	if (empty($signature)){
		return $response->withStatus(400, 'Signature not set');
	}

	// is this request comes from LINE?
	if($_ENV['PASS_SIGNATURE'] == false && ! SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature)){
		return $response->withStatus(400, 'Invalid signature');
	}

	// init bot
	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

	$data = json_decode($body, true);

	//mysqlconnect
	$servername = "localhost";
	$username = "herryfau_bot";
	$password = "HFR_78itb";
	$dbname = "herryfau_linebot";
	foreach ($data['events'] as $event)
	{
		if ($event['type'] == 'message')
		{
			if($event['message']['text'] == '/next')
			{
				// Create connection
				$conn = new mysqli($servername, $username, $password, $dbname);
				$groupid = $event['source']['groupId'];
				// Check connection
				if ($conn->connect_error) {
				    die("Connection failed: " . $conn->connect_error);
				} 
				$sql = "SELECT id,tanya,level,poin FROM quest WHERE id=(SELECT state FROM grup WHERE groupid= '$groupid' );";
				$result = $conn->query($sql);
			
				if ($result->num_rows > 0) {
					// output data of each row
						while( $row = mysqli_fetch_array ($result)) {
							$text= "Nomor:".$row["id"]." Level:".$row["level"]." dengan Poin:".$row["poin"]."- Pertanyaan: " . $row["tanya"];
						}
				} else {
						  $text= "0 results";
				}
				$conn->close();
				// send same message as reply to user
				$result = $bot->replyText($event['replyToken'], $text);

				// or we can use pushMessage() instead to send reply message
				 // $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event['message']['text']);
				 // $result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				
				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
			} else if(substr($event['message']['text'], 0, 5) == '/grup'){
				if($event['source']['type'] == 'group'){
					// Create connection
					$conn = new mysqli($servername, $username, $password, $dbname);
				
					// Check connection
					if ($conn->connect_error) {
					    die("Connection failed: " . $conn->connect_error);
					} 
					$namagrup = substr($event['message']['text'], 6);
					$groupid = $event['source']['groupId'];
					//query SQL
					$sql = "INSERT INTO `grup` ( `group`, `groupid`, `state`, `poin` ) VALUES ( '".$namagrup."', '".$groupid."', 1, 0);";
					$result = $conn->query($sql);
					if (!$result) {
						$text="gagal buat nama group".$namagrup."group id= ".$groupid;
					} else {
						$text= "Grup kamu telah ditambahkan";
					}
				
					$conn->close();
					// send same message as reply to user
					$result = $bot->replyText($event['replyToken'], $text);
					
					return $result->getHTTPStatus() . ' ' . $result->getRawBody();
				}else {
					$result = $bot->replyText($event['replyToken'], ' Permainan grup hanya dapat dimainkan di grup');
					return $result->getHTTPStatus() . ' ' . $result->getRawBody();
				}
				
			} else{
				//quest (id, tanya, jawab, level, poin, komentar)
				// Create connection
				$conn = new mysqli($servername, $username, $password, $dbname);
				$groupid = $event['source']['groupId'];
				// Check connection
				if ($conn->connect_error) {
				    die("Connection failed: " . $conn->connect_error);
				} 
				$sql = "SELECT jawab, komentar, poin FROM `quest` WHERE id=(SELECT state FROM grup WHERE groupid= '".$groupid."' );";
				$result = $conn->query($sql);
				$answer="0";
				if ($result->num_rows > 0) {
					// output data of each row
					while($row = $result->fetch_assoc()) {
						$answer = $row['jawab'];
						$komentar = $row['komentar'];
						$poin = $row['poin'];
					}
				} else {
						  $text= "akses tabel quest error".mysqli_error($conn)." ";
				}
				if($event['message']['text'] == $answer){
					
					$sql = "SELECT state, poin FROM grup WHERE id= '".$groupid."';";
					$result = $conn->query($sql);
					if ($result->num_rows > 0) {
					// output data of each row
						while($row = $result->fetch_assoc()) {
							$state = $row["state"] + 1;
							$poingrup = $row["poin"] + $poin;
						}
						$sql = "UPDATE grup SET state='".$state."', poin='".$poingrup."' WHERE id='".$groupid."';";
						$result = $conn->query($sql);
						$text = $komentar.mysqli_error($conn);
					} else {
						$text= "add poin grup error".mysqli_error($conn)." ";
					}
					
					$result = $bot->replyText($event['replyToken'], $text);
					return $result->getHTTPStatus() . ' ' . $result->getRawBody();
				}
				$conn->close();
				// send same message as reply to user
				$result = $bot->replyText($event['replyToken'], $text);
				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
			}
		}
	}

});

$app->get('/push/{to}/{message}', function ($request, $response, $args)
{
	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

	$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($args['message']);
	$result = $bot->pushMessage($args['to'], $textMessageBuilder);

	return $result->getHTTPStatus() . ' ' . $result->getRawBody();
});

/* JUST RUN IT */
$app->run();