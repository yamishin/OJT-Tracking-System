<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

// Check if student is already assigned/pending
$stmt = $pdo->prepare("SELECT company_id, application_status FROM student_profiles WHERE user_id = ?");
$stmt->execute([$studentId]);
$myStatus = $stmt->fetch();

// Fetch all partner companies with slot counts
$stmt = $pdo->query("
    SELECT cp.*, 
    (SELECT COUNT(*) FROM student_profiles WHERE company_id = cp.user_id AND application_status = 'accepted') as current_slots
    FROM company_profiles cp 
    WHERE cp.is_partner = 1 
    ORDER BY cp.company_name ASC
");
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

<style>
    /* Custom Styles for Company Boxes */
    .company-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    .company-box {
        background: var(--white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .company-box:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--light-blue);
    }
    .company-header {
        padding: 20px 24px 16px;
        border-bottom: 1px dashed var(--gray-200);
        position: relative;
    }
    .company-header h3 {
        font-family: var(--font-heading);
        font-size: 18px;
        color: var(--gray-800);
        margin-bottom: 4px;
        padding-right: 60px; /* Space for slot badge */
    }
    .company-industry {
        font-size: 13px;
        color: var(--gray-400);
        font-weight: 600;
    }
    .slot-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: var(--light-blue-light);
        color: var(--light-blue-dark);
        padding: 4px 10px;
        border-radius: var(--radius-full);
        font-size: 12px;
        font-weight: 700;
    }
    .company-body {
        padding: 20px 24px;
        flex: 1;
    }
    .company-desc {
        font-size: 14px;
        color: var(--gray-600);
        margin-bottom: 16px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.5;
    }
    .company-detail-row {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: var(--gray-800);
        margin-bottom: 8px;
    }
    .company-detail-row i {
        color: var(--light-blue);
        width: 16px;
        text-align: center;
    }
    .company-footer {
        padding: 16px 24px;
        background: var(--gray-100);
        display: flex;
        gap: 10px;
        border-top: 1px solid var(--gray-200);
    }
</style>

<div class="company-grid">
    <?php if (empty($companies)): ?>
        <div class="empty-state" style="grid-column: 1 / -1;">
            <div class="em-icon"><i class="fas fa-building" style="color:var(--gray-300);"></i></div>
            <h4>No partner companies yet</h4>
            <p>Please check back later.</p>
        </div>
    <?php else: ?>
        <?php foreach ($companies as $c): 
            $maxSlots = (int)($c['max_slots'] ?? 1);
            $currentSlots = (int)$c['current_slots'];
            $availableSlots = max(0, $maxSlots - $currentSlots);
            $isFull = ($availableSlots <= 0);
        ?>
            <div class="company-box">
                <div class="company-header">
                    <h3><?= e($c['company_name']) ?></h3>
                    <p class="company-industry"><i class="fas fa-industry"></i> <?= e($c['industry'] ?? 'Various Industries') ?></p>
                    
                    <div class="slot-badge" style="<?= $isFull ? 'background:var(--red-light); color:var(--red);' : '' ?>">
                        <i class="fas fa-users"></i> 
                        <?= $isFull ? 'FULL' : "$availableSlots Slot(s) Left" ?>
                    </div>
                </div>
                
                <div class="company-body">
                    <p class="company-desc"><?= e($c['description'] ?? 'No description provided.') ?></p>
                    
                    <div class="company-detail-row">
                        <i class="fas fa-briefcase"></i>
                        <span><strong>Positions:</strong> <?= e($c['available_positions'] ?? 'Not specified') ?></span>
                    </div>
                    <div class="company-detail-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= e($c['address'] ?? 'Not specified') ?></span>
                    </div>
                    <div class="company-detail-row">
                        <i class="fas fa-user-tie"></i>
                        <span><strong>HR:</strong> <?= e($c['hr_contact_person'] ?? 'Not specified') ?></span>
                    </div>
                </div>

                <div class="company-footer">
                    <button class="btn btn-sm btn-ghost" onclick="viewCompany(<?= htmlspecialchars(json_encode($c)) ?>)" style="flex:1;">
                        <i class="fas fa-info-circle"></i> Details
                    </button>
                    
                    <?php if ($myStatus['application_status'] == 'unassigned' || $myStatus['application_status'] == 'rejected'): ?>
                        <?php if ($isFull): ?>
                            <button class="btn btn-sm btn-primary" style="flex:1; background:var(--gray-400); cursor:not-allowed;" disabled>Slots Full</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-primary" style="flex:1;" onclick="openApplyModal(<?= $c['user_id'] ?>, '<?= e($c['company_name']) ?>')">
                                <i class="fas fa-paper-plane"></i> Apply
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Company Modal -->
<div class="modal-overlay" id="companyModal">
    <div class="modal-box" style="max-width: 600px; padding: 0;">
        <form style="display: flex; flex-direction: column; max-height: 85vh;"> <!-- Wrapper for scroll fix -->
            <div class="modal-header">
                <h3><i class="fas fa-building"></i> Company Profile</h3>
                <button type="button" class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
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
                <p style="font-size:14px; margin-bottom:16px;"><span id="m_positions"></span></p>

                <h4 style="margin-bottom:8px; font-size:15px;">Requirements</h4>
                <p style="font-size:14px;"><span id="m_requirements"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeModal()" style="background:var(--gray-200); color:var(--gray-800);">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Apply Modal -->
