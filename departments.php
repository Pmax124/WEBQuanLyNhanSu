<?php
$pageTitle = 'Quản Lý Phòng Ban';
$pageSubtitle = 'Thêm, sửa, xóa phòng ban';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $pdo->prepare("INSERT INTO departments (name, description) VALUES (?,?)")->execute([$name, $desc]);
        flash('success', '✅ Thêm phòng ban thành công!');
        redirect('departments.php');
    }
    if ($action === 'edit') {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $pdo->prepare("UPDATE departments SET name=?, description=? WHERE id=?")->execute([$name, $desc, $id]);
        flash('success', '✅ Cập nhật phòng ban thành công!');
        redirect('departments.php');
    }
    if ($action === 'delete') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM departments WHERE id=?")->execute([$id]);
        flash('success', '🗑️ Xóa phòng ban thành công!');
        redirect('departments.php');
    }
}

$departments = $pdo->query("SELECT d.*, COUNT(e.id) as emp_count 
    FROM departments d LEFT JOIN employees e ON d.id=e.department_id AND e.status='Đang làm'
    GROUP BY d.id ORDER BY d.name")->fetchAll();

$flashSuccess = flash('success');
?>

<?php if ($flashSuccess): ?><div class="alert alert-success"><?= $flashSuccess ?></div><?php endif; ?>

<div class="card mb-20">
    <div class="card-header">
        <h2>➕ Thêm phòng ban mới</h2>
    </div>
    <div class="card-body">
        <form method="POST" class="d-flex gap-10 flex-wrap align-center">
            <input type="hidden" name="action" value="add">
            <input type="text" name="name" class="form-control" placeholder="Tên phòng ban" required style="max-width:250px">
            <input type="text" name="description" class="form-control" placeholder="Mô tả" style="max-width:350px">
            <button type="submit" class="btn btn-success">➕ Thêm</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>🏬 Danh sách phòng ban (<?= count($departments) ?>)</h2>
    </div>
    <div class="card-body p-0">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên phòng ban</th>
                    <th>Mô tả</th>
                    <th>Số nhân viên</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                <tr>
                    <td><strong>#<?= $dept['id'] ?></strong></td>
                    <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                    <td><?= htmlspecialchars($dept['description'] ?? '-') ?></td>
                    <td><span class="badge badge-primary"><?= $dept['emp_count'] ?> người</span></td>
                    <td><?= formatDate($dept['created_at']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="openEditDept(<?= $dept['id'] ?>, '<?= addslashes($dept['name']) ?>', '<?= addslashes($dept['description'] ?? '') ?>')">✏️</button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Xóa phòng ban này?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                            <button class="btn btn-sm btn-danger">️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal sửa -->
<div class="modal-overlay" id="editDeptModal">
    <div class="modal" style="max-width:450px">
        <div class="modal-header">
            <h3>✏️ Sửa phòng ban</h3>
            <button class="modal-close" onclick="document.getElementById('editDeptModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_dept_id">
                <div class="form-group">
                    <label>Tên phòng ban</label>
                    <input type="text" name="name" id="edit_dept_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" id="edit_dept_desc" class="form-control" rows="3"></textarea>
                </div>
                <div class="modal-footer" style="padding:16px 0 0; border:none;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('editDeptModal').classList.remove('active')">Hủy</button>
                    <button type="submit" class="btn btn-primary">💾 Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditDept(id, name, desc) {
    document.getElementById('edit_dept_id').value = id;
    document.getElementById('edit_dept_name').value = name;
    document.getElementById('edit_dept_desc').value = desc;
    document.getElementById('editDeptModal').classList.add('active');
}
</script>

<?php require_once 'includes/footer.php'; ?>