<?php require_once __DIR__ . '/../config.php'; requireLogin(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Quản Lý Nhân Sự' ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏢</text></svg>">
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <span class="logo">🏢</span>
            <div>
                <h2>HR Management</h2>
                <small>Hệ thống QLNS v1.0</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">Tổng quan</div>
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <span class="icon">📊</span> Bảng điều khiển
            </a>

            <div class="nav-section">Quản lý</div>
            <a href="employees.php" class="<?= basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : '' ?>">
                <span class="icon">👥</span> Nhân viên
            </a>
            <a href="departments.php" class="<?= basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : '' ?>">
                <span class="icon">🏬</span> Phòng ban
            </a>
            <a href="attendance.php" class="<?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>">
                <span class="icon">📅</span> Chấm công
            </a>
            <a href="salary.php" class="<?= basename($_SERVER['PHP_SELF']) == 'salary.php' ? 'active' : '' ?>">
                <span class="icon">💰</span> Lương
            </a>
            <a href="leaves.php" class="<?= basename($_SERVER['PHP_SELF']) == 'leaves.php' ? 'active' : '' ?>">
                <span class="icon">📋</span> Nghỉ phép
            </a>

            <div class="nav-section">Báo cáo</div>
            <a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
                <span class="icon">📈</span> Báo cáo
            </a>

            <?php if (isAdmin()): ?>
            <div class="nav-section">Hệ thống</div>
            <a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                <span class="icon"></span> Tài khoản
            </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                <p><?= $pageSubtitle ?? 'Chào mừng trở lại!' ?></p>
            </div>
            <div class="topbar-right">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?></div>
                    <div>
                        <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                        <div class="user-role"><?= ucfirst($_SESSION['role']) ?></div>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-danger btn-sm"> Đăng xuất</a>
            </div>
        </div>

        <div class="page-content">