document.querySelector('.header__burger').addEventListener('click', function () {
    document.querySelector('.header__burger').classList.toggle('active');
    document.querySelector('.header__list').classList.toggle('active');
    document.querySelector('.header__button').classList.toggle('active');
    document.querySelector('body').classList.toggle('lock');
});

//$(".phone_mask").mask("+7(999)999-99-99");

function fun1() {
    var chbox;
    chbox = document.getElementById('pasport');
    if (chbox.checked) {
        document.querySelector('.form__input-pasport').classList.add('active');
    }
    else {
        document.querySelector('.form__input-pasport').classList.remove('active');
    }
}
function fun2() {
    var chbox;
    chbox = document.getElementById('car');
    if (chbox.checked) {
        document.querySelector('.form__input-car').classList.add('active');
    }
    else {
        document.querySelector('.form__input-car').classList.remove('active');
    }
}
new Swiper('.partners__block', {
    navigation: {
        nextEl: '.swiper-button-nex',
        prevEl: '.swiper-button-pre',
    },
    slidesPerView: 4,
    slidesPerGroup: 1,
    breakpoints: {
        992: {
            slidesPerView: 3,
            slidesPerGroup: 1,
        },
        767: {
            slidesPerView: 2,
            slidesPerGroup: 1,
        },
        500: {
            spaceBetween: 20,
            slidesPerView: 1,
            slidesPerGroup: 1,
        },
        200: {
            spaceBetween: 20,
            slidesPerView: 1,
            slidesPerGroup: 1,
        },
    }
});
document.querySelector('.arrow-span1').addEventListener('click', function () {
    document.querySelector('.header__sub-List1').classList.toggle('active');
});
document.querySelector('.arrow-span2').addEventListener('click', function () {
    document.querySelector('.header__sub-List2').classList.toggle('active');
});
document.querySelector('.arrow-span3').addEventListener('click', function () {
    document.querySelector('.header__sub-List3').classList.toggle('active');
});






const animItems = document.querySelectorAll('._anim-items');

if (animItems.length > 0) {
    window.addEventListener('scroll', animOnScroll);
    function animOnScroll() {
        for (let index = 0; index < animItems.length; index++) {
            const animItem = animItems[index];
            const animItemHeight = animItem.offsetHeight;
            const animItemOffset = offset(animItem).top;
            const animStart = 4;

            let animItemPoint = window.innerHeight - animItemHeight / animStart;
            if (animItemHeight > window.innerHeight) {
                animItemPoint = window.innerHeight - window.innerHeight / animStart;
            }

            if ((pageYOffset > animItemOffset - animItemPoint) && pageYOffset < (animItemOffset + animItemHeight)) {
                animItem.classList.add('_active');
            } else {
                if (!animItem.classList.contains('_anim-no-hide')) {
                    animItem.classList.remove('_active');
                }
            }
        }
    }
    function offset(el) {
        const rect = el.getBoundingClientRect(),
            scrollLeft = window.pageXOffset || document.documentElement.scrollLeft,
            scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        return { top: rect.top + scrollTop, left: rect.left + scrollLeft }
    }

    setTimeout(() => {
        animOnScroll();
    }, 300);
}