<div class="modal-overlay" id="applyModal">
    <div class="modal-box" style="max-width: 450px; padding: 0;">
        <form action="/PractiTrack/php/student/apply-handler.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; max-height: 85vh;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="company_id" id="apply_company_id">
            
            <div class="modal-header">
                <h3><i class="fas fa-paper-plane"></i> Apply to Company</h3>
                <button type="button" class="modal-close" onclick="closeApplyModal()"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-body">
                <p style="margin-bottom: 16px; font-size: 14px;">You are applying to <strong id="apply_company_name" style="color:var(--light-blue-dark);"></strong></p>
                
                <div class="field">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= e($_SESSION['name']) ?>" readonly style="background:var(--gray-100);">
                </div>

                <div class="field">
                    <label>Attach Resume (PDF) - <em>Visual Only</em></label>
                    <div class="file-drop" style="padding: 20px;">
                        <div class="drop-icon"><i class="fas fa-file-pdf" style="font-size:30px; color:var(--red);"></i></div>
                        <div class="drop-title" style="font-size:13px;">Click to upload Resume</div>
                        <input type="file" name="resume" accept=".pdf">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeApplyModal()" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<script>
    const applyModal = document.getElementById('applyModal');
    const companyModal = document.getElementById('companyModal');

    function openApplyModal(id, name) {
        document.getElementById('apply_company_id').value = id;
        document.getElementById('apply_company_name').textContent = name;
        applyModal.classList.add('open');
    }

    function closeApplyModal() { 
        applyModal.classList.remove('open'); 
    }

    function viewCompany(data) {
        document.getElementById('m_company_name').textContent = data.company_name;
        document.getElementById('m_industry').textContent = data.industry || 'Industry not specified';
        document.getElementById('m_description').textContent = data.description || 'No description provided.';
        document.getElementById('m_address').textContent = data.address || 'Not specified';
        document.getElementById('m_website').textContent = data.website || 'Not specified';
        document.getElementById('m_social').textContent = data.social_media || 'Not specified';
        document.getElementById('m_hr').textContent = data.hr_contact_person || 'Not specified';
        document.getElementById('m_positions').textContent = data.available_positions || 'Not specified';
        document.getElementById('m_requirements').textContent = data.requirements || 'No specific requirements listed.';

        companyModal.classList.add('open');
    }

    function closeModal() {
        companyModal.classList.remove('open');
    }
</script>

<?php student_layout_bottom(); ?>