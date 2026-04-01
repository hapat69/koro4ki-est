<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корочки.есть - <?php echo $page_title ?? 'Главная'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css для дополнительных анимаций -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>Корочки.есть
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_login']); ?>
                            </a>
                        </li>
                        <?php if ($_SESSION['user_role'] == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin.php">
                                    <i class="fas fa-cog me-1"></i>Админ-панель
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Выход
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Вход</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Регистрация</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Хлебные крошки -->
    <div class="container mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Главная</a></li>
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                if ($current_page == 'profile.php') {
                    echo '<li class="breadcrumb-item active">Личный кабинет</li>';
                } elseif ($current_page == 'admin.php') {
                    echo '<li class="breadcrumb-item active">Панель администратора</li>';
                } elseif ($current_page == 'login.php') {
                    echo '<li class="breadcrumb-item active">Вход</li>';
                } elseif ($current_page == 'register.php') {
                    echo '<li class="breadcrumb-item active">Регистрация</li>';
                }
                ?>
            </ol>
        </nav>
    </div>

    <!-- Основной контент (обертка) -->
    <main class="flex-fill">