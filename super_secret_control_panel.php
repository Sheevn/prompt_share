<?php
session_start();

// --- CONFIGURATION ---
$admin_pin = 'CHANGE_THIS_TO_YOUR_PIN'; // IMPORTANT: Change this to your secret PIN!
$timeout_duration = 1800;
$page_title = 'Admin Panel';
$promptDir = 'prompts';
$commentsDir = 'comments';

// --- LOGOUT LOGIC ---
if (isset($_GET['logout'])) { session_unset(); session_destroy(); header('Location: super_secret_control_panel.php'); exit; }

// --- LOGIN POST HANDLING ---
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    if ($_POST['pin'] === $admin_pin) { $_SESSION['is_admin_logged_in'] = true; $_SESSION['last_activity'] = time(); header('Location: super_secret_control_panel.php'); exit; } 
    else { $login_error = 'Invalid PIN. Please try again.'; }
}

// --- SESSION VALIDATION ---
$is_logged_in = false;
if (isset($_SESSION['is_admin_logged_in']) && $_SESSION['is_admin_logged_in'] === true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) { session_unset(); session_destroy(); } 
    else { $_SESSION['last_activity'] = time(); $is_logged_in = true; }
}

// --- DISPLAY LOGIN PAGE OR ADMIN PANEL ---
if (!$is_logged_in) {
    include 'templates/header.php';
    ?>
    <div class="login-container"><div class="login-form"><h1>Admin Access</h1><p>Please enter your PIN to continue.</p><form action="super_secret_control_panel.php" method="post"><input type="password" name="pin" id="pin" placeholder="••••" required autofocus><button type="submit">Login</button></form><?php if (!empty($login_error)): ?><p class="error-message"><?php echo $login_error; ?></p><?php endif; ?></div></div>
    <?php
    include 'templates/footer.php';
    exit;
}

// --- REUSABLE FUNCTION TO DELETE A DIRECTORY AND ITS CONTENTS ---
function delete_directory($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) { (is_dir("$dir/$file")) ? delete_directory("$dir/$file") : unlink("$dir/$file"); }
    rmdir($dir);
}

