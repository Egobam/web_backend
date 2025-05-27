<?php
// Подключение к БД уже выполнено в server.php

// Валидация входных данных
function validateInput($data) {
    $errors = [];
    if (empty($data['fio']) || strlen($data['fio']) < 2) {
        $errors[] = 'Имя должно содержать минимум 2 символа';
    }
    if (empty($data['phone']) || !preg_match('/^\+?[0-9]{10,15}$/', $data['phone'])) {
        $errors[] = 'Некорректный номер телефона';
    }
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email';
    }
    if (empty($data['birthdate']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['birthdate'])) {
        $errors[] = 'Некорректная дата рождения (формат: ГГГГ-ММ-ДД)';
    }
    if (empty($data['gender']) || !in_array($data['gender'], ['male', 'female'])) {
        $errors[] = 'Пол должен быть "male" или "female"';
    }
    if (empty($data['contract']) || $data['contract'] !== true) {
        $errors[] = 'Необходимо согласие на обработку персональных данных';
    }
    if (empty($data['languages']) || !is_array($data['languages'])) {
        $errors[] = 'Выберите хотя бы один язык программирования';
    }
    return $errors;
}

// Генерация случайной строки для логина и пароля
function generateRandomString($length) {
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

// Получение данных из запроса
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fallback для стандартного POST
    $input = [
        'fio' => $_POST['fio'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'birthdate' => $_POST['birthdate'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'bio' => $_POST['bio'] ?? '',
        'contract' => isset($_POST['contract']),
        'languages' => $_POST['languages'] ?? []
    ];
}

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный JSON']);
    exit;
}

// Валидация
$errors = validateInput($input);
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['errors' => $errors]);
    exit;
}

// Проверка, не существует ли пользователь с таким email
$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$input['email']]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'Пользователь с таким email уже существует']);
    exit;
}

// Генерация логина, пароля и токена
$login = generateRandomString(8);
$password = generateRandomString(12);
$password_hash = password_hash($password, PASSWORD_BCRYPT);
$session_token = generateRandomString(32);

// Сохранение пользователя
$stmt = $db->prepare('INSERT INTO users (fio, phone, email, birthdate, gender, bio, contract, login, password_hash, session_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    $input['fio'],
    $input['phone'],
    $input['email'],
    $input['birthdate'],
    $input['gender'],
    $input['bio'] ?? '',
    $input['contract'] ? 1 : 0,
    $login,
    $password_hash,
    $session_token
]);
$user_id = $db->lastInsertId();

// Сохранение языков программирования
foreach ($input['languages'] as $language_id) {
    $stmt = $db->prepare('INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)');
    $stmt->execute([$user_id, $language_id]);
}

// Ответ
http_response_code(201);
echo json_encode([
    'login' => $login,
    'password' => $password,
    'profile_url' => "/profile/$user_id"
]);