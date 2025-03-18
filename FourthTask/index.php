<?php
session_start();

$values = isset($_COOKIE['form_values']) ? unserialize($_COOKIE['form_values']) : [];
$errors = isset($_COOKIE['form_errors']) ? unserialize($_COOKIE['form_errors']) : [];

if (!empty($errors)) {
    setcookie('form_errors', '', time() - 3600, '/');
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
    <h1>Заполните форму</h1>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="success-box">Данные успешно сохранены!</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $field => $message): ?>
                <p>Ошибка в поле '<?=$field?>': <?=$message?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="save.php" method="POST">
        <div class="form-group">
            <label for="fio">ФИО:</label>
            <div class="textInputWrapper">
                <input type="text" id="fio" name="fio" placeholder="Введите ФИО" value="<?= htmlspecialchars($values['fio'] ?? '') ?>"
                       class="textInput <?= isset($errors['fio']) ? 'error-field' : '' ?>" required>
            </div>
            <?php if (isset($errors['fio'])): ?>
                <span class="error"><?=$errors['fio']?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="phone">Телефон:</label>
            <div class="textInputWrapper">
                <input type="tel" id="phone" name="phone" placeholder="Введите телефон" value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                       class="textInput <?= isset($errors['phone']) ? 'error-field' : '' ?>" required>
            </div>
            <?php if (isset($errors['phone'])): ?>
                <span class="error"><?=$errors['phone']?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">E-mail:</label>
            <div class="textInputWrapper">
                <input type="email" id="email" name="email" placeholder="Введите email" value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                       class="textInput <?= isset($errors['email']) ? 'error-field' : '' ?>" required>
            </div>
            <?php if (isset($errors['email'])): ?>
                <span class="error"><?=$errors['email']?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="birthdate">Дата рождения:</label>
            <div class="textInputWrapper">
                <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($values['birthdate'] ?? '') ?>"
                       class="textInput <?= isset($errors['birthdate']) ? 'error-field' : '' ?>" required>
            </div>
            <?php if (isset($errors['birthdate'])): ?>
                <span class="error"><?=$errors['birthdate']?></span>
            <?php endif; ?>
        </div>

        <div class="form-group radio-group">
            <label>Пол:</label>
            <div class="radio-wrapper">
                <input type="radio" id="male" name="gender" value="male" <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?> required>
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
            <select id="languages" name="languages[]" multiple class="<?= isset($errors['languages']) ? 'error-field' : '' ?>" required>
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
                <textarea id="bio" name="bio" rows="5" placeholder="Введите биографию" class="textInput <?= isset($errors['bio']) ? 'error-field' : '' ?>" required><?= htmlspecialchars($values['bio'] ?? '') ?></textarea>
            </div>
            <?php if (isset($errors['bio'])): ?>
                <span class="error"><?=$errors['bio']?></span>
            <?php endif; ?>
        </div>

        <div class="form-group checkbox-group">
            <label class="checkbox-label">С контрактом ознакомлен(а)</label>
            <div class="custom-checkbox">
                <input type="checkbox" id="contract" name="contract" value="yes" <?= ($values['contract'] ?? '') === 'yes' ? 'checked' : '' ?> required>
                <span class="checkmark"></span>
            </div>
            <?php if (isset($errors['contract'])): ?>
                <span class="error"><?=$errors['contract']?></span>
            <?php endif; ?>
        </div>

        <button type="submit"><span>Сохранить</span></button>
    </form>
</body>
</html>