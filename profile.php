<?php
require_once 'includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Получение списка курсов для формы заявки
$courses = $pdo->query("SELECT * FROM courses")->fetchAll();

// Получение заявок пользователя
$stmt = $pdo->prepare("
    SELECT a.*, c.name as course_name 
    FROM applications a 
    JOIN courses c ON a.course_id = c.id 
    WHERE a.user_id = ? 
    ORDER BY a.created_at DESC
");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();

// Обработка создания заявки
$application_errors = [];
$application_success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_application'])) {
    $course_id = $_POST['course_id'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    
    // Валидация даты
    if (empty($start_date)) {
        $application_errors[] = 'Укажите дату начала обучения';
    } else {
        // Проверка формата даты
        $date_obj = DateTime::createFromFormat('d.m.Y', $start_date);
        if (!$date_obj || $date_obj->format('d.m.Y') !== $start_date) {
            $application_errors[] = 'Неверный формат даты. Используйте ДД.ММ.ГГГГ';
        } else {
            $start_date_mysql = $date_obj->format('Y-m-d');
            $current_date = new DateTime(); // Текущая дата
            $current_date->setTime(0, 0, 0); // Убираем время для корректного сравнения
            
            // Проверка, что дата начала не раньше сегодняшнего дня
            if ($date_obj < $current_date) {
                $application_errors[] = 'Дата начала обучения не может быть раньше сегодняшнего дня';
            }
            
            // Проверка, что дата начала не сильно в будущем (например, не более 1 года)
            $max_future_date = (new DateTime())->modify('+1 year');
            if ($date_obj > $max_future_date) {
                $application_errors[] = 'Дата начала не может быть более чем через год от текущей даты';
            }
        }
    }
    
    // Валидация остальных полей
    if (empty($course_id)) {
        $application_errors[] = 'Выберите курс';
    }
    
    if (empty($payment_method)) {
        $application_errors[] = 'Выберите способ оплаты';
    }
    
    // Если ошибок нет, сохраняем заявку
    if (empty($application_errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO applications (user_id, course_id, start_date, payment_method, status) VALUES (?, ?, ?, ?, 'new')");
            if ($stmt->execute([$user_id, $course_id, $start_date_mysql, $payment_method])) {
                $application_success = true;
                // Перенаправляем, чтобы избежать повторной отправки формы
                redirect('profile.php?success=1');
            } else {
                $application_errors[] = 'Ошибка при создании заявки';
            }
        } catch (PDOException $e) {
            $application_errors[] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// Обработка отзыва
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_review'])) {
    $application_id = $_POST['application_id'] ?? '';
    $review_text = trim($_POST['review_text'] ?? '');
    $rating = $_POST['rating'] ?? 5;
    
    $review_errors = [];
    
    if (empty($review_text)) {
        $review_errors[] = 'Введите текст отзыва';
    }
    
    if (empty($review_errors)) {
        // Проверяем, что заявка завершена и принадлежит пользователю
        $stmt = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND user_id = ? AND status = 'completed'");
        $stmt->execute([$application_id, $user_id]);
        $app = $stmt->fetch();
        
        if ($app) {
            // Проверяем, не оставлял ли пользователь уже отзыв на эту заявку
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND application_id = ?");
            $stmt->execute([$user_id, $application_id]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, application_id, review_text, rating) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$user_id, $application_id, $review_text, $rating])) {
                    redirect('profile.php?review_success=1');
                } else {
                    $review_errors[] = 'Ошибка при сохранении отзыва';
                }
            } else {
                $review_errors[] = 'Вы уже оставили отзыв на этот курс';
            }
        } else {
            $review_errors[] = 'Отзыв можно оставить только после завершения обучения';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Корочки.есть</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .date-warning {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 5px;
            font-size: 0.9em;
            display: none;
        }
        .date-warning i {
            margin-right: 5px;
        }
        .date-error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .min-date-info {
            font-size: 0.85em;
            color: #6c757d;
            margin-top: 5px;
        }
        .calendar-icon {
            position: relative;
        }
        .calendar-icon input {
            padding-right: 40px;
        }
        .calendar-icon i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Заявка успешно создана! Администратор рассмотрит её в ближайшее время.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['review_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-star me-2"></i>
                Спасибо за ваш отзыв! Он поможет нам стать лучше.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Форма создания заявки -->
            <div class="col-md-5 mb-4">
                <div class="card shadow-sm animate__animated animate__fadeInLeft">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Новая заявка на обучение</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($application_errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($application_errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="applicationForm">
                            <div class="mb-3">
                                <label for="course_id" class="form-label fw-bold">
                                    <i class="fas fa-book me-1"></i>Выберите курс *
                                </label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">-- Выберите курс --</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" 
                                            <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="start_date" class="form-label fw-bold">
                                    <i class="fas fa-calendar-alt me-1"></i>Желаемая дата начала *
                                </label>
                                <div class="calendar-icon">
                                    <input type="text" 
                                           class="form-control" 
                                           id="start_date" 
                                           name="start_date" 
                                           value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" 
                                           placeholder="ДД.ММ.ГГГГ" 
                                           pattern="\d{2}\.\d{2}\.\d{4}"
                                           maxlength="10"
                                           required
                                           autocomplete="off">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Минимальная дата: <strong><?php echo date('d.m.Y'); ?></strong>
                                </div>
                                <div id="dateWarning" class="date-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span id="warningMessage"></span>
                                </div>
                                <div id="dateError" class="date-warning date-error" style="display: none;">
                                    <i class="fas fa-times-circle"></i>
                                    <span id="errorMessage"></span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-credit-card me-1"></i>Способ оплаты *
                                </label>
                                <div class="card p-3 bg-light">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="cash" value="cash" <?php echo (!isset($_POST['payment_method']) || $_POST['payment_method'] == 'cash') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cash">
                                            <i class="fas fa-money-bill-wave text-success me-2"></i>
                                            Наличные
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="transfer" value="transfer" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'transfer') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="transfer">
                                            <i class="fas fa-mobile-alt text-primary me-2"></i>
                                            Перевод по номеру телефона
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="create_application" class="btn btn-primary w-100 py-2" id="submitBtn">
                                <i class="fas fa-paper-plane me-2"></i>Отправить заявку
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Список заявок -->
            <div class="col-md-7 mb-4">
                <div class="card shadow-sm animate__animated animate__fadeInRight">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Мои заявки</h5>
                        <span class="badge bg-light text-dark">Всего: <?php echo count($applications); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($applications)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <p class="text-muted mb-0">У вас пока нет заявок</p>
                                <p class="text-muted small">Создайте первую заявку на обучение</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($applications as $index => $app): 
                                    $start_date = new DateTime($app['start_date']);
                                    $current_date = new DateTime();
                                    $is_expired = $start_date < $current_date && $app['status'] != 'completed';
                                ?>
                                    <div class="list-group-item list-group-item-action animate__animated animate__fadeIn" 
                                         style="animation-delay: <?php echo $index * 0.1; ?>s">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="mb-0 me-2"><?php echo htmlspecialchars($app['course_name']); ?></h6>
                                                    <?php if ($is_expired): ?>
                                                        <span class="badge bg-secondary" title="Дата начала прошла">
                                                            <i class="fas fa-clock"></i> Просрочена
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="row small">
                                                    <div class="col-md-6">
                                                        <p class="mb-1">
                                                            <i class="fas fa-calendar-plus text-primary me-1"></i>
                                                            Дата заявки: <?php echo date('d.m.Y', strtotime($app['created_at'])); ?>
                                                        </p>
                                                        <p class="mb-1">
                                                            <i class="fas fa-calendar-check text-success me-1"></i>
                                                            Начало: <?php echo date('d.m.Y', strtotime($app['start_date'])); ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1">
                                                            <i class="fas fa-credit-card text-info me-1"></i>
                                                            Оплата: <?php echo $app['payment_method'] == 'cash' ? 'Наличные' : 'Перевод'; ?>
                                                        </p>
                                                        <?php if ($app['status'] == 'completed'): ?>
                                                            <p class="mb-1">
                                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                                Завершена: <?php echo date('d.m.Y', strtotime($app['updated_at'] ?? $app['created_at'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="text-end ms-3">
                                                <span class="badge <?php 
                                                    echo $app['status'] == 'new' ? 'bg-warning text-dark' : 
                                                        ($app['status'] == 'in_progress' ? 'bg-info' : 'bg-success'); 
                                                ?> mb-2">
                                                    <i class="fas <?php 
                                                        echo $app['status'] == 'new' ? 'fa-clock' : 
                                                            ($app['status'] == 'in_progress' ? 'fa-book-open' : 'fa-check-circle'); 
                                                    ?> me-1"></i>
                                                    <?php 
                                                    echo $app['status'] == 'new' ? 'Новая' : 
                                                        ($app['status'] == 'in_progress' ? 'В обучении' : 'Завершена'); 
                                                    ?>
                                                </span>
                                                
                                                <?php if ($app['status'] == 'completed'): ?>
                                                    <button class="btn btn-sm btn-outline-primary w-100" 
                                                            onclick="showReviewForm(<?php echo $app['id']; ?>)"
                                                            data-bs-toggle="tooltip" 
                                                            title="Оставить отзыв о курсе">
                                                        <i class="fas fa-star me-1"></i>Отзыв
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для отзыва -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-star me-2"></i>Оставить отзыв
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="reviewForm">
                    <div class="modal-body">
                        <?php if (!empty($review_errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($review_errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="application_id" id="review_application_id">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Оцените курс</label>
                            <div class="rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                           id="star<?php echo $i; ?>" <?php echo $i == 5 ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> звезд">
                                        <i class="fas fa-star"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="review_text" class="form-label fw-bold">Ваш отзыв</label>
                            <textarea class="form-control" id="review_text" name="review_text" 
                                      rows="4" placeholder="Поделитесь своими впечатлениями о курсе..." 
                                      required maxlength="500"></textarea>
                            <div class="form-text">
                                <span id="reviewCount">0</span>/500 символов
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Отмена
                        </button>
                        <button type="submit" name="add_review" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Отправить отзыв
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Инициализация tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Функция показа модального окна отзыва
        function showReviewForm(applicationId) {
            document.getElementById('review_application_id').value = applicationId;
            document.getElementById('review_text').value = '';
            document.getElementById('reviewCount').textContent = '0';
            
            // Сброс рейтинга на 5 звезд
            document.getElementById('star5').checked = true;
            
            new bootstrap.Modal(document.getElementById('reviewModal')).show();
        }

        // Подсчет символов в отзыве
        document.getElementById('review_text')?.addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('reviewCount').textContent = count;
            
            if (count > 500) {
                this.value = this.value.substring(0, 500);
                document.getElementById('reviewCount').textContent = '500';
            }
        });

        // Валидация даты в реальном времени
        document.getElementById('start_date')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            // Автоматическая вставка точек
            if (value.length >= 2) {
                value = value.substring(0, 2) + '.' + value.substring(2);
            }
            if (value.length >= 5) {
                value = value.substring(0, 5) + '.' + value.substring(5, 9);
            }
            e.target.value = value.substring(0, 10);
            
            // Валидация даты
            validateDate(value.substring(0, 10));
        });

        // Функция валидации даты
        function validateDate(dateString) {
            const warningDiv = document.getElementById('dateWarning');
            const errorDiv = document.getElementById('dateError');
            const warningMessage = document.getElementById('warningMessage');
            const errorMessage = document.getElementById('errorMessage');
            const submitBtn = document.getElementById('submitBtn');
            
            // Скрываем все сообщения по умолчанию
            warningDiv.style.display = 'none';
            errorDiv.style.display = 'none';
            submitBtn.disabled = false;
            
            if (dateString.length === 10) {
                // Проверка формата
                const parts = dateString.split('.');
                if (parts.length === 3) {
                    const day = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10) - 1;
                    const year = parseInt(parts[2], 10);
                    
                    const date = new Date(year, month, day);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    // Проверка корректности даты
                    if (date.getDate() === day && date.getMonth() === month && date.getFullYear() === year) {
                        // Проверка на прошедшую дату
                        if (date < today) {
                            errorDiv.style.display = 'block';
                            errorMessage.textContent = 'Дата начала не может быть раньше сегодняшнего дня';
                            submitBtn.disabled = true;
                        }
                        // Проверка на слишком отдаленную дату (больше года)
                        else {
                            const nextYear = new Date(today);
                            nextYear.setFullYear(today.getFullYear() + 1);
                            
                            if (date > nextYear) {
                                warningDiv.style.display = 'block';
                                warningMessage.textContent = 'Дата начала более чем через год. Убедитесь, что курс будет доступен в это время.';
                            }
                            // Проверка на выходные (опционально)
                            else if (date.getDay() === 0 || date.getDay() === 6) {
                                warningDiv.style.display = 'block';
                                warningMessage.textContent = 'Вы выбрали выходной день. Обучение может не проводиться по выходным.';
                            }
                        }
                    } else {
                        errorDiv.style.display = 'block';
                        errorMessage.textContent = 'Введите корректную дату';
                        submitBtn.disabled = true;
                    }
                }
            }
        }

        // Блокировка отправки формы при невалидной дате
        document.getElementById('applicationForm')?.addEventListener('submit', function(e) {
            const dateInput = document.getElementById('start_date');
            const dateString = dateInput.value;
            
            if (dateString.length === 10) {
                const parts = dateString.split('.');
                const day = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const year = parseInt(parts[2], 10);
                
                const date = new Date(year, month, day);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (date < today) {
                    e.preventDefault();
                    alert('Дата начала не может быть раньше сегодняшнего дня!');
                    return false;
                }
            }
        });

        // Установка минимальной даты в HTML5 date picker (если используется)
        const today = new Date().toISOString().split('T')[0];
        
        // Для браузеров с поддержкой date input
        if (document.createElement('input').type === 'date') {
            // Можно раскомментировать, если хотите использовать нативный date picker
            // document.getElementById('start_date').type = 'date';
            // document.getElementById('start_date').min = today;
        }

        // Автоматическое заполнение сегодняшней даты при клике на иконку
        document.querySelector('.calendar-icon i')?.addEventListener('click', function() {
            const input = document.getElementById('start_date');
            if (!input.value) {
                const today = new Date();
                const day = String(today.getDate()).padStart(2, '0');
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const year = today.getFullYear();
                input.value = `${day}.${month}.${year}`;
                validateDate(input.value);
            }
        });

        // Подсветка просроченных заявок
        document.querySelectorAll('.list-group-item').forEach(item => {
            if (item.querySelector('.badge.bg-secondary')) {
                item.style.borderLeft = '3px solid #6c757d';
            }
        });
    </script>
</body>
</html>