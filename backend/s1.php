<?php

//As a test- getting the survey ids of all surveys created within a specific year


$hostname = 'localhost';
$username = 'peacockjs';
$password = 'placeholderpassword';

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');

$year = $_POST['year'];

//connection to the database
$dbhandle = mysql_connect($hostname, $username, $password) 
  or die("Unable to connect to MySQL");
echo "Connected to MySQL<br>";


//select a database to work with
$selected = mysql_select_db("limesurvey",$dbhandle) 
  or die("Could not select examples");




 //execute the SQL query and return records
$result = mysql_query("SELECT sid FROM limesurvey.lime_surveys where datecreated like '%$year%';");

$result = array();
//fetch tha data from the database
while ($row = mysql_fetch_array($result)) {
  array_push($result, "Survey ID:",$row{'submitdate'});
}
echo json_encode(result);

mysql_close($dbhandle);

?>