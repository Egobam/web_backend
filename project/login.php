<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

if (!empty($_SESSION['login'])) {
    header('Location: ./');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = 'u68918'; 
    $pass = '7758388'; 
    $db = new PDO('mysql:host=localhost;dbname=u68918', $user, $pass,
    [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); 
    
    $login = $_POST['login'];
    $password = md5($_POST['password']);
    
    try {
        
        $stmt = $db->prepare("SELECT id, role FROM users WHERE login = ? and password = ?");
        $stmt->execute([$login, $password]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $_SESSION['login'] = $_POST['login'];
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['role'] = $user_data['role']; 
            
            
            if ($user_data['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: form.php#footer');
            }
            exit();
        } else {
            $error = 'Неверный логин или пароль';
        }
    } catch (PDOException $e) {
        print ('Error : ' . $e->getMessage());
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="bootstrap.min.css" />
    <title>Авторизация</title>
</head>

<body>
    <form action="" method="post" class="form">
        <div class="mess" style="color: red;"><?php echo $error; ?></div>
        <h2>Авторизация</h2>
        <div> <input class="input" style="width: 100%;" type="text" name="login" placeholder="Логин"> </div>
        <div> <input class="input" style="width: 100%;" type="password" name="password" placeholder="Пароль"> </div>
        <button class="button" type="submit">Войти</button>
    </form>
</body>

</html>