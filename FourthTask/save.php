<?php
session_start();

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

// Функция для генерации HTML-формы с ошибками или значениями
function generateForm($errors = [], $values = []) {
    $html = '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Заполните форму</h1>';

    if (!empty($errors)) {
        $html .= '<div class="error-box">';
        foreach ($errors as $field => $message) {
            $html .= "<p>Ошибка в поле '$field': $message</p>";
        }
        $html .= '</div>';
    }

    $html .= '<form action="save.php" method="POST">
        <div class="form-group">
            <label for="fio">ФИО:</label><br>
            <input type="text" id="fio" name="fio" value="' . htmlspecialchars($values['fio'] ?? '') . '"
                   class="' . (isset($errors['fio']) ? 'error-field' : '') . '" required>
        </div>

        <div class="form-group">
            <label for="phone">Телефон:</label><br>
            <input type="tel" id="phone" name="phone" value="' . htmlspecialchars($values['phone'] ?? '') . '"
                   class="' . (isset($errors['phone']) ? 'error-field' : '') . '" required>
        </div>

        <div class="form-group">
            <label for="email">E-mail:</label><br>
            <input type="email" id="email" name="email" value="' . htmlspecialchars($values['email'] ?? '') . '"
                   class="' . (isset($errors['email']) ? 'error-field' : '') . '" required>
        </div>

        <div class="form-group">
            <label for="birthdate">Дата рождения:</label><br>
            <input type="date" id="birthdate" name="birthdate" value="' . htmlspecialchars($values['birthdate'] ?? '') . '"
                   class="' . (isset($errors['birthdate']) ? 'error-field' : '') . '" required>
        </div>

        <div class="form-group radio-group">
            <label>Пол:</label><br>
            <input type="radio" id="male" name="gender" value="male" ' . (($values['gender'] ?? '') === 'male' ? 'checked' : '') . ' required>
            <label for="male">Мужской</label>
            <input type="radio" id="female" name="gender" value="female" ' . (($values['gender'] ?? '') === 'female' ? 'checked' : '') . '>
            <label for="female">Женский</label>
        </div>

        <div class="form-group">
            <label for="languages">Любимый язык программирования:</label><br>
            <select id="languages" name="languages[]" multiple class="' . (isset($errors['languages']) ? 'error-field' : '') . '" required>';

    $langs = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
    foreach ($langs as $lang) {
        $selected = in_array($lang, $values['languages'] ?? []) ? 'selected' : '';
        $html .= "<option value='$lang' $selected>$lang</option>";
    }

    $html .= '</select>
        </div>

        <div class="form-group">
            <label for="bio">Биография:</label><br>
            <textarea id="bio" name="bio" rows="5" class="' . (isset($errors['bio']) ? 'error-field' : '') . '" required>' . htmlspecialchars($values['bio'] ?? '') . '</textarea>
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" id="contract" name="contract" value="yes" ' . (isset($values['contract']) ? 'checked' : '') . ' required>
            <label for="contract">С контрактом ознакомлен(а)</label>
        </div>

        <input type="submit" value="Сохранить">
    </form>
</body>
</html>';

    return $html;
}

// Валидация данных
$errors = [];
$data = $_POST;
$values = isset($_COOKIE['form_values']) ? unserialize($_COOKIE['form_values']) : [];

if (!preg_match("/^[а-яА-Яa-zA-Z\s]{1,150}$/u", trim($data['fio'] ?? ''))) {
    $errors['fio'] = "Допустимы только буквы и пробелы, длина до 150 символов";
}

if (!preg_match("/^(\+7|8)\d{10}$/", trim($data['phone'] ?? ''))) {
    $errors['phone'] = "Формат: +7XXXXXXXXXX или 8XXXXXXXXXX";
}

if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", trim($data['email'] ?? ''))) {
    $errors['email'] = "Допустимы латинские буквы, цифры, ._%+- и корректный домен";
}

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data['birthdate'] ?? '') || strtotime($data['birthdate']) > time()) {
    $errors['birthdate'] = "Формат: ГГГГ-ММ-ДД, дата не позже сегодняшней";
}

if (!in_array($data['gender'] ?? '', ['male', 'female'])) {
    $errors['gender'] = "Выберите мужской или женский пол";
}

$valid_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
$languages = $data['languages'] ?? [];
if (empty($languages) || count(array_diff($languages, $valid_languages)) > 0) {
    $errors['languages'] = "Выберите хотя бы один язык из списка";
}

if (!preg_match("/^[\s\S]{1,1000}$/", trim($data['bio'] ?? ''))) {
    $errors['bio'] = "Длина до 1000 символов";
}

if (!isset($data['contract']) || $data['contract'] !== 'yes') {
    $errors['contract'] = "Необходимо согласиться с контрактом";
}

// Если есть ошибки, показываем форму с ошибками
if (!empty($errors)) {
    setcookie('form_errors', serialize($errors), 0, '/');
    setcookie('form_values', serialize($data), 0, '/');
    echo generateForm($errors, $data);
    setcookie('form_errors', '', time() - 3600, '/'); // Удаляем ошибки после отображения
    exit;
}

// Сохранение в базу данных
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

    // Успешно: сохраняем данные в куки на год
    setcookie('form_values', serialize($data), time() + 365 * 24 * 60 * 60, '/');
    setcookie('form_errors', '', time() - 3600, '/');
    echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Успех</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="success-box">Данные успешно сохранены!</div>
    <a href="index.php">Вернуться к форме</a>
</body>
</html>';
} catch (PDOException $e) {
    die("Ошибка сохранения: " . $e->getMessage());
}
?>