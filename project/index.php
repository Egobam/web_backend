<?php
header('Content-Type: application/json; charset=utf-8');

// Конфигурация базы данных (рекомендуется вынести в config.php)
$config = [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'u68918',
        'user' => 'u68918',
        'pass' => '7758388',
        'options' => [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    ]
];

try {
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8",
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage() . ' | File: ' . __FILE__ . ' | Line: ' . __LINE__);
    exit(json_encode(['success' => false, 'errors' => ['database' => 'Ошибка подключения к базе данных']]));
}

session_start();

// Инициализация ответа
$response = [
    'success' => false,
    'message' => '',
    'info' => '',
    'errors' => [],
    'values' => []
];

$error = false;
$log = !empty($_SESSION['login']);

// Функция валидации формы
function validateForm($data, $db, &$response, &$error) {
    $messages = [
        'fio_empty' => 'Пожалуйста, введите ваше ФИО',
        'fio_invalid' => 'ФИО должно содержать только русские буквы и пробелы, до 150 символов',
        'number_empty' => 'Пожалуйста, введите номер телефона',
        'number_invalid' => 'Формат номера: +7XXXXXXXXXX или 8XXXXXXXXXX',
        'email_empty' => 'Пожалуйста, введите адрес электронной почты',
        'email_invalid' => 'Введите корректный email, например, example@mail.ru',
        'date_empty' => 'Пожалуйста, выберите дату рождения',
        'date_invalid' => 'Дата должна быть в формате ГГГГ-ММ-ДД и не позднее текущей',
        'radio_invalid' => 'Выберите пол: мужской или женский',
        'language_empty' => 'Выберите хотя бы один язык программирования',
        'language_invalid' => 'Выбраны неверные языки программирования',
        'language_limit' => 'Можно выбрать не более 5 языков программирования',
        'bio_empty' => 'Пожалуйста, заполните поле биографии',
        'bio_invalid' => 'Биография должна содержать до 1000 символов',
        'check_invalid' => 'Необходимо согласиться с условиями контракта'
    ];

    // Кэширование списка языков
    $cacheFile = 'languages_cache.php';
    if (file_exists($cacheFile)) {
        $valid_languages = include $cacheFile;
    } else {
        $dbLangs = $db->query("SELECT name FROM all_languages")->fetchAll(PDO::FETCH_COLUMN);
        file_put_contents($cacheFile, '<?php return ' . var_export($dbLangs, true) . ';');
        $valid_languages = $dbLangs;
    }

    // ФИО
    if (empty($data['fio'])) {
        $response['errors']['fio'] = $messages['fio_empty'];
        $error = true;
    } elseif (!preg_match("/^[а-яА-Я\s]{1,150}$/u", trim($data['fio']))) {
        $response['errors']['fio'] = $messages['fio_invalid'];
        $error = true;
    }

    // Номер телефона
    if (empty($data['number'])) {
        $response['errors']['number'] = $messages['number_empty'];
        $error = true;
    } elseif (!preg_match("/^(\+7|8)\d{10}$/", trim($data['number']))) {
        $response['errors']['number'] = $messages['number_invalid'];
        $error = true;
    }

    // Email
    if (empty($data['email'])) {
        $response['errors']['email'] = $messages['email_empty'];
        $error = true;
    } elseif (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", trim($data['email']))) {
        $response['errors']['email'] = $messages['email_invalid'];
        $error = true;
    }

    // Дата рождения
    if (empty($data['date'])) {
        $response['errors']['date'] = $messages['date_empty'];
        $error = true;
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data['date']) || strtotime($data['date']) > time()) {
        $response['errors']['date'] = $messages['date_invalid'];
        $error = true;
    }

    // Пол
    if (empty($data['radio']) || !in_array($data['radio'], ['M', 'W'])) {
        $response['errors']['radio'] = $messages['radio_invalid'];
        $error = true;
    }

    // Языки программирования
    $language = $data['language'] ?? [];
    if (empty($language)) {
        $response['errors']['language'] = $messages['language_empty'];
        $error = true;
    } elseif (count($language) > 5) {
        $response['errors']['language'] = $messages['language_limit'];
        $error = true;
    } elseif (count(array_diff($language, $valid_languages)) > 0) {
        $response['errors']['language'] = $messages['language_invalid'];
        $error = true;
    } else {
        try {
            $placeholders = str_repeat('?,', count($language) - 1) . '?';
            $dbLangs = $db->prepare("SELECT id, name FROM all_languages WHERE name IN ($placeholders)");
            foreach ($language as $key => $value) {
                $dbLangs->bindValue(($key + 1), $value);
            }
            $dbLangs->execute();
            $languages = $dbLangs->fetchAll(PDO::FETCH_ASSOC);
            
            if ($dbLangs->rowCount() != count($language)) {
                $response['errors']['language'] = $messages['language_invalid'];
                $error = true;
            }
        } catch (PDOException $e) {
            $response['errors']['database'] = 'Ошибка базы данных: ' . $e->getMessage();
            $error = true;
        }
    }

    // Биография
    if (empty($data['bio'])) {
        $response['errors']['bio'] = $messages['bio_empty'];
        $error = true;
    } elseif (!preg_match("/^[\s\S]{1,1000}$/", trim($data['bio']))) {
        $response['errors']['bio'] = $messages['bio_invalid'];
        $error = true;
    }

    // Согласие с контрактом
    if (empty($data['check']) || $data['check'] !== 'yes') {
        $response['errors']['check'] = $messages['check_invalid'];
        $error = true;
    }

    return [
        'fio' => $data['fio'],
        'number' => $data['number'],
        'email' => $data['email'],
        'date' => $data['date'],
        'radio' => $data['radio'],
        'language' => implode(',', $language),
        'bio' => $data['bio'],
        'check' => $data['check']
    ];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Обработка выхода из системы
    if (isset($_POST['logout_form'])) {
        session_destroy();
        setcookie('login', '', time() - 3600);
        setcookie('pass', '', time() - 3600);
        $response['success'] = true;
        $response['message'] = 'Вы успешно вышли';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Получение данных формы
    $data = [
        'fio' => $_POST['fio'] ?? '',
        'number' => $_POST['number'] ?? '',
        'email' => $_POST['email'] ?? '',
        'date' => $_POST['date'] ?? '',
        'radio' => $_POST['radio'] ?? '',
        'language' => isset($_POST['language']) ? explode(',', $_POST['language']) : [],
        'bio' => $_POST['bio'] ?? '',
        'check' => $_POST['check'] ?? ''
    ];

    // Валидация формы
    $response['values'] = validateForm($data, $db, $response, $error);

    // Если есть ошибки, возвращаем их
    if ($error) {
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Обработка данных в БД
    try {
        $db->beginTransaction();

        if ($log) {
            // Обновление данных авторизованного пользователя
            $form_id = $_POST['form_id'] ?? $_SESSION['form_id'] ?? null;
            
            if (!$form_id) {
                throw new Exception('Не удалось определить ID формы для обновления');
            }

            $checkStmt = $db->prepare("SELECT id FROM dannye WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$form_id, $_SESSION['user_id']]);
            
            if (!$checkStmt->fetch()) {
                throw new Exception('Запись не найдена или нет прав доступа');
            }

            $stmt = $db->prepare("UPDATE dannye SET fio = ?, number = ?, email = ?, dat = ?, radio = ?, bio = ? WHERE id = ?");
            $updateResult = $stmt->execute([$data['fio'], $data['number'], $data['email'], $data['date'], $data['radio'], $data['bio'], $form_id]);
            
            if (!$updateResult) {
                throw new Exception('Ошибка при обновлении основных данных');
            }

            $deleteStmt = $db->prepare("DELETE FROM form_dannd_l WHERE id_form = ?");
            $deleteResult = $deleteStmt->execute([$form_id]);
            
            if (!$deleteResult) {
                throw new Exception('Ошибка при удалении старых языков');
            }

            $insertStmt = $db->prepare("INSERT INTO form_dannd_l(id_form, id_lang) VALUES (?, ?)");
            foreach ($languages as $row) {
                $insertResult = $insertStmt->execute([$form_id, $row['id']]);
                if (!$insertResult) {
                    throw new Exception('Ошибка при добавлении языков программирования');
                }
            }

            $response['success'] = true;
            $response['message'] = 'Данные успешно обновлены';
        } else {
            // Создание нового пользователя
            $login = uniqid();
            $pass = uniqid();
            $mpass = password_hash($pass, PASSWORD_BCRYPT);
            
            $stmt = $db->prepare("INSERT INTO users (login, password) VALUES (?, ?)");
            $stmt->execute([$login, $mpass]);
            $user_id = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO dannye (user_id, fio, number, email, dat, radio, bio) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $data['fio'], $data['number'], $data['email'], $data['date'], $data['radio'], $data['bio']]);
            $fid = $db->lastInsertId();

            $stmt1 = $db->prepare("INSERT INTO form_dannd_l (id_form, id_lang) VALUES (?, ?)");
            foreach ($languages as $row) {
                $stmt1->execute([$fid, $row['id']]);
            }

            $response['info'] = sprintf(
                'Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong><br>и паролем <strong>%s</strong> для изменения данных.',
                htmlspecialchars($login),
                htmlspecialchars($pass)
            );
        }

        $response['success'] = true;
        $response['message'] = 'Спасибо, результаты сохранены.';
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        $response['errors']['database'] = 'Ошибка базы данных: ' . $e->getMessage();
        error_log('Database error: ' . $e->getMessage() . ' | File: ' . __FILE__ . ' | Line: ' . __LINE__);
    } catch (Exception $e) {
        $db->rollBack();
        $response['errors']['update'] = $e->getMessage();
        error_log('Update error: ' . $e->getMessage() . ' | File: ' . __FILE__ . ' | Line: ' . __LINE__);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
?>