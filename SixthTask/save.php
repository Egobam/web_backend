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

$action = $_GET['action'] ?? 'login';
$errors = [];
$values = $_POST;

if ($action === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        if (!in_array($role, ['user', 'admin'])) {
            $errors['role'] = 'Выберите корректную роль';
        } else {
            if ($role === 'user') {
                $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE login = ?");
                $stmt->execute([$login]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    unset($_SESSION['credentials']);
                    header('Location: index.php');
                    exit;
                } else {
                    $errors['auth'] = 'Неверный логин или пароль пользователя';
                }
            } elseif ($role === 'admin') {
                $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE login = ?");
                $stmt->execute([$login]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin && password_verify($password, $admin['password_hash'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    header('Location: index.php');
                    exit;
                } else {
                    $errors['auth'] = 'Неверный логин или пароль администратора';
                }
            }
        }
    }
} elseif ($action === 'register') {
    // Валидация данных формы
    if (!preg_match("/^[а-яА-Яa-zA-Z\s]{1,150}$/u", trim($values['fio'] ?? ''))) {
        $errors['fio'] = "Допустимы только буквы и пробелы, длина до 150 символов";
    }

    if (!preg_match("/^(\+7|8)\d{10}$/", trim($values['phone'] ?? ''))) {
        $errors['phone'] = "Допустимы форматы: +7XXXXXXXXXX или 8XXXXXXXXXX";
    }

    if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", trim($values['email'] ?? ''))) {
        $errors['email'] = "Допустимы латинские буквы, цифры, ._%+- и корректный домен";
    }

    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $values['birthdate'] ?? '') || strtotime($values['birthdate']) > time()) {
        $errors['birthdate'] = "Допустим формат ГГГГ-ММ-ДД, дата не позже текущей";
    }

    if (!in_array($values['gender'] ?? '', ['male', 'female'])) {
        $errors['gender'] = "Допустимы только значения: мужской или женский";
    }

    $valid_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
    $languages = $values['languages'] ?? [];
    if (empty($languages) || count(array_diff($languages, $valid_languages)) > 0) {
        $errors['languages'] = "Допустимы только языки из списка, выберите хотя бы один";
    }

    if (!preg_match("/^[\s\S]{1,1000}$/", trim($values['bio'] ?? ''))) {
        $errors['bio'] = "Допустимы любые символы, длина до 1000 символов";
    }

    if (!isset($values['contract']) || $values['contract'] !== 'yes') {
        $errors['contract'] = "Необходимо согласиться с контрактом";
    }

    if (empty($errors)) {
        try {
            // Генерация логина и пароля
            $login = 'user_' . bin2hex(random_bytes(4));
            $raw_password = bin2hex(random_bytes(8));
            $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
            
            // Сохранение пользователя
            $stmt = $pdo->prepare("INSERT INTO users (fio, phone, email, birthdate, gender, bio, contract, login, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([trim($values['fio']), trim($values['phone']), trim($values['email']), $values['birthdate'], $values['gender'], trim($values['bio']), 1, $login, $password_hash]);
            $user_id = $pdo->lastInsertId();
            
            // Сохранение языков
            $stmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $insert = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            foreach ($languages as $language) {
                $stmt->execute([$language]);
                $lang_id = $stmt->fetchColumn();
                $insert->execute([$user_id, $lang_id]);
            }
            
            // Сохранение учетных данных в сессии
            $_SESSION['credentials'] = ['login' => $login, 'password' => $raw_password];
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            die("Ошибка сохранения: " . $e->getMessage());
        }
    }
} elseif ($action === 'update' && isset($_SESSION['user_id'])) {
    // Валидация данных формы для обновления
    if (!preg_match("/^[а-яА-Яa-zA-Z\s]{1,150}$/u", trim($values['fio'] ?? ''))) {
        $errors['fio'] = "Допустимы только буквы и пробелы, длина до 150 символов";
    }

    if (!preg_match("/^(\+7|8)\d{10}$/", trim($values['phone'] ?? ''))) {
        $errors['phone'] = "Допустимы форматы: +7XXXXXXXXXX или 8XXXXXXXXXX";
    }

    if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", trim($values['email'] ?? ''))) {
        $errors['email'] = "Допустимы латинские буквы, цифры, ._%+- и корректный домен";
    }

    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $values['birthdate'] ?? '') || strtotime($values['birthdate']) > time()) {
        $errors['birthdate'] = "Допустим формат ГГГГ-ММ-ДД, дата не позже текущей";
    }

    if (!in_array($values['gender'] ?? '', ['male', 'female'])) {
        $errors['gender'] = "Допустимы только значения: мужской или женский";
    }

    $valid_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
    $languages = $values['languages'] ?? [];
    if (empty($languages) || count(array_diff($languages, $valid_languages)) > 0) {
        $errors['languages'] = "Допустимы только языки из списка, выберите хотя бы один";
    }

    if (!preg_match("/^[\s\S]{1,1000}$/", trim($values['bio'] ?? ''))) {
        $errors['bio'] = "Допустимы любые символы, длина до 1000 символов";
    }

    if (!isset($values['contract']) || $values['contract'] !== 'yes') {
        $errors['contract'] = "Необходимо согласиться с контрактом";
    }

    if (empty($errors)) {
        try {
            // Обновление данных пользователя
            $stmt = $pdo->prepare("UPDATE users SET fio = ?, phone = ?, email = ?, birthdate = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
            $stmt->execute([trim($values['fio']), trim($values['phone']), trim($values['email']), $values['birthdate'], $values['gender'], trim($values['bio']), 1, $_SESSION['user_id']]);
            
            // Обновление языков
            $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            $stmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $insert = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            foreach ($languages as $language) {
                $stmt->execute([$language]);
                $lang_id = $stmt->fetchColumn();
                $insert->execute([$_SESSION['user_id'], $lang_id]);
            }
            
            header('Location: index.php?success=1');
            exit;
        } catch (PDOException $e) {
            die("Ошибка сохранения: " . $e->getMessage());
        }
    }
} else {
    header('Location: index.php');
    exit;
}

// Сохранение ошибок и значений в сессии при наличии ошибок
$_SESSION['form_errors'] = $errors;
$_SESSION['form_values'] = $values;
header('Location: index.php');
exit;
?>