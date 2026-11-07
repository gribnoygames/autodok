<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

// Доступ только для авторизованных пользователей
require_login();

// Получаем список услуг для выпадающего списка
$services = $pdo->query("SELECT id, name FROM services ORDER BY name")->fetchAll();

$errors = [];
$car_brand = $car_plate = $service_id = $visit_datetime = $payment_method = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_brand = trim($_POST['car_brand'] ?? '');
    $car_plate = trim($_POST['car_plate'] ?? '');
    $service_id = $_POST['service_id'] ?? '';
    $visit_datetime = $_POST['visit_datetime'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    // Валидация марки авто
    if (empty($car_brand)) {
        $errors['car_brand'] = 'Укажите марку автомобиля.';
    }

    // Валидация госномера (формат А000АА00)
    if (!preg_match('/^[АВЕКМНОРСТУХ]\d{3}[АВЕКМНОРСТУХ]{2}\d{2}$/u', $car_plate)) {
        $errors['car_plate'] = 'Формат номера: А000АА00 (буквы кириллицей).';
    }

    // Валидация услуги
    if (empty($service_id) || !is_numeric($service_id)) {
        $errors['service_id'] = 'Выберите вид услуги.';
    }

    // Валидация даты и времени
    if (empty($visit_datetime)) {
        $errors['visit_datetime'] = 'Укажите дату и время визита.';
    } else {
        // Конвертируем формат datetime-local (YYYY-MM-DDTHH:MM) в формат MySQL (YYYY-MM-DD HH:MM:SS)
        $visit_datetime = str_replace('T', ' ', $visit_datetime) . ':00';
    }

    // Валидация оплаты
    if (!in_array($payment_method, ['cash', 'card'])) {
        $errors['payment_method'] = 'Выберите способ оплаты.';
    }

    // Сохранение, если нет ошибок
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO requests (user_id, car_brand, car_plate, service_id, visit_datetime, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $car_brand,
            $car_plate,
            $service_id,
            $visit_datetime,
            $payment_method
        ]);

        flash('success', 'Заявка успешно создана и отправлена администратору.');
        header('Location: /autodok/dashboard.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-12">
        <h2 class="mb-4">Новая запись на обслуживание</h2>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label">Марка автомобиля</label>
                <input type="text" name="car_brand" class="form-control <?= isset($errors['car_brand']) ? 'is-invalid' : '' ?>" 
                       value="<?= e($car_brand) ?>" required placeholder="Например: Lada Vesta">
                <?php if (isset($errors['car_brand'])): ?>
                    <div class="invalid-feedback"><?= e($errors['car_brand']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Государственный номер</label>
                <input type="text" name="car_plate" class="form-control <?= isset($errors['car_plate']) ? 'is-invalid' : '' ?>" 
                       value="<?= e($car_plate) ?>" required 
                       pattern="[АВЕКМНОРСТУХ]\d{3}[АВЕКМНОРСТУХ]{2}\d{2}" 
                       title="Формат: А000АА00" placeholder="А000АА00">
                <?php if (isset($errors['car_plate'])): ?>
                    <div class="invalid-feedback"><?= e($errors['car_plate']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Вид услуги</label>
                <select name="service_id" class="form-select <?= isset($errors['service_id']) ? 'is-invalid' : '' ?>" required>
                    <option value="">Выберите услугу...</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= $service['id'] ?>" <?= $service_id == $service['id'] ? 'selected' : '' ?>>
                            <?= e($service['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['service_id'])): ?>
                    <div class="invalid-feedback"><?= e($errors['service_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Дата и время визита</label>
                <input type="datetime-local" name="visit_datetime" class="form-control <?= isset($errors['visit_datetime']) ? 'is-invalid' : '' ?>" 
                       value="<?= e($visit_datetime ? str_replace(' ', 'T', substr($visit_datetime, 0, 16)) : '') ?>" required>
                <?php if (isset($errors['visit_datetime'])): ?>
                    <div class="invalid-feedback"><?= e($errors['visit_datetime']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="form-label d-block">Способ оплаты</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input <?= isset($errors['payment_method']) ? 'is-invalid' : '' ?>" type="radio" name="payment_method" id="pay_cash" value="cash" <?= $payment_method === 'cash' ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="pay_cash">Наличными</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input <?= isset($errors['payment_method']) ? 'is-invalid' : '' ?>" type="radio" name="payment_method" id="pay_card" value="card" <?= $payment_method === 'card' ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="pay_card">Банковской картой</label>
                </div>
                <?php if (isset($errors['payment_method'])): ?>
                    <div class="text-danger small mt-1"><?= e($errors['payment_method']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-success w-100">Записаться</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>