<?php
$pageTitle = 'Quản Lý Nghỉ Phép';
$pageSubtitle = 'Đơn xin nghỉ phép của nhân viên';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $pdo->prepare("INSERT INTO leaves (employee_id, leave_type, start_date, end_date, reason, status) VALUES (?,?,?,?,?,'Chờ duyệt')")
            ->execute([$_POST['employee_id'], $_POST['leave_type'], $_POST['start_date'], $_POST['end_date'], trim($_POST['reason'])]);
        flash('success', '✅ Gửi đơn nghỉ phép thành công!');
        redirect('leaves.php');
    }
    
    if ($action === 'approve') {
        $pdo->prepare("UPDATE leaves SET status='Đã duyệt', approved_by=? WHERE id=?")
            ->execute([$_SESSION['user_id'], $_POST['id']]);
        flash('success', '✅ Đã duyệt đơn nghỉ phép!');
        redirect('leaves.php');
    }
    
    if ($action === 'reject') {
        $pdo->prepare("UPDATE leaves SET status='Từ chối', approved_by=? WHERE id=?")
            ->execute([$_SESSION['user_id'], $_POST['id']]);
        flash('success', '❌ Đã từ chối đơn nghỉ phép!');
        redirect('leaves.php');
    }
    
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM leaves WHERE id=?")->execute([$_POST['id']]);
        flash('success', '🗑️ Xóa đơn nghỉ phép!');
        redirect('leaves.php');
    }
}

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT l.*, e.employee_code, e.full_name, u.full_name as approver_name 
    FROM leaves l 
    JOIN employees e ON l.employee_id=e.id 
    LEFT JOIN users u ON l.approved_by=u.id WHERE 1=1";
$params = [];
if ($statusFilter) { $sql .= " AND l.status=?"; $params[] = $statusFilter; }
$sql .= " ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leaves = $stmt->fetchAll();

$employees = $pdo->query("SELECT id, employee_code, full_name FROM employees WHERE status='Đang làm' ORDER BY full_name")->fetchAll();

$flashSuccess = flash('success');
?>

<?php if ($flashSuccess): ?><div class="alert alert-success"><?= $flashSuccess ?></div><?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon orange">📋</div>
        <div class="stat-info"><h3><?= count(array_filter($leaves, fn($l) => $l['status'] === 'Chờ duyệt')) ?></h3><p>Chờ duyệt</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info"><h3><?= count(array_filter($leaves, fn($l) => $l['status'] === 'Đã duyệt')) ?></h3><p>Đã duyệt</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">❌</div>
        <div class="stat-info"><h3><?= count(array_filter($leaves, fn($l) => $l['status'] === 'Từ chối')) ?></h3><p>Từ chối</p></div>
    </div>
</div>

<!-- Add + Filter -->
<div class="card mb-20">
    <div class="card-body">
        <div class="d-flex gap-10 flex-wrap align-center justify-between">
            <form method="GET" class="d-flex gap-10 align-center">
                <select name="status" class="form-control" style="max-width:200px">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="Chờ duyệt" <?= $statusFilter == 'Chờ duyệt' ? 'selected' : '' ?>>Chờ duyệt</option>
                    <option value="Đã duyệt" <?= $statusFilter == 'Đã duyệt' ? 'selected' : '' ?>>Đã duyệt</option>
                    <option value="Từ chối" <?= $statusFilter == 'Từ chối' ? 'selected' : '' ?>>Từ chối</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">🔍 Lọc</button>
                <a href="leaves.php" class="btn btn-outline btn-sm">✖ Bỏ lọc</a>
            </form>
            <button class="btn btn-success" onclick="document.getElementById('addLeaveModal').classList.add('active')"> Tạo đơn nghỉ phép</button>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h2>📋 Danh sách đơn nghỉ phép (<?= count($leaves) ?>)</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Họ tên</th>
                        <th>Loại nghỉ</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                        <th>Số ngày</th>
                        <th>Lý do</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaves)): ?>
                    <tr><td colspan="9" class="text-center text-muted" style="padding:40px">📭 Không có đơn nghỉ phép</td></tr>
                    <?php else: foreach ($leaves as $l):
                        $days = (strtotime($l['end_date']) - strtotime($l['start_date'])) / 86400 + 1;
                        $statusClass = match($l['status']) {
                            'Chờ duyệt' => 'badge-warning', 'Đã duyệt' => 'badge-success', 'Từ chối' => 'badge-danger',
                        };
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($l['employee_code']) ?></strong></td>
                        <td><?= htmlspecialchars($l['full_name']) ?></td>
                        <td><span class="badge badge-info"><?= $l['leave_type'] ?></span></td>
                        <td><?= formatDate($l['start_date']) ?></td>
                        <td><?= formatDate($l['end_date']) ?></td>
                        <td><strong><?= $days ?> ngày</strong></td>
                        <td><?= htmlspecialchars($l['reason'] ?? '-') ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= $l['status'] ?></span></td>
                        <td>
                            <?php if ($l['status'] === 'Chờ duyệt'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                <button class="btn btn-sm btn-success" title="Duyệt">✅</button>
                            </form>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                <button class="btn btn-sm btn-danger" title="Từ chối">❌</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Xóa đơn này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                <button class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Thêm -->
<div class="modal-overlay" id="addLeaveModal">
    <div class="modal">
        <div class="modal-header">
            <h3>📋 Tạo đơn nghỉ phép</h3>
            <button class="modal-close" onclick="document.getElementById('addLeaveModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Nhân viên *</label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- Chọn --</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['employee_code'] . ' - ' . $emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Loại nghỉ *</label>
                    <select name="leave_type" class="form-control" required>
                        <option value="Nghỉ phép năm">Nghỉ phép năm</option>
                        <option value="Nghỉ ốm">Nghỉ ốm</option>
                        <option value="Nghỉ không lương">Nghỉ không lương</option>
                        <option value="Nghỉ thai sản">Nghỉ thai sản</option>
                        <option value="Nghỉ việc riêng">Nghỉ việc riêng</option>
                    </select>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Từ ngày *</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Đến ngày *</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Lý do</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Nhập lý do nghỉ phép..."></textarea>
                </div>
                <div class="modal-footer" style="padding:16px 0 0; border:none;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addLeaveModal').classList.remove('active')">Hủy</button>
                    <button type="submit" class="btn btn-success">📤 Gửi đơn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>