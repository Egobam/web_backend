<?php
header('Content-Type: application/json');

$user = 'u68918'; 
$pass = '7758388'; 
$db = new PDO('mysql:host=localhost;dbname=u68918', $user, $pass,
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
   
    if (isset($_POST['logout_form'])) {
        session_destroy();
        setcookie('login', '', time() - 3600);
        setcookie('pass', '', time() - 3600);
        $response['success'] = true;
        $response['message'] = 'Вы успешно вышли';
        echo json_encode($response);
        exit;
    }

    $fio = $_POST['fio'] ?? '';
    $number = $_POST['number'] ?? '';
    $email = $_POST['email'] ?? '';
    $date = $_POST['date'] ?? '';
    $radio = $_POST['radio'] ?? '';
    $language = isset($_POST['language']) ? explode(',', $_POST['language']) : [];
    $bio = $_POST['bio'] ?? '';
    $check = $_POST['check'] ?? '';

    if (empty($fio)) {
        $response['errors']['fio'] = 'Заполните поле';
        $error = true;
    } elseif (!preg_match('/^([а-яё]+-?[а-яё]+)( [а-яё]+-?[а-яё]+){1,2}$/Diu', $fio)) {
        $response['errors']['fio'] = 'Допустимы только русские буквы, формат: Имя Фамилия';
        $error = true;
    }

    if (empty($number)) {
        $response['errors']['number'] = 'Это поле пустое';
        $error = true;
    } elseif (strlen($number) != 11) {
        $response['errors']['number'] = 'Поле должно содержать 11 цифр';
        $error = true;
    } elseif ($number != preg_replace('/\D/', '', $number)) {
        $response['errors']['number'] = 'Другие символы, кроме цифр, не допускаются';
        $error = true;
    }

    if (empty($email)) {
        $response['errors']['email'] = 'Заполните поле';
        $error = true;
    } elseif (!preg_match('/^\w+([.-]?\w+)@\w+([.-]?\w+)(.\w{2,3})+$/', $email)) {
        $response['errors']['email'] = 'Пожалуйста, введите почту по образцу: example@mail.ru';
        $error = true;
    }

    if (empty($date)) {
        $response['errors']['date'] = 'Заполните поле';
        $error = true;
    } elseif (strtotime('now') < strtotime($date)) {
        $response['errors']['date'] = 'Дата не может превышать нынешнюю';
        $error = true;
    }

    if (empty($radio) || !preg_match('/^(M|W)$/', $radio)) {
        $response['errors']['radio'] = 'Выберите пол';
        $error = true;
    }

    if (empty($bio)) {
        $response['errors']['bio'] = 'Заполните поле';
        $error = true;
    } elseif (strlen($bio) > 65535) {
        $response['errors']['bio'] = 'Пожалуйста, сократите объем сообщения. Максимальное количество символов: 65535';
        $error = true;
    }

    if (empty($check)) {
        $response['errors']['check'] = 'Не ознакомлены с контрактом';
        $error = true;
    }

    if (empty($language)) {
        $response['errors']['language'] = 'Выберите язык программирования';
        $error = true;
    } else {
        try {
            $inQuery = implode(',', array_fill(0, count($language), '?'));
            $dbLangs = $db->prepare("SELECT id, name FROM all_languages WHERE name IN ($inQuery)");
            foreach ($language as $key => $value) {
                $dbLangs->bindValue(($key + 1), $value);
            }
            $dbLangs->execute();
            $languages = $dbLangs->fetchAll(PDO::FETCH_ASSOC);
            
            if ($dbLangs->rowCount() != count($language)) {
                $response['errors']['language'] = 'Неверно выбраны языки';
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
            $updateResult = $stmt->execute([$fio, $number, $email, $date, $radio, $bio, $form_id]);
            
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
               
                $login = uniqid();
                $pass = uniqid();
                $mpass = md5($pass);
                
                $stmt = $db->prepare("INSERT INTO users (login, password) VALUES (?, ?)");
                $stmt->execute([$login, $mpass]);
                $user_id = $db->lastInsertId();

                $stmt = $db->prepare("INSERT INTO dannye (user_id, fio, number, email, dat, radio, bio) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $fio, $number, $email, $date, $radio, $bio]);
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
            $response['values'] = [
                'fio' => $fio,
                'number' => $number,
                'email' => $email,
                'date' => $date,
                'radio' => $radio,
                'language' => implode(',', $language),
                'bio' => $bio,
                'check' => $check
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