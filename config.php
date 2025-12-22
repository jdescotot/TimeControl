<?php
$host = 'db5019247411.hosting-data.io'; 
$dbname = 'dbs15099139';              
$username = 'dbu5641171';               
$password = 'Hostur.1710';              

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Conexión fallida: " . $e->getMessage());
}
?>