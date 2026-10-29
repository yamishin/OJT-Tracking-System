<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$studentId]);
$documents = $stmt->fetchAll();

student_layout_top('Documents', 'Submit and track your OJT documents');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>...</span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span>...</span></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3>Submit a Document</h3>
    </div>
    <div class="panel-body">
        <form action="/PractiTrack/php/student/document-handler.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_document">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="field">
                <label>Document Type</label>
                <select name="doc_type" required>
                    <option value="">— Select type —</option>
                    <option>MOA / Memorandum of Agreement</option>
                    <option>Endorsement Letter</option>
                    <option>Parent's Consent</option>
                    <option>Medical Certificate</option>
                    <option>Resume / CV</option>
                    <option>Certificate of Completion</option>
                    <option>Other</option>
                </select>
            </div>

            <div class="file-drop">
                <div class="drop-icon"><i class="fas fa-cloud-upload-alt" style="font-size:40px; color:var(--light-blue);"></i></div>
                <div class="drop-title">Click or drag file here</div>
                <div class="drop-sub">PDF, DOC, DOCX, JPG, PNG — max 5MB</div>
                <input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:16px;">Upload Document</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>My Documents</h3>
        <div class="sub"><?= count($documents) ?> document(s)</div>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>File</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr>
                            <td colspan="5" class="empty">No documents submitted yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($documents as $d): ?>
                            <tr>
                                <td><strong><?= e($d['doc_type']) ?></strong></td>
                                <td><a href="/PractiTrack/<?= e($d['file_path']) ?>" target="_blank" style="color:var(--light-blue-dark);font-weight:600;">📄 <?= e($d['file_name']) ?></a></td>
                                <td><?= date('M d, Y', strtotime($d['submitted_at'])) ?></td>
                                <td><span class="badge <?= e($d['status']) ?>"><?= e($d['status']) ?></span></td>
                                <td><?= e($d['remarks'] ?? '—') ?></td>
                                <td><a href="/PractiTrack/<?= e($d['file_path']) ?>" target="_blank" style="color:var(--light-blue-dark);font-weight:600;"><i class="fas fa-file-pdf"></i> <?= e($d['file_name']) ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php student_layout_bottom(); ?>