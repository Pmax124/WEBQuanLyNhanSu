<?php
$pageTitle = 'Bảng Điều Khiển';
$pageSubtitle = 'Tổng quan hệ thống quản lý nhân sự';
require_once 'includes/header.php';

// Thống kê
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='Đang làm'")->fetchColumn();
$totalDepts = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$todayAttendance = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Đi làm'")->fetchColumn();
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status='Chờ duyệt'")->fetchColumn();
$totalSalary = $pdo->query("SELECT COALESCE(SUM(total_salary),0) FROM salary WHERE month=MONTH(CURDATE()) AND year=YEAR(CURDATE())")->fetchColumn();
$lateToday = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Đi muộn'")->fetchColumn();
$absentToday = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Vắng mặt'")->fetchColumn();
$onLeaveToday = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Nghỉ phép'")->fetchColumn();

// Nhân viên mới nhất
$newEmployees = $pdo->query("SELECT e.*, d.name as dept_name 
    FROM employees e LEFT JOIN departments d ON e.department_id=d.id 
    ORDER BY e.created_at DESC LIMIT 5")->fetchAll();

// Phòng ban có nhiều nhân viên nhất
$deptStats = $pdo->query("SELECT d.name, COUNT(e.id) as count 
    FROM departments d LEFT JOIN employees e ON d.id=e.department_id AND e.status='Đang làm'
    GROUP BY d.id ORDER BY count DESC")->fetchAll();
$maxDeptCount = max(array_column($deptStats, 'count'), 1);
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div class="stat-info">
            <h3><?= $totalEmployees ?></h3>
            <p>Nhân viên đang làm</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">🏬</div>
        <div class="stat-info">
            <h3><?= $totalDepts ?></h3>
            <p>Phòng ban</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">✅</div>
        <div class="stat-info">
            <h3><?= $todayAttendance ?></h3>
            <p>Đi làm hôm nay</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">⏰</div>
        <div class="stat-info">
            <h3><?= $lateToday ?></h3>
            <p>Đi muộn hôm nay</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">❌</div>
        <div class="stat-info">
            <h3><?= $absentToday ?></h3>
            <p>Vắng mặt hôm nay</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📋</div>
        <div class="stat-info">
            <h3><?= $pendingLeaves ?></h3>
            <p>Đơn nghỉ chờ duyệt</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
            <h3 style="font-size:20px"><?= formatMoney($totalSalary) ?></h3>
            <p>Tổng lương tháng này</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">🏖️</div>
        <div class="stat-info">
            <h3><?= $onLeaveToday ?></h3>
            <p>Nghỉ phép hôm nay</p>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Nhân viên mới -->
    <div class="card">
        <div class="card-header">
            <h2> Nhân viên mới</h2>
            <a href="employees.php" class="btn btn-sm btn-outline">Xem tất cả →</a>
        </div>
        <div class="card-body p-0">
            <table>
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Họ tên</th>
                        <th>Phòng ban</th>
                        <th>Ngày vào</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($newEmployees as $emp): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($emp['employee_code']) ?></strong></td>
                        <td><?= htmlspecialchars($emp['full_name']) ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($emp['dept_name'] ?? 'Chưa phân') ?></span></td>
                        <td><?= formatDate($emp['hire_date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Thống kê phòng ban -->
    <div class="card">
        <div class="card-header">
            <h2>📊 Nhân sự theo phòng ban</h2>
        </div>
        <div class="card-body">
            <?php foreach ($deptStats as $dept): 
                $percent = ($dept['count'] / $maxDeptCount) * 100;
                $colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6'];
                $color = $colors[array_search($dept, $deptStats) % count($colors)];
            ?>
            <div class="chart-bar">
                <div class="chart-bar-label"><?= htmlspecialchars($dept['name']) ?></div>
                <div class="chart-bar-track">
                    <div class="chart-bar-fill" style="width:<?= $percent ?>%; background:<?= $color ?>">
                        <?= $dept['count'] ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Chấm công hôm nay -->
<div class="card">
    <div class="card-header">
        <h2>📅 Chấm công hôm nay - <?= date('d/m/Y') ?></h2>
        <a href="attendance.php" class="btn btn-sm btn-outline">Quản lý chấm công →</a>
    </div>
    <div class="card-body p-0">
        <table>
            <thead>
                <tr>
                    <th>Mã NV</th>
                    <th>Họ tên</th>
                    <th>Vào</th>
                    <th>Ra</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $todayRecords = $pdo->query("SELECT a.*, e.employee_code, e.full_name 
                    FROM attendance a JOIN employees e ON a.employee_id=e.id 
                    WHERE a.date=CURDATE() ORDER BY a.check_in ASC")->fetchAll();
                foreach ($todayRecords as $rec):
                    $statusClass = match($rec['status']) {
                        'Đi làm' => 'badge-success',
                        'Đi muộn' => 'badge-warning',
                        'Nghỉ phép' => 'badge-info',
                        'Nghỉ ốm' => 'badge-warning',
                        'Vắng mặt' => 'badge-danger',
                        default => 'badge-primary'
                    };
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($rec['employee_code']) ?></strong></td>
                    <td><?= htmlspecialchars($rec['full_name']) ?></td>
                    <td><?= formatTime($rec['check_in']) ?></td>
                    <td><?= formatTime($rec['check_out']) ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= $rec['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>