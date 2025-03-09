<?php
// Подключение к базе данных
$dsn = 'mysql:host=localhost;dbname=u68860;charset=utf8';
$username = 'u68860';
$password = '8500150';
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Валидация данных
$errors = [];
$fio = trim($_POST['fio']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$birthdate = $_POST['birthdate'];
$gender = $_POST['gender'];
$languages = $_POST['languages'];
$bio = trim($_POST['bio']);
$contract = isset($_POST['contract']) ? 1 : 0;

// Проверка ФИО
if (!preg_match("/^[а-яА-Яa-zA-Z\s]+$/u", $fio) || strlen($fio) > 150) {
    $errors[] = "ФИО должно содержать только буквы и пробелы, не длиннее 150 символов.";
}

// Проверка телефона (пример: +7 или 8 и 10 цифр)
if (!preg_match("/^(\+7|8)\d{10}$/", $phone)) {
    $errors[] = "Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX.";
}

// Проверка email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный email.";
}

// Проверка даты рождения
if (strtotime($birthdate) > time() || !$birthdate) {
    $errors[] = "Дата рождения некорректна.";
}

// Проверка пола
if (!in_array($gender, ['male', 'female'])) {
    $errors[] = "Выберите корректный пол.";
}

// Проверка языков
$valid_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
if (empty($languages) || count(array_diff($languages, $valid_languages)) > 0) {
    $errors[] = "Выберите хотя бы один корректный язык программирования.";
}

// Проверка биографии
if (empty($bio)) {
    $errors[] = "Биография не может быть пустой.";
}

// Проверка контракта
if (!$contract) {
    $errors[] = "Необходимо согласиться с контрактом.";
}

// Если есть ошибки, выводим их
if (!empty($errors)) {
    echo "<h2>Ошибки:</h2><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul><a href='index.php'>Вернуться к форме</a>";
    exit;
}

// Сохранение в базу данных
try {
    // Вставка в таблицу users
    $stmt = $pdo->prepare("INSERT INTO users (fio, phone, email, birthdate, gender, bio, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fio, $phone, $email, $birthdate, $gender, $bio, $contract]);
    $user_id = $pdo->lastInsertId();

    // Вставка языков
    $stmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
    $insert = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
    foreach ($languages as $language) {
        $stmt->execute([$language]);
        $lang_id = $stmt->fetchColumn();
        $insert->execute([$user_id, $lang_id]);
    }

    echo "<h2>Данные успешно сохранены!</h2><a href='index.php'>Вернуться к форме</a>";
} catch (PDOException $e) {
    die("Ошибка сохранения: " . $e->getMessage());
}
?>