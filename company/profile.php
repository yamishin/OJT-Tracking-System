<?php
require_once __DIR__ . '/../includes/company-layout.php';

$companyId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM company_profiles WHERE user_id = ?");
$stmt->execute([$companyId]);
$profile = $stmt->fetch();

company_layout_top('Company Profile', 'View your company details');
?>

<div class="panel">
    <div class="panel-header">
        <h3>Company Information</h3>
    </div>
    <div class="panel-body">
        <div class="grid-2">
            <div>
                <p style="margin-bottom:12px;"><strong>Company Name:</strong> <?= e($profile['company_name']) ?></p>
                <p style="margin-bottom:12px;"><strong>Industry:</strong> <?= e($profile['industry'] ?? '—') ?></p>
                <p style="margin-bottom:12px;"><strong>Address:</strong> <?= e($profile['address'] ?? '—') ?></p>
                <p style="margin-bottom:12px;"><strong>Website:</strong> <?= e($profile['website'] ?? '—') ?></p>
            </div>
            <div>
                <p style="margin-bottom:12px;"><strong>HR / Contact Person:</strong> <?= e($profile['hr_contact_person'] ?? '—') ?></p>
                <p style="margin-bottom:12px;"><strong>Social Media / Email:</strong> <?= e($profile['social_media'] ?? '—') ?></p>
                <p style="margin-bottom:12px;"><strong>Max Student Slots:</strong> <?= e($profile['max_slots'] ?? '1') ?></p>
                <p style="margin-bottom:12px;"><strong>Partner Status:</strong> 
                    <?= $profile['is_partner'] ? '<span class="badge approved">Official Partner</span>' : '<span class="badge pending">Standard</span>' ?>
                </p>
            </div>
        </div>
        
        <hr style="margin:20px 0; border:0; border-top:1px solid var(--gray-200);">
        
        <h4 style="margin-bottom:8px; font-size:15px;">Description</h4>
        <p style="font-size:14px; color:var(--gray-600); margin-bottom:20px;"><?= nl2br(e($profile['description'] ?? 'No description provided.')) ?></p>

        <h4 style="margin-bottom:8px; font-size:15px;">Available Positions</h4>
        <p style="font-size:14px; color:var(--gray-600); margin-bottom:20px;"><?= nl2br(e($profile['available_positions'] ?? 'None specified.')) ?></p>

        <h4 style="margin-bottom:8px; font-size:15px;">Requirements</h4>
        <p style="font-size:14px; color:var(--gray-600);"><?= nl2br(e($profile['requirements'] ?? 'None specified.')) ?></p>
    </div>
</div>

<?php company_layout_bottom(); ?>