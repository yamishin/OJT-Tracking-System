<?php
require_once __DIR__ . '/../includes/admin-layout.php';

// Fetch companies and count assigned students
$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.is_active, 
           cp.company_name, cp.industry, cp.is_partner, cp.description, 
           cp.available_positions, cp.address, cp.website, cp.hr_contact_person, cp.social_media,
           cp.max_slots, cp.requirements,
           (SELECT COUNT(*) FROM student_profiles WHERE company_id = u.id AND application_status = 'accepted') as student_count
    FROM users u
    JOIN company_profiles cp ON cp.user_id = u.id
    WHERE u.role = 'company'
    ORDER BY cp.company_name ASC
");
$companies = $stmt->fetchAll();

// Fetch all students (Added required_hours to prevent JS errors)
$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, sp.student_no, sp.course, sp.required_hours, sp.company_id, sp.application_status,
    (SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE student_id = u.id AND verification_status = 'verified') as rendered_hours
    FROM users u
    JOIN student_profiles sp ON sp.user_id = u.id
    WHERE sp.company_id IS NOT NULL
");
$all_students = $stmt->fetchAll();

admin_layout_top('Company Partners', 'Manage OJT host companies');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $msg ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<style>
    .view-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
    .view-section h4 { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-400); margin-bottom: 8px; font-weight: 700; }
    .view-section p { font-size: 14px; color: var(--gray-800); line-height: 1.6; }
    .info-pill { display: inline-flex; align-items: center; gap: 8px; background: var(--gray-100); padding: 6px 12px; border-radius: var(--radius-sm); font-size: 13px; color: var(--gray-600); font-weight: 600; margin-right: 8px; margin-bottom: 8px; }
    .info-pill i { color: var(--light-blue-dark); }
</style>

<div class="panel">
    <div class="panel-header" style="flex-wrap: wrap; gap: 10px;">
        <div>
            <h3>Registered Companies</h3>
            <div class="sub" id="companyCount"><?= count($companies) ?> company account(s)</div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-left: auto; align-items: center;">
            <div class="field" style="margin: 0; width: 220px;">
                <input type="text" id="searchCompany" placeholder="Search company name..." style="padding: 8px 12px; font-size: 13px;">
            </div>
            <div class="field" style="margin: 0;">
                <select id="filterStatus" style="padding: 8px 12px; font-size: 13px;">
                    <option value="all">All Statuses</option>
                    <option value="1">Official Partners</option>
                    <option value="0">Standard Companies</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="openAddModal()" style="background:var(--gray-800);">
                <i class="fas fa-plus"></i> Add Company
            </button>
        </div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table" id="companiesTable">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Slots (Assigned/Max)</th>
                        <th>Partner Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($companies)): ?>
                        <tr><td colspan="5" class="empty">No companies registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($companies as $c): ?>
                            <tr class="company-row" data-status="<?= $c['is_partner'] ?>">
                                <td>
                                    <strong class="company-name"><?= e($c['company_name']) ?></strong><br>
                                    <small style="color:var(--gray-400);"><?= e($c['industry'] ?? 'Industry not set') ?></small>
                                </td>
                                <td>
                                    <?= e($c['first_name'] . ' ' . $c['last_name']) ?><br>
                                    <small style="color:var(--gray-400);"><?= e($c['email']) ?></small>
                                </td>
                                <td>
                                    <span class="badge" style="background:#E2E8F0; color:#1E293B;">
                                        <i class="fas fa-users"></i> <?= $c['student_count'] ?> / <?= $c['max_slots'] ?? '1' ?>
                                    </span>
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
                                        <button type="button" class="btn btn-sm" style="background:#F1F5F9; color:#1E293B;" onclick='viewCompany(<?= json_encode($c) ?>, <?= json_encode($all_students) ?>)'>
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
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

