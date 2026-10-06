<?php
$pageTitle = 'Báo Cáo Thống Kê';
$pageSubtitle = 'Tổng hợp dữ liệu nhân sự';
require_once 'includes/header.php';

// Thống kê theo phòng ban
$deptReport = $pdo->query("SELECT d.name, 
    COUNT(e.id) as total,
    SUM(CASE WHEN e.status='Đang làm' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN e.gender='Nam' THEN 1 ELSE 0 END) as male,
    SUM(CASE WHEN e.gender='Nữ' THEN 1 ELSE 0 END) as female
    FROM departments d LEFT JOIN employees e ON d.id=e.department_id
    GROUP BY d.id ORDER BY total DESC")->fetchAll();

// Thống kê lương theo tháng (6 tháng gần nhất)
$salaryReport = $pdo->query("SELECT month, year, 
    COUNT(*) as count, 
    SUM(total_salary) as total,
    SUM(CASE WHEN status='Đã thanh toán' THEN total_salary ELSE 0 END) as paid
    FROM salary GROUP BY year, month ORDER BY year DESC, month DESC LIMIT 6")->fetchAll();

// Top nhân viên có lương cao nhất
$topSalary = $pdo->query("SELECT s.total_salary, e.employee_code, e.full_name, d.name as dept
    FROM salary s JOIN employees e ON s.employee_id=e.id 
    LEFT JOIN departments d ON e.department_id=d.id
    WHERE s.month=MONTH(CURDATE()) AND s.year=YEAR(CURDATE())
    ORDER BY s.total_salary DESC LIMIT 10")->fetchAll();

// Thống kê chấm công tháng này
$attStats = $pdo->query("SELECT status, COUNT(*) as count FROM attendance 
    WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())
    GROUP BY status ORDER BY count DESC")->fetchAll();
$totalAtt = array_sum(array_column($attStats, 'count'));

$flashSuccess = flash('success');
?>

<?php if ($flashSuccess): ?><div class="alert alert-success"><?= $flashSuccess ?></div><?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"></div>
        <div class="stat-info">
            <h3><?= $pdo->query("SELECT COUNT(*) FROM employees WHERE status='Đang làm'")->fetchColumn() ?></h3>
            <p>Tổng nhân sự</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
            <h3 style="font-size:20px"><?= formatMoney($pdo->query("SELECT COALESCE(SUM(total_salary),0) FROM salary WHERE month=MONTH(CURDATE()) AND year=YEAR(CURDATE())")->fetchColumn()) ?></h3>
            <p>Quỹ lương tháng này</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📊</div>
        <div class="stat-info">
            <h3><?= $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn() ?></h3>
            <p>Phòng ban</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📋</div>
        <div class="stat-info">
            <h3><?= $pdo->query("SELECT COUNT(*) FROM leaves WHERE status='Chờ duyệt'")->fetchColumn() ?></h3>
            <p>Đơn chờ duyệt</p>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Báo cáo phòng ban -->
    <div class="card">
        <div class="card-header"><h2>🏬 Thống kê theo phòng ban</h2></div>
        <div class="card-body p-0">
            <table>
                <thead>
                    <tr><th>Phòng ban</th><th>Tổng</th><th>Đang làm</th><th>Nam</th><th>Nữ</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($deptReport as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                        <td><?= $r['total'] ?></td>
                        <td><span class="badge badge-success"><?= $r['active'] ?></span></td>
                        <td>👨 <?= $r['male'] ?></td>
                        <td>👩 <?= $r['female'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chấm công tháng này -->
    <div class="card">
        <div class="card-header"><h2>📅 Chấm công tháng <?= date('m/Y') ?></h2></div>
        <div class="card-body">
            <?php foreach ($attStats as $s): 
                $percent = $totalAtt > 0 ? ($s['count'] / $totalAtt) * 100 : 0;
                $color = match($s['status']) {
                    'Đi làm' => '#10b981', 'Đi muộn' => '#f59e0b', 'Nghỉ phép' => '#3b82f6',
                    'Nghỉ ốm' => '#f97316', 'Vắng mặt' => '#ef4444', 'Về sớm' => '#eab308',
                };
            ?>
            <div class="chart-bar">
                <div class="chart-bar-label"><?= $s['status'] ?></div>
                <div class="chart-bar-track">
                    <div class="chart-bar-fill" style="width:<?= $percent ?>%; background:<?= $color ?>">
                        <?= $s['count'] ?> (<?= round($percent) ?>%)
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <p class="text-muted mt-20" style="font-size:13px">Tổng: <?= $totalAtt ?> bản ghi chấm công</p>
        </div>
    </div>
</div>

<!-- Top lương -->
<div class="card">
    <div class="card-header"><h2>🏆 Top 10 nhân viên lương cao nhất - Tháng <?= date('m/Y') ?></h2></div>
    <div class="card-body p-0">
        <table>
            <thead>
                <tr><th>#</th><th>Mã NV</th><th>Họ tên</th><th>Phòng ban</th><th>Lương thực nhận</th></tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($topSalary as $t): ?>
                <tr>
                    <td><strong><?= $rank++ ?></strong></td>
                    <td><?= htmlspecialchars($t['employee_code']) ?></td>
                    <td><?= htmlspecialchars($t['full_name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($t['dept'] ?? '-') ?></span></td>
                    <td class="text-right fw-bold text-success"><?= formatMoney($t['total_salary']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Lịch sử lương -->
<div class="card">
    <div class="card-header"><h2>📈 Lịch sử quỹ lương (6 tháng gần nhất)</h2></div>
    <div class="card-body p-0">
        <table>
            <thead>
                <tr><th>Tháng</th><th>Số NV</th><th>Tổng quỹ lương</th><th>Đã thanh toán</th><th>Chưa thanh toán</th></tr>
            </thead>
            <tbody>
                <?php foreach ($salaryReport as $r): 
                    $unpaid = $r['total'] - $r['paid'];
                ?>
                <tr>
                    <td><strong>Tháng <?= $r['month'] ?>/<?= $r['year'] ?></strong></td>
                    <td><?= $r['count'] ?> NV</td>
                    <td class="fw-bold"><?= formatMoney($r['total']) ?></td>
                    <td class="text-success"><?= formatMoney($r['paid']) ?></td>
                    <td class="text-danger"><?= formatMoney($unpaid) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>