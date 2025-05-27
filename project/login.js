// login.js — обрабатывает AJAX-авторизацию формы
// Подключается после загрузки DOM
document.addEventListener("DOMContentLoaded", function() {
    // Ссылка на форму входа
    const forma = document.getElementById("loginForm");

    // Обработчик события отправки формы
    forma.addEventListener("submit", (e) => {
        e.preventDefault(); // Отключаем стандартную отправку

        // Собираем данные формы в объект
        const formData = new FormData(e.target);
        const formObject = Object.fromEntries(formData.entries());

        // AJAX-запрос с JSON-данными
        fetch(e.target.action, {
            method: e.target.method,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(formObject),
        })
        .then(res => {
            // Если сервер сделал перенаправление, выполняем его на клиенте
            if (res.redirected) {
                window.location.href = res.url;
                return;
            }
            // Иначе ожидаем JSON-ответ
            return res.json();
        })
        .then(json => {
            // Если сервер вернул ошибку — отображаем сообщение
            if (json.error) {
                const errorEl = document.createElement("p");
                errorEl.className = "error-message";
                errorEl.textContent = json.error;
                errorEl.style.color = "red";
                errorEl.style.fontSize = "0.8rem";
                // Вставляем сообщение перед формой
                forma.insertBefore(errorEl, forma.firstChild);
            }
        })
        .catch(err => console.error(err)); // Логируем непредвиденные ошибки
    });
});