<!-- VIEW COMPANY MODAL -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-box" style="max-width: 750px; padding: 0;">
        <div class="modal-header">
            <h3><i class="fas fa-building"></i> <span id="v_name"></span></h3>
            <button class="modal-close" onclick="closeModal('viewModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <div style="margin-bottom: 20px;">
                <span class="info-pill" id="v_industry"><i class="fas fa-industry"></i> </span>
                <span class="info-pill" id="v_hr"><i class="fas fa-user-tie"></i> </span>
                <span class="info-pill" id="v_website"><i class="fas fa-globe"></i> </span>
            </div>

            <div class="view-modal-grid">
                <div class="view-section">
                    <h4>Address & Contact</h4>
                    <p id="v_address" style="margin-bottom: 10px;"></p>
                    <p id="v_social" style="margin-bottom: 0;"></p>
                </div>
                <div class="view-section">
                    <h4>Available Positions</h4>
                    <p id="v_positions"></p>
                </div>
            </div>

            <div class="view-section" style="margin-bottom: 24px;">
                <h4>Company Description</h4>
                <p id="v_description"></p>
            </div>

            <div class="view-section" style="margin-bottom: 24px;">
                <h4>Requirements</h4>
                <p id="v_requirements"></p>
            </div>

            <hr style="border:0; border-top: 2px dashed var(--gray-200); margin: 24px 0;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h4 style="font-size: 16px; color: var(--gray-800); font-family: var(--font-heading);"><i class="fas fa-user-graduate" style="color: var(--light-blue-dark);"></i> Assigned Students</h4>
                <span class="badge" style="background: var(--light-blue-light); color: var(--light-blue-dark);" id="v_student_count">0 Students</span>
            </div>

            <div class="table-wrap" style="border: 1px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden;">
                <table class="data-table" style="font-size: 13px;">
                    <thead style="background: var(--gray-100);">
                        <tr>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Progress</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="v_student_list">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm" onclick="closeModal('viewModal')" style="background:var(--gray-200); color:var(--gray-800);">Close</button>
        </div>
    </div>
</div>

