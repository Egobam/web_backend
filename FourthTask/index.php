<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Заполните форму</h1>
    <div id="error-container" class="error-box" style="display: none;"></div>
    <form action="save.php" method="POST">
        <div class="form-group">
            <label for="fio">ФИО:</label><br>
            <input type="text" id="fio" name="fio" required>
        </div>

        <div class="form-group">
            <label for="phone">Телефон:</label><br>
            <input type="tel" id="phone" name="phone" required>
        </div>

        <div class="form-group">
            <label for="email">E-mail:</label><br>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="birthdate">Дата рождения:</label><br>
            <input type="date" id="birthdate" name="birthdate" required>
        </div>

        <div class="form-group radio-group">
            <label>Пол:</label><br>
            <input type="radio" id="male" name="gender" value="male" required>
            <label for="male">Мужской</label>
            <input type="radio" id="female" name="gender" value="female">
            <label for="female">Женский</label>
        </div>

        <div class="form-group">
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
            </select>
        </div>

        <div class="form-group">
            <label for="bio">Биография:</label><br>
            <textarea id="bio" name="bio" rows="5" required></textarea>
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" id="contract" name="contract" value="yes" required>
            <label for="contract">С контрактом ознакомлен(а)</label>
        </div>

        <input type="submit" value="Сохранить">
    </form>
</body>
</html>