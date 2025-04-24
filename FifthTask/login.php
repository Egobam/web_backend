<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=u68860;charset=utf8', 'u68860', '8500150');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: edit.php");
        exit;
    } else {
        $error = "Неверный логин или пароль";
    }
}
?>

<form method="POST">
    <h1>Вход</h1>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <label>Логин: <input type="text" name="login" required></label><br>
    <label>Пароль: <input type="password" name="password" required></label><br>
    <button type="submit">Войти</button>
</form>
