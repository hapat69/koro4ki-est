<?php
require_once 'includes/config.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    redirect('login.php');
}

// Получение статистики
$total_applications = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$new_applications = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'new'")->fetchColumn();
$in_progress = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'in_progress'")->fetchColumn();
$completed = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'completed'")->fetchColumn();

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Фильтры
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Базовый запрос
$query = "
    SELECT a.*, u.full_name, u.login, u.email, u.phone, c.name as course_name 
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    JOIN courses c ON a.course_id = c.id 
    WHERE 1=1
";

$count_query = "SELECT COUNT(*) FROM applications a WHERE 1=1";
$params = [];
$count_params = [];

// Добавляем фильтр по статусу
if (!empty($status_filter)) {
    $query .= " AND a.status = :status";
    $count_query .= " AND status = :status";
    $params[':status'] = $status_filter;
    $count_params[':status'] = $status_filter;
}

// Добавляем поиск
if (!empty($search)) {
    $query .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR c.name LIKE :search)";
    $count_query .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR c.name LIKE :search)";
    $search_param = "%$search%";
    $params[':search'] = $search_param;
    $count_params[':search'] = $search_param;
}

// Добавляем сортировку
$query .= " ORDER BY a.created_at DESC";

// Получение общего количества записей
$stmt = $pdo->prepare($count_query);
if (!empty($count_params)) {
    foreach ($count_params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Добавляем LIMIT и OFFSET для пагинации
$query .= " LIMIT :limit OFFSET :offset";

// Подготовка и выполнение основного запроса
$stmt = $pdo->prepare($query);

// Привязываем все параметры
if (!empty($params)) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
}

// Привязываем параметры пагинации (как integers)
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

// Выполняем запрос
$stmt->execute();
$applications = $stmt->fetchAll();

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_status'])) {
    $app_id = (int)$_POST['app_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $app_id])) {
        $_SESSION['message'] = 'Статус заявки успешно изменен';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Ошибка при изменении статуса';
        $_SESSION['message_type'] = 'danger';
    }
    
    // Сохраняем текущие параметры для редиректа
    $redirect_params = [];
    if (!empty($status_filter)) {
        $redirect_params[] = "status=" . urlencode($status_filter);
    }
    if (!empty($search)) {
        $redirect_params[] = "search=" . urlencode($search);
    }
    $redirect_params[] = "page=" . $page;
    
    $redirect_url = 'admin.php';
    if (!empty($redirect_params)) {
        $redirect_url .= '?' . implode('&', $redirect_params);
    }
    
    redirect($redirect_url);
}

