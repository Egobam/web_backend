<?php
session_start();

$dsn = 'mysql:host=localhost;dbname=u68860;charset=utf8';
$username = 'u68860';
$password = '8500150';
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

$errors = [];
$data = $_POST;

if (!preg_match("/^[а-яА-Яa-zA-Z\s]{1,150}$/u", trim($data['fio'] ?? ''))) {
    $errors['fio'] = "Допустимы только буквы и пробелы, длина до 150 символов";
}

if (!preg_match("/^(\+7|8)\d{10}$/", trim($data['phone'] ?? ''))) {
    $errors['phone'] = "Допустимы форматы: +7XXXXXXXXXX или 8XXXXXXXXXX";
}

if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", trim($data['email'] ?? ''))) {
    $errors['email'] = "Допустимы латинские буквы, цифры, ._%+- и корректный домен";
}

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data['birthdate'] ?? '') || strtotime($data['birthdate']) > time()) {
    $errors['birthdate'] = "Допустим формат ГГГГ-ММ-ДД, дата не позже текущей";
}

if (!in_array($data['gender'] ?? '', ['male', 'female'])) {
    $errors['gender'] = "Допустимы только значения: мужской или женский";
}

$valid_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
$languages = $data['languages'] ?? [];
if (empty($languages) || count(array_diff($languages, $valid_languages)) > 0) {
    $errors['languages'] = "Допустимы только языки из списка, выберите хотя бы один";
}

if (!preg_match("/^[\s\S]{1,1000}$/", trim($data['bio'] ?? ''))) {
    $errors['bio'] = "Допустимы любые символы, длина до 1000 символов";
}

if (!isset($data['contract']) || $data['contract'] !== 'yes') {
    $errors['contract'] = "Необходимо согласиться с контрактом";
}

if (!empty($errors)) {
    setcookie('form_errors', serialize($errors), 0, '/');
    setcookie('form_values', serialize($data), 0, '/');
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO users (fio, phone, email, birthdate, gender, bio, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([trim($data['fio']), trim($data['phone']), trim($data['email']), $data['birthdate'], $data['gender'], trim($data['bio']), 1]);
    $user_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
    $insert = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
    foreach ($languages as $language) {
        $stmt->execute([$language]);
        $lang_id = $stmt->fetchColumn();
        $insert->execute([$user_id, $lang_id]);
    }

    setcookie('form_values', serialize($data), time() + 365 * 24 * 60 * 60, '/');
    setcookie('form_errors', '', time() - 3600, '/');
    header('Location: index.php?success=1');
    exit;
} catch (PDOException $e) {
    die("Ошибка сохранения: " . $e->getMessage());
}
?>