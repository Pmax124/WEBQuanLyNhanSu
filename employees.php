<?php
$pageTitle = 'Quản Lý Nhân Viên';
$pageSubtitle = 'Thêm, sửa, xóa thông tin nhân viên';
require_once 'includes/header.php';

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $code = trim($_POST['employee_code']);
        $name = trim($_POST['full_name']);
        $gender = $_POST['gender'];
        $dob = $_POST['date_of_birth'];
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $dept = $_POST['department_id'] ?: null;
        $position = trim($_POST['position']);
        $hireDate = $_POST['hire_date'];
        $status = $_POST['status'];

        try {
            $stmt = $pdo->prepare("INSERT INTO employees (employee_code, full_name, gender, date_of_birth, phone, email, address, department_id, position, hire_date, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$code, $name, $gender, $dob, $phone, $email, $address, $dept, $position, $hireDate, $status]);
            flash('success', '✅ Thêm nhân viên thành công!');
        } catch (PDOException $e) {
            flash('error', '❌ Lỗi: Mã nhân viên đã tồn tại!');
        }
        redirect('employees.php');
    }

    if ($action === 'edit') {
        $id = $_POST['id'];
        $code = trim($_POST['employee_code']);
        $name = trim($_POST['full_name']);
        $gender = $_POST['gender'];
        $dob = $_POST['date_of_birth'];
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $dept = $_POST['department_id'] ?: null;
        $position = trim($_POST['position']);
        $hireDate = $_POST['hire_date'];
        $status = $_POST['status'];

        $stmt = $pdo->prepare("UPDATE employees SET employee_code=?, full_name=?, gender=?, date_of_birth=?, phone=?, email=?, address=?, department_id=?, position=?, hire_date=?, status=? WHERE id=?");
        $stmt->execute([$code, $name, $gender, $dob, $phone, $email, $address, $dept, $position, $hireDate, $status, $id]);
        flash('success', '✅ Cập nhật nhân viên thành công!');
        redirect('employees.php');
    }

    if ($action === 'delete') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM employees WHERE id=?")->execute([$id]);
        flash('success', '🗑️ Xóa nhân viên thành công!');
        redirect('employees.php');
    }
}

