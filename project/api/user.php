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
    if (empty($data['languages']) || !is_array($data['languages'])) {
        $errors[] = 'Выберите хотя бы один язык программирования';
    }
    return $errors;
}

// Получение данных из запроса
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный JSON']);
    exit;
}

// Проверка токена авторизации
$session_token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? '';
if (!$session_token) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

// Поиск пользователя
$user_id = $_GET['id'] ?? 0;
$stmt = $db->prepare('SELECT id FROM users WHERE id = ? AND session_token = ?');
$stmt->execute([$user_id, $session_token]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Недостаточно прав или неверный токен']);
    exit;
}

// Валидация
$errors = validateInput($input);
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['errors' => $errors]);
    exit;
}

// Обновление данных пользователя
$stmt = $db->prepare('UPDATE users SET fio = ?, phone = ?, email = ?, birthdate = ?, gender = ?, bio = ? WHERE id = ?');
$stmt->execute([
    $input['fio'],
    $input['phone'],
    $input['email'],
    $input['birthdate'],
    $input['gender'],
    $input['bio'] ?? '',
    $user_id
]);

// Обновление языков программирования
$db->prepare('DELETE FROM user_languages WHERE user_id = ?')->execute([$user_id]);
foreach ($input['languages'] as $language_id) {
    $stmt = $db->prepare('INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)');
    $stmt->execute([$user_id, $language_id]);
}

// Ответ
http_response_code(200);
echo json_encode(['message' => 'Данные успешно обновлены']);