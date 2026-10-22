<?php
require_once __DIR__ . '/../includes/admin-layout.php';

// Fetch existing companies, schools, and admins
$stmt = $pdo->query("SELECT role, first_name, last_name, email FROM users WHERE role IN ('company', 'school', 'admin') ORDER BY role, created_at DESC");
$users = $stmt->fetchAll();

admin_layout_top('Manage Accounts', 'Create and view Company, School, and Admin accounts');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success" style="font-size: 16px;"><span>🔑</span><span><?= $msg ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><span>⚠</span><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="grid-2">
    <!-- Create Form -->
    <div class="panel">
        <div class="panel-header"><h3>Create New Account</h3></div>
        <div class="panel-body">
            <form action="/PractiTrack/php/admin/user-handler.php" method="POST">
                <input type="hidden" name="action" value="create_user">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="field">
                    <label>Account Type</label>
                    <select name="role" id="roleSelect" required>
                        <option value="company">Company (OJT Host)</option>
                        <option value="school">School (Instructor/Coordinator)</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <div class="form-grid">
                    <div class="field">
                        <label>First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="field">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>

                <div class="field">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>

                <div class="field" id="orgField">
                    <label>Company/School Name</label>
                    <input type="text" name="org_name" id="orgInput" required>
                </div>

                <button type="submit" class="btn btn-primary" style="background:var(--gray-800);">Generate Account</button>
            </form>
        </div>
    </div>

    <!-- Existing Accounts -->
    <div class="panel">
        <div class="panel-header"><h3>Existing Accounts</h3></div>
        <div class="panel-body no-pad">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Role</th><th>Name</th><th>Email</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="3" class="empty">No accounts created yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><span class="badge" style="background:#E2E8F0; color:#1E293B;"><?= ucfirst($u['role']) ?></span></td>
                                    <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                    <td><?= e($u['email']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('roleSelect').addEventListener('change', function() {
        const orgInput = document.getElementById('orgInput');
        if (this.value === 'admin') {
            document.getElementById('orgField').style.display = 'none';
            orgInput.required = false;
        } else {
            document.getElementById('orgField').style.display = 'block';
            orgInput.required = true;
        }
    });
</script>

<?php admin_layout_bottom(); ?>