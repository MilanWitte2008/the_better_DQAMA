<?php
$servername = "localhost";
$port = 3306;
$username = "root";
$password = "";
$dbname = "leerlingbegeleiding";

try {
  $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
}
?>