<!-- ADD COMPANY MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box" style="max-width: 650px; padding: 0;">
        <form action="/PractiTrack/php/admin/company-handler.php" method="POST" style="display: flex; flex-direction: column; max-height: 85vh; width: 100%;">
            <input type="hidden" name="action" value="add_company">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="modal-header">
                <h3><i class="fas fa-building"></i> Add New Company</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field full">
                        <label>Company Name</label>
                        <input type="text" name="company_name" required>
                    </div>
                    <div class="field">
                        <label>Industry</label>
                        <input type="text" name="industry">
                    </div>
                    <div class="field">
                        <label>Max Student Slots</label>
                        <input type="number" name="max_slots" min="1" value="1" required>
                    </div>
                    <div class="field full">
                        <label>Partner Status</label>
                        <select name="is_partner">
                            <option value="1">Official Partner (Visible to Students)</option>
                            <option value="0">Standard (Hidden from Students)</option>
                        </select>
                    </div>
                    <div class="field full">
                        <label>Description</label>
                        <textarea name="description" style="min-height: 60px;"></textarea>
                    </div>
                    <div class="field full">
                        <label>Available Positions</label>
                        <input type="text" name="available_positions" placeholder="e.g. Web Developer, IT Support">
                    </div>
                    <div class="field full">
                        <label>Requirements</label>
                        <textarea name="requirements" style="min-height: 60px;"></textarea>
                    </div>
                    <div class="field">
                        <label>Address</label>
                        <input type="text" name="address">
                    </div>
                    <div class="field">
                        <label>Website</label>
                        <input type="text" name="website">
                    </div>
                    <div class="field">
                        <label>HR / Contact Person</label>
                        <input type="text" name="hr_contact_person">
                    </div>
                    <div class="field">
                        <label>Social Media / Email</label>
                        <input type="text" name="social_media">
                    </div>
                    <div class="field full" style="border-top: 1px dashed var(--gray-300); padding-top: 16px; margin-top: 8px;">
                        <h4 style="margin-bottom: 12px; font-size: 14px; color: var(--gray-800);"><i class="fas fa-user-lock"></i> Login Credentials</h4>
                        <div class="form-grid">
                            <div class="field">
                                <label>Company Email (Login)</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="field">
                                <label>First Name (Rep)</label>
                                <input type="text" name="first_name" required>
                            </div>
                            <div class="field">
                                <label>Last Name (Rep)</label>
                                <input type="text" name="last_name" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeModal('addModal')" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Create Company</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT COMPANY MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box" style="max-width: 650px; padding: 0;">
        <form action="/PractiTrack/php/admin/company-handler.php" method="POST" style="display: flex; flex-direction: column; max-height: 85vh; width: 100%;">
            <input type="hidden" name="action" value="edit_company">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="company_id" id="e_id">
            
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Company Details</h3>
                <button type="button" class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-body">
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
                        <label>Max Student Slots</label>
                        <input type="number" name="max_slots" id="e_slots" min="1" value="1" required>
                    </div>
                    <div class="field full">
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
                        <input type="text" name="available_positions" id="e_positions">
                    </div>
                    <div class="field full">
                        <label>Requirements</label>
                        <textarea name="requirements" id="e_requirements" style="min-height: 60px;"></textarea>
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
    function openAddModal() { document.getElementById('addModal').classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    function viewCompany(data, allStudents) {
        document.getElementById('v_name').textContent = data.company_name;
        document.getElementById('v_industry').innerHTML = `<i class="fas fa-industry"></i> ${data.industry || 'N/A'}`;
        document.getElementById('v_hr').innerHTML = `<i class="fas fa-user-tie"></i> ${data.hr_contact_person || 'N/A'}`;
        document.getElementById('v_website').innerHTML = `<i class="fas fa-globe"></i> ${data.website || 'N/A'}`;
        
        document.getElementById('v_address').innerHTML = `<strong>Address:</strong> ${data.address || 'Not specified'}`;
        document.getElementById('v_social').innerHTML = `<strong>Email/Social:</strong> ${data.social_media || 'Not specified'}`;
        
        document.getElementById('v_positions').textContent = data.available_positions || 'None specified.';
        document.getElementById('v_description').textContent = data.description || 'No description provided.';
        document.getElementById('v_requirements').textContent = data.requirements || 'No specific requirements listed.';

        const companyStudents = allStudents.filter(s => s.company_id == data.id);
        const tbody = document.getElementById('v_student_list');
        
        document.getElementById('v_student_count').textContent = `${companyStudents.length} Students`;

        if (companyStudents.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 20px; color: var(--gray-400);">No students assigned to this company yet.</td></tr>`;
        } else {
            let html = '';
            companyStudents.forEach(s => {
                // Use proper null checks to prevent JS errors
                const required = parseInt(s.required_hours) || 486;
                const rendered = parseFloat(s.rendered_hours) || 0;
                const progress = Math.min(100, Math.round((rendered / required) * 100));
                
                let statusBadge = '';
                if (s.application_status === 'accepted') statusBadge = '<span class="badge approved">Accepted</span>';
                else if (s.application_status === 'pending') statusBadge = '<span class="badge pending">Pending</span>';
                else statusBadge = `<span class="badge rejected">${s.application_status}</span>`;

                html += `
                    <tr>
                        <td>
                            <strong>${s.first_name} ${s.last_name}</strong><br>
                            <small style="color:var(--gray-400);">${s.student_no || 'No USN'}</small>
                        </td>
                        <td>${s.course || '—'}</td>
                        <td>
                            <div style="font-size:11px; font-weight:600; margin-bottom:2px;">${progress}%</div>
                            <div class="progress-block" style="margin:0;">
                                <div class="pb-bar" style="height:4px;">
                                    <div class="pb-fill" style="width: ${progress}%;"></div>
                                </div>
                            </div>
                        </td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        document.getElementById('viewModal').classList.add('open');
    }

    function editCompany(data) {
        document.getElementById('e_id').value = data.id;
        document.getElementById('e_name').value = data.company_name;
        document.getElementById('e_industry').value = data.industry || '';
        document.getElementById('e_slots').value = data.max_slots || 1;
        document.getElementById('e_partner').value = data.is_partner;
        document.getElementById('e_description').value = data.description || '';
        document.getElementById('e_positions').value = data.available_positions || '';
        document.getElementById('e_requirements').value = data.requirements || '';
        document.getElementById('e_address').value = data.address || '';
        document.getElementById('e_website').value = data.website || '';
        document.getElementById('e_hr').value = data.hr_contact_person || '';
        document.getElementById('e_social').value = data.social_media || '';
        document.getElementById('editModal').classList.add('open');
    }

    const searchInput = document.getElementById('searchCompany');
    const filterSelect = document.getElementById('filterStatus');
    const rows = document.querySelectorAll('.company-row');
    const countDisplay = document.getElementById('companyCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterTerm = filterSelect.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.querySelector('.company-name').textContent.toLowerCase();
            const status = row.getAttribute('data-status');

            const matchesSearch = name.includes(searchTerm);
            const matchesFilter = (filterTerm === 'all') || (status === filterTerm);

            if (matchesSearch && matchesFilter) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        countDisplay.textContent = `${visibleCount} company account(s)`;
    }

    if (searchInput && filterSelect) {
        searchInput.addEventListener('input', filterTable);
        filterSelect.addEventListener('change', filterTable);
    }
</script>

<?php admin_layout_bottom(); ?>