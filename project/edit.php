<?php
// Начало обработки запроса: старт сессии и подключение БД
session_start();
require 'db.php';

// Перенаправляем неавторизованных пользователей на страницу входа
if (empty($_SESSION['user_id'])) {
    header('Location: /project/login.php');
    exit;
}

// Готовим запрос для получения текущих данных заявки
$stmt = $pdo->prepare(
    "SELECT u.id AS user_id, a.* 
     FROM users u
     JOIN application a ON u.application_id = a.id
     WHERE u.id = :uid"
);

// Функция для чтения JSON из тела запроса (AJAX)
function getJsonInput(): ?array {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') === 0) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
    return null;
}

// Определяем, был ли AJAX-запрос и подготавливаем входные данные
$json    = getJsonInput();
$isAJAX  = $json !== null;
$input   = $json ?? $_POST;

// Приводим languages к массиву, если это необходимо
$languagesArr       = isset($input['languages']) ? (array)$input['languages'] : [];
$input['languages'] = $languagesArr;

// Выполняем запрос для загрузки первоначальных данных заявки
$stmt->execute([':uid' => $_SESSION['user_id']]);
$data      = $stmt->fetch(PDO::FETCH_ASSOC);
$errorForm = $data;  // Значения для повторного вывода в случае ошибки
$errors     = [];    // Массив сообщений об ошибках
$errorFields = [];   // Список полей с ошибками

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Копируем поля во временный массив для повторного заполнения формы
    $errorForm['name']  = $input['name'];
    $errorForm['phone'] = $input['phone'];
    $errorForm['email'] = $input['email'];
    $errorForm['dob']   = $input['dob'];
    $errorForm['bio']   = $input['bio'];
    // Валидация ФИО
    if (empty($input['name'])) {
        $errors[]      = "Поле ФИО обязательно для заполнения.";
        $errorFields[] = 'name';
    } elseif (!preg_match("/^[a-zA-Zа-яА-ЯёЁ]+\s[a-zA-Zа-яА-ЯёЁ]+\s[a-zA-Zа-яА-ЯёЁ]+$/u", $input['name'])) {
        $errors[]      = "Поле ФИО должно содержать ровно три слова (например, Иванов Иван Иванович).";
        $errorFields[] = 'name';
    }

    // Валидация телефона
    if (empty($input['phone'])) {
        $errors[]      = "Поле Телефон обязательно для заполнения.";
        $errorFields[] = 'phone';
    } elseif (!preg_match("/^\+[0-9]{1,15}$/", $input['phone'])) {
        $errors[]      = "Телефон должен начинаться с '+' и содержать только цифры (максимум 15 цифр).";
        $errorFields[] = 'phone';
    }

    // Валидация email
    if (empty($input['email'])) {
        $errors[]      = "Поле E-mail обязательно для заполнения.";
        $errorFields[] = 'email';
    } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[]      = "Некорректный формат E-mail.";
        $errorFields[] = 'email';
    }

    // Валидация даты рождения
    if (empty($input['dob'])) {
        $errors[]      = "Поле Дата рождения обязательно для заполнения.";
        $errorFields[] = 'dob';
    } else {
        $dob = DateTime::createFromFormat('Y-m-d', $input['dob']);
        if (!$dob || $dob->format('Y-m-d') !== $input['dob']) {
            $errors[] = "Некорректный формат даты рождения. Используйте формат ГГГГ-ММ-ДД.";
        } else {
            $today = new DateTime();
            $age   = $today->diff($dob)->y;
            if ($age < 18) {
                $errors[]      = "Вы должны быть старше 18 лет.";
                $errorFields[] = 'dob';
            }
        }
    }

    // Валидация поля «пол»
    if (empty($input['gender'])) {
        $errors[]      = "Поле Пол обязательно для заполнения.";
        $errorFields[] = 'gender';
    }

    // Проверяем, что выбран хотя бы один язык программирования
    if (empty($input['languages'])) {
        $errors[]      = "Выберите хотя бы один язык программирования.";
        $errorFields[] = 'languages';
    }

    // Если ошибки найдены, сохраняем их в Cookies и перенаправляем
    if (!empty($errors)) {
        // Записываем массив ошибок и полей для подсветки
        setcookie('errors', json_encode($errors), time() + 3600, '/');
        setcookie('error_form', json_encode($errorForm), time() + 3600, '/');
        setcookie('error_fields', json_encode($errorFields), time() + 3600, '/');

        // Записываем введённые значения для повторного вывода
        foreach (['name','phone','email','dob','gender','bio'] as $field) {
            setcookie($field, $input[$field] ?? '', time() + 3600, '/');
        }
        setcookie('languages', json_encode($input['languages']), time() + 3600, '/');

        // Возвращаем JSON для AJAX или делаем редирект для обычного запроса
        if ($isAJAX) {
            echo json_encode([
                'success'      => false,
                'redirect_url' => '/project/edit.php',
            ]);
            exit;
        }
        header('Location: /project/edit.php');
        exit;
    }

    // Если данных без ошибок — обновляем запись
    $pdo->beginTransaction();
    $upd = $pdo->prepare(
        "UPDATE application SET
             name   = :name,
             phone  = :phone,
             email  = :email,
             dob    = :dob,
             gender = :gender,
             bio    = :bio
         WHERE id = :aid"
    );
    $upd->execute([
        ':name'   => $input['name'],
        ':phone'  => $input['phone'],
        ':email'  => $input['email'],
        ':dob'    => $input['dob'],
        ':gender' => $input['gender'],
        ':bio'    => $input['bio'],
        ':aid'    => $data['id']
    ]);

    // Обновляем связи языков: сначала удаляем старые, затем вставляем новые
    $pdo->prepare("DELETE FROM application_languages WHERE application_id = :aid")
        ->execute([':aid' => $data['id']]);
    $lnk = $pdo->prepare(
        "INSERT INTO application_languages (application_id, language_id)
         VALUES (:aid, (SELECT id FROM languages WHERE name = :lang))"
    );
    foreach ($input['languages'] as $lang) {
        $lnk->execute([':aid' => $data['id'], ':lang' => $lang]);
    }
    $pdo->commit();

    // При AJAX-запросе возвращаем JSON с флагом успеха, иначе — редирект с параметром success
    if ($isAJAX) {
        echo json_encode([
            'success'      => true,
            'redirect_url' => '/project/edit.php?success=1',
        ]);
        exit;
    }
    header('Location: /project/edit.php?success=1');
    exit;
}

