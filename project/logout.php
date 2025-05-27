<?php
// Начало сессии и сброс всех данных
session_start();
session_unset();   // Удаляем все переменные сессии
session_destroy(); // Уничтожаем сессию

// Очищаем все cookie, связанные с формой и авторизацией
setcookie('errors',     '', time() - 3600, '/');
setcookie('error_fields','', time() - 3600, '/');
setcookie('name',       '', time() - 3600, '/');
setcookie('phone',      '', time() - 3600, '/');
setcookie('email',      '', time() - 3600, '/');
setcookie('dob',        '', time() - 3600, '/');
setcookie('gender',     '', time() - 3600, '/');
setcookie('languages',  '', time() - 3600, '/');
setcookie('bio',        '', time() - 3600, '/');
setcookie('contract',   '', time() - 3600, '/');
setcookie('username',        '', time() - 3600, '/');

// Перенаправляем пользователя на страницу входа
header('Location: /project/login.php');
exit;
?>