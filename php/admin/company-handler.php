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

// ============================================
// 1. HANDLE ADD COMPANY
// ============================================
if ($action === 'add_company') {
    $email = trim($_POST['email']);
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $companyName = trim($_POST['company_name']);
    
    $generatedPassword = 'PractiTrack2026';
    $hash = password_hash($generatedPassword, PASSWORD_DEFAULT);

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        set_flash('error', 'Email is already registered.');
        redirect('/PractiTrack/admin/companies.php');
    }

    try {
        $pdo->beginTransaction();

        // Create User Account
        $stmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, password_hash) VALUES ('company', ?, ?, ?, ?)");
        $stmt->execute([$firstName, $lastName, $email, $hash]);
        $userId = $pdo->lastInsertId();

        // Create Company Profile
        $industry = trim($_POST['industry'] ?? '');
        $maxSlots = (int)($_POST['max_slots'] ?? 1);
        $isPartner = (int)$_POST['is_partner'];
        $description = trim($_POST['description'] ?? '');
        $positions = trim($_POST['available_positions'] ?? '');
        $requirements = trim($_POST['requirements'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $hrContact = trim($_POST['hr_contact_person'] ?? '');
        $social = trim($_POST['social_media'] ?? '');

        $stmt = $pdo->prepare("INSERT INTO company_profiles 
            (user_id, company_name, industry, max_slots, is_partner, description, available_positions, requirements, address, website, hr_contact_person, social_media) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $userId, $companyName, $industry, $maxSlots, $isPartner, 
            $description, $positions, $requirements, $address, $website, $hrContact, $social
        ]);

        $pdo->commit();
        set_flash('success', "Company created!<br><br><strong>Email:</strong> $email<br><strong>Password:</strong> $generatedPassword<br><br><em>Please copy this password and give it to the company.</em>");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('error', 'Failed to create company: ' . $e->getMessage());
    }
    
    redirect('/PractiTrack/admin/companies.php');
}

// ============================================
// 2. HANDLE QUICK TOGGLE
// ============================================
if ($action === '' && isset($_POST['current_status'])) {
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus === 1 ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE company_profiles SET is_partner = ? WHERE user_id = ?");
    $stmt->execute([$newStatus, $companyId]);

    $text = $newStatus === 1 ? 'added to' : 'removed from';
    set_flash('success', "Company successfully $text the partner list.");
    redirect('/PractiTrack/admin/companies.php');
}

// ============================================
// 3. HANDLE FULL EDIT FORM
// ============================================
if ($action === 'edit_company') {
    $companyName = trim($_POST['company_name']);
    $industry = trim($_POST['industry'] ?? '');
    $maxSlots = (int)($_POST['max_slots'] ?? 1);
    $isPartner = (int)$_POST['is_partner'];
    $description = trim($_POST['description'] ?? '');
    $positions = trim($_POST['available_positions'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $hrContact = trim($_POST['hr_contact_person'] ?? '');
    $social = trim($_POST['social_media'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE company_profiles SET 
            company_name = ?, industry = ?, max_slots = ?, is_partner = ?, description = ?, 
            available_positions = ?, requirements = ?, address = ?, website = ?, hr_contact_person = ?, social_media = ?
            WHERE user_id = ?");
            
        $stmt->execute([
            $companyName, $industry, $maxSlots, $isPartner, $description, 
            $positions, $requirements, $address, $website, $hrContact, $social, $companyId
        ]);

        set_flash('success', 'Company profile updated successfully.');
    } catch (Exception $e) {
        set_flash('error', 'Failed to update company: ' . $e->getMessage());
    }

    redirect('/PractiTrack/admin/companies.php');
}

// Default fallback
redirect('/PractiTrack/admin/companies.php');