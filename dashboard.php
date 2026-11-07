<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

require_login();

$user_id = $_SESSION['user_id'];

// Обработка отправки отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $sentiment = $_POST['sentiment'] ?? '';
    $text = trim($_POST['text'] ?? '');

    // Проверяем, что заявка принадлежит юзеру, имеет статус 'done' и отзыва еще нет
    $stmt = $pdo->prepare("
        SELECT r.id 
        FROM requests r 
        LEFT JOIN reviews rev ON r.id = rev.request_id 
        WHERE r.id = ? AND r.user_id = ? AND r.status = 'done' AND rev.id IS NULL
    ");
    $stmt->execute([$request_id, $user_id]);
    
    if ($stmt->fetch()) {
        if (in_array($sentiment, ['positive', 'negative']) && !empty($text)) {
            $stmt = $pdo->prepare("INSERT INTO reviews (request_id, sentiment, text) VALUES (?, ?, ?)");
            $stmt->execute([$request_id, $sentiment, $text]);
            flash('success', 'Спасибо! Ваш отзыв сохранен.');
        } else {
            flash('error', 'Выберите тип отзыва и напишите текст.');
        }
    } else {
        flash('error', 'Оставить отзыв нельзя.');
    }
    
    header('Location: /autodok/dashboard.php');
    exit;
}

// Получаем все заявки пользователя с названием услуги и отзывом (если есть)
$stmt = $pdo->prepare("
    SELECT r.*, s.name AS service_name, rev.sentiment, rev.text AS review_text
    FROM requests r
    JOIN services s ON r.service_id = s.id
    LEFT JOIN reviews rev ON r.id = rev.request_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

// Хелпер для перевода статуса
function get_status_badge($status) {
    switch ($status) {
        case 'new': return '<span class="badge bg-secondary">Новая</span>';
        case 'in_progress': return '<span class="badge bg-primary">Автомобиль в работе</span>';
        case 'done': return '<span class="badge bg-success">Работы выполнены</span>';
        case 'cancelled': return '<span class="badge bg-danger">Отменена</span>';
        default: return '<span class="badge bg-light text-dark">Неизвестно</span>';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Мои записи</h2>
    <a href="/autodok/create_request.php" class="btn btn-primary">Новая запись</a>
</div>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<?php if (empty($requests)): ?>
    <div class="alert alert-info">У вас пока нет записей.</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($requests as $req): ?>
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title"><?= e($req['car_brand']) ?> <small class="text-muted">(<?= e($req['car_plate']) ?>)</small></h5>
                            <?= get_status_badge($req['status']) ?>
                        </div>
                        
                        <p class="card-text mb-1">
                            <strong>Услуга:</strong> <?= e($req['service_name']) ?><br>
                            <strong>Дата и время:</strong> <?= date('d.m.Y H:i', strtotime($req['visit_datetime'])) ?><br>
                            <strong>Оплата:</strong> <?= $req['payment_method'] === 'cash' ? 'Наличными' : 'Банковской картой' ?>
                        </p>

                        <?php if ($req['status'] === 'cancelled'): ?>
                            <div class="alert alert-danger py-2 mt-2 mb-0">
                                <strong>Причина отмены:</strong> <?= e($req['cancel_reason']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($req['status'] === 'done'): ?>
                            <?php if ($req['sentiment']): ?>
                                <div class="mt-3 p-3 bg-light rounded">
                                    <strong>Ваш отзыв:</strong> 
                                    <?= $req['sentiment'] === 'positive' ? '<span class="text-success">Положительный</span>' : '<span class="text-danger">Отрицательный</span>' ?>
                                    <p class="mb-0 mt-1"><?= nl2br(e($req['review_text'])) ?></p>
                                </div>
                            <?php else: ?>
                                <hr>
                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="action" value="add_review">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <h6>Оставить отзыв о качестве ремонта</h6>
                                    <div class="mb-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="sentiment" id="pos_<?= $req['id'] ?>" value="positive" required>
                                            <label class="form-check-label text-success" for="pos_<?= $req['id'] ?>">Положительный</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="sentiment" id="neg_<?= $req['id'] ?>" value="negative" required>
                                            <label class="form-check-label text-danger" for="neg_<?= $req['id'] ?>">Отрицательный</label>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <textarea name="text" class="form-control" rows="2" placeholder="Текст отзыва..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-success">Отправить отзыв</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>