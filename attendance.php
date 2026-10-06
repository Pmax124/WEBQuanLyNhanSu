<?php
$pageTitle = 'Quản Lý Chấm Công';
$pageSubtitle = 'Theo dõi chấm công hàng ngày';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $empId = $_POST['employee_id'];
        $date = $_POST['date'];
        $checkIn = $_POST['check_in'] ?: null;
        $checkOut = $_POST['check_out'] ?: null;
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);
        
        try {
            $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in, check_out, status, notes) VALUES (?,?,?,?,?,?)")
                ->execute([$empId, $date, $checkIn, $checkOut, $status, $notes]);
            flash('success', '✅ Thêm chấm công thành công!');
        } catch (PDOException $e) {
            flash('error', '❌ Nhân viên đã được chấm công ngày này!');
        }
        redirect('attendance.php');
    }
    
    if ($action === 'edit') {
        $id = $_POST['id'];
        $checkIn = $_POST['check_in'] ?: null;
        $checkOut = $_POST['check_out'] ?: null;
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);
        $pdo->prepare("UPDATE attendance SET check_in=?, check_out=?, status=?, notes=? WHERE id=?")
            ->execute([$checkIn, $checkOut, $status, $notes, $id]);
        flash('success', '✅ Cập nhật chấm công thành công!');
        redirect('attendance.php');
    }
    
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM attendance WHERE id=?")->execute([$_POST['id']]);
        flash('success', '🗑️ Xóa bản ghi chấm công!');
        redirect('attendance.php');
    }

    // Check-in nhanh
    if ($action === 'quick_checkin') {
        $empId = $_POST['employee_id'];
        $now = date('H:i:s');
        try {
            $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in, status) VALUES (?,CURDATE(),?,'Đi làm') ON DUPLICATE KEY UPDATE check_in=?")
                ->execute([$empId, $now, $now]);
            flash('success', '✅ Check-in thành công lúc ' . $now);
        } catch (PDOException $e) {
            flash('error', '❌ Đã check-in rồi!');
        }
        redirect('attendance.php');
    }
}

// Filter
$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterEmp = $_GET['employee'] ?? '';

$sql = "SELECT a.*, e.employee_code, e.full_name, d.name as dept_name 
    FROM attendance a 
    JOIN employees e ON a.employee_id=e.id 
    LEFT JOIN departments d ON e.department_id=d.id 
    WHERE a.date=?";
$params = [$filterDate];
if ($filterEmp) { $sql .= " AND a.employee_id=?"; $params[] = $filterEmp; }
$sql .= " ORDER BY a.check_in ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$employees = $pdo->query("SELECT id, employee_code, full_name FROM employees WHERE status='Đang làm' ORDER BY full_name")->fetchAll();

$flashSuccess = flash('success');
$flashError = flash('error');
?>

<?php if ($flashSuccess): ?><div class="alert alert-success"><?= $flashSuccess ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-danger"><?= $flashError ?></div><?php endif; ?>

