<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$user = 'u68860'; 
$pass = '8500150'; 
$db = new PDO('mysql:host=localhost;dbname=u68860', $user, $pass,
    [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

session_start();

$response = [
    'success' => false,
    'message' => '',
    'info' => '',
    'errors' => [],
    'values' => []
];

$error = false;
$log = !empty($_SESSION['login']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Получение данных из JSON или POST
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        $input = $_POST;
    }

    $fio = trim($input['fio'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $email = trim($input['email'] ?? '');
    $birthdate = $input['birthdate'] ?? '';
    $gender = $input['gender'] ?? '';
    $languages = isset($input['languages']) ? (array)$input['languages'] : [];
    $bio = trim($input['bio'] ?? '');
    $contract = $input['contract'] ?? '';

    // Валидация
    if (empty($fio)) {
        $response['errors']['fio'] = 'Заполните поле';
        $error = true;
    } elseif (!preg_match('/^[а-яА-Яa-zA-Z\s]{1,150}$/u', $fio)) {
        $response['errors']['fio'] = 'Допустимы только буквы и пробелы, длина до 150 символов';
        $error = true;
    }

    if (empty($phone)) {
        $response['errors']['phone'] = 'Это поле пустое';
        $error = true;
    } elseif (!preg_match('/^(\+7|8)\d{10}$/', $phone)) {
        $response['errors']['phone'] = 'Допустимы форматы: +7XXXXXXXXXX или 8XXXXXXXXXX';
        $error = true;
    }

    if (empty($email)) {
        $response['errors']['email'] = 'Заполните поле';
        $error = true;
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $response['errors']['email'] = 'Допустимы латинские буквы, цифры, ._%+- и корректный домен';
        $error = true;
    }

    if (empty($birthdate)) {
        $response['errors']['birthdate'] = 'Заполните поле';
        $error = true;
    } elseif (strtotime('now') < strtotime($birthdate)) {
        $response['errors']['birthdate'] = 'Дата не может превышать нынешнюю';
        $error = true;
    }

    if (empty($gender) || !preg_match('/^(male|female)$/', $gender)) {
        $response['errors']['gender'] = 'Выберите пол';
        $error = true;
    }

    if (empty($bio)) {
        $response['errors']['bio'] = 'Заполните поле';
        $error = true;
    } elseif (strlen($bio) > 1000) {
        $response['errors']['bio'] = 'Максимальная длина: 1000 символов';
        $error = true;
    }

    if (empty($contract)) {
        $response['errors']['contract'] = 'Необходимо согласиться с обработкой персональных данных';
        $error = true;
    }

    if (empty($languages)) {
        $response['errors']['languages'] = 'Выберите язык программирования';
        $error = true;
    } else {
        try {
            $inQuery = implode(',', array_fill(0, count($languages), '?'));
            $dbLangs = $db->prepare("SELECT id, name FROM programming_languages WHERE id IN ($inQuery)");
            foreach ($languages as $key => $value) {
                $dbLangs->bindValue(($key + 1), $value);
            }
            $dbLangs->execute();
            $db_languages = $dbLangs->fetchAll(PDO::FETCH_ASSOC);
            
            if ($dbLangs->rowCount() != count($languages)) {
                $response['errors']['languages'] = 'Неверно выбраны языки';
                $error = true;
            }
        } catch (PDOException $e) {
            $response['errors']['database'] = 'Ошибка базы данных: ' . $e->getMessage();
            $error = true;
        }
    }

    if (!$error) {
        try {
            if ($log) {
                $user_id = $_SESSION['user_id'] ?? null;
            
                if (!$user_id) {
                    throw new Exception('Не удалось определить ID пользователя для обновления');
                }

                $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
                $checkStmt->execute([$user_id]);
                
                if (!$checkStmt->fetch()) {
                    throw new Exception('Запись не найдена или нет прав доступа');
                }

                $stmt = $db->prepare("UPDATE users SET fio = ?, phone = ?, email = ?, birthdate = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
                $updateResult = $stmt->execute([$fio, $phone, $email, $birthdate, $gender, $bio, 1, $user_id]);
                
                if (!$updateResult) {
                    throw new Exception('Ошибка при обновлении основных данных');
                }

                $deleteStmt = $db->prepare("DELETE FROM user_languages WHERE user_id = ?");
                $deleteResult = $deleteStmt->execute([$user_id]);
                
                if (!$deleteResult) {
                    throw new Exception('Ошибка при удалении старых языков');
                }

                $insertStmt = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
                foreach ($db_languages as $row) {
                    $insertResult = $insertStmt->execute([$user_id, $row['id']]);
                    if (!$insertResult) {
                        throw new Exception('Ошибка при добавлении языков программирования');
                    }
                }

                $response['success'] = true;
                $response['message'] = 'Данные успешно обновлены';
            } else {
                $login = uniqid();
                $pass = uniqid();
                $password_hash = password_hash($pass, PASSWORD_BCRYPT);
                
                $stmt = $db->prepare("INSERT INTO users (login, password_hash, fio, phone, email, birthdate, gender, bio, contract) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$login, $password_hash, $fio, $phone, $email, $ birthdate, $gender, $bio, 1]);
                $user_id = $db->lastInsertId();

                $stmt = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
                foreach ($db_languages as $row) {
                    $stmt->execute([$user_id, $row['id']]);
                }

                $response['info'] = sprintf(
                    'Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                    htmlspecialchars($login),
                    htmlspecialchars($pass)
                );
                $response['success'] = true;
                $response['message'] = 'Спасибо, результаты сохранены.';
                $response['login'] = $login;
                $response['password'] = $pass;
                $response['profile_url'] = "/profile/$user_id";
            }

            $response['values'] = [
                'fio' => $fio,
                'phone' => $phone,
                'email' => $email,
                'birthdate' => $birthdate,
                'gender' => $gender,
                'languages' => implode(',', $languages),
                'bio' => $bio,
                'contract' => $contract
            ];
            
        } catch (PDOException $e) {
            $response['errors']['database'] = 'Ошибка базы данных: ' . $e->getMessage();
            error_log('Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            $response['errors']['update'] = $e->getMessage();
            error_log('Update error: ' . $e->getMessage());
        }
    }
    echo json_encode($response);
    exit;
}
?>