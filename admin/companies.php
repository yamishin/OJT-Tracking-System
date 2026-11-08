<?php
require_once __DIR__ . '/../includes/admin-layout.php';

// Fetch companies and count assigned students
$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.is_active, 
           cp.company_name, cp.industry, cp.is_partner, cp.description, 
           cp.available_positions, cp.address, cp.website, cp.hr_contact_person, cp.social_media,
           (SELECT COUNT(*) FROM student_profiles WHERE company_id = u.id) as student_count
    FROM users u
    JOIN company_profiles cp ON cp.user_id = u.id
    WHERE u.role = 'company'
    ORDER BY cp.company_name ASC
");
$companies = $stmt->fetchAll();

admin_layout_top('Company Partners', 'Manage OJT host companies');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3>Registered Companies</h3>
        <div class="sub"><?= count($companies) ?> company account(s)</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Assigned Students</th>
                        <th>Partner Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($companies)): ?>
                        <tr><td colspan="5" class="empty">No companies registered yet. Go to "Manage Accounts" to create one.</td></tr>
                    <?php else: ?>
                        <?php foreach ($companies as $c): ?>
                            <tr>
                                <td>
                                    <strong><?= e($c['company_name']) ?></strong><br>
                                    <small style="color:var(--gray-400);"><?= e($c['industry'] ?? 'Industry not set') ?></small>
                                </td>
                                <td>
                                    <?= e($c['first_name'] . ' ' . $c['last_name']) ?><br>
                                    <small style="color:var(--gray-400);"><?= e($c['email']) ?></small>
                                </td>
                                <td>
                                    <span class="badge" style="background:#E2E8F0; color:#1E293B;"><?= $c['student_count'] ?> Students</span>
                                </td>
                                <td>
                                    <?php if ($c['is_partner']): ?>
                                        <span class="badge approved">Official Partner</span>
                                    <?php else: ?>
                                        <span class="badge pending">Standard</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <!-- VIEW BUTTON -->
                                        <button type="button" class="btn btn-sm" style="background:#F1F5F9; color:#1E293B;" onclick='viewCompany(<?= json_encode($c) ?>)'>
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <!-- EDIT BUTTON -->
                                        <button type="button" class="btn btn-sm" style="background:#E0F2FE; color:#0284C7;" onclick='editCompany(<?= json_encode($c) ?>)'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- VIEW COMPANY MODAL -->
<!-- ========================================== -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-box" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-building"></i> <span id="v_name"></span></h3>
            <button class="modal-close" onclick="closeModal('viewModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <p style="font-size:14px; font-weight:600; color:var(--gray-600); margin-bottom:20px;" id="v_industry"></p>
            
            <h4 style="margin-bottom:8px; font-size:15px;">About the Company</h4>
            <p id="v_description" style="font-size:14px; color:var(--gray-800); margin-bottom:20px; line-height:1.6;"></p>
            
            <div class="grid-2" style="gap:16px;">
                <div>
                    <h4 style="margin-bottom:8px; font-size:15px;">Contact Info</h4>
                    <p style="font-size:13px; margin-bottom:6px;"><i class="fas fa-map-marker-alt" style="color:var(--gray-400); width:16px;"></i> <span id="v_address"></span></p>
                    <p style="font-size:13px; margin-bottom:6px;"><i class="fas fa-globe" style="color:var(--gray-400); width:16px;"></i> <span id="v_website"></span></p>
                    <p style="font-size:13px;"><i class="fas fa-envelope" style="color:var(--gray-400); width:16px;"></i> <span id="v_social"></span></p>
                </div>
                <div>
                    <h4 style="margin-bottom:8px; font-size:15px;">HR / Contact Person</h4>
                    <p style="font-size:13px; margin-bottom:6px;"><i class="fas fa-user-tie" style="color:var(--gray-400); width:16px;"></i> <span id="v_hr"></span></p>
                </div>
            </div>
            
            <h4 style="margin-top:20px; margin-bottom:8px; font-size:15px;">Available Positions</h4>
            <p style="font-size:14px;"><span id="v_positions"></span></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm" onclick="closeModal('viewModal')" style="background:var(--gray-200); color:var(--gray-800);">Close</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- EDIT COMPANY MODAL -->
<!-- ========================================== -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box" style="max-width: 650px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Company Details</h3>
            <button class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
        </div>
        <form action="/PractiTrack/php/admin/company-handler.php" method="POST">
            <input type="hidden" name="action" value="edit_company">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="company_id" id="e_id">
            
            <div class="modal-body" style="padding: 24px; max-height: 70vh; overflow-y: auto;">
                <div class="form-grid">
                    <div class="field full">
                        <label>Company Name</label>
                        <input type="text" name="company_name" id="e_name" required>
                    </div>
                    <div class="field">
                        <label>Industry</label>
                        <input type="text" name="industry" id="e_industry">
                    </div>
                    <div class="field">
                        <label>Partner Status</label>
                        <select name="is_partner" id="e_partner">
                            <option value="1">Official Partner (Visible to Students)</option>
                            <option value="0">Standard (Hidden from Students)</option>
                        </select>
                    </div>
                    
                    <div class="field full">
                        <label>Description</label>
                        <textarea name="description" id="e_description" style="min-height: 80px;"></textarea>
                    </div>
                    
                    <div class="field full">
                        <label>Available Positions</label>
                        <input type="text" name="available_positions" id="e_positions" placeholder="e.g. Web Developer, IT Support">
                    </div>
                    
                    <div class="field">
                        <label>Address</label>
                        <input type="text" name="address" id="e_address">
                    </div>
                    <div class="field">
                        <label>Website</label>
                        <input type="text" name="website" id="e_website">
                    </div>
                    
                    <div class="field">
                        <label>HR / Contact Person</label>
                        <input type="text" name="hr_contact_person" id="e_hr">
                    </div>
                    <div class="field">
                        <label>Social Media / Email</label>
                        <input type="text" name="social_media" id="e_social">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeModal('editModal')" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // View Modal Logic
    function viewCompany(data) {
        document.getElementById('v_name').textContent = data.company_name;
        document.getElementById('v_industry').textContent = data.industry || 'Industry not specified';
        document.getElementById('v_description').textContent = data.description || 'No description provided.';
        document.getElementById('v_address').textContent = data.address || 'Not specified';
        document.getElementById('v_website').textContent = data.website || 'Not specified';
        document.getElementById('v_social').textContent = data.social_media || 'Not specified';
        document.getElementById('v_hr').textContent = data.hr_contact_person || 'Not specified';
        document.getElementById('v_positions').textContent = data.available_positions || 'Not specified';
        
        document.getElementById('viewModal').classList.add('open');
    }

    // Edit Modal Logic
    function editCompany(data) {
        document.getElementById('e_id').value = data.id;
        document.getElementById('e_name').value = data.company_name;
        document.getElementById('e_industry').value = data.industry || '';
        document.getElementById('e_partner').value = data.is_partner;
        document.getElementById('e_description').value = data.description || '';
        document.getElementById('e_positions').value = data.available_positions || '';
        document.getElementById('e_address').value = data.address || '';
        document.getElementById('e_website').value = data.website || '';
        document.getElementById('e_hr').value = data.hr_contact_person || '';
        document.getElementById('e_social').value = data.social_media || '';
        
        document.getElementById('editModal').classList.add('open');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }
</script>

<?php admin_layout_bottom(); ?>