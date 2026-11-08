<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';
require_login('admin');

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid session.');
    redirect('/PractiTrack/admin/companies.php');
}

$action = $_POST['action'] ?? '';
$companyId = (int)($_POST['company_id'] ?? 0);

// 1. Handle Quick Toggle (Make/Remove Partner)
if ($action === '' && isset($_POST['current_status'])) {
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus === 1 ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE company_profiles SET is_partner = ? WHERE user_id = ?");
    $stmt->execute([$newStatus, $companyId]);

    $text = $newStatus === 1 ? 'added to' : 'removed from';
    set_flash('success', "Company successfully $text the partner list.");
    redirect('/PractiTrack/admin/companies.php');
}

// 2. Handle Full Edit Form
if ($action === 'edit_company') {
    $companyName = trim($_POST['company_name']);
    $industry = trim($_POST['industry'] ?? '');
    $isPartner = (int)$_POST['is_partner'];
    $description = trim($_POST['description'] ?? '');
    $positions = trim($_POST['available_positions'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $hrContact = trim($_POST['hr_contact_person'] ?? '');
    $social = trim($_POST['social_media'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE company_profiles SET 
            company_name = ?, industry = ?, is_partner = ?, description = ?, 
            available_positions = ?, address = ?, website = ?, hr_contact_person = ?, social_media = ?
            WHERE user_id = ?");
            
        $stmt->execute([
            $companyName, $industry, $isPartner, $description, 
            $positions, $address, $website, $hrContact, $social, $companyId
        ]);

        set_flash('success', 'Company profile updated successfully.');
    } catch (Exception $e) {
        set_flash('error', 'Failed to update company: ' . $e->getMessage());
    }

    redirect('/PractiTrack/admin/companies.php');
}

// Default fallback
redirect('/PractiTrack/admin/companies.php');