// Обрабатываем GET-запрос: читаем флаг success и ранее сохранённые ошибки из Cookies
$success     = $_GET['success'] ?? null;
$errors      = isset($_COOKIE['errors']) ? json_decode($_COOKIE['errors'], true) : [];
$errorFields = isset($_COOKIE['error_fields']) ? json_decode($_COOKIE['error_fields'], true) : [];
$errorForm   = isset($_COOKIE['error_form'])   ? json_decode($_COOKIE['error_form'], true)   : $data;

// Функции для безопасного чтения значений из куки
$get = function($key) {
    return $_COOKIE[$key] ?? '';
};
$getArray = function($key) {
    return isset($_COOKIE[$key]) ? json_decode($_COOKIE[$key], true) : [];
};

// Очищаем куки с ошибками после их использования
foreach (['errors','error_form','error_fields','name','phone','email','dob','gender','languages','bio','contract'] as $c) {
    setcookie($c, '', -1, '/');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать заявку</title>
    <script defer src="/project/edit.js"></script>
    <link rel="stylesheet" href="profilestyle.css">
</head>
<body>
  <div class="page-container">
    <div class="user-container">
      <h1 class="user-title"><?= $get('username') ?></h1>

      <form action="edit.php" method="post" id="edit-form" class="edit-form">
        <?php if ($success === '1'): ?>
          <div class="success-message">Успех!</div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
          <?php foreach($errors as $e): ?>
            <div class="error-message"><?= htmlspecialchars($e) ?></div>
          <?php endforeach; ?>
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label">ФИО:</label>
          <input type="text" name="name" value="<?= htmlspecialchars($errorForm['name']) ?>" class="form-input <?= in_array('name', $errorFields) ? 'input-error' : '' ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Телефон:</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($errorForm['phone']) ?>" class="form-input <?= in_array('phone', $errorFields) ? 'input-error' : '' ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Email:</label>
          <input type="email" name="email" value="<?= htmlspecialchars($errorForm['email']) ?>" class="form-input <?= in_array('email', $errorFields) ? 'input-error' : '' ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Дата рождения:</label>
          <input type="date" name="dob" value="<?= htmlspecialchars($errorForm['dob']) ?>" class="form-input <?= in_array('dob', $errorFields) ? 'input-error' : '' ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Пол:</label>
          <div class="radio-group <?= in_array('gender', $errorFields) ? 'input-error' : '' ?>">
            <label class="radio-label"><input type="radio" name="gender" value="male" <?= $data['gender'] === 'male' ? 'checked' : '' ?>> Мужской</label>
            <label class="radio-label"><input type="radio" name="gender" value="female" <?= $data['gender'] === 'female' ? 'checked' : '' ?>> Женский</label>
          </div>
        </div>

        <div class="form-group">
    <label for="languages">Любимый язык программирования:</label>
<?php
    //получаем список всех возможных языков
    $stmt = $pdo->query("SELECT name FROM languages");
    //получаем список выбранных языков
    if(isset($_COOKIE['languages'])){
        $selectedLangs = $getArray('languages');
    } else {
        $userLangs = $pdo->prepare("SELECT l.name FROM application_languages al JOIN languages l ON al.language_id = l.id WHERE al.application_id = :aid");
        $userLangs->execute([':aid' => $data['id']]);
        $selectedLangs = array_column($userLangs->fetchAll(PDO::FETCH_ASSOC), 'name');
    }
    $langErr = in_array('languages', $errorFields) ? 'error' : '';
    //Выводим языки, отмечая уже выбранные ранее
    echo "<select id='languages'
            name='languages[]'
            class='{$langErr}'
            multiple >";
    foreach ($stmt as $row) {
        $checked = in_array($row['name'], $selectedLangs) ? 'selected' : '';
        echo "<option
                    value='{$row['name']}' {$checked}> {$row['name']}
                </option>";
    }
    echo '</select>';
?>
    </div>

        <div class="form-group">
          <label for="bio" class="form-label">Биография:</label>
          <textarea id="bio" name="bio" class="form-input <?= in_array('bio', $errorFields) ? 'input-error' : '' ?>"><?=  htmlspecialchars($errorForm['bio'])?></textarea>
        </div>

        <div class="form-actions">
          <input type="submit" value="Сохранить изменения" class="buttons submit-button">
        </div>
      </form>

      <div class="form-actions logout-actions">
        <form action="logout.php" method="get">
          <input type="submit" value="Выйти" class="buttons logout-button">
        </form>
      </div>
    </div>
  </div>
</body>

</html>