// Tìm kiếm
$search = $_GET['search'] ?? '';
$deptFilter = $_GET['department'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT e.*, d.name as dept_name FROM employees e LEFT JOIN departments d ON e.department_id=d.id WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (e.full_name LIKE ? OR e.employee_code LIKE ? OR e.phone LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($deptFilter) { $sql .= " AND e.department_id=?"; $params[] = $deptFilter; }
if ($statusFilter) { $sql .= " AND e.status=?"; $params[] = $statusFilter; }
$sql .= " ORDER BY e.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

$flashSuccess = flash('success');
$flashError = flash('error');
?>

<?php if ($flashSuccess): ?><div class="alert alert-success"><?= $flashSuccess ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-danger"><?= $flashError ?></div><?php endif; ?>

<!-- Filter Bar -->
<div class="card mb-20">
    <div class="card-body">
        <form method="GET" class="d-flex gap-10 flex-wrap align-center">
            <input type="text" name="search" class="form-control" placeholder="🔍 Tìm theo tên, mã, SĐT..." value="<?= htmlspecialchars($search) ?>" style="max-width:300px">
            <select name="department" class="form-control" style="max-width:200px">
                <option value="">-- Tất cả phòng ban --</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-control" style="max-width:180px">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="Đang làm" <?= $statusFilter == 'Đang làm' ? 'selected' : '' ?>>Đang làm</option>
                <option value="Nghỉ việc" <?= $statusFilter == 'Nghỉ việc' ? 'selected' : '' ?>>Nghỉ việc</option>
                <option value="Tạm ngưng" <?= $statusFilter == 'Tạm ngưng' ? 'selected' : '' ?>>Tạm ngưng</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Tìm</button>
            <a href="employees.php" class="btn btn-outline">✖ Bỏ lọc</a>
            <button type="button" class="btn btn-success" onclick="openAddModal()">➕ Thêm nhân viên</button>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h2> Danh sách nhân viên (<?= count($employees) ?>)</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Họ tên</th>
                        <th>Giới tính</th>
                        <th>Ngày sinh</th>
                        <th>SĐT</th>
                        <th>Email</th>
                        <th>Phòng ban</th>
                        <th>Chức vụ</th>
                        <th>Ngày vào</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                    <tr><td colspan="11" class="text-center text-muted" style="padding:40px">📭 Không có dữ liệu</td></tr>
                    <?php else: foreach ($employees as $emp): 
                        $statusClass = match($emp['status']) {
                            'Đang làm' => 'badge-success',
                            'Nghỉ việc' => 'badge-danger',
                            'Tạm ngưng' => 'badge-warning',
                        };
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($emp['employee_code']) ?></strong></td>
                        <td><?= htmlspecialchars($emp['full_name']) ?></td>
                        <td><?= $emp['gender'] ?></td>
                        <td><?= formatDate($emp['date_of_birth']) ?></td>
                        <td><?= htmlspecialchars($emp['phone']) ?></td>
                        <td><?= htmlspecialchars($emp['email']) ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($emp['dept_name'] ?? 'Chưa phân') ?></span></td>
                        <td><?= htmlspecialchars($emp['position']) ?></td>
                        <td><?= formatDate($emp['hire_date']) ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= $emp['status'] ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick='openEditModal(<?= json_encode($emp, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa nhân viên này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
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
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ Thêm nhân viên mới</h3>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Mã nhân viên *</label>
                        <input type="text" name="employee_code" class="form-control" required placeholder="VD: NV009">
                    </div>
                    <div class="form-group">
                        <label>Họ và tên *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Giới tính *</label>
                        <select name="gender" class="form-control" required>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày sinh</label>
                        <input type="date" name="date_of_birth" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" placeholder="09xxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@company.com">
                    </div>
                    <div class="form-group">
                        <label>Phòng ban</label>
                        <select name="department_id" class="form-control">
                            <option value="">-- Chọn phòng ban --</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Chức vụ</label>
                        <input type="text" name="position" class="form-control" placeholder="VD: Lập trình viên">
                    </div>
                    <div class="form-group">
                        <label>Ngày vào làm *</label>
                        <input type="date" name="hire_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status" class="form-control">
                            <option value="Đang làm">Đang làm</option>
                            <option value="Nghỉ việc">Nghỉ việc</option>
                            <option value="Tạm ngưng">Tạm ngưng</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Địa chỉ</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer" style="padding:16px 0 0; border:none;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('active')">Hủy</button>
                    <button type="submit" class="btn btn-success">💾 Lưu nhân viên</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Chỉnh sửa nhân viên</h3>
            <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Mã nhân viên *</label>
                        <input type="text" name="employee_code" id="edit_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Họ và tên *</label>
                        <input type="text" name="full_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Giới tính *</label>
                        <select name="gender" id="edit_gender" class="form-control" required>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày sinh</label>
                        <input type="date" name="date_of_birth" id="edit_dob" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Phòng ban</label>
                        <select name="department_id" id="edit_dept" class="form-control">
                            <option value="">-- Chọn phòng ban --</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Chức vụ</label>
                        <input type="text" name="position" id="edit_position" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Ngày vào làm *</label>
                        <input type="date" name="hire_date" id="edit_hire" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="Đang làm">Đang làm</option>
                            <option value="Nghỉ việc">Nghỉ việc</option>
                            <option value="Tạm ngưng">Tạm ngưng</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Địa chỉ</label>
                    <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer" style="padding:16px 0 0; border:none;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('editModal').classList.remove('active')">Hủy</button>
                    <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.add('active');
}

function openEditModal(emp) {
    document.getElementById('edit_id').value = emp.id;
    document.getElementById('edit_code').value = emp.employee_code;
    document.getElementById('edit_name').value = emp.full_name;
    document.getElementById('edit_gender').value = emp.gender;
    document.getElementById('edit_dob').value = emp.date_of_birth || '';
    document.getElementById('edit_phone').value = emp.phone || '';
    document.getElementById('edit_email').value = emp.email || '';
    document.getElementById('edit_dept').value = emp.department_id || '';
    document.getElementById('edit_position').value = emp.position || '';
    document.getElementById('edit_hire').value = emp.hire_date;
    document.getElementById('edit_status').value = emp.status;
    document.getElementById('edit_address').value = emp.address || '';
    document.getElementById('editModal').classList.add('active');
}
</script>

<?php require_once 'includes/footer.php'; ?>