<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

// Доступ только для администратора
require_admin();

// Обработка смены статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $cancel_reason = trim($_POST['cancel_reason'] ?? '');

    $valid_statuses = ['new', 'in_progress', 'done', 'cancelled'];
    
    if (in_array($new_status, $valid_statuses)) {
        if ($new_status === 'cancelled' && empty($cancel_reason)) {
            flash('error', 'Для отмены заявки обязательно укажите причину.');
        } else {
            // Если статус не отменен, очищаем причину
            if ($new_status !== 'cancelled') {
                $cancel_reason = null;
            }
            $stmt = $pdo->prepare("UPDATE requests SET status = ?, cancel_reason = ? WHERE id = ?");
            $stmt->execute([$new_status, $cancel_reason, $request_id]);
            flash('success', 'Статус заявки обновлен.');
        }
    }
    
    // Сохраняем параметры фильтров при редиректе
    $query = http_build_query([
        'status' => $_GET['status'] ?? '',
        'date' => $_GET['date'] ?? '',
        'search' => $_GET['search'] ?? ''
    ]);
    header('Location: /autodok/admin/panel.php?' . $query);
    exit;
}

// Сборка фильтров
$where = [];
$params = [];

if (!empty($_GET['status']) && in_array($_GET['status'], ['new', 'in_progress', 'done', 'cancelled'])) {
    $where[] = "r.status = ?";
    $params[] = $_GET['status'];
}

if (!empty($_GET['date'])) {
    $where[] = "DATE(r.visit_datetime) = ?";
    $params[] = $_GET['date'];
}

if (!empty($_GET['search'])) {
    $where[] = "r.car_plate LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Получаем записи
$sql = "
    SELECT r.*, s.name AS service_name, u.full_name, u.phone, rev.sentiment, rev.text AS review_text
    FROM requests r
    JOIN services s ON r.service_id = s.id
    JOIN users u ON r.user_id = u.id
    LEFT JOIN reviews rev ON r.id = rev.request_id
    $where_sql
    ORDER BY r.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

function get_status_badge($status) {
    switch ($status) {
        case 'new': return '<span class="badge bg-secondary">Новая</span>';
        case 'in_progress': return '<span class="badge bg-primary">Автомобиль в работе</span>';
        case 'done': return '<span class="badge bg-success">Работы выполнены</span>';
        case 'cancelled': return '<span class="badge bg-danger">Отменена</span>';
        default: return '<span class="badge bg-light text-dark">Неизвестно</span>';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4">Панель администратора</h2>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<!-- Блок фильтров -->
<div class="card mb-4 bg-light">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="">Все</option>
                    <option value="new" <?= ($_GET['status'] ?? '') === 'new' ? 'selected' : '' ?>>Новая</option>
                    <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                    <option value="done" <?= ($_GET['status'] ?? '') === 'done' ? 'selected' : '' ?>>Выполнена</option>
                    <option value="cancelled" <?= ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Отменена</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Дата визита</label>
                <input type="date" name="date" class="form-control" value="<?= e($_GET['date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Поиск по госномеру</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="А000АА00" value="<?= e($_GET['search'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary">Найти</button>
                    <a href="/autodok/admin/panel.php" class="btn btn-outline-secondary">Сброс</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Карточки записей -->
<?php if (empty($requests)): ?>
    <div class="alert alert-info">Записей не найдено.</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($requests as $req): ?>
            <div class="col-md-6 col-12 mb-3">
                <div class="card h-100 <?= $req['status'] === 'cancelled' ? 'border-danger' : '' ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= e($req['car_brand']) ?> (<?= e($req['car_plate']) ?>)</strong>
                        <?= get_status_badge($req['status']) ?>
                    </div>
                    <div class="card-body">
                        <p class="mb-2 small text-muted">
                            Клиент: <?= e($req['full_name']) ?> (<?= e($req['phone']) ?>)
                        </p>
                        <p class="card-text">
                            <strong>Услуга:</strong> <?= e($req['service_name']) ?><br>
                            <strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($req['visit_datetime'])) ?><br>
                            <strong>Оплата:</strong> <?= $req['payment_method'] === 'cash' ? 'Наличные' : 'Карта' ?>
                        </p>

                        <?php if ($req['status'] === 'cancelled'): ?>
                            <div class="alert alert-danger py-1 px-2 mb-2 small">
                                <strong>Причина:</strong> <?= e($req['cancel_reason']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($req['sentiment']): ?>
                            <div class="bg-light p-2 rounded mb-3 small">
                                <strong>Отзыв:</strong> 
                                <?= $req['sentiment'] === 'positive' ? '<span class="text-success">Положительный</span>' : '<span class="text-danger">Отрицательный</span>' ?>
                                <p class="mb-0 mt-1"><?= nl2br(e($req['review_text'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Форма смены статуса -->
                        <form method="POST" class="mt-auto border-top pt-3">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            
                            <div class="row g-2 align-items-end">
                                <div class="col-12">
                                    <label class="form-label small mb-1">Изменить статус:</label>
                                    <select name="status" class="form-select form-select-sm status-select" required>
                                        <option value="new" <?= $req['status'] === 'new' ? 'selected' : '' ?>>Новая</option>
                                        <option value="in_progress" <?= $req['status'] === 'in_progress' ? 'selected' : '' ?>>Автомобиль в работе</option>
                                        <option value="done" <?= $req['status'] === 'done' ? 'selected' : '' ?>>Работы выполнены</option>
                                        <option value="cancelled" <?= $req['status'] === 'cancelled' ? 'selected' : '' ?>>Отменена</option>
                                    </select>
                                </div>
                                
                                <div class="col-12 cancel-reason-block" style="display: <?= $req['status'] === 'cancelled' ? 'block' : 'none' ?>;">
                                    <input type="text" name="cancel_reason" class="form-control form-control-sm" 
                                           placeholder="Укажите причину отмены" 
                                           value="<?= e($req['cancel_reason']) ?>" 
                                           <?= $req['status'] === 'cancelled' ? 'required' : '' ?>>
                                </div>
                                
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-sm btn-primary">Сохранить</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>