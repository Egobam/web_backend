<?php
//файл соединения с базой данных mysql
$host   = 'localhost';
$dbname = 'u68687';
$user   = 'u68687';
$pass   = '8401584';
//Подключаемся к базе
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
?>