$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
unset($_SESSION['message'], $_SESSION['message_type']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Корочки.есть</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 10px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom-width: 2px;
        }
        .badge {
            padding: 8px 12px;
            font-size: 0.85em;
        }
        .action-btn {
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }
        .pagination .page-link {
            color: #007bff;
            transition: all 0.3s ease;
        }
        .pagination .page-link:hover {
            background-color: #007bff;
            color: white;
        }
        .search-highlight {
            background-color: #fff3cd;
            transition: background-color 0.5s ease;
        }
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.9rem;
            }
            .badge {
                padding: 4px 8px;
                font-size: 0.75em;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Всего заявок</h6>
                                <h2 class="mt-2 mb-0"><?php echo $total_applications; ?></h2>
                            </div>
                            <i class="fas fa-file-alt fa-3x opacity-50"></i>
                        </div>
                        <small class="d-block mt-2 opacity-75">
                            <i class="fas fa-arrow-up me-1"></i>За все время
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Новые</h6>
                                <h2 class="mt-2 mb-0"><?php echo $new_applications; ?></h2>
                            </div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                        <small class="d-block mt-2 opacity-75">
                            Требуют внимания
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">В обучении</h6>
                                <h2 class="mt-2 mb-0"><?php echo $in_progress; ?></h2>
                            </div>
                            <i class="fas fa-book-open fa-3x opacity-50"></i>
                        </div>
                        <small class="d-block mt-2 opacity-75">
                            Активные курсы
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Завершено</h6>
                                <h2 class="mt-2 mb-0"><?php echo $completed; ?></h2>
                            </div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                        <small class="d-block mt-2 opacity-75">
                            Успешно завершены
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Фильтры и поиск -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-3">
                        <label for="status" class="form-label fw-bold">
                            <i class="fas fa-filter me-1"></i>Статус
                        </label>
                        <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                            <option value="">Все статусы</option>
                            <option value="new" <?php echo $status_filter == 'new' ? 'selected' : ''; ?>>Новые</option>
                            <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>В обучении</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Завершены</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label for="search" class="form-label fw-bold">
                            <i class="fas fa-search me-1"></i>Поиск
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Поиск по ФИО, email или названию курса...">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Найти
                            </button>
                            <?php if (!empty($search) || !empty($status_filter)): ?>
                                <a href="admin.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Сбросить
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-info w-100" onclick="exportTable()">
                            <i class="fas fa-download me-2"></i>Экспорт
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Информация о результатах поиска -->
        <?php if (!empty($search) || !empty($status_filter)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Найдено записей: <strong><?php echo $total_records; ?></strong>
                <?php if (!empty($search)): ?>
                    по запросу "<?php echo htmlspecialchars($search); ?>"
                <?php endif; ?>
                <?php if (!empty($status_filter)): ?>
                    со статусом 
                    <?php 
                    $status_names = [
                        'new' => '"Новые"',
                        'in_progress' => '"В обучении"',
                        'completed' => '"Завершены"'
                    ];
                    echo $status_names[$status_filter] ?? '';
                    ?>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Таблица заявок -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Список заявок
                </h5>
                <span class="badge bg-light text-dark">
                    Страница <?php echo $page; ?> из <?php echo $total_pages ?: 1; ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($applications)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Заявок не найдено</p>
                        <?php if (!empty($search) || !empty($status_filter)): ?>
                            <a href="admin.php" class="btn btn-primary mt-3">
                                <i class="fas fa-times me-2"></i>Сбросить фильтры
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="applicationsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Пользователь</th>
                                    <th>Контактные данные</th>
                                    <th>Курс</th>
                                    <th>Дата начала</th>
                                    <th>Оплата</th>
                                    <th>Статус</th>
                                    <th>Дата заявки</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $index => $app): ?>
                                    <tr class="animate__animated animate__fadeIn" style="animation-delay: <?php echo $index * 0.05; ?>s">
                                        <td>
                                            <span class="badge bg-secondary">#<?php echo $app['id']; ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($app['full_name']); ?></strong><br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($app['login']); ?></small>
                                        </td>
                                        <td>
                                            <i class="fas fa-envelope text-primary me-1"></i>
                                            <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>">
                                                <?php echo htmlspecialchars($app['email']); ?>
                                            </a><br>
                                            <i class="fas fa-phone text-success me-1"></i>
                                            <?php echo htmlspecialchars($app['phone']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($app['course_name']); ?></td>
                                        <td>
                                            <i class="fas fa-calendar-alt text-info me-1"></i>
                                            <?php echo date('d.m.Y', strtotime($app['start_date'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($app['payment_method'] == 'cash'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-money-bill-wave me-1"></i>Наличные
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-mobile-alt me-1"></i>Перевод
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $app['status'] == 'new' ? 'bg-warning text-dark' : 
                                                    ($app['status'] == 'in_progress' ? 'bg-info' : 'bg-success'); 
                                            ?>">
                                                <i class="fas <?php 
                                                    echo $app['status'] == 'new' ? 'fa-clock' : 
                                                        ($app['status'] == 'in_progress' ? 'fa-book-open' : 'fa-check-circle'); 
                                                ?> me-1"></i>
                                                <?php 
                                                echo $app['status'] == 'new' ? 'Новая' : 
                                                    ($app['status'] == 'in_progress' ? 'В обучении' : 'Завершена'); 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-clock text-muted me-1"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary action-btn" 
                                                    onclick="showStatusModal(<?php echo $app['id']; ?>, '<?php echo $app['status']; ?>')"
                                                    data-bs-toggle="tooltip" title="Изменить статус">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-info action-btn" 
                                                    onclick="viewDetails(<?php echo $app['id']; ?>)"
                                                    data-bs-toggle="tooltip" title="Просмотр деталей">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php
                                // Показываем не все страницы, а только ближайшие
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1&status=' . urlencode($status_filter) . '&search=' . urlencode($search) . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++): 
                                ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        
                        <!-- Информация о записях -->
                        <div class="text-center text-muted small mt-2">
                            Показаны записи <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_records); ?> из <?php echo $total_records; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Модальное окно изменения статуса -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="statusModalLabel">
                        <i class="fas fa-edit me-2"></i>Изменить статус заявки
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="statusForm">
                    <div class="modal-body">
                        <input type="hidden" name="app_id" id="status_app_id">
                        
                        <div class="mb-3">
                            <label for="status_select" class="form-label fw-bold">Новый статус</label>
                            <select class="form-select" id="status_select" name="status" required>
                                <option value="new">🕒 Новая</option>
                                <option value="in_progress">📚 В обучении</option>
                                <option value="completed">✅ Завершена</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            После изменения статуса пользователь получит уведомление (в разработке)
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Отмена
                        </button>
                        <button type="submit" name="change_status" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно деталей заявки -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Детали заявки
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Содержимое будет загружаться через AJAX -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Инициализация tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Функция показа модального окна статуса
        function showStatusModal(appId, currentStatus) {
            document.getElementById('status_app_id').value = appId;
            document.getElementById('status_select').value = currentStatus;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        // Функция просмотра деталей
        function viewDetails(appId) {
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            const contentDiv = document.getElementById('detailsContent');
            
            // Показываем загрузку
            contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Загрузка...</span></div></div>';
            modal.show();
            
            // Загружаем данные (здесь можно сделать AJAX запрос)
            // Для примера показываем статические данные
            setTimeout(function() {
                contentDiv.innerHTML = `
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Информация о пользователе</h6>
                                <p class="mb-1"><i class="fas fa-user text-primary me-2"></i>${appId}</p>
                                <p class="mb-1"><i class="fas fa-envelope text-info me-2"></i>email@example.com</p>
                                <p class="mb-1"><i class="fas fa-phone text-success me-2"></i>8(999)123-45-67</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Детали заявки</h6>
                                <p class="mb-1"><i class="fas fa-book text-warning me-2"></i>Название курса</p>
                                <p class="mb-1"><i class="fas fa-calendar text-danger me-2"></i>01.01.2026</p>
                                <p class="mb-1"><i class="fas fa-credit-card text-success me-2"></i>Способ оплаты</p>
                            </div>
                        </div>
                    </div>
                `;
            }, 500);
        }

        // Экспорт таблицы в CSV
        function exportTable() {
            const table = document.getElementById('applicationsTable');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length - 1; j++) { // Пропускаем последний столбец с действиями
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/,/g, ';');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }
            
            const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            const downloadLink = document.createElement('a');
            downloadLink.download = 'applications_export.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        // Подсветка поисковых запросов
        <?php if (!empty($search)): ?>
        $(document).ready(function() {
            const searchTerm = "<?php echo addslashes($search); ?>";
            $('#applicationsTable tbody td:not(:last-child)').each(function() {
                const text = $(this).text();
                const regex = new RegExp('(' + searchTerm + ')', 'gi');
                if (regex.test(text)) {
                    $(this).html(text.replace(regex, '<span class="search-highlight">$1</span>'));
                }
            });
        });
        <?php endif; ?>

        // Автоматическое скрытие алертов
        setTimeout(function() {
            $('.alert-dismissible').alert('close');
        }, 5000);

        // Подтверждение при сбросе фильтров
        $('a[href="admin.php"]').on('click', function(e) {
            if ('<?php echo !empty($search) || !empty($status_filter); ?>' && 
                !confirm('Сбросить все фильтры?')) {
                e.preventDefault();
            }
        });

        // Анимация при наведении на строки
        $('#applicationsTable tbody tr').hover(
            function() { $(this).addClass('shadow-sm'); },
            function() { $(this).removeClass('shadow-sm'); }
        );
    </script>
</body>
</html>