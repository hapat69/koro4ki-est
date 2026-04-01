<?php
require_once 'includes/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Валидация
    if (!validateLogin($login)) {
        $errors[] = 'Логин должен содержать минимум 6 символов (латиница и цифры)';
    }
    
    if (!validatePassword($password)) {
        $errors[] = 'Пароль должен содержать минимум 8 символов';
    }
    
    if (!validateFullName($full_name)) {
        $errors[] = 'ФИО должно содержать только кириллицу и пробелы';
    }
    
    if (!validatePhone($phone)) {
        $errors[] = 'Телефон должен быть в формате 8(XXX)XXX-XX-XX';
    }
    
    if (!validateEmail($email)) {
        $errors[] = 'Введите корректный email';
    }
    
    // Проверка уникальности логина
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким логином уже существует';
        }
    }
    
    // Сохранение пользователя
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (login, password, full_name, phone, email) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$login, $hashed_password, $full_name, $phone, $email])) {
            $success = true;
            // Автоматический вход после регистрации
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_login'] = $login;
            $_SESSION['user_role'] = 'user';
            redirect('profile.php');
        } else {
            $errors[] = 'Ошибка при регистрации';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Корочки.есть</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm animate-form">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Регистрация</h2>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="registerForm" novalidate>
                            <div class="mb-3">
                                <label for="login" class="form-label">Логин *</label>
                                <input type="text" class="form-control" id="login" name="login" 
                                       value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" 
                                       pattern="[a-zA-Z0-9]{6,}" 
                                       title="Минимум 6 символов (латиница и цифры)" required>
                                <div class="invalid-feedback">Логин должен содержать минимум 6 символов (латиница и цифры)</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Пароль должен содержать минимум 8 символов</div>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">ФИО *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                                       pattern="^[а-яА-ЯёЁ\s]+$" 
                                       title="Только кириллица и пробелы" required>
                                <div class="invalid-feedback">ФИО должно содержать только кириллицу и пробелы</div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Телефон *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                       placeholder="8(XXX)XXX-XX-XX" 
                                       pattern="8\(\d{3}\)\d{3}-\d{2}-\d{2}" required>
                                <div class="invalid-feedback">Формат: 8(XXX)XXX-XX-XX</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Введите корректный email</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Зарегистрироваться
                            </button>
                            
                            <p class="text-center mb-0">
                                Уже есть аккаунт? 
                                <a href="login.php" class="text-decoration-none">Войти</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Валидация формы в реальном времени
        (function() {
            'use strict';
            
            const forms = document.querySelectorAll('#registerForm');
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // Показать/скрыть пароль
            document.getElementById('togglePassword').addEventListener('click', function() {
                const password = document.getElementById('password');
                const icon = this.querySelector('i');
                
                if (password.type === 'password') {
                    password.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    password.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });

            // Автоматическое форматирование телефона
            document.getElementById('phone').addEventListener('input', function(e) {
                let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
                e.target.value = !x[2] ? x[1] : '8(' + x[2] + ')' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
            });
        })();
    </script>
</body>
</html>