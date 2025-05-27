(function ($) {
    window.onload = function () {
        let start = false;

        function slicker() {
            let vw = window.innerWidth;
            let vh = window.innerHeight;

            if (start) {
                $(".autoplay").slick("unslick");
                $(".autoplay2").slick("unslick");
            }

            if (vw >= 1000) {
                $(".autoplay").slick({
                    arrows: false,
                    dots: true,
                    infinite: true,
                    slidesToShow: 5,
                    slidesToScroll: 1,
                    autoplay: true,
                    autoplaySpeed: 2000,
                });

                setTimeout(function () {
                    $(".autoplay2").slick({
                        arrows: false,
                        dots: true,
                        infinite: true,
                        slidesToShow: 5,
                        slidesToScroll: 1,
                        autoplay: true,
                        autoplaySpeed: 2000,
                    });
                }, 800);
            }
        }

        slicker();
        start = true;
        window.addEventListener("resize", slicker);
    };

    $(".mob_menu").on("click", function () {
        $("body").toggleClass("menu_active");
    });

    $(".a").css("height", $(".aa > div:eq(0)").height());

    function aa(p) {
        $(".aa > div").css("opacity", "0");
        setTimeout(function () {
            $(".aa > div").css("display", "block");
        }, 0);

        $(".aa > div:eq(" + p + ")").css("display", "block");
        setTimeout(function () {
            $(".aa > div:eq(" + p + ")").css("opacity", "1");
        }, 0);

        setTimeout(function () {
            $(".a").animate({
                height: $(".aa > div:eq(" + p + ")").height()
            }, 300, "linear");
        }, 100);

        $(".ednum").html((p + 1).toString().padStart(2, "0"));
    }

    let p = 0;
    let pl = $(".aa > div").length - 1;

    $(".b2").on("click", function () {
        if (p == 0) p = pl;
        else p--;
        aa(p);
    });

    $(".b1").on("click", function () {
        if (p == pl) p = 0;
        else p++;
        aa(p);
    });

    $(document).ready(function initSelect2() {
        $('select[name="language[]"]').select2({
            placeholder: "Выберите языки",
            allowClear: true,
            width: '100%'
        });
    $('.form').on('submit', function(e) {
      e.preventDefault();
      const form = $(this);
      const formData = new FormData(this);
      
      const languages = $('select[name="language[]"]').val() || [];
      formData.set('language', languages.join(','));
      
      const submitBtn = form.find('button[type="submit"]');
      const originalText = submitBtn.text();
      submitBtn.prop('disabled', true).text('Отправка...');

      $('.mess, .mess_info').empty();
      $('.input').removeClass('red');
      $('.error').empty();
      
      $.ajax({
        url: 'index.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
success: function(response) {
             console.log("Ответ сервера:", response);
    if (response.success) {
        $('.mess').html('<div class="success">' + response.message + '</div>');
        
        if (response.info) {
            $('.mess_info').html('<div class="info">' + response.info + '</div>');
        }
        
        
        $('.error').empty();
        $('.input').removeClass('red');
    } else {
        $('.error').empty();
        $('.input').removeClass('red');
        
       
        if (response.errors) {
            for (const field in response.errors) {
                const errorMessage = response.errors[field];
                const input = form.find('[name="' + field + '"]');
                
                
                if (field === 'radio') {
                    form.find('input[name="radio"]').closest('div').next('.error').html(errorMessage);
                    form.find('input[name="radio"]').closest('div').find('span').addClass('error-text');
                } else {
                    input.addClass('red');
                    input.closest('div').find('.error').html(errorMessage);
                }
            }
        }
    }
        },
        error: function(xhr, status, error) {
          console.error("Ошибка AJAX:", status, error);
          $('.mess').html('<div class="error">Ошибка при отправке формы: ' + error + '</div>');
        },
        complete: function() {
          submitBtn.prop('disabled', false).text(originalText);
        }
      });
    });
        $('button[name="edit_form"]').off('click').on('click', function (e) {
            const originalText = $(this).text();
            setTimeout(() => {
                $(this).text(originalText);
            }, 50);
        });
    $('button[name="logout_form"]').on('click', function(e) {
      e.preventDefault();
      $.ajax({
        url: 'index.php',
        type: 'POST',
        data: { logout_form: true },
        success: function() {
          location.reload();
        }
      });
    });
    });
    $(document).ajaxComplete(function () {
        $('button[name="edit_form"]').text('Изменить');
        $('button[name="logout_form"]').text('Выйти');
    });
})(jQuery);