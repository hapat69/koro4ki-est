    </main> <!-- Закрываем main из header.php -->

    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5><i class="fas fa-graduation-cap me-2"></i>Корочки.есть</h5>
                    <p class="small text-white-50">Портал дополнительного профессионального образования. Учитесь онлайн, получайте сертификаты и повышайте квалификацию.</p>
                    <div class="social-links">
                        <a href="#" class="text-white-50 me-2"><i class="fab fa-vk fa-lg"></i></a>
                        <a href="#" class="text-white-50 me-2"><i class="fab fa-telegram fa-lg"></i></a>
                        <a href="#" class="text-white-50 me-2"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <h5>Навигация</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white-50 text-decoration-none">Главная</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">О нас</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Курсы</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Контакты</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <h5>Пользователям</h5>
                    <ul class="list-unstyled">
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <li><a href="login.php" class="text-white-50 text-decoration-none">Вход</a></li>
                            <li><a href="register.php" class="text-white-50 text-decoration-none">Регистрация</a></li>
                        <?php else: ?>
                            <li><a href="profile.php" class="text-white-50 text-decoration-none">Личный кабинет</a></li>
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <li><a href="admin.php" class="text-white-50 text-decoration-none">Админ-панель</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <li><a href="#" class="text-white-50 text-decoration-none">Помощь</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Контакты</h5>
                    <ul class="list-unstyled text-white-50">
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> support@koro4ki.ru</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i> 8(800)123-45-67</li>
                        <li class="mb-2"><i class="fas fa-clock me-2"></i> Пн-Пт: 9:00 - 18:00</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> г. Москва, ул. Образования, 1</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/slider.js"></script>
    <script src="assets/js/main.js"></script>
    
    <!-- Дополнительный скрипт для коррекции высоты -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Функция для коррекции высоты main
        function adjustMainHeight() {
            const body = document.body;
            const html = document.documentElement;
            const main = document.querySelector('main');
            const footer = document.querySelector('footer');
            
            if (main && footer) {
                const windowHeight = window.innerHeight;
                const bodyHeight = Math.max(
                    body.scrollHeight,
                    body.offsetHeight,
                    html.clientHeight,
                    html.scrollHeight,
                    html.offsetHeight
                );
                
                // Если контента мало, растягиваем main
                if (bodyHeight < windowHeight) {
                    const footerHeight = footer.offsetHeight;
                    const headerHeight = document.querySelector('.navbar')?.offsetHeight || 0;
                    const breadcrumbHeight = document.querySelector('.breadcrumb')?.closest('.container')?.offsetHeight || 0;
                    const minMainHeight = windowHeight - footerHeight - headerHeight - breadcrumbHeight - 40; // 40px на отступы
                    
                    main.style.minHeight = minMainHeight + 'px';
                } else {
                    main.style.minHeight = '';
                }
            }
        }
        
        // Вызываем при загрузке
        adjustMainHeight();
        
        // Вызываем при изменении размера окна
        window.addEventListener('resize', adjustMainHeight);
    });
    </script>
</body>
</html>