// --- FORM PROCESSING LOGIC ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_prompt'])) {
        $category = $_POST['category']; $file = $_POST['file'];
        $prompt_path = "$promptDir/$category/$file";
        $comment_path = "$commentsDir/$category/" . basename($file, '.md') . '.txt';
        $tags_path = "$commentsDir/$category/" . basename($file, '.md') . '.tags';
        if (file_exists($prompt_path)) {
            unlink($prompt_path);
            if (file_exists($comment_path)) { unlink($comment_path); }
            if (file_exists($tags_path)) { unlink($tags_path); }
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Prompt deleted successfully.'];
        } else { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error: Could not find prompt to delete.']; }
        header('Location: index.php'); exit;
    }
    elseif (isset($_POST['submit_prompt']) || isset($_POST['submit_update'])) {
        $is_update = isset($_POST['submit_update']);
        $category = ($_POST['category_select'] === '--new--') ? trim($_POST['category_new']) : $_POST['category_select'];
        $comment = trim($_POST['comment']);
        $tags_string = trim($_POST['tags']);
        $content = '';
        $filename_no_ext = '';
        $action_text = $is_update ? "updated" : "created";

        if ($is_update || (isset($_POST['source_type']) && $_POST['source_type'] === 'paste')) {
            $filename_no_ext = trim($_POST['filename']);
            $content = trim($_POST['prompt_content']);
        } elseif (isset($_POST['source_type']) && $_POST['source_type'] === 'upload') {
            if (isset($_FILES['md_file']) && $_FILES['md_file']['error'] == 0) {
                $filename_no_ext = pathinfo($_FILES['md_file']['name'], PATHINFO_FILENAME);
                $content = file_get_contents($_FILES['md_file']['tmp_name']);
            }
        }

        if (!empty($category) && !empty($filename_no_ext) && !empty($content)) {
            $sane_category = preg_replace('/[^a-zA-Z0-9_-]/', '', $category);
            
            // CORRECTED: Replace spaces with hyphens first, then sanitize
            $filename_with_hyphens = str_replace(' ', '-', $filename_no_ext);
            $sane_filename = preg_replace('/[^a-zA-Z0-9-]/', '', $filename_with_hyphens) . '.md';

            if (!is_dir("$promptDir/$sane_category")) mkdir("$promptDir/$sane_category", 0755, true);
            if (!is_dir("$commentsDir/$sane_category")) mkdir("$commentsDir/$sane_category", 0755, true);
            file_put_contents("$promptDir/$sane_category/$sane_filename", $content);
            file_put_contents("$commentsDir/$sane_category/" . basename($sane_filename, '.md') . '.txt', $comment);
            file_put_contents("$commentsDir/$sane_category/" . basename($sane_filename, '.md') . '.tags', $tags_string);
            $view_link = 'view.php?category=' . urlencode($sane_category) . '&file=' . urlencode($sane_filename);
            $success_text = "Prompt '<a href=\"{$view_link}\" target=\"_blank\">" . htmlspecialchars($filename_no_ext) . "</a>' {$action_text} successfully!";
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => $success_text];
            header('Location: super_secret_control_panel.php'); exit;
        } else { $message = "<p class='error'>Please fill out all required fields.</p>"; }
    }
    elseif (isset($_POST['rename_category'])) {
        $old_name = $_POST['category_to_rename'];
        $new_name_raw = trim($_POST['new_category_name']);
        if (!empty($old_name) && !empty($new_name_raw)) {
            $new_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $new_name_raw);
            if (is_dir("$promptDir/$old_name") && !is_dir("$promptDir/$new_name")) {
                rename("$promptDir/$old_name", "$promptDir/$new_name");
                if (is_dir("$commentsDir/$old_name")) { rename("$commentsDir/$old_name", "$commentsDir/$new_name"); }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Category '$old_name' renamed to '$new_name'."];
            } else { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error renaming category. Check if it exists or if the new name is already taken.']; }
        } else { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Please select a category and provide a new name.']; }
        header('Location: super_secret_control_panel.php'); exit;
    }
    elseif (isset($_POST['delete_category'])) {
        $category_to_delete = $_POST['category_to_delete'];
        if (!empty($category_to_delete)) {
            delete_directory("$promptDir/$category_to_delete");
            delete_directory("$commentsDir/$category_to_delete");
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Category '$category_to_delete' and all its prompts have been deleted."];
        } else { $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Please select a category to delete.']; }
        header('Location: super_secret_control_panel.php'); exit;
    }
}
$edit_mode = false;
$edit_data = ['category' => '', 'filename_no_ext' => '', 'comment' => '', 'content' => '', 'tags' => ''];
if (isset($_GET['edit']) && $_GET['edit'] === 'true') {
    $edit_mode = true; $category = $_GET['category']; $file = $_GET['file'];
    $prompt_path = "$promptDir/$category/$file";
    if (file_exists($prompt_path)) {
        $edit_data['category'] = $category; $edit_data['filename_no_ext'] = basename($file, '.md');
        $edit_data['content'] = file_get_contents($prompt_path);
        $comment_path = "$commentsDir/$category/" . $edit_data['filename_no_ext'] . '.txt';
        $tags_path = "$commentsDir/$category/" . $edit_data['filename_no_ext'] . '.tags';
        if (file_exists($comment_path)) { $edit_data['comment'] = file_get_contents($comment_path); }
        if (file_exists($tags_path)) { $edit_data['tags'] = file_get_contents($tags_path); }
    }
}
$existing_categories = is_dir($promptDir) ? array_filter(scandir($promptDir), function($item) use ($promptDir) { return is_dir($promptDir . '/' . $item) && !in_array($item, ['.', '..']); }) : [];
include 'templates/header.php';
?>
<div class="container">
    <h1><?php echo $edit_mode ? 'Edit Prompt' : 'Admin Control Panel'; ?></h1>
    <?php if (isset($_SESSION['flash_message'])) { $flash = $_SESSION['flash_message']; echo "<div class='{$flash['type']}'>" . $flash['text'] . "</div>"; unset($_SESSION['flash_message']); } echo $message; ?>

    <?php if ($edit_mode): ?>
        <div class="admin-section">
            <h2>Update Prompt Details</h2>
            <form action="super_secret_control_panel.php" method="post">
                <input type="hidden" name="submit_update" value="1">
                <label for="category_select">Category:</label>
                <div class="select-wrapper"><select name="category_select" id="category_select" required><?php foreach ($existing_categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>" <?php if($cat === $edit_data['category']) echo 'selected'; ?>><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $cat))); ?></option><?php endforeach; ?><option value="--new--">** Add New Category **</option></select></div>
                <div id="new_category_wrapper" class="hidden"><label for="category_new">New Category Name:</label><input type="text" id="category_new" name="category_new"></div>
                <label for="filename">Filename (no extension):</label><input type="text" id="filename" name="filename" value="<?php echo htmlspecialchars(str_replace('-', ' ', $edit_data['filename_no_ext'])); ?>" required readonly>
                <label for="comment">My Comments/Notes:</label><textarea id="comment" name="comment" rows="4"><?php echo htmlspecialchars($edit_data['comment']); ?></textarea>
                <label for="tags">Tags (comma-separated):</label><input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($edit_data['tags']); ?>">
                <label for="prompt_content">Prompt Markdown Content:</label><textarea id="prompt_content" name="prompt_content" rows="10" required><?php echo htmlspecialchars($edit_data['content']); ?></textarea>
                <button type="submit">Update Prompt</button>
            </form>
        </div>
    <?php else: ?>
        <div class="admin-dashboard">
            <div class="dashboard-column-actions">
                <div class="admin-section">
                    <h2>Add New Prompt</h2>
                    <form action="super_secret_control_panel.php" method="post" enctype="multipart/form-data" id="add-prompt-form">
                        <div class="tab-buttons">
                            <button type="button" class="tab-button active" data-tab="paste">Paste Text</button>
                            <button type="button" class="tab-button" data-tab="upload">Upload File</button>
                        </div>
                        <input type="hidden" name="source_type" id="source_type" value="paste">
                        <label for="category_select">Category:</label>
                        <div class="select-wrapper"><select name="category_select" id="category_select" required><option value="" disabled selected>-- Select a Category --</option><?php foreach ($existing_categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $cat))); ?></option><?php endforeach; ?><option value="--new--">** Add New Category **</option></select></div>
                        <div id="new_category_wrapper" class="hidden"><label for="category_new">New Category Name:</label><input type="text" id="category_new" name="category_new"></div>
                        <div id="paste" class="tab-panel active">
                            <label for="filename">Filename (no extension):</label><input type="text" id="filename" name="filename" required>
                            <label for="prompt_content">Prompt Markdown Content:</label><textarea id="prompt_content" name="prompt_content" rows="10" required></textarea>
                        </div>
                        <div id="upload" class="tab-panel">
                            <label for="md_file">Select .md file:</label><input type="file" id="md_file" name="md_file" accept=".md">
                        </div>
                        <label for="comment">My Comments/Notes:</label><textarea id="comment" name="comment" rows="4"></textarea>
                        <label for="tags">Tags (comma-separated):</label><input type="text" id="tags" name="tags">
                        <button type="submit" name="submit_prompt">Create Prompt</button>
                    </form>
                </div>
            </div>
            <div class="dashboard-column-management">
                <div class="admin-section">
                    <h2>Category Management</h2>
                    <form action="super_secret_control_panel.php" method="post" class="management-form">
                        <h3>Rename Category</h3>
                        <label for="category_to_rename">Category to Rename:</label>
                        <div class="select-wrapper"><select name="category_to_rename" id="category_to_rename" required><option value="" disabled selected>-- Select Category --</option><?php foreach ($existing_categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $cat))); ?></option><?php endforeach; ?></select></div>
                        <label for="new_category_name">New Name:</label><input type="text" name="new_category_name" id="new_category_name" required>
                        <button type="submit" name="rename_category">Rename</button>
                    </form>
                    <form action="super_secret_control_panel.php" method="post" class="management-form" onsubmit="return confirm('WARNING: This will permanently delete the category and ALL prompts inside it. This cannot be undone. Are you sure?');">
                        <h3>Delete Category</h3>
                        <label for="category_to_delete">Category to Delete:</label>
                        <div class="select-wrapper"><select name="category_to_delete" id="category_to_delete" required><option value="" disabled selected>-- Select Category --</option><?php foreach ($existing_categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $cat))); ?></option><?php endforeach; ?></select></div>
                        <button type="submit" name="delete_category" class="delete-btn">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const catSelect = document.getElementById('category_select');
    const newCatWrapper = document.getElementById('new_category_wrapper');
    const newCatInput = document.getElementById('category_new');
    if (catSelect) {
        catSelect.addEventListener('change', function() {
            if (this.value === '--new--') {
                newCatWrapper.classList.remove('hidden');
                newCatInput.required = true;
            } else {
                newCatWrapper.classList.add('hidden');
                newCatInput.required = false;
            }
        });
    }

    const tabButtons = document.querySelectorAll('.tab-button');
    const pastePanel = document.getElementById('paste');
    const uploadPanel = document.getElementById('upload');
    const sourceTypeInput = document.getElementById('source_type');
    const filenameInput = document.getElementById('filename');
    const contentTextarea = document.getElementById('prompt_content');
    const fileInput = document.getElementById('md_file');

    function setRequiredFields(activeTab) {
        if (activeTab === 'paste') {
            filenameInput.required = true;
            contentTextarea.required = true;
            if(fileInput) fileInput.required = false;
        } else { // upload
            filenameInput.required = false;
            contentTextarea.required = false;
            if(fileInput) fileInput.required = true;
        }
    }

    if (tabButtons.length > 0 && pastePanel && uploadPanel) {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                const tab = this.getAttribute('data-tab');
                sourceTypeInput.value = tab;
                if (tab === 'paste') {
                    pastePanel.classList.add('active');
                    uploadPanel.classList.remove('active');
                } else {
                    pastePanel.classList.remove('active');
                    uploadPanel.classList.add('active');
                }
                setRequiredFields(tab);
            });
        });
        setRequiredFields('paste');
    }
});
</script>
<?php include 'templates/footer.php'; ?>