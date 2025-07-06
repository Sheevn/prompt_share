<?php
require_once 'lib/Parsedown.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_GET['category']) || !isset($_GET['file'])) { http_response_code(404); include '404.php'; exit; }
$category = $_GET['category'];
$file = $_GET['file'];
if (strpos($category, '..') !== false || strpos($file, '..') !== false) { http_response_code(404); include '404.php'; exit; }
$prompt_path = "prompts/$category/$file";
if (!file_exists($prompt_path)) { http_response_code(404); include '404.php'; exit; }
$filename_no_ext = basename($file, '.md');
$comment_path = "comments/$category/$filename_no_ext.txt";
$tags_path = "comments/$category/$filename_no_ext.tags";
$markdown_content = file_get_contents($prompt_path);
$comment_content = file_exists($comment_path) ? file_get_contents($comment_path) : 'No comments provided.';
$tags_string = file_exists($tags_path) ? trim(file_get_contents($tags_path)) : '';
$tags_array = !empty($tags_string) ? array_filter(array_map('trim', explode(',', $tags_string))) : [];
$Parsedown = new Parsedown();
$html_content = $Parsedown->text($markdown_content);
// CORRECTED: Convert hyphens back to spaces for display
$prompt_title = str_replace('-', ' ', $filename_no_ext);
$page_title = $prompt_title;
$current_url = urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$share_title = urlencode("Check out this AI Prompt: " . $prompt_title);
include 'templates/header.php';
?>
<div class="container">
    <?php if (isset($_SESSION['is_admin_logged_in'])): ?>
        <div class="admin-actions">
            <form action="super_secret_control_panel.php" method="get" class="action-form"><input type="hidden" name="edit" value="true"><input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>"><input type="hidden" name="file" value="<?php echo htmlspecialchars($file); ?>"><button type="submit" class="edit-btn">Edit Prompt</button></form>
            <form action="super_secret_control_panel.php" method="post" class="action-form" onsubmit="return confirm('Are you sure you want to delete this prompt permanently?');"><input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>"><input type="hidden" name="file" value="<?php echo htmlspecialchars($file); ?>"><button type="submit" name="delete_prompt" class="delete-btn">Delete Prompt</button></form>
        </div>
    <?php endif; ?>
    <h1><?php echo htmlspecialchars($prompt_title); ?></h1>
    <?php if (!empty($tags_array)): ?>
        <div class="tags-list">
            <?php foreach ($tags_array as $tag): ?>
                <a href="index.php?tag=<?php echo urlencode($tag); ?>" class="tag-item"><?php echo htmlspecialchars($tag); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="comment-box">
        <h3>My Notes</h3>
        <p><?php echo nl2br(htmlspecialchars($comment_content)); ?></p>
    </div>
    <div class="prompt-box">
        <button class="copy-btn" id="copyButton">Copy Prompt</button>
        <textarea id="rawMarkdown" class="visually-hidden"><?php echo htmlspecialchars($markdown_content); ?></textarea>
        <h3>The Prompt</h3>
        <?php echo $html_content; ?>
    </div>
    <div class="share-widget">
        <h4>Share this page</h4>
        <div class="share-icons">
            <a href="https://twitter.com/intent/tweet?url=<?php echo $current_url; ?>&text=<?php echo $share_title; ?>" target="_blank" rel="noopener noreferrer" aria-label="Share on X"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $current_url; ?>" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg></a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $current_url; ?>&title=<?php echo $share_title; ?>" target="_blank" rel="noopener noreferrer" aria-label="Share on LinkedIn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg></a>
            <a href="https://www.reddit.com/submit?url=<?php echo $current_url; ?>&title=<?php echo $share_title; ?>" target="_blank" rel="noopener noreferrer" aria-label="Share on Reddit"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-reddit" viewBox="0 0 16 16"><path d="M6.167 8a.83.83 0 0 0-.83.83c0 .459.372.84.83.831a.831.831 0 0 0 0-1.661m1.843 3.647c.315 0 1.403-.038 1.976-.611a.23.23 0 0 0 0-.306.213.213 0 0 0-.306 0c-.353.363-1.126.487-1.67.487-.545 0-1.308-.124-1.671-.487a.213.213 0 0 0-.306 0 .213.213 0 0 0 0 .306c.564.563 1.652.61 1.977.61zm.992-2.807c0 .458.373.83.831.83s.83-.381.83-.83a.831.831 0 0 0-1.66 0z"/><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.828-1.165c-.315 0-.602.124-.812.325-.801-.573-1.9-.945-3.121-.993l.534-2.501 1.738.372a.83.83 0 1 0 .83-.869.83.83 0 0 0-.744.468l-1.938-.41a.2.2 0 0 0-.153.028.2.2 0 0 0-.086.134l-.592 2.788c-1.24.038-2.358.41-3.17.992-.21-.2-.496-.324-.81-.324a1.163 1.163 0 0 0-.478 2.224q-.03.17-.029.353c0 1.795 2.091 3.256 4.669 3.256s4.668-1.451 4.668-3.256c0-.114-.01-.238-.029-.353.401-.181.688-.592.688-1.069 0-.65-.525-1.165-1.165-1.165"/></svg></a>
        </div>
    </div>
</div>
<script>
document.getElementById('copyButton').addEventListener('click', function() { navigator.clipboard.writeText(document.getElementById('rawMarkdown').value).then(() => { const originalText = this.textContent; this.textContent = 'Copied!'; setTimeout(() => { this.textContent = originalText; }, 2000); }).catch(err => { console.error('Error copying text: ', err); }); });
</script>
<?php include 'templates/footer.php'; ?>