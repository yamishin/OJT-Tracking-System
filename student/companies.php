<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

// Check if student is already assigned/pending
$stmt = $pdo->prepare("SELECT company_id, application_status FROM student_profiles WHERE user_id = ?");
$stmt->execute([$studentId]);
$myStatus = $stmt->fetch();

// Fetch all partner companies
$stmt = $pdo->query("SELECT * FROM company_profiles WHERE is_partner = 1 ORDER BY company_name ASC");
$companies = $stmt->fetchAll();

student_layout_top('Find a Company', 'Browse OJT partner companies and apply');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<?php if ($myStatus['company_id'] && $myStatus['application_status'] == 'pending'): ?>
    <div class="alert alert-info">
        <i class="fas fa-hourglass-half"></i>
        <span>You have a pending application. You cannot apply to other companies until it is processed.</span>
    </div>
<?php elseif ($myStatus['company_id'] && $myStatus['application_status'] == 'accepted'): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span>You are officially assigned to a company! Check your dashboard for details.</span>
    </div>
<?php endif; ?>

<div class="features-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
    <?php if (empty($companies)): ?>
        <div class="empty-state" style="grid-column: 1 / -1;">
            <div class="em-icon"><i class="fas fa-building" style="color:var(--gray-300);"></i></div>
            <h4>No partner companies yet</h4>
            <p>Please check back later.</p>
        </div>
    <?php else: ?>
        <?php foreach ($companies as $c): ?>
            <div class="panel" style="margin-bottom:0;">
                <div class="panel-body">
                    <h3 style="color:var(--light-blue-dark); margin-bottom:4px;"><?= e($c['company_name']) ?></h3>
                    <p style="font-size:13px; color:var(--gray-400); margin-bottom:16px;"><i class="fas fa-industry"></i> <?= e($c['industry'] ?? 'Various Industries') ?></p>
                    
                    <p style="font-size:14px; color:var(--gray-600); margin-bottom:16px; height:60px; overflow:hidden; text-overflow: ellipsis;">
                        <?= e($c['description'] ?? 'No description provided.') ?>
                    </p>

                    <div style="font-size:13px; margin-bottom:16px;">
                        <p style="margin-bottom:6px;"><strong><i class="fas fa-briefcase"></i> Positions:</strong> <?= e($c['available_positions'] ?? 'Not specified') ?></p>
                        <p style="margin-bottom:6px;"><strong><i class="fas fa-map-marker-alt"></i> Address:</strong> <?= e($c['address'] ?? 'Not specified') ?></p>
                        <p><strong><i class="fas fa-user-tie"></i> HR Contact:</strong> <?= e($c['hr_contact_person'] ?? 'Not specified') ?></p>
                    </div>

                    <div style="display:flex; gap:8px; margin-top:20px;">
                        <button class="btn btn-sm btn-outline" onclick="viewCompany(<?= htmlspecialchars(json_encode($c)) ?>)" style="flex:1;">View Details</button>
                        
                        <?php if ($myStatus['application_status'] == 'unassigned' || $myStatus['application_status'] == 'rejected'): ?>
                            <form action="/PractiTrack/php/student/apply-handler.php" method="POST" style="flex:1;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="company_id" value="<?= $c['user_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-primary" style="width:100%;" data-confirm="Apply to <?= e($c['company_name']) ?>?">Apply Now</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Company Modal -->
<div class="modal-overlay" id="companyModal">
    <div class="modal-box" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-building"></i> Company Profile</h3>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <h2 id="m_company_name" style="color:var(--light-blue-dark); margin-bottom:8px;"></h2>
            <p id="m_industry" style="font-size:14px; font-weight:600; color:var(--gray-600); margin-bottom:20px;"></p>
            
            <h4 style="margin-bottom:8px; font-size:15px;">About the Company</h4>
            <p id="m_description" style="font-size:14px; color:var(--gray-800); margin-bottom:20px; line-height:1.6;"></p>
            
            <div class="grid-2" style="gap:16px;">
                <div>
                    <h4 style="margin-bottom:8px; font-size:15px;">Contact Info</h4>
                    <p style="font-size:13px; margin-bottom:6px;"><i class="fas fa-map-marker-alt" style="color:var(--gray-400); width:16px;"></i> <span id="m_address"></span></p>
                    <p style="font-size:13px; margin-bottom:6px;"><i class="fas fa-globe" style="color:var(--gray-400); width:16px;"></i> <span id="m_website"></span></p>
                    <p style="font-size:13px;"><i class="fas fa-envelope" style="color:var(--gray-400); width:16px;"></i> <span id="m_social"></span></p>
                </div>
                <div>
                    <h4 style="margin-bottom:8px; font-size:15px;">HR / Contact Person</h4>
                    <p style="font-size:13px; margin-bottom:6px;"><i class="fas fa-user-tie" style="color:var(--gray-400); width:16px;"></i> <span id="m_hr"></span></p>
                </div>
            </div>
            
            <h4 style="margin-top:20px; margin-bottom:8px; font-size:15px;">Available Positions</h4>
            <p style="font-size:14px;"><span id="m_positions"></span></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm" onclick="closeModal()" style="background:var(--gray-200); color:var(--gray-800);">Close</button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('companyModal');

    function viewCompany(data) {
        document.getElementById('m_company_name').textContent = data.company_name;
        document.getElementById('m_industry').textContent = data.industry || 'Industry not specified';
        document.getElementById('m_description').textContent = data.description || 'No description provided.';
        document.getElementById('m_address').textContent = data.address || 'Not specified';
        document.getElementById('m_website').textContent = data.website || 'Not specified';
        document.getElementById('m_social').textContent = data.social_media || 'Not specified';
        document.getElementById('m_hr').textContent = data.hr_contact_person || 'Not specified';
        document.getElementById('m_positions').textContent = data.available_positions || 'Not specified';

        modal.classList.add('open');
    }

    function closeModal() {
        modal.classList.remove('open');
    }
</script>

<?php student_layout_bottom(); ?>