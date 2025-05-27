<?php
// Начало сессии и подключение к базе данных
session_start();
require 'db.php';

// Функции для чтения значений из cookies
$get      = fn($key) => isset($_COOKIE[$key]) ? htmlspecialchars($_COOKIE[$key]) : '';
$getArray = fn($key) => isset($_COOKIE[$key]) ? json_decode($_COOKIE[$key], true) : [];

// Обработка POST-запроса (обычного или AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Функция для чтения JSON из тела запроса
    function getJsonInput(): ?array {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ct, 'application/json') === 0) {
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw, true);
            return is_array($data) ? $data : null;
        }
        return null;
    }

    $json       = getJsonInput();
    $isAJAX     = $json !== null;
    $input      = $json ?? $_POST;
    // Гарантируем, что languages — массив
    $input['languages'] = isset($input['languages']) 
        ? (array)$input['languages'] 
        : [];

    $errors      = [];   // Сообщения об ошибках
    $errorFields = [];   // Поля с ошибками

    // Валидация ФИО
    if (empty($input['name'])) {
        $errors[]      = "Поле ФИО обязательно для заполнения.";
        $errorFields[] = 'name';
    } elseif (!preg_match("/^[\p{L}]+\s[\p{L}]+\s[\p{L}]+$/u", $input['name'])) {
        $errors[]      = "Поле ФИО должно содержать ровно три слова (Иванов Иван Иванович).";
        $errorFields[] = 'name';
    }

    // Валидация телефона
    if (empty($input['phone'])) {
        $errors[]      = "Поле Телефон обязательно для заполнения.";
        $errorFields[] = 'phone';
    } elseif (!preg_match("/^\+[0-9]{1,15}$/", $input['phone'])) {
        $errors[]      = "Телефон должен начинаться с '+' и содержать только цифры.";
        $errorFields[] = 'phone';
    }

    // Валидация e-mail
    if (empty($input['email'])) {
        $errors[]      = "Поле E-mail обязательно для заполнения.";
        $errorFields[] = 'email';
    } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[]      = "Некорректный формат E-mail.";
        $errorFields[] = 'email';
    }

    // Валидация даты рождения и возраста
    if (empty($input['dob'])) {
        $errors[]      = "Поле Дата рождения обязательно для заполнения.";
        $errorFields[] = 'dob';
    } else {
        $dobObj = DateTime::createFromFormat('Y-m-d', $input['dob']);
        if (!$dobObj || $dobObj->format('Y-m-d') !== $input['dob']) {
            $errors[]      = "Некорректный формат даты.";
            $errorFields[] = 'dob';
        } elseif ((new DateTime())->diff($dobObj)->y < 18) {
            $errors[]      = "Вы должны быть старше 18 лет.";
            $errorFields[] = 'dob';
        }
    }

    // Валидация прочих полей
    if (empty($input['gender'])) {
        $errors[]      = "Поле Пол обязательно для заполнения.";
        $errorFields[] = 'gender';
    }
    if (empty($input['languages'])) {
        $errors[]      = "Выберите хотя бы один язык программирования.";
        $errorFields[] = 'languages';
    }
    if (!isset($input['contract'])) {
        $errors[]      = "Необходимо ознакомиться с контрактом.";
        $errorFields[] = 'contract';
    }

    // Если есть ошибки — сохраняем их в cookies и возвращаемся к форме
    if ($errors) {
        setcookie('errors',       json_encode($errors),      time()+3600, '/');
        setcookie('error_fields', json_encode($errorFields), time()+3600, '/');
        // Сохраняем введённые значения для повторного вывода
        foreach (['name','phone','email','dob','gender','bio'] as $f) {
            setcookie($f, $input[$f] ?? '', time()+3600, '/');
        }
        setcookie('languages', json_encode($input['languages']), time()+3600, '/');
        setcookie('contract',  $input['contract'] ?? '',       time()+3600, '/');

        if ($isAJAX) {
            // Возвращаем JSON с указанием редиректа
            echo json_encode([
                'success'      => false,
                'redirect_url' => '/project/index.php',
            ]);
            exit;
        }
        header('Location: index.php');
        exit;
    }

    // Нет ошибок — сохраняем заявку и пользователя
    try {
        $pdo->beginTransaction();
        // Добавляем запись в application
        $stmt = $pdo->prepare(
            "INSERT INTO application (name, phone, email, dob, gender, bio)
             VALUES (:name,:phone,:email,:dob,:gender,:bio)"
        );
        $stmt->execute([
            ':name'   => $input['name'],
            ':phone'  => $input['phone'],
            ':email'  => $input['email'],
            ':dob'    => $input['dob'],
            ':gender' => $input['gender'],
            ':bio'    => $input['bio'] ?? ''
        ]);
        $appId = $pdo->lastInsertId();

        // Сохраняем выбранные языки
        $link = $pdo->prepare(
            "INSERT INTO application_languages (application_id, language_id)
             VALUES (:aid,(SELECT id FROM languages WHERE name=:lang))"
        );
        foreach ($input['languages'] as $lang) {
            $link->execute([':aid'=>$appId, ':lang'=>$lang]);
        }

        // Генерируем учётные данные
        $username = 'user_' . bin2hex(random_bytes(4));
        $password = bin2hex(random_bytes(4));
        setcookie('username', $username, time()+3600, '/');
        setcookie('password', $password, time()+3600, '/');
        // Сохраняем хеш пароля
        $u = $pdo->prepare(
            "INSERT INTO users (username, password_hash, application_id)
             VALUES (:u,:h,:aid)"
        );
        $u->execute([
            ':u'   => $username,
            ':h'   => password_hash($password, PASSWORD_DEFAULT),
            ':aid'=> $appId
        ]);

        $pdo->commit();

        if ($isAJAX) {
            echo json_encode([
                'success'      => true,
                'redirect_url' => '/project/form.php',
            ]);
            exit;
        }
        header('Location: /project/form.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Ошибка: " . $e->getMessage());
    }
}

