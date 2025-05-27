// edit.js — логика проверки и отправки формы редактирования

// Сообщения об ошибках для каждого поля
const fielderrors = {
    name: "ФИО должно быть полностью на кириллице: фамилия, имя и отчество, разделённые пробелами. Разрешены только буквы, минимум три слова (например: Петров Иван Сергеевич).",
    phone: "Укажите номер телефона в формате +XXXXXXXXXXX. Допустимы только цифры после знака '+'.",
    email: "Введите действительный адрес электронной почты в формате username@domain.com. Разрешены буквы, цифры, точки, дефисы и символ '@'.",
    dob: "Укажите дату рождения в формате ГГГГ-ММ-ДД (например: 2000-01-01).",
    gender: "Поле обязательно. Выберите «М» (мужской) или «Ж» (женский).",
    languages: "Выберите как минимум один язык программирования из предложенного списка. Допускается множественный выбор.",
    bio: "Поле может содержать буквы (кириллица и латиница), цифры, пробелы и символы: \";,.:-!?\".",
};

document.addEventListener("DOMContentLoaded", function() {
    const forma = document.getElementById("forma");          // Находим форму по идентификатору
    addErrors();                                            // Добавляем элементы для ошибок

    forma.addEventListener("submit", (e) => {
        e.preventDefault();                                 // Отменяем стандартную отправку
        // Убираем предшествующие метки об ошибках
        forma.querySelectorAll('.error').forEach(input => {
            input.classList.remove('error');
        });
        forma.querySelectorAll('.error-message').forEach(el => {
            el.style.display = 'none';
        });

        const formData = new FormData(e.target);            // Читаем данные формы
        const validationResult = validate(formData);        // Выполняем валидацию

        if (validationResult) {
            showErrors(validationResult);                   // Отображаем найденные ошибки
        } else {
            // Подготавливаем объект для отправки
            const formObject = Object.fromEntries(formData.entries());
            formObject.languages = formData.getAll("languages"); // Собираем все выбранные языки
            // С соглашением всё давно прописано, повторять не нужно

            // Отправляем JSON на сервер
            fetch(e.target.action, {
                method: e.target.method,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(formObject),
            })
            .then(res => res.json())                      // Читаем JSON из ответа
            .then(data => {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url; // Редирект при успехе
                }
            })
            .catch(err => console.error(err));             // Логируем ошибки сети
        }
    });
});

// Добавляет скрытые контейнеры для сообщений об ошибках под каждым полем
const addErrors = () => {
    for (const [field, message] of Object.entries(fielderrors)) {
        const input = forma.querySelector(`[name="${field}"]`);
        if (!input) {
            console.warn(`Поле с name="${field}" не найдено`);
            continue;
        }
        const errorEl = document.createElement("div");
        errorEl.className = "error-message";
        errorEl.textContent = message;
        errorEl.dataset.for = field;
        errorEl.style.color = "red";
        errorEl.style.fontSize = "0.8rem";
        errorEl.style.display = "none";
        input.parentNode.insertBefore(errorEl, input);
    }
};

// Выводит ошибки в соответствующие элементы формы
const showErrors = (errors) => {
    for (const [field, message] of Object.entries(errors)) {
        const input = forma.querySelector(`[name="${field}"]`);
        const errorEl = forma.querySelector(`.error-message[data-for="${field}"]`);
        if (input && errorEl) {
            input.classList.add('error');                // Подсветка поля
            errorEl.style.display = 'block';             // Показ сообщения
        } else {
            console.warn(`Не найдены элементы для поля: ${field}`);
        }
    }
};

// Проверяет все поля и возвращает объект с ошибками или null
const validate = (data) => {
    const errors = {};

    // ФИО
    if (!data.get("name") || !/^[a-zA-Zа-яА-ЯёЁ]{1,}\s[a-zA-Zа-яА-ЯёЁ]{1,}\s[a-zA-Zа-яА-ЯёЁ]{1,}$/.test(data.get("name"))) {
        errors.name = "Invalid name";
    }

    // Телефон
    if (!data.get("phone") || !/^\+[0-9]{1,29}$/.test(data.get("phone"))) {
        errors.phone = "Invalid phone";
    }

    // Email
    if (!data.get("email") || !/^[A-Za-z0-9._%+-]{1,30}@[A-Za-z0-9.-]{1,20}\.[A-Za-z]{1,10}$/.test(data.get("email"))) {
        errors.email = "Invalid email";
    }

    // Дата рождения
    if (!data.get("dob") || !/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/.test(data.get("dob"))) {
        errors.dob = "Invalid date";
    }

    // Пол
    if (!["male", "female"].includes(data.get("gender"))) {
        errors.gender = "Invalid gender";
    }

    // Языки
    if (!data.getAll("languages")?.length) {
        errors.languages = "Favourite langs are required";
    }

    // Биография
    if (data.get("bio") && !/^[A-Za-zА-Яа-яЁё;,.:0-9\-!?""\s]*$/.test(data.get("bio"))) {
        errors.bio = "Invalid Bio";
    }

    return Object.keys(errors).length ? errors : null;
};
