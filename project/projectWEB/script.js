window.onload = function () {
    //burger
    $('.burger_menu').on('click', function(){
        $('body').toggleClass('menu_active');
    });

    // for partners
    // сликер для двух полосок картинок, причем они не должны листаться синхронно + они листаются автоматически
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
  //

    // for tarifs
    // при наведении блок увеличивается
    $('.tarif_category:not(.active)').hover(function () {
      $('.tarif_category.active').removeClass('active');
    });
    $( ".tarif_category:not(.active)").on( "mouseleave", function() {
      $('.tarif_category:eq(1)').addClass('active');
    } );
  //

    // for reviews
    // для того чтобы какие-то отзывы показывались а какие-то нет + для подсчета там при пролистывании
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
  
    // для листалки
    p = 0, pl = $('.aa > div').length - 1;
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
  //

    // for FAQ
    $('#AskList > div').on('click', function(){
        $('#AskList > div').removeClass('active');
        $(this).addClass('active');
    });
  };
