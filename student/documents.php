<?php
require_once __DIR__ . '/../includes/student-layout.php';

$studentId = $_SESSION['user_id'];

// Fetch documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$studentId]);
$documents = $stmt->fetchAll();

student_layout_top('Documents', 'Submit and track your OJT documents');
?>

<?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><span><?= e($msg) ?></span></div>
<?php endif; ?>

<!-- Header Panel with Add Button -->
<div class="panel" style="margin-bottom: 24px; background: linear-gradient(135deg, var(--light-blue-dark), var(--light-blue)); color: white; border: none;">
    <div class="panel-body" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-family: var(--font-heading); font-size: 22px; margin-bottom: 4px;">My Documents</h2>
            <p style="font-size: 14px; opacity: 0.9;">Upload your MOA, endorsement letters, and other OJT requirements.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openDocModal()" style="background: var(--white); color: var(--light-blue-dark); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas fa-cloud-upload-alt"></i> Add Document
        </button>
    </div>
</div>

<style>
    /* Document Grid & Box Styles */
    .docs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    .doc-box {
        background: var(--white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        padding: 24px;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        position: relative;
    }
    .doc-box:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--light-blue);
    }
    .doc-icon {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-md);
        background: var(--light-blue-light);
        color: var(--light-blue-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 16px;
    }
    .doc-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 4px;
        line-height: 1.3;
    }
    .doc-file {
        font-size: 13px;
        color: var(--gray-400);
        margin-bottom: 16px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .doc-footer {
        margin-top: auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 16px;
        border-top: 1px dashed var(--gray-200);
    }
</style>

<!-- Documents Grid -->
<div class="docs-grid">
    <?php if (empty($documents)): ?>
        <div class="empty-state" style="grid-column: 1 / -1; background:var(--white); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm);">
            <div class="em-icon"><i class="fas fa-folder-open" style="color:var(--gray-300);"></i></div>
            <h4>No documents yet</h4>
            <p>Click "Add Document" to upload your requirements.</p>
        </div>
    <?php else: ?>
        <?php foreach ($documents as $d): 
            // Choose icon based on file type
            $icon = 'fa-file-alt';
            $ext = strtolower(pathinfo($d['file_name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') $icon = 'fa-file-pdf';
            elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) $icon = 'fa-file-image';
        ?>
            <div class="doc-box">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div class="doc-icon"><i class="fas <?= $icon ?>"></i></div>
                    <span class="badge <?= e($d['status']) ?>"><?= e($d['status']) ?></span>
                </div>
                
                <div class="doc-title"><?= e($d['doc_type']) ?></div>
                <div class="doc-file" title="<?= e($d['file_name']) ?>"><?= e($d['file_name']) ?></div>
                
                <?php if ($d['status'] === 'rejected' && $d['remarks']): ?>
                    <div style="font-size:12px; color:var(--red-dark); background:var(--red-light); padding:8px; border-radius:4px; margin-bottom:12px;">
                        <strong>Reason:</strong> <?= e($d['remarks']) ?>
                    </div>
                <?php endif; ?>

                <div class="doc-footer">
                    <span style="font-size:12px; color:var(--gray-400);"><?= date('M d, Y', strtotime($d['submitted_at'])) ?></span>
                    <a href="/PractiTrack/<?= e($d['file_path']) ?>" target="_blank" class="btn btn-sm btn-ghost" style="padding:6px 12px;">
                        <i class="fas fa-download"></i> View
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- ADD DOCUMENT MODAL -->
<!-- ========================================== -->
<div class="modal-overlay" id="addDocModal">
    <div class="modal-box" style="max-width: 500px; padding: 0;">
        
        <form action="/PractiTrack/php/student/document-handler.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; max-height: 85vh;">
            <input type="hidden" name="action" value="upload_document">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="modal-header">
                <h3><i class="fas fa-cloud-upload-alt"></i> Upload Document</h3>
                <button type="button" class="modal-close" onclick="closeDocModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
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

                <div class="file-drop" style="padding: 30px 20px;">
                    <div class="drop-icon"><i class="fas fa-file-upload" style="font-size:40px; color:var(--light-blue);"></i></div>
                    <div class="drop-title">Click or drag file here</div>
                    <div class="drop-sub">PDF, DOC, DOCX, JPG, PNG — max 5MB</div>
                    <input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                </div>
                
                <div class="form-hint" style="margin-top: 12px; text-align: center;">
                    <em>Make sure the document is clear and readable.</em>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm" onclick="closeDocModal()" style="background:var(--gray-200); color:var(--gray-800);">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Upload Document</button>
            </div>
        </form>
    </div>
</div>

<script>
    const docModal = document.getElementById('addDocModal');
    
    function openDocModal() {
        docModal.classList.add('open');
    }

    function closeDocModal() {
        docModal.classList.remove('open');
    }

    // Close on clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === docModal) closeDocModal();
    });

    // --- FIX: File Drop Zone Logic ---
    document.addEventListener('DOMContentLoaded', () => {
        const dropZone = document.querySelector('.file-drop');
        const fileInput = dropZone?.querySelector('input[type="file"]');
        const dropTitle = dropZone?.querySelector('.drop-title');

        if (dropZone && fileInput) {
            // 1. Click to open file explorer
            dropZone.addEventListener('click', () => fileInput.click());

            // 2. Drag & Drop visual effects
            ['dragenter', 'dragover'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropZone.classList.add('drag');
                });
            });

            ['dragleave', 'drop'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('drag');
                });
            });

            // 3. Handle Drop
            dropZone.addEventListener('drop', (e) => {
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    updateFileName();
                }
            });

            // 4. Handle normal click selection
            fileInput.addEventListener('change', updateFileName);

            // Function to show the selected file name
            function updateFileName() {
                if (fileInput.files.length > 0) {
                    dropTitle.textContent = fileInput.files[0].name;
                    dropTitle.style.color = 'var(--light-blue-dark)';
                }
            }
        }
    });
</script>

<?php student_layout_bottom(); ?>