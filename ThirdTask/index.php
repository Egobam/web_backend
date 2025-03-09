<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Заполните форму</h1>
    <form action="save.php" method="POST">
        <!-- ФИО -->
        <label for="fio">ФИО:</label><br>
        <input type="text" id="fio" name="fio" required><br>

        <!-- Телефон -->
        <label for="phone">Телефон:</label><br>
        <input type="tel" id="phone" name="phone" required><br>

        <!-- Email -->
        <label for="email">E-mail:</label><br>
        <input type="email" id="email" name="email" required><br>

        <!-- Дата рождения -->
        <label for="birthdate">Дата рождения:</label><br>
        <input type="date" id="birthdate" name="birthdate" required><br>

        <!-- Пол -->
        <label>Пол:</label><br>
        <input type="radio" id="male" name="gender" value="male" required>
        <label for="male">Мужской</label>
        <input type="radio" id="female" name="gender" value="female">
        <label for="female">Женский</label><br>

        <!-- Любимый язык программирования -->
        <label for="languages">Любимый язык программирования:</label><br>
        <select id="languages" name="languages[]" multiple required>
            <option value="Pascal">Pascal</option>
            <option value="C">C</option>
            <option value="C++">C++</option>
            <option value="JavaScript">JavaScript</option>
            <option value="PHP">PHP</option>
            <option value="Python">Python</option>
            <option value="Java">Java</option>
            <option value="Haskell">Haskell</option>
            <option value="Clojure">Clojure</option>
            <option value="Prolog">Prolog</option>
            <option value="Scala">Scala</option>
            <option value="Go">Go</option>
        </select><br>

        <!-- Биография -->
        <label for="bio">Биография:</label><br>
        <textarea id="bio" name="bio" rows="5" required></textarea><br>

        <!-- Чекбокс -->
        <input type="checkbox" id="contract" name="contract" value="yes" required>
        <label for="contract">С контрактом ознакомлен(а)</label><br>

        <!-- Кнопка -->
        <input type="submit" value="Сохранить">
    </form>
</body>
</html>