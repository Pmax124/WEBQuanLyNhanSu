<?php
$pageTitle = 'Quản Lý Lương';
$pageSubtitle = 'Tính và quản lý lương nhân viên';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $empId = $_POST['employee_id'];
        $month = (int)$_POST['month'];
        $year = (int)$_POST['year'];
        $base = (float)$_POST['base_salary'];
        $bonus = (float)($_POST['bonus'] ?? 0);
        $deduction = (float)($_POST['deduction'] ?? 0);
        $total = $base + $bonus - $deduction;
        $status = $_POST['status'];
        $note = trim($_POST['note']);
        
        try {
            $pdo->prepare("INSERT INTO salary (employee_id, month, year, base_salary, bonus, deduction, total_salary, status, note) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$empId, $month, $year, $base, $bonus, $deduction, $total, $status, $note]);
            flash('success', '✅ Thêm bảng lương thành công!');
        } catch (PDOException $e) {
            flash('error', '❌ Lương tháng này đã tồn tại!');
        }
        redirect('salary.php');
    }
    
    if ($action === 'edit') {
        $id = $_POST['id'];
        $base = (float)$_POST['base_salary'];
        $bonus = (float)($_POST['bonus'] ?? 0);
        $deduction = (float)($_POST['deduction'] ?? 0);
        $total = $base + $bonus - $deduction;
        $status = $_POST['status'];
        $note = trim($_POST['note']);
        $pdo->prepare("UPDATE salary SET base_salary=?, bonus=?, deduction=?, total_salary=?, status=?, note=? WHERE id=?")
            ->execute([$base, $bonus, $deduction, $total, $status, $note, $id]);
        flash('success', '✅ Cập nhật lương thành công!');
        redirect('salary.php');
    }
    
    if ($action === 'pay') {
        $pdo->prepare("UPDATE salary SET status='Đã thanh toán' WHERE id=?")->execute([$_POST['id']]);
        flash('success', '✅ Đã thanh toán lương!');
        redirect('salary.php');
    }
    
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM salary WHERE id=?")->execute([$_POST['id']]);
        flash('success', '🗑️ Xóa bản ghi lương!');
        redirect('salary.php');
    }
}

// Filter
$filterMonth = $_GET['month'] ?? date('m');
$filterYear = $_GET['year'] ?? date('Y');

