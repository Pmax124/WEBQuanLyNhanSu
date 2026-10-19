<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            redirect('dashboard.php');
        } else {
            $error = 'Sai tên đăng nhập hoặc mật khẩu!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Quản Lý Nhân Sự</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-box">
        <div class="logo-icon">🏢</div>
        <h1>Quản Lý Nhân Sự</h1>
        <p class="subtitle">Đăng nhập để tiếp tục</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>👤 Tên đăng nhập</label>
                <input type="text" name="username" class="form-control" 
                       placeholder="Nhập tên đăng nhập" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>🔒 Mật khẩu</label>
                <input type="password" name="password" class="form-control" 
                       placeholder="Nhập mật khẩu" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                🚀 Đăng nhập
            </button>
        </form>

        <div style="margin-top:20px; padding:15px; background:#f8fafc; border-radius:8px; font-size:13px;">
            <strong>📌 Tài khoản demo:</strong><br>
            • Admin: <code>admin</code> / <code>admin123</code><br>
            • Manager: <code>manager</code> / <code>admin123</code><br>
            • Staff: <code>staff</code> / <code>admin123</code>
        </div>
    </div>
</div>
</body>
</html>