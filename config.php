<?php
$servername = "127.0.0.1";
$port = 3310;
$username = "root";
$password = "";
$dbname = "leerlingbegeleiding";

try {
  $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  // Log de echte oorzaak voor diagnose; toon databasegegevens niet aan bezoekers.
  error_log('Databaseverbinding mislukt: ' . $e->getMessage());
}
?>