<!-- Quick Check-in -->
<div class="card mb-20">
    <div class="card-header">
        <h2>⚡ Check-in nhanh</h2>
    </div>
    <div class="card-body">
        <form method="POST" class="d-flex gap-10 flex-wrap align-center">
            <input type="hidden" name="action" value="quick_checkin">
            <select name="employee_id" class="form-control" required style="max-width:300px">
                <option value="">-- Chọn nhân viên --</option>
                <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['employee_code'] . ' - ' . $emp['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-success">✅ Check-in ngay</button>
            <span class="text-muted">Giờ hiện tại: <strong><?= date('H:i:s') ?></strong></span>
        </form>
    </div>
</div>

<!-- Filter + Add -->
<div class="card mb-20">
    <div class="card-body">
        <form method="GET" class="d-flex gap-10 flex-wrap align-center">
            <label class="fw-bold">📅 Ngày:</label>
            <input type="date" name="date" class="form-control" value="<?= $filterDate ?>" style="max-width:200px">
            <select name="employee" class="form-control" style="max-width:250px">
                <option value="">-- Tất cả nhân viên --</option>
                <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" <?= $filterEmp == $emp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($emp['employee_code'] . ' - ' . $emp['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Lọc</button>
            <a href="attendance.php" class="btn btn-outline">✖ Bỏ lọc</a>
            <button type="button" class="btn btn-success" onclick="document.getElementById('addAttModal').classList.add('active')">➕ Thêm bản ghi</button>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h2> Chấm công ngày <?= formatDate($filterDate) ?> (<?= count($records) ?> bản ghi)</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Họ tên</th>
                        <th>Phòng ban</th>
                        <th>Vào</th>
                        <th>Ra</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                    <tr><td colspan="8" class="text-center text-muted" style="padding:40px"> Không có dữ liệu chấm công ngày này</td></tr>
                    <?php else: foreach ($records as $r): 
                        $statusClass = match($r['status']) {
                            'Đi làm' => 'badge-success', 'Đi muộn' => 'badge-warning',
                            'Nghỉ phép' => 'badge-info', 'Nghỉ ốm' => 'badge-warning',
                            'Vắng mặt' => 'badge-danger', 'Về sớm' => 'badge-warning',
                        };
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['employee_code']) ?></strong></td>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($r['dept_name'] ?? '-') ?></span></td>
                        <td><?= formatTime($r['check_in']) ?></td>
                        <td><?= formatTime($r['check_out']) ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= $r['status'] ?></span></td>
                        <td><?= htmlspecialchars($r['notes'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick='openEditAtt(<?= json_encode($r, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Xóa bản ghi này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-danger">️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Thêm chấm công -->
<div class="modal-overlay" id="addAttModal">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ Thêm bản ghi chấm công</h3>
            <button class="modal-close" onclick="document.getElementById('addAttModal').classList.remove('active')">&times;</button>
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
                <div class="grid-2">
                    <div class="form-group">
                        <label>Ngày *</label>
                        <input type="date" name="date" class="form-control" value="<?= $filterDate ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái *</label>
                        <select name="status" class="form-control" required>
                            <option value="Đi làm">Đi làm</option>
                            <option value="Đi muộn">Đi muộn</option>
                            <option value="Về sớm">Về sớm</option>
                            <option value="Nghỉ phép">Nghỉ phép</option>
                            <option value="Nghỉ ốm">Nghỉ ốm</option>
                            <option value="Vắng mặt">Vắng mặt</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giờ vào</label>
                        <input type="time" name="check_in" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Giờ ra</label>
                        <input type="time" name="check_out" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer" style="padding:16px 0 0; border:none;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addAttModal').classList.remove('active')">Hủy</button>
                    <button type="submit" class="btn btn-success">💾 Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa -->
<div class="modal-overlay" id="editAttModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Sửa chấm công</h3>
            <button class="modal-close" onclick="document.getElementById('editAttModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_att_id">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Giờ vào</label>
                        <input type="time" name="check_in" id="edit_att_in" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Giờ ra</label>
                        <input type="time" name="check_out" id="edit_att_out" class="form-control">
                    </div>
                    <div class="form-group" style="grid-column: span 2">
                        <label>Trạng thái</label>
                        <select name="status" id="edit_att_status" class="form-control">
                            <option value="Đi làm">Đi làm</option>
                            <option value="Đi muộn">Đi muộn</option>
                            <option value="Về sớm">Về sớm</option>
                            <option value="Nghỉ phép">Nghỉ phép</option>
                            <option value="Nghỉ ốm">Nghỉ ốm</option>
                            <option value="Vắng mặt">Vắng mặt</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="notes" id="edit_att_notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer" style="padding:16px 0 0; border:none;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('editAttModal').classList.remove('active')">Hủy</button>
                    <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditAtt(r) {
    document.getElementById('edit_att_id').value = r.id;
    document.getElementById('edit_att_in').value = r.check_in || '';
    document.getElementById('edit_att_out').value = r.check_out || '';
    document.getElementById('edit_att_status').value = r.status;
    document.getElementById('edit_att_notes').value = r.notes || '';
    document.getElementById('editAttModal').classList.add('active');
}
</script>

<?php require_once 'includes/footer.php'; ?>