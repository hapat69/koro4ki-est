$(document).ready(function() {
    // Анимация появления элементов при скролле
    function checkVisibility() {
        $('.scroll-animate').each(function() {
            const elementTop = $(this).offset().top;
            const elementBottom = elementTop + $(this).outerHeight();
            const viewportTop = $(window).scrollTop();
            const viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $(this).addClass('visible');
            }
        });
    }
    
    // Проверяем при загрузке и при скролле
    checkVisibility();
    $(window).on('scroll', checkVisibility);
    
    // Автоматическое закрытие алертов
    setTimeout(function() {
        $('.alert-dismissible').alert('close');
    }, 5000);
    
    // Валидация форм в реальном времени
    $('form').on('input', 'input, select, textarea', function() {
        if ($(this).hasClass('is-invalid')) {
            if ($(this).val().trim() !== '') {
                $(this).removeClass('is-invalid').addClass('is-valid');
            }
        }
    });
    
    // Подтверждение действий
    $('.confirm-action').on('click', function(e) {
        if (!confirm('Вы уверены, что хотите выполнить это действие?')) {
            e.preventDefault();
        }
    });
    
    // Плавный скролл к якорям
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 70
            }, 800);
        }
    });
    
    // Анимация для кнопок
    $('.btn').on('mousedown', function() {
        $(this).addClass('scale-95');
    }).on('mouseup mouseleave', function() {
        $(this).removeClass('scale-95');
    });
    
    // Загрузка данных при скролле (бесконечная прокрутка)
    let isLoading = false;
    $(window).on('scroll', function() {
        if ($(window).scrollTop() + $(window).height() > $(document).height() - 200) {
            if (!isLoading && $('#load-more').length) {
                isLoading = true;
                $('#load-more').trigger('click');
            }
        }
    });
    
    // Маска для телефона (дополнительная)
    if ($.fn.mask) {
        $('.phone-mask').mask('8(000)000-00-00');
    }
    
    // Инициализация всплывающих подсказок
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Темная/светлая тема
    $('#theme-toggle').on('click', function() {
        $('body').toggleClass('dark-theme');
        const isDark = $('body').hasClass('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        
        if (isDark) {
            $(this).html('<i class="fas fa-sun"></i> Светлая тема');
        } else {
            $(this).html('<i class="fas fa-moon"></i> Темная тема');
        }
    });
    
    // Проверка сохраненной темы
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        $('body').addClass('dark-theme');
        $('#theme-toggle').html('<i class="fas fa-sun"></i> Светлая тема');
    }
    
    // Фильтрация таблиц
    $('#table-search').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('.table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Сортировка таблиц
    $('.sortable').on('click', function() {
        const $table = $(this).closest('table');
        const $rows = $table.find('tbody tr').get();
        const index = $(this).index();
        const isAscending = $(this).hasClass('asc');
        
        $rows.sort(function(a, b) {
            const aVal = $(a).children('td').eq(index).text();
            const bVal = $(b).children('td').eq(index).text();
            
            if ($.isNumeric(aVal) && $.isNumeric(bVal)) {
                return isAscending ? aVal - bVal : bVal - aVal;
            }
            return isAscending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        });
        
        $.each($rows, function(index, row) {
            $table.children('tbody').append(row);
        });
        
        $(this).toggleClass('asc desc');
    });
    
    // Добавляем класс для анимации
    $('style').append(`
        .scale-95 {
            transform: scale(0.95);
            transition: transform 0.1s ease;
        }
    `);
});

// Обработка ошибок AJAX
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    console.error('AJAX Error:', thrownError);
    alert('Произошла ошибка при выполнении запроса. Пожалуйста, попробуйте позже.');
});