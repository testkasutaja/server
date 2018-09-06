<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

//Selles failis on andmebaaside andmed.
require "config.php";


//connectDB saab luua ühendusi andmebaasidega.
//Funktsioonile peab kaasa andma andmebaasi IP, andmebaasi kasutaja kasutajanime, parooli, andmebaasi nime. 
//Funktsioon väljastab ühenduse muutujana.
function connectDB($ip,$username,$password,$database)
{
	$connection = mysqli_connect($ip, $username, $password, $database);
	if (!$connection) {
    		die("Connection failed: " . mysqli_connect_error());
	}
	return $connection;
}

//isUnique kontrollib kas sisestatud kasutajanimi on unikaalne või on andmebaasis juba olemas.
//Funktsioonile peab kaasa andma kontrollitava kasutajanimi ja andmebaasi millest kontrollida.
function isUnique($username, $connection)
{
	$sql = "SELECT username FROM USERS WHERE username = '$username'";
	$usern = $connection->query($sql);
	if($usern->num_rows < 1)
	{
		return TRUE;
	} else {
		return FALSE;
	} 
}

//checkPassword kontrollib kas kasutatud parool vastab sellele paroolile mis on andmebaasis.
//Funktsioonile peab kaasa andma kasutajanime, parooli ja andmebaasi millest kontrollida.
function checkPassword($username, $password, $connection)
{
	$sql = "SELECT password FROM USERS WHERE username = '$username'";
	$result = $connection->query($sql);
	if($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc())
	       	{
			$passwd = $row["password"]; 
		}
		if($passwd === $password)
		{
			return TRUE;
		} else {
			return FALSE;
		}
	}
}

//createUser loob kasutaja.
//Funktsioonile peab kaasa andma kasutaja nime, parooli, 2 andmebaasi kuhu kasutaja luua.
function createUser($username,$password,$connection,$connection1)
{
	$sql = "INSERT INTO USERS (username, password) VALUES ('$username','$password')";	
	if ($connection->query($sql) === TRUE) {
		$connection1->query($sql);
	} else {
		echo "Error: " . $sql . "<br>" . $connection->error;
	}
	echo "user has been created<br>";
}

//setLastLogin seab andmebaasis viimase aja millal kasutajat nähti, seda muudetakse iga kord kui kasutaja sisse logib.
//Funktsioonile peab kaasa andma kasutajanime ja 2 andmebaasi kus uuendada millal kasutajat viimati nähti.
function setLastLogin($username,$connection,$connection1)
{
	$sql = "UPDATE USERS SET last_login = now() WHERE username = '$username'";

	if(countActiveUsers($connection) < 10){	
		if($connection->query($sql) === TRUE) {
			$connection1->query($sql); 
		} else {
			echo "Error: " . $sql . "<br>" . $connection->error;
		}	
	} else {
		echo "maximum amount of users has been reached<br>";
	}
}

//countActiveUsers loendab kasutajaid kes on viimase 10 minuti jooksul sisse loginud.
//Funktsioonile peab kaasa andma andmebaasi mille pealt loendada kasutajaid mis on sisse loginud viimase 10 minuti jooksul.
function countActiveUsers($connection)
{
	$sql = "SELECT username FROM USERS WHERE last_login >= NOW() - INTERVAL 10 MINUTE";
	$result = $connection->query($sql);
	$activeCount = $result->num_rows; 
	return $activeCount;	
}


$connection1 = connectDB($config["db1"]["ip"],$config["db1"]["username"],$config["db1"]["password"],$config["db1"]["db"]);
$connection2 = connectDB($config["db2"]["ip"],$config["db2"]["username"],$config["db2"]["password"],$config["db2"]["db"]);

if(isUnique($_POST["name"],$connection1) === TRUE){
	createUser($_POST["name"],$_POST["password"],$connection1,$connection2);
	setLastLogin($_POST["name"],$connection1,$connection2);	

} elseif(checkPassword($_POST["name"],$_POST["password"],$connection1) === TRUE){
	setLastLogin($_POST["name"],$connection1,$connection2);
	echo "You are logged in!";

} else {
	echo "You have entered the wrong password.";
}
?>

