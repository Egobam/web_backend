```php
<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Проверка, если пользователь уже авторизован
if (!empty($_SESSION['login'])) {
    header('Location: ./');
    exit;
}

// Конфигурация базы данных
$config = [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'u68918',
        'user' => 'u68918',
        'pass' => '7758388',
        'options' => [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    ]
];

try {
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8",
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage() . ' | File: ' . __FILE__ . ' | Line: ' . __LINE__);
    die('Ошибка подключения к базе данных');
}

// Функция для CSRF-токена
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Недействительный CSRF-токен';
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login)) {
            $error = 'Введите логин';
        } elseif (empty($password)) {
            $error = 'Введите пароль';
        } else {
            try {
                $stmt = $db->prepare("SELECT id, role, password FROM users WHERE login = ?");
                $stmt->execute([$login]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user_data && password_verify($password, $user_data['password'])) {
                    $_SESSION['login'] = $login;
                    $_SESSION['user_id'] = $user_data['id'];
                    $_SESSION['role'] = $user_data['role'];
                    unset($_SESSION['csrf_token']);

                    if ($user_data['role'] === 'admin') {
                        header('Location: admin.php');
                    } else {
                        header('Location: form.php#footer');
                    }
                    exit;
                } else {
                    $error = 'Неверный логин или пароль';
                }
            } catch (PDOException $e) {
                error_log('Database error: ' . $e->getMessage() . ' | File: ' . __FILE__ . ' | Line: ' . __LINE__);
                $error = 'Ошибка базы данных';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Авторизация</title>
    <style>
        .form-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .error-message {
            color: red;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .spinner {
            display: none;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">Авторизация</h2>
            <?php if ($error): ?>
                <div class="error-message text-center"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="" method="post" id="loginForm" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                <div class="mb-3">
                    <label for="login" class="form-label">Логин</label>
                    <input type="text" class="form-control" id="login" name="login" placeholder="Введите логин" aria-describedby="loginError" required>
                    <div id="loginError" class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Введите пароль" aria-describedby="passwordError" required>
                    <div id="passwordError" class="invalid-feedback"></div>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="submitBtn">Войти <span class="spinner-border spinner-border-sm spinner" role="status" aria-hidden="true"></span></button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            const spinner = submitBtn.querySelector('.spinner');
            const login = document.getElementById('login');
            const password = document.getElementById('password');
            const loginError = document.getElementById('loginError');
            const passwordError = document.getElementById('passwordError');
            const formError = document.querySelector('.error-message');

            // Сброс ошибок
            formError.textContent = '';
            loginError.textContent = '';
            passwordError.textContent = '';
            login.classList.remove('is-invalid');
            password.classList.remove('is-invalid');

            let hasError = false;
            if (!login.value.trim()) {
                loginError.textContent = 'Введите логин';
                login.classList.add('is-invalid');
                hasError = true;
            }
            if (!password.value) {
                passwordError.textContent = 'Введите пароль';
                password.classList.add('is-invalid');
                hasError = true;
            }

            if (!hasError) {
                submitBtn.disabled = true;
                spinner.style.display = 'inline-block';

                const formData = new FormData(form);
                fetch('', { method: 'POST', body: formData })
                    .then(response => response.text())
                    .then(data => {
                        submitBtn.disabled = false;
                        spinner.style.display = 'none';
                        try {
                            const json = JSON.parse(data);
                            formError.textContent = json.error || 'Произошла ошибка';
                        } catch (e) {
                            // Редирект уже произошёл
                        }
                    })
                    .catch(() => {
                        submitBtn.disabled = false;
                        spinner.style.display = 'none';
                        formError.textContent = 'Произошла ошибка при отправке';
                    });
            }
        });
    </script>
</body>
</html>
```