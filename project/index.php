<?php
header('Content-Type: application/json');

session_start();

$user = 'u68918';
$password = '7758388';
try {
    $pdo = new PDO('mysql:host=localhost;dbname=u68918;charset=utf8', $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Ошибка подключения: ' . $e->getMessage(),
        'errors' => []
    ];
    echo json_encode($response);
    exit;
}

$response = [
    'success' => false,
    'message' => '',
    'info' => '',
    'errors' => [],
    'values' => []
];

$errors = [];
$data = $_POST;
$log = !empty($_SESSION['login']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['logout_form'])) {
        session_destroy();
        setcookie('login', '', time() - 3600);
        setcookie('pass', '', time() - 3600);
        $response['success'] = true;
        $response['message'] = 'Вы успешно вышли';
        echo json_encode($response);
        exit;
    }

    // Валидация ФИО
    if (!preg_match("/^[а-яА-Я\s]{1,150}$/u", trim($data['fio'] ?? ''))) {
        $errors['fio'] = "Допустимы только русские буквы и пробелы, длина до 150 символов";
    }

    // Валидация номера телефона
    if (!preg_match("/^(\+7|8)\d{10}$/", trim($data['number'] ?? ''))) {
        $errors['number'] = "Допустимы форматы: +7XXXXXXXXXX или 8XXXXXXXXXX";
    }

    // Валидация email
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", trim($data['email'] ?? ''))) {
        $errors['email'] = "Допустимы латинские буквы, цифры, ._%+- и корректный домен";
    }

    // Валидация даты рождения
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data['date'] ?? '') || strtotime($data['date']) > time()) {
        $errors['date'] = "Допустим формат ГГГГ-ММ-ДД, дата не позже текущей";
    }

    // Валидация пола
    if (!in_array($data['radio'] ?? '', ['M', 'W'])) {
        $errors['radio'] = "Допустимы только значения: M или W";
    }

    // Валидация языков программирования
    $valid_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
    $languages = isset($data['language']) ? (is_array($data['language']) ? $data['language'] : explode(',', $data['language'])) : [];
    if (empty($languages) || count(array_diff($languages, $valid_languages)) > 0) {
        $errors['language'] = "Допустимы только языки из списка, выберите хотя бы один";
    }

    // Валидация биографии
    if (!preg_match("/^[\s\S]{1,1000}$/", trim($data['bio'] ?? ''))) {
        $errors['bio'] = "Допустимы любые символы, длина до 1000 символов";
    }

    // Валидация согласия с контрактом
    if (!isset($data['check']) || $data['check'] !== 'yes') {
        $errors['check'] = "Необходимо согласиться с контрактом";
    }

    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['values'] = $data;
        echo json_encode($response);
        exit;
    }

    // Остальная логика обработки формы остается без изменений
    try {
        if ($log) {
            $form_id = $_POST['form_id'] ?? $_SESSION['form_id'] ?? null;
            if (!$form_id) {
                throw new Exception('Не удалось определить ID формы для обновления');
            }

            $checkStmt = $pdo->prepare("SELECT id FROM dannye WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$form_id, $_SESSION['user_id']]);
            
            if (!$checkStmt->fetch()) {
                throw new Exception('Запись не найдена или нет прав доступа');
            }

            $stmt = $pdo->prepare("UPDATE dannye SET fio = ?, number = ?, email = ?, dat = ?, radio = ?, bio = ? WHERE id = ?");
            $stmt->execute([trim($data['fio']), trim($data['number']), trim($data['email']), $data['date'], $data['radio'], trim($data['bio']), $form_id]);

            $deleteStmt = $pdo->prepare("DELETE FROM form_dannd_l WHERE id_form = ?");
            $deleteStmt->execute([$form_id]);

            $insertStmt = $pdo->prepare("INSERT INTO form_dannd_l(id_form, id_lang) VALUES (?, ?)");
            $langStmt = $pdo->prepare("SELECT id FROM all_languages WHERE name = ?");
            foreach ($languages as $language) {
                $langStmt->execute([$language]);
                $lang_id = $langStmt->fetchColumn();
                $insertStmt->execute([$form_id, $lang_id]);
            }

            $response['success'] = true;
            $response['message'] = 'Данные успешно обновлены';
        } else {
            $login = uniqid();
            $pass = uniqid();
            $mpass = md5($pass);
            
            $stmt = $pdo->prepare("INSERT INTO users (login, password) VALUES (?, ?)");
            $stmt->execute([$login, $mpass]);
            $user_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO dannye (user_id, fio, number, email, dat, radio, bio) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, trim($data['fio']), trim($data['number']), trim($data['email']), $data['date'], $data['radio'], trim($data['bio'])]);
            $fid = $pdo->lastInsertId();

            $stmt1 = $pdo->prepare("INSERT INTO form_dannd_l (id_form, id_lang) VALUES (?, ?)");
            $langStmt = $pdo->prepare("SELECT id FROM all_languages WHERE name = ?");
            foreach ($languages as $language) {
                $langStmt->execute([$language]);
                $lang_id = $langStmt->fetchColumn();
                $stmt1->execute([$fid, $lang_id]);
            }

            $response['info'] = sprintf(
                'Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong><br>и паролем <strong>%s</strong> для изменения данных.',
                htmlspecialchars($login),
                htmlspecialchars($pass)
            );
        }

        $response['success'] = true;
        $response['message'] = 'Спасибо, результаты сохранены.';
        $response['values'] = [
            'fio' => trim($data['fio']),
            'number' => trim($data['number']),
            'email' => trim($data['email']),
            'date' => $data['date'],
            'radio' => $data['radio'],
            'language' => implode(',', $languages),
            'bio' => trim($data['bio']),
            'check' => $data['check']
        ];
        
    } catch (PDOException $e) {
        $response['errors']['database'] = 'Ошибка базы данных: ' . $e->getMessage();
        error_log('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        $response['errors']['update'] = $e->getMessage();
        error_log('Update error: ' . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}