// GET-запрос — показываем страницу с логином/паролем
$password = $get('password');
setcookie('password', '', -1, '/');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Сохранение заявки</title>

<style>
body {
  margin: 0;
  background: #040613;
  color: #e0effd;
  font-family: Montserrat, sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
  box-sizing: border-box;
}

.notification-container {
  background: #264d8f;
  border: 1px solid #ffffff;
  border-radius: 10px;
  padding: 25px;
  max-width: 400px;
  width: 100%;
  text-align: center;
  box-sizing: border-box;
}

.notification-title {
  margin: 0 0 15px;
  color: #5c9669;
  font-size: 1.8em;
}

.credentials {
  background: #2c2c2c;
  border: 1px solid #aed7fd;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
}

.credentials p {
  margin: 8px 0;
  font-size: 1em;
}

.credential-value {
  color: #ffffff;
}

.credentials-note p {
  margin: 0 0 15px;
  font-size: 0.95em;
}

.credentials-form {
  display: flex;
  justify-content: center;
}

.buttons {
  background: #f14d34;
  color: #ffffff;
  border: none;
  border-radius: 5px;
  padding: 12px 20px;
  font-family: Montserrat, sans-serif;
  font-size: 1em;
  cursor: pointer;
  transition: background 0.25s ease;
}

.buttons:hover {
  background: #d13f2e;
}

@media (min-width: 1024px) {
  .notification-container {
    max-width: 500px;
  }
  .notification-title {
    font-size: 2em;
  }
  .credentials p {
    font-size: 1.1em;
  }
}
  
    </style>
    </head>
    <body>
  <div class="notification-container">
    <h2 class="notification-title">Заявка успешно сохранена!</h2>
    <div class="credentials">
      <p>Ваш логин: <strong class="credential-value"><?= $get('username') ?></strong></p>
      <p>Ваш пароль: <strong class="credential-value"><?= $password ?></strong></p>
    </div>
    <div class="credentials-note">
      <p>Сохраните эти данные для редактирования вашего профиля.</p>
      <form action="login.php" method="GET" class="credentials-form">
        <input type="submit" value="Перейти к входу" class="buttons">
      </form>
    </div>
  </div>
</body>

</html>
