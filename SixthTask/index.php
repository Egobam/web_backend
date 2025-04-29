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

$errors = isset($_SESSION['form_errors']) ? $_SESSION['form_errors'] : [];
$values = isset($_SESSION['form_values']) ? $_SESSION['form_values'] : [];
$credentials = isset($_SESSION['credentials']) ? $_SESSION['credentials'] : null;

// Очистка данных сессии после использования
unset($_SESSION['form_errors']);
unset($_SESSION['form_values']);

// Если пользователь авторизован, загружаем его данные
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT pl.name FROM user_languages ul JOIN programming_languages pl ON ul.language_id = pl.id WHERE ul.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $values = array_merge($user, ['languages' => $user_languages]);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <h1>Редактирование данных</h1>
        <p><a href="save.php?action=logout">Выйти</a></p>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="success-box">Данные успешно обновлены!</div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $field => $message): ?>
                    <p>Ошибка в поле '<?=$field?>': <?=$message?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="save.php?action=update" method="POST">
            <div class="form-group">
                <label for="fio">ФИО:</label>
                <div class="textInputWrapper">
                    <input type="text" id="fio" name="fio" placeholder="Введите ФИО" value="<?= htmlspecialchars($values['fio'] ?? '') ?>"
                           class="textInput <?= isset($errors['fio']) ? 'error-field' : '' ?>">
                </div>
                <?php if (isset($errors['fio'])): ?>
                    <span class="error"><?=$errors['fio']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <div class="textInputWrapper">
                    <input type="tel" id="phone" name="phone" placeholder="Введите телефон" value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                           class="textInput <?= isset($errors['phone']) ? 'error-field' : '' ?>">
                </div>
                <?php if (isset($errors['phone'])): ?>
                    <span class="error"><?=$errors['phone']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">E-mail:</label>
                <div class="textInputWrapper">
                    <input type="email" id="email" name="email" placeholder="Введите email" value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                           class="textInput <?= isset($errors['email']) ? 'error-field' : '' ?>">
                </div>
                <?php if (isset($errors['email'])): ?>
                    <span class="error"><?=$errors['email']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="birthdate">Дата рождения:</label>
                <div class="textInputWrapper">
                    <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($values['birthdate'] ?? '') ?>"
                           class="textInput <?= isset($errors['birthdate']) ? 'error-field' : '' ?>">
                </div>
                <?php if (isset($errors['birthdate'])): ?>
                    <span class="error"><?=$errors['birthdate']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group radio-group">
                <label>Пол:</label>
                <div class="radio-wrapper">
                    <input type="radio" id="male" name="gender" value="male" <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?>>
                    <label for="male">Мужской</label>
                    <input type="radio" id="female" name="gender" value="female" <?= ($values['gender'] ?? '') === 'female' ? 'checked' : '' ?>>
                    <label for="female">Женский</label>
                </div>
                <?php if (isset($errors['gender'])): ?>
                    <span class="error"><?=$errors['gender']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="languages">Любимый язык программирования:</label>
                <select id="languages" name="languages[]" multiple class="<?= isset($errors['languages']) ? 'error-field' : '' ?>">
                    <?php
                    $langs = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                    foreach ($langs as $lang) {
                        $selected = in_array($lang, $values['languages'] ?? []) ? 'selected' : '';
                        echo "<option value='$lang' $selected>$lang</option>";
                    }
                    ?>
                </select>
                <?php if (isset($errors['languages'])): ?>
                    <span class="error"><?=$errors['languages']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="bio">Биография:</label>
                <div class="textInputWrapper">
                    <textarea id="bio" name="bio" rows="5" placeholder="Введите биографию" class="textInput <?= isset($errors['bio']) ? 'error-field' : '' ?>"><?= htmlspecialchars($values['bio'] ?? '') ?></textarea>
                </div>
                <?php if (isset($errors['bio'])): ?>
                    <span class="error"><?=$errors['bio']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-label">С контрактом ознакомлен(а)</label>
                <div class="custom-checkbox">
                    <input type="checkbox" id="contract" name="contract" value="yes" <?= ($values['contract'] ?? '') === 'yes' ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                </div>
                <?php if (isset($errors['contract'])): ?>
                    <span class="error"><?=$errors['contract']?></span>
                <?php endif; ?>
            </div>

            <button type="submit"><span>Сохранить</span></button>
        </form>
    <?php elseif (isset($_SESSION['admin_id'])): ?>
        <h1>Панель администратора</h1>
        <p><a href="save.php?action=logout">Выйти</a></p>
        <p><a href="admin.php">Перейти к управлению пользователями</a></p>
    <?php else: ?>
        <h1>Вход / Регистрация</h1>
        
        <?php if ($credentials): ?>
            <div class="success-box">
                <p>Ваши учетные данные:</p>
                <p>Логин: <?= htmlspecialchars($credentials['login']) ?></p>
                <p>Пароль: <?= htmlspecialchars($credentials['password']) ?></p>
                <p>Сохраните эти данные!</p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $field => $message): ?>
                    <p><?= htmlspecialchars($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <h2>Вход</h2>
        <form action="save.php?action=login" method="POST">
            <div class="form-group">
                <label for="login">Логин:</label>
                <div class="textInputWrapper">
                    <input type="text" id="login" name="login" placeholder="Введите логин" class="textInput">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <div class="textInputWrapper">
                    <input type="password" id="password" name="password" placeholder="Введите пароль" class="textInput">
                </div>
            </div>
            
            <div class="form-group radio-group">
                <label>Войти как:</label>
                <div class="radio-wrapper">
                    <input type="radio" id="role_user" name="role" value="user" checked>
                    <label for="role_user">Пользователь</label>
                    <input type="radio" id="role_admin" name="role" value="admin">
                    <label for="role_admin">Администратор</label>
                </div>
                <?php if (isset($errors['role'])): ?>
                    <span class="error"><?=$errors['role']?></span>
                <?php endif; ?>
            </div>
            
            <button type="submit"><span>Войти</span></button>
        </form>
        
        <h2>Регистрация</h2>
        <form action="save.php?action=register" method="POST">
            <div class="form-group">
                <label for="fio">ФИО:</label>
                <div class="textInputWrapper">
                    <input type="text" id="fio" name="fio" placeholder="Введите ФИО" value="<?= htmlspecialchars($values['fio'] ?? '') ?>"
                           class="textInput <?= isset($errors['fio']) ? 'error-field' : '' ?>">
                </div>
                <?php if (isset($errors['fio'])): ?>
                    <span class="error"><?=$errors['fio']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <div class="textInputWrapper">
                    <input type="tel" id="phone" name="phone" placeholder="Введите телефон" value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                           class="textInput <?= isset($errors['phone']) ? 'error-field' : '' ?>">
                </div>
                <?php if (isset($errors['phone'])): ?>
                    <span class="error"><?=$errors['phone']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">E-mail:</label>
                <div class="textInputWrapper">
                    <input type="email" id="email" name="email" placeholder="Введите email" value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                           class="textInput <?= isset($errors['email']) ? 'error-field' : '' ?>">
                </div>
                <?php if (isset($errors['email'])): ?>
                    <span class="error"><?=$errors['email']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="birthdate">Дата рождения:</label>
                <div class="textInputWrapper">
                    <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($values['birthdate'] ?? '') ?>"
                           class="textInput <?= isset($errors['birthdate']) ? 'error-field' : '' ?>">
                </div>
                <?php if (isset($errors['birthdate'])): ?>
                    <span class="error"><?=$errors['birthdate']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group radio-group">
                <label>Пол:</label>
                <div class="radio-wrapper">
                    <input type="radio" id="male" name="gender" value="male" <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?>>
                    <label for="male">Мужской</label>
                    <input type="radio" id="female" name="gender" value="female" <?= ($values['gender'] ?? '') === 'female' ? 'checked' : '' ?>>
                    <label for="female">Женский</label>
                </div>
                <?php if (isset($errors['gender'])): ?>
                    <span class="error"><?=$errors['gender']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="languages">Любимый язык программирования:</label>
                <select id="languages" name="languages[]" multiple class="<?= isset($errors['languages']) ? 'error-field' : '' ?>">
                    <?php
                    $langs = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                    foreach ($langs as $lang) {
                        $selected = in_array($lang, $values['languages'] ?? []) ? 'selected' : '';
                        echo "<option value='$lang' $selected>$lang</option>";
                    }
                    ?>
                </select>
                <?php if (isset($errors['languages'])): ?>
                    <span class="error"><?=$errors['languages']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="bio">Биография:</label>
                <div class="textInputWrapper">
                    <textarea id="bio" name="bio" rows="5" placeholder="Введите биографию" class="textInput <?= isset($errors['bio']) ? 'error-field' : '' ?>"><?= htmlspecialchars($values['bio'] ?? '') ?></textarea>
                </div>
                <?php if (isset($errors['bio'])): ?>
                    <span class="error"><?=$errors['bio']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-label">С контрактом ознакомлен(а)</label>
                <div class="custom-checkbox">
                    <input type="checkbox" id="contract" name="contract" value="yes" <?= ($values['contract'] ?? '') === 'yes' ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                </div>
                <?php if (isset($errors['contract'])): ?>
                    <span class="error"><?=$errors['contract']?></span>
                <?php endif; ?>
            </div>

            <button type="submit"><span>Зарегистрироваться</span></button>
        </form>
    <?php endif; ?>
</body>
</html>