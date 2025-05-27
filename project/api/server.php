<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Разрешить CORS для тестирования
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Token');

// Подключение к БД
$user = 'u68860';
$pass = '8500150';
try {
    $db = new PDO('mysql:host=localhost;dbname=u68860', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к базе данных']);
    exit;
}

// Получение метода и пути запроса
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Маршрутизация
if ($method === 'POST' && $uri === '/api/register') {
    require_once 'api/register.php';
} elseif ($method === 'PUT' && preg_match('/\/api\/user\/(\d+)/', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    require_once 'api/user.php';
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}