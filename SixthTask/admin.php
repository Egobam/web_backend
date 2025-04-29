<?php
session_start();

$dsn = 'mysql:host=localhost;dbname=u68860;charset=utf8';
$username = 'u68860';
$password = '8500150';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Проверка авторизации администратора
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Действия по управлению
$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? null;

if ($action === 'delete' && $user_id) {
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        $pdo->commit();
        header('Location: admin.php?success=deleted');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Delete failed: " . $e->getMessage());
    }
}

if ($action === 'edit' && $user_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = $_POST;
    $errors = [];

    // Валидация
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
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET fio = ?, phone = ?, email = ?, birthdate = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
            $stmt->execute([trim($values['fio']), trim($values['phone']), trim($values['email']), $values['birthdate'], $values['gender'], trim($values['bio']), 1, $user_id]);

            $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?")->execute([$user_id]);
            $stmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $insert = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            foreach ($languages as $language) {
                $stmt->execute([$language]);
                $lang_id = $stmt->fetchColumn();
                $insert->execute([$user_id, $lang_id]);
            }

            $pdo->commit();
            header('Location: admin.php?success=updated');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Update failed: " . $e->getMessage());
        }
    } else {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_values'] = $values;
        header("Location: admin.php?action=edit&id=$user_id");
        exit;
    }
}

// Получение всех пользователей
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as &$user) {
    $stmt = $pdo->prepare("SELECT pl.name FROM user_languages ul JOIN programming_languages pl ON ul.language_id = pl.id WHERE ul.user_id = ?");
    $stmt->execute([$user['id']]);
    $user['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Получение статистики
$stats = $pdo->query("SELECT pl.name, COUNT(ul.user_id) as count 
                      FROM programming_languages pl 
                      LEFT JOIN user_languages ul ON pl.id = ul.language_id 
                      GROUP BY pl.id, pl.name")->fetchAll(PDO::FETCH_ASSOC);

// Получение данных пользователя для редактирования
$edit_user = null;
$edit_languages = [];
if ($action === 'edit' && $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT pl.name FROM user_languages ul JOIN programming_languages pl ON ul.language_id = pl.id WHERE ul.user_id = ?");
    $stmt->execute([$user_id]);
    $edit_languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$errors = $_SESSION['form_errors'] ?? [];
$values = $_SESSION['form_values'] ?? [];
unset($_SESSION['form_errors']);
unset($_SESSION['form_values']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <h1>Панель администратора</h1>
    <p><a href="save.php?action=logout">Выйти</a></p>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-box">
            <?= $_GET['success'] === 'deleted' ? 'User deleted successfully!' : 'User updated successfully!' ?>
        </div>
    <?php endif; ?>

    <h2>User Data</h2>
    <table border="1" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <th>ID</th>
            <th>FIO</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Birthdate</th>
            <th>Gender</th>
            <th>Languages</th>
            <th>Bio</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['fio']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['birthdate']) ?></td>
                <td><?= htmlspecialchars($user['gender']) ?></td>
                <td><?= htmlspecialchars(implode(', ', $user['languages'])) ?></td>
                <td><?= htmlspecialchars(substr($user['bio'], 0, 50)) . (strlen($user['bio']) > 50 ? '...' : '') ?></td>
                <td>
                    <a href="admin.php?action=edit&id=<?= $user['id'] ?>">Edit</a> |
                    <a href="admin.php?action=delete&id=<?= $user['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Programming Language Statistics</h2>
    <table border="1" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <th>Language</th>
            <th>User Count</th>
        </tr>
        <?php foreach ($stats as $stat): ?>
            <tr>
                <td><?= htmlspecialchars($stat['name']) ?></td>
                <td><?= $stat['count'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if ($action === 'edit' && $edit_user): ?>
        <h2>Edit User #<?= $edit_user['id'] ?></h2>
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $field => $message): ?>
                    <p>Error in '<?=$field?>': <?= htmlspecialchars($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form action="admin.php?action=edit&id=<?= $edit_user['id'] ?>" method="POST">
            <div class="form-group">
                <label for="fio">ФИО:</label>
                <div class="textInputWrapper">
                    <input type="text" id="fio" name="fio" placeholder="Введите ФИО" value="<?= htmlspecialchars($values['fio'] ?? $edit_user['fio']) ?>"
                           class="textInput <?= isset($errors['fio']) ? 'error-field' : '' ?>" required>
                </div>
                <?php if (isset($errors['fio'])): ?>
                    <span class="error"><?=$errors['fio']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <div class="textInputWrapper">
                    <input type="tel" id="phone" name="phone" placeholder="Введите телефон" value="<?= htmlspecialchars($values['phone'] ?? $edit_user['phone']) ?>"
                           class="textInput <?= isset($errors['phone']) ? 'error-field' : '' ?>" required>
                </div>
                <?php if (isset($errors['phone'])): ?>
                    <span class="error"><?=$errors['phone']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">E-mail:</label>
                <div class="textInputWrapper">
                    <input type="email" id="email" name="email" placeholder="Введите email" value="<?= htmlspecialchars($values['email'] ?? $edit_user['email']) ?>"
                           class="textInput <?= isset($errors['email']) ? 'error-field' : '' ?>" required>
                </div>
                <?php if (isset($errors['email'])): ?>
                    <span class="error"><?=$errors['email']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="birthdate">Дата рождения:</label>
                <div class="textInputWrapper">
                    <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($values['birthdate'] ?? $edit_user['birthdate']) ?>"
                           class="textInput <?= isset($errors['birthdate']) ? 'error-field' : '' ?>" required>
                </div>
                <?php if (isset($errors['birthdate'])): ?>
                    <span class="error"><?=$errors['birthdate']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group radio-group">
                <label>Пол:</label>
                <div class="radio-wrapper">
                    <input type="radio" id="male" name="gender" value="male" <?= ($values['gender'] ?? $edit_user['gender']) === 'male' ? 'checked' : '' ?> required>
                    <label for="male">Мужской</label>
                    <input type="radio" id="female" name="gender" value="female" <?= ($values['gender'] ?? $edit_user['gender']) === 'female' ? 'checked' : '' ?>>
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
                        $selected = in_array($lang, $values['languages'] ?? $edit_languages) ? 'selected' : '';
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
                    <textarea id="bio" name="bio" rows="5" placeholder="Введите биографию" class="textInput <?= isset($errors['bio']) ? 'error-field' : '' ?>" required><?= htmlspecialchars($values['bio'] ?? $edit_user['bio']) ?></textarea>
                </div>
                <?php if (isset($errors['bio'])): ?>
                    <span class="error"><?=$errors['bio']?></span>
                <?php endif; ?>
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-label">С контрактом ознакомлен(а)</label>
                <div class="custom-checkbox">
                    <input type="checkbox" id="contract" name="contract" value="yes" <?= ($values['contract'] ?? ($edit_user['contract'] ? 'yes' : '')) === 'yes' ? 'checked' : '' ?> required>
                    <span class="checkmark"></span>
                </div>
                <?php if (isset($errors['contract'])): ?>
                    <span class="error"><?=$errors['contract']?></span>
                <?php endif; ?>
            </div>

            <button type="submit"><span>Save</span></button>
        </form>
    <?php endif; ?>
</body>
</html>