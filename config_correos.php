<?php
// Configuración de la base de datos para correos masivos
$host_correos = 'db5019247411.hosting-data.io'; 
$dbname_correos = 'dbs15099139'; // Cambia esto por el nombre de tu base de datos de correos
$username_correos = 'dbu5641171';
$password_correos = 'Hostur.1710';

try {
    $pdo_correos = new PDO("mysql:host=$host_correos;dbname=$dbname_correos;charset=utf8", $username_correos, $password_correos);
    $pdo_correos->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Conexión fallida a base de datos de correos: " . $e->getMessage());
}
?>
