window.onload = function () {
    // Обработка формы
    const form = document.getElementById('form_send');
    const status = document.getElementById('status');

    // Клиентская валидация
    function validateForm(data) {
        const errors = [];
        if (!data.fio || data.fio.length < 2) {
            errors.push('Имя должно содержать минимум 2 символа');
        }
        if (!data.phone || !/^\+?[0-9]{10,15}$/.test(data.phone)) {
            errors.push('Некорректный номер телефона');
        }
        if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            errors.push('Некорректный email');
        }
        if (!data.birthdate || !/^\d{4}-\d{2}-\d{2}$/.test(data.birthdate)) {
            errors.push('Некорректная дата рождения (формат: ГГГГ-ММ-ДД)');
        }
        if (!data.gender || !['male', 'female'].includes(data.gender)) {
            errors.push('Выберите пол');
        }
        if (!data.contract) {
            errors.push('Необходимо согласие на обработку персональных данных');
        }
        if (!data.languages || data.languages.length === 0) {
            errors.push('Выберите хотя бы один язык программирования');
        }
        return errors;
    }

    // Отображение ошибок
    function showErrors(errors) {
        status.innerHTML = errors.map(error => `<p style="color: red;">${error}</p>`).join('');
    }

    // Отображение успешного результата
    function showSuccess(data) {
        status.innerHTML = `
            <p style="color: green;">Регистрация успешна!</p>
            <p>Логин: ${data.login}</p>
            <p>Пароль: ${data.password}</p>
            <p>Профиль: <a href="${data.profile_url}">${data.profile_url}</a></p>
        `;
    }

    // Обработка отправки формы
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const languages = Array.from(document.getElementById('languages').selectedOptions).map(option => option.value);
            const formData = {
                fio: document.getElementById('fio').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                birthdate: document.getElementById('birthdate').value,
                gender: document.querySelector('input[name="gender"]:checked')?.value,
                bio: document.getElementById('bio').value,
                contract: document.getElementById('contract').checked,
                languages: languages
            };

            // Клиентская валидация
            const errors = validateForm(formData);
            if (errors.length > 0) {
                showErrors(errors);
                return;
            }

            // Отправка через Fetch
            fetch('/api/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json().then(data => ({ status: response.status, data })))
            .then(({ status, data }) => {
                if (status >= 400) {
                    showErrors(data.errors || [data.error]);
                } else {
                    showSuccess(data);
                }
            })
            .catch(error => {
                showErrors(['Ошибка соединения с сервером']);
            });
        });
    }

    // Бургер-меню
    $('.burger_menu').on('click', function(){
        $('body').toggleClass('menu_active');
    });

    // Слайдер для партнеров
    let setTimer;
    const partners = document.querySelector('.autoplay').innerHTML;
    let start = false;
    function slicker() {
        let sl_w = $('.partner:eq(0)').width(),
            cols = Math.round(window.innerWidth/sl_w) + 2;
        for(let i = 0; i < Math.round(cols / 3) + 1; i++)
            $('.autoplay, .autoplay2').append(partners);
  
        console.log(cols)
        if (start) {
            $('.autoplay').slick('unslick');
            $('.autoplay2').slick('unslick');
        }
        
        $('.autoplay').slick({
            infinite: true,
            slidesToShow: cols,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 2000,
            variableWidth: true
        });
        setTimeout(function(){
          $('.autoplay2').slick({
            infinite: true,
            slidesToShow: cols,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 2000,
            variableWidth: true
          });
        },800);
  
        sl_w = $('.partner:eq(0)').width();
        $('#companies .slick:eq(0)').css('margin-left', -sl_w + "px");
        $('#companies .slick:eq(1)').css('margin-left', -(sl_w / 2) + "px");
    }
    slicker();
    start = true;
    window.addEventListener("resize", function () {
        clearTimeout(setTimer);
        setTimer = setTimeout(() => { slicker(); }, 500);
    });

    // Тарифы
    $('.tarif_category:not(.active)').hover(function () {
      $('.tarif_category.active').removeClass('active');
    });
    $( ".tarif_category:not(.active)").on( "mouseleave", function() {
      $('.tarif_category:eq(1)').addClass('active');
    });

    // Отзывы
    $(".a").css('height', $('.aa > div:eq(0)').height());
    function aa(p){
        console.log(p)
        $('.aa > div').css('opacity', '0');
        setTimeout(function(){ $('.aa > div').css('display', 'block'); }, 0);
        $('.aa > div:eq(' + p + ')').css('display', 'block');
        setTimeout(function(){ $('.aa > div:eq(' + p + ')').css('opacity', '1'); }, 0);
        
        setTimeout(function(){
            $(".a").animate({
                'height': $('.aa > div:eq(' + p + ')').height()
            }, 300, "linear");
        }, 100);
  
        $('.ednum').html((p+1).toString().padStart(2, '0'))
    }
  
    // Листалка для отзывов
    let p = 0, pl = $('.aa > div').length - 1;
    $('.b1').on('click', function(){
        if(p == 0) p = pl;
        else p--;
        aa(p);
    });
    $('.b2').on('click', function(){
        if(p == pl) p = 0;
        else p++;
        aa(p);
    });

    // FAQ
    $('#AskList > div').on('click', function(){
        $('#AskList > div').removeClass('active');
        $(this).addClass('active');
    });
};