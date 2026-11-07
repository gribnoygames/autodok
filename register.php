<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

if (is_logged_in() || is_admin()) {
    header('Location: /autodok/');
    exit;
}

$errors = [];
$login = $full_name = $phone = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Валидация логина
    if (!preg_match('/^[a-zA-Z0-9]{5,}$/', $login)) {
        $errors['login'] = 'Логин: латиница/цифры, минимум 5 символов.';
    } else {
        // Проверка уникальности
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $errors['login'] = 'Этот логин уже занят.';
        }
    }

    // Валидация пароля
    if (strlen($password) < 6 || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Пароль: минимум 6 символов, хотя бы одна цифра.';
    }

    // Валидация ФИО
    if (!preg_match('/^[А-Яа-яЁё\s]+$/u', $full_name)) {
        $errors['full_name'] = 'ФИО: только кириллица и пробелы.';
    }

    // Валидация телефона
    if (!preg_match('/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/', $phone)) {
        $errors['phone'] = 'Телефон в формате: +7(XXX)-XXX-XX-XX.';
    }

    // Валидация email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный формат email.';
    }

    // Сохранение
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, full_name, phone, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$login, $hash, $full_name, $phone, $email]);
        
        flash('success', 'Регистрация успешна. Войдите в систему.');
        header('Location: /autodok/login.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-12">
        <h2 class="mb-4">Регистрация</h2>
        
        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label">Логин</label>
                <input type="text" name="login" class="form-control <?= isset($errors['login']) ? 'is-invalid' : '' ?>" 
                    value="<?= e($login) ?>" required pattern="[a-zA-Z0-9]{5,}">
                <?php if (isset($errors['login'])): ?>
                    <div class="invalid-feedback"><?= e($errors['login']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="6">
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">ФИО</label>
                <input type="text" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
                       value="<?= e($full_name) ?>" required pattern="[А-Яа-яЁё\s]+">
                <?php if (isset($errors['full_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Телефон</label>
                <input type="text" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                       value="<?= e($phone) ?>" required placeholder="+7(999)-123-45-67">
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                       value="<?= e($email) ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
        </form>

        <div class="mt-3 text-center">
            <a href="/autodok/login.php">Уже зарегистрированы? Вход</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>