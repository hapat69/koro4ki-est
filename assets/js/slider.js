// Дополнительные настройки для слайдера
$(document).ready(function() {
    // Инициализация слайдера с дополнительными опциями
    $('#mainSlider').carousel({
        interval: 3000,
        pause: 'hover',
        wrap: true,
        keyboard: true
    });
    
    // Добавляем анимацию при смене слайдов
    $('#mainSlider').on('slide.bs.carousel', function(e) {
        const $activeItem = $(e.relatedTarget);
        $activeItem.find('.carousel-caption').css('animation', 'fadeInUp 0.5s ease-out');
    });
    
    // Индикатор загрузки изображений
    $('#mainSlider img').on('load', function() {
        $(this).css('opacity', '0').animate({ opacity: 1 }, 500);
    }).each(function() {
        if (this.complete) $(this).trigger('load');
    });
    
    // Добавляем эффект параллакса
    $('#mainSlider').on('mousemove', function(e) {
        const moveX = (e.pageX * -1 / 30);
        const moveY = (e.pageY * -1 / 30);
        $(this).find('.carousel-item.active img').css({
            'transform': 'translate(' + moveX + 'px, ' + moveY + 'px)'
        });
    });
});