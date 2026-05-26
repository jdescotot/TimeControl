<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'config.php';

if (!es_gerente()) {
    header('Location: index.php');
    exit;
}

define('TC_GERENTE_VIEW', true);
require __DIR__ . '/dueño.php';