$stmt = $pdo->prepare("SELECT s.*, e.employee_code, e.full_name, d.name as dept_name 
    FROM salary s 
    JOIN employees e ON s.employee_id=e.id 
    LEFT JOIN departments d ON e.department_id=d.id 
    WHERE s.month=? AND s.year=? 
    ORDER BY e.full_name");
$stmt->execute([$filterMonth, $filterYear]);
$salaries = $stmt->fetchAll();

$employees = $pdo->query("SELECT id, employee_code, full_name FROM employees WHERE status='Đang làm' ORDER BY full_name")->fetchAll();

$totalMonth = array_sum(array_column($salaries, 'total_salary'));
$paidCount = count(array_filter($salaries, fn($s) => $s['status'] === 'Đã thanh toán'));
$unpaidCount = count($salaries) - $paidCount;

$flashSuccess = flash('success');
$flashError = flash('error');
?>

<?php if ($flashSuccess): ?><div class="alert alert-success"><?= $flashSuccess ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-danger"><?= $flashError ?></div><?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
            <h3 style="font-size:22px"><?= formatMoney($totalMonth) ?></h3>
            <p>Tổng lương tháng <?= $filterMonth ?>/<?= $filterYear ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div class="stat-info">
            <h3><?= count($salaries) ?></h3>
            <p>Nhân viên có lương</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
            <h3><?= $paidCount ?></h3>
            <p>Đã thanh toán</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"></div>
        <div class="stat-info">
            <h3><?= $unpaidCount ?></h3>
            <p>Chưa thanh toán</p>
        </div>
    </div>
</div>

<!-- Filter + Add -->
<div class="card mb-20">
    <div class="card-body">
        <form method="GET" class="d-flex gap-10 flex-wrap align-center">
            <label class="fw-bold">📅 Tháng/Năm:</label>
            <select name="month" class="form-control" style="max-width:120px">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $filterMonth == $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form-control" style="max-width:120px">
                <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Xem</button>
            <button type="button" class="btn btn-success" onclick="document.getElementById('addSalaryModal').classList.add('active')">➕ Thêm lương</button>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h2>💰 Bảng lương tháng <?= $filterMonth ?>/<?= $filterYear ?></h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Họ tên</th>
                        <th>Phòng ban</th>
                        <th>Lương cơ bản</th>
                        <th>Thưởng</th>
                        <th>Khấu trừ</th>
                        <th>Thực nhận</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salaries)): ?>
                    <tr><td colspan="9" class="text-center text-muted" style="padding:40px"> Chưa có dữ liệu lương tháng này</td></tr>
                    <?php else: foreach ($salaries as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['employee_code']) ?></strong></td>
                        <td><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($s['dept_name'] ?? '-') ?></span></td>
                        <td class="text-right"><?= formatMoney($s['base_salary']) ?></td>
                        <td class="text-right text-success">+<?= formatMoney($s['bonus']) ?></td>
                        <td class="text-right text-danger">-<?= formatMoney($s['deduction']) ?></td>
                        <td class="text-right fw-bold"><?= formatMoney($s['total_salary']) ?></td>
                        <td>
                            <span class="badge <?= $s['status'] === 'Đã thanh toán' ? 'badge-success' : 'badge-warning' ?>">
                                <?= $s['status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($s['status'] !== 'Đã thanh toán'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="pay">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button class="btn btn-sm btn-success" title="Thanh toán">💵</button>
                            </form>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-warning" onclick='openEditSalary(<?= json_encode($s, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Xóa bản ghi lương?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <?php if (!empty($salaries)): ?>
                <tfoot>
                    <tr style="background:var(--gray-light); font-weight:700">
                        <td colspan="6" class="text-right">TỔNG CỘNG:</td>
                        <td class="text-right" style="font-size:16px; color:var(--primary)"><?= formatMoney($totalMonth) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Modal Thêm lương -->
<div class="modal-overlay" id="addSalaryModal">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ Thêm bảng lương</h3>
            <button class="modal-close" onclick="document.getElementById('addSalaryModal').classList.remove('active')">&times;</button>
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
                        <label>Tháng *</label>
                        <select name="month" class="form-control" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $filterMonth == $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Năm *</label>
                        <select name="year" class="form-control" required>
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Lương cơ bản *</label>
                        <input type="number" name="base_salary" class="form-control" required min="0" step="1000" placeholder="VD: 15000000">
                    </div>
                    <div class="form-group">
                        <label>Thưởng</label>
                        <input type="number" name="bonus" class="form-control" min="0" step="1000" value="0">
                    </div>
                    <div class="form-group">
                        <label>Khấu trừ</label>
                        <input type="number" name="deduction" class="form-control" min="0" step="1000" value="0">
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status" class="form-control">
                            <option value="Chưa thanh toán">Chưa thanh toán</option>
                            <option value="Đã thanh toán">Đã thanh toán</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="note" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer" style="padding:16px 0 0; border:none;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addSalaryModal').classList.remove('active')">Hủy</button>
                    <button type="submit" class="btn btn-success">💾 Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa lương -->
<div class="modal-overlay" id="editSalaryModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Sửa bảng lương</h3>
            <button class="modal-close" onclick="document.getElementById('editSalaryModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_sal_id">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Lương cơ bản</label>
                        <input type="number" name="base_salary" id="edit_sal_base" class="form-control" required min="0" step="1000">
                    </div>
                    <div class="form-group">
                        <label>Thưởng</label>
                        <input type="number" name="bonus" id="edit_sal_bonus" class="form-control" min="0" step="1000">
                    </div>
                    <div class="form-group">
                        <label>Khấu trừ</label>
                        <input type="number" name="deduction" id="edit_sal_deduct" class="form-control" min="0" step="1000">
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status" id="edit_sal_status" class="form-control">
                            <option value="Chưa thanh toán">Chưa thanh toán</option>
                            <option value="Đã thanh toán">Đã thanh toán</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="note" id="edit_sal_note" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer" style="padding:16px 0 0; border:none;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('editSalaryModal').classList.remove('active')">Hủy</button>
                    <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditSalary(s) {
    document.getElementById('edit_sal_id').value = s.id;
    document.getElementById('edit_sal_base').value = s.base_salary;
    document.getElementById('edit_sal_bonus').value = s.bonus;
    document.getElementById('edit_sal_deduct').value = s.deduction;
    document.getElementById('edit_sal_status').value = s.status;
    document.getElementById('edit_sal_note').value = s.note || '';
    document.getElementById('editSalaryModal').classList.add('active');
}
</script>

<?php require_once 'includes/footer.php'; ?>