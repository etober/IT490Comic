#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
function loginUser($username, $pass)
{
	//set up database
	$host = 'localhost';
	$user = 'admin';
	$pw = 'password';
	$db = 'testdb';
	$mysqli = new mysqli($host, $user, $pw, $db);
	$userData = array();
	$un = $mysqli->escape_string($username);
	$pass = $mysqli->escape_string($pass);
	#hash that shit
	$pass= hash('sha256', $pass);
        $query = "select * from users where userName = '$un' and password = '$pass'";
        $response = $mysqli->query($query);
        while ($row = $response->fetch_assoc())
        {
                echo "checking password for $username".PHP_EOL;
                if ($row["password"] == $pass)
                {
			echo "passwords match for $username".PHP_EOL;
			$userData['username']=$row['userName'];
			$userData['firstName']=$row['firstName'];
			$userData['lastName']=$row['lastName'];
			$userData['email'] = $row['email'];
			#UPDATE USER SESSION
			$sessionID = updateSession($row['userName']);
			$userData['sessionID']=$sessionID;
			#GET POINTS
			$query= "SELECT * from PointTable where userName='$un'";
			$response = $mysqli->query($query);
			while ($row1= $response->fetch_assoc())
			{
				$userData['points'] = $row1['totalPoints'];
			}
			#GET TOKENS
			$query= "SELECT * FROM TokenTable where userName='$un'";
			$response = $mysqli->query($query);
                        while ($row2= $response->fetch_assoc())
                        {
                                $userData['points'] = $row2['availTokens'];
                        }
			echo json_encode ($userData);
			return json_encode($userData);
                }
                echo "passwords did not match for $username".PHP_EOL;
        }
        return false;//no users matched username

}
function regUser($username, $pass, $email, $firstN, $lastN)
{
	//set up database
        $host = 'localhost';
        $user = 'admin';
        $pw = 'password';
        $db = 'testdb';
        $mysqli = new mysqli($host, $user, $pw, $db);
        $userData = array();
        $un = $mysqli->escape_string($username);
	$pass = $mysqli->escape_string($pass);
	#hash that shit
	$pass = hash('sha256',$pass);
	$email= $mysqli->escape_string($email);
	$firstName = $mysqli->escape_string($firstN);
	$lastName = $mysqli->escape_string($lastN);
        $query = "select * from users where userName = '$un'";
	$response = $mysqli->query($query);
	if( $response->num_rows == 0)#account doesn't exist already, createone
	{
		$query="INSERT INTO users values('$email','$pass', '$firstName', '$lastName', '$un')";
		$mysqli->query($query) or die($mysqli->error);
		echo "Account created successfully";
		echo "passwords match for $username".PHP_EOL;
		$userData['username']=$un;
		$userData['firstName']=$firstName;
		$userData['lastName']=$lastName;
		$userData['email'] = $email;
		$sessionID= createSession($un);
		$userData['sessionID']=$sessionID;
		#insert tokens here 
		$userData['tokens']= 1000;
		$userData['points'] = 0;
		$query="INSERT INTO PointTable values('$un', 0, '')";
		$mysqli->query($query) or die($mysqli->error);
		$query="INSERT INTO TokenTable values ('$un', 1000)";
		$mysqli->query($query) or die($mysqli->error);

                echo json_encode ($userData);
                return json_encode($userData);
	}
	else #account already exists
	{
		return false;

	}
}
function createSession($username)
{
	//set up database
        $host = 'localhost';
        $user = 'admin';
        $pw = 'password';
        $db = 'testdb';
	$mysqli = new mysqli($host, $user, $pw, $db);
	$sDate = time();
	$sessionKey= hash('sha256', $username.$sDate);
	$query = "insert into sessionTable values('$username','$sessionKey',$sDate, 'true')";
        $mysqli->query($query);
        return $sessionKey;

}
function updateSession($username)
{
	//set up database
        $host = 'localhost';
        $user = 'admin';
        $pw = 'password';
        $db = 'testdb';
	$mysqli = new mysqli($host, $user, $pw, $db);
	$sDate = time();
	$sessionKey = hash('sha256',$username.$sDate);
	$query = "update sessionTable set isValid='true', sessionKey = '$sessionKey', sessDate= '$sDate' where userName='$username'";
	$mysqli->query($query);
	return $sessionKey;
}
function dailyMatchup()
{
	//set up database
        $host = 'localhost';
        $user = 'admin';
        $pw = 'password';
        $db = 'testdb';
	$mysqli = new mysqli($host, $user, $pw, $db);
	#get general info from charTable
	$idQuery= "SELECT * from MatchupTable  where  publisher = 'DC Comics'";
	$DCHeroes = array();
	$results= $mysqli->query($idQuery) or die($mysqli->error);
	while ($char = $results->fetch_assoc())
	{
		$charID = $char['charID'];
		$heroQuery = "SELECT * from CharacterTable where charID = $charID";
		$powerQuery= "SELECT * from charPowerTable where charID = $charID";
		$powersArray =  array();
		$presult = $mysqli->query($powerQuery) or die($mysqli->error);
		if( $presult->num_rows == 0)
		{
			#no powers ):	
		}
		else
		{
			while($power = $presult->fetch_assoc())
			{
				$powerID = $power['powerID'];
				$descQuery = "SELECT * from PowerTable where powerID = $powerID";
				$dresult= $mysqli->query($descQuery) or die($mysqli->error);
				$wrapped= $dresult->fetch_assoc();
				array_push($powersArray, $wrapped);				
			}
		}
		$hresult = $mysqli->query($heroQuery) or die($mysqli->error);
		while($h  = $hresult->fetch_assoc())
		{
		
			$hero = new hero();
			$hero->charName=$h['charName'];
			$hero->imgURL=$h['imgURL'];
			$hero->powers= $powersArray;
			#$jHero = json_encode(var_dump($hero));
			array_push($DCHeroes,$hero); 
		}
	}
	$idQuery= "SELECT * from MatchupTable where publisher = 'Marvel'";
        $MarvelHeroes = array();
        $results= $mysqli->query($idQuery) or die($mysqli->error);
        while($char = $results->fetch_assoc())
        {
		$charID = $char['charID'];
		echo $charID;
                $heroQuery = "SELECT * from CharacterTable where charID = $charID";
                $powerQuery= "SELECT * from charPowerTable where charID = $charID";
                $powersArray = array();
                $presult = $mysqli->query($powerQuery) or die($mysqli->error);
                if( $presult->num_rows == 0)
                {
                        #no powers ):
                }
                else
                {
                        foreach($presult as $power)
                        {
                                $powerID = $power['powerID'];
                                $descQuery = "SELECT * from PowerTable where powerID = $powerID";
				$dresult= $mysqli->query($descQuery) or die($mysqli->error);
				$d= $dresult->fetch_assoc();
				$p = $d['powerDesc'];
                                array_push($powersArray, $p);
                        }
                }
                $hresult = $mysqli->query($heroQuery) or die($mysqli->error);
		$hero = new hero();
		$h= $hresult->fetch_assoc();
                $hero->charName=$h['charName'];
                $hero->imgURL=$h['imgURL'];
		$hero->powers= $powersArray;
		#$jHero = json_encode($hero);
                array_push($MarvelHeroes,$hero);
	}
	$matchUp = new matchup();
	$matchUp->DC= $DCHeroes;
	$matchUp->Marvel= $MarvelHeroes;
	echo json_encode ($matchUp);
	$sMatchup= json_encode($matchUp);
	#echo json_decode ($sMatchup);
	return json_encode($matchUp);

}
function authUser($userName,$sessionID)
{
	 //set up database
        $host = 'localhost';
        $user = 'admin';
        $pw = 'password';
        $db = 'testdb';
	$mysqli = new mysqli($host, $user, $pw, $db);
	if($userName==NULL)
	{
		return false;
	}
	if($sessionID ==NULL)
	{
		return false;
	}
	$statement = "select * from sessionTable where userName = '$userName'";
        $response = $mysqli->query($statement);
        while ($row = $response->fetch_assoc())
        {
                if ($row["sessionKey"] == $sessionID)
                {
                        echo "sessionID match for $userName".PHP_EOL;
                        $userData['username']=$userName;
                        $userData['sessionID']=$sessionID;
                        echo json_encode ($userData);
                        return json_encode($userData);
		}
		else
		{
			echo "sessionID did not match".PHP_EOL;
			return false;
		}
	}

}
function vote($userName, $vote)
{
	$host = 'localhost';
        $user = 'admin';
        $pw = 'password';
        $db = 'testdb';
        $mysqli = new mysqli($host, $user, $pw, $db);
	#check if voted already
	$statement = "select * from PointTable where userName = '$userName'";
        $response = $mysqli->query($statement);
        while ($row = $response->fetch_assoc())
        {
                if ($row["vote"] == "")
                {
                        echo "user not voted, checking tokens".PHP_EOL;
			$query="select * from TokenTable where availTokens>=100";
			$response= $mysqli->query($query);
			while ($row1= $response->fetch_assoc())
			{
				if($row1["userName"]==$userName)
				{
					echo "enough tokens, submitting vote".PHP_EOL;
					$query="update TokenTable set availTokens= availTokens-100 where userName= '$userName'";
					$mysqli->query($query);
					$query="update PointTable set vote='$vote' where userName='$userName'";
					$mysqli->query($query);
					echo "submitted vote".PHP_EOL;
					$query="update jackpot set totalTokens=totalTokens+100";
					$mysqli->query($query);
					echo "added to jackpot";
					return true;
				}
				else
				{
					echo "not enough tokens".PHP_EOL;
					return false;
				}
			}
                }
                else
                {
                        echo "user already voted, updating vote".PHP_EOL;
			$query = "update PointTable set vote='$vote' where userName = '$userName'";
			$mysqli->query($query);
			return true;
                }
        }
	#check if voted
	#check Tokens in TokenTable (availTokens)
	#remove 100 tokens in TokenTable
	#update (vote) in PointTable where userName = userName
	#update jackpot update totalTokens +=100
	
}

class hero
{
	public $charName;
	public $imgURL;
	public $powers;
	
}
class matchup
{
	public $DC;
	public $Marvel;
	function _construct(){}
}


function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "login":
	    return loginUser($request['username'],$request['password']);
    case "register":
	    return regUser($request['username'], $request['password'], $request['email'], $request['firstName'], $request['lastName']);
    case "dailyMatchup":
	    return dailyMatchup();
    case "weeklyMatchup":
	    return weeklyMatchup();
    case "validate":
	    return authUser($request['username'],$request['sessionID']);
    case "vote":
	    return vote($request['username'], $request['vote']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");
echo "login BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "login END".PHP.EOL;
exit();
?>

