<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корочки.есть - Главная</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-4">
        <!-- Слайдер -->
        <div id="mainSlider" class="carousel slide mb-5" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="2"></button>
                <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="3"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active" data-bs-interval="3000">
                    <img src="assets/images/slider/slide1.jpg" class="d-block w-100" alt="Курсы программирования">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Основы алгоритмизации</h5>
                        <p>Начните свой путь в программировании</p>
                    </div>
                </div>
                <div class="carousel-item" data-bs-interval="3000">
                    <img src="assets/images/slider/slide2.jpg" class="d-block w-100" alt="Веб-дизайн">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Веб-дизайн</h5>
                        <p>Создавайте красивые сайты</p>
                    </div>
                </div>
                <div class="carousel-item" data-bs-interval="3000">
                    <img src="assets/images/slider/slide3.jpg" class="d-block w-100" alt="Базы данных">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Базы данных</h5>
                        <p>Управляйте данными эффективно</p>
                    </div>
                </div>
                <div class="carousel-item" data-bs-interval="3000">
                    <img src="assets/images/slider/slide4.jpg" class="d-block w-100" alt="Обучение">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>ДПО образование</h5>
                        <p>Повышайте квалификацию</p>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Предыдущий</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Следующий</span>
            </button>
        </div>

        <!-- Информационные блоки -->
        <div class="row mt-5">
            <div class="col-md-4 mb-4">
                <div class="card h-100 animate-card">
                    <div class="card-body text-center">
                        <i class="fas fa-laptop-code fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Онлайн курсы</h5>
                        <p class="card-text">Современные программы дополнительного профессионального образования</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 animate-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3 text-success"></i>
                        <h5 class="card-title">Гибкий график</h5>
                        <p class="card-text">Выбирайте удобное время начала обучения</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 animate-card">
                    <div class="card-body text-center">
                        <i class="fas fa-certificate fa-3x mb-3 text-warning"></i>
                        <h5 class="card-title">Сертификаты</h5>
                        <p class="card-text">Получите официальный документ об образовании</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/slider.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>