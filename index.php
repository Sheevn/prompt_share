<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$page_title = 'My AI Prompt Library';
$promptDir = 'prompts';
$commentsDir = 'comments';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$tag_query = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$results = [];
$page_heading = 'My AI Prompt Library';
function find_prompts($dir, $callback) {
    $found = [];
    if (!is_dir($dir)) return $found;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() == 'md') {
            if ($callback($file)) {
                $category = basename($file->getPath());
                // CORRECTED: Convert hyphens to spaces for display
                $prompt_name = str_replace('-', ' ', $file->getBasename('.md'));
                $found[] = ['category' => $category, 'filename' => $file->getFilename(), 'prompt_name' => $prompt_name];
            }
        }
    }
    return $found;
}
function get_recent_prompts($dir, $count = 5) {
    $all_prompts = [];
    if (!is_dir($dir)) return $all_prompts;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() == 'md') {
            $all_prompts[$file->getPathname()] = $file->getMTime();
        }
    }
    arsort($all_prompts);
    $recent_files = array_slice($all_prompts, 0, $count, true);
    $recent_prompts_data = [];
    foreach ($recent_files as $path => $time) {
        $file_info = new SplFileInfo($path);
        $category = basename($file_info->getPath());
        // CORRECTED: Convert hyphens to spaces for display
        $prompt_name = str_replace('-', ' ', $file_info->getBasename('.md'));
        $recent_prompts_data[] = [ 'category' => $category, 'filename' => $file_info->getFilename(), 'prompt_name' => $prompt_name ];
    }
    return $recent_prompts_data;
}
if (!empty($search_query)) {
    $page_heading = 'Search Results for "' . htmlspecialchars($search_query) . '"';
    $results = find_prompts($promptDir, function($file) use ($search_query) {
        $content = file_get_contents($file->getPathname());
        return (stripos($file->getFilename(), $search_query) !== false || stripos($content, $search_query) !== false);
    });
} elseif (!empty($tag_query)) {
    $page_heading = 'Prompts Tagged With "' . htmlspecialchars($tag_query) . '"';
    $results = find_prompts($promptDir, function($file) use ($tag_query, $commentsDir) {
        $tags_path = "$commentsDir/" . basename($file->getPath()) . "/" . $file->getBasename('.md') . '.tags';
        if (file_exists($tags_path)) {
            $tags = array_map('trim', explode(',', file_get_contents($tags_path)));
            return in_array($tag_query, $tags);
        }
        return false;
    });
}
include 'templates/header.php';
?>
<div class="container">
    <?php if (isset($_SESSION['flash_message'])) { $message = $_SESSION['flash_message']; echo "<div class='{$message['type']}'>" . $message['text'] . "</div>"; unset($_SESSION['flash_message']); } ?>
    <h1><?php echo $page_heading; ?></h1>
    <?php if (empty($search_query) && empty($tag_query)): ?>
        <form action="index.php" method="get" class="search-form"><input type="search" name="search" placeholder="Search all prompts..." aria-label="Search all prompts"><button type="submit">Search</button></form>
        <?php
        $recent_prompts = get_recent_prompts($promptDir);
        if (!empty($recent_prompts)) {
            echo '<div class="recent-prompts"><h2 class="list-heading">Recently Added</h2><ul>';
            foreach ($recent_prompts as $prompt) { echo '<li><a href="view.php?category=' . urlencode($prompt['category']) . '&file=' . urlencode($prompt['filename']) . '">' . htmlspecialchars($prompt['prompt_name']) . '<span class="category-badge">' . htmlspecialchars(ucwords(str_replace('-', ' ', $prompt['category']))) . '</span></a></li>'; }
            echo '</ul></div>';
        }
        $all_tags = [];
        if (is_dir($commentsDir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($commentsDir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() == 'tags') {
                    $tags_in_file = array_map('trim', explode(',', file_get_contents($file->getPathname())));
                    $all_tags = array_merge($all_tags, $tags_in_file);
                }
            }
        }
        $unique_tags = array_filter(array_unique($all_tags));
        sort($unique_tags);
        if (!empty($unique_tags)) {
            echo '<h2 class="list-heading">Browse by Tag</h2><div class="tag-cloud">';
            foreach ($unique_tags as $tag) { echo '<a href="index.php?tag=' . urlencode($tag) . '" class="tag-item">' . htmlspecialchars($tag) . '</a>'; }
            echo '</div>';
        }
        ?>
        <div class="prompt-list">
            <h2 class="list-heading">Categories</h2>
            <?php
            $categories = is_dir($promptDir) ? array_filter(scandir($promptDir), function($item) use ($promptDir) { return is_dir($promptDir . '/' . $item) && !in_array($item, ['.', '..']); }) : [];
            if (empty($categories)) { echo "<p>No prompt categories found.</p>"; } 
            else {
                foreach ($categories as $category) {
                    echo '<div class="category-accordion"><button class="accordion-header">' . htmlspecialchars(ucwords(str_replace('-', ' ', $category))) . '</button><div class="accordion-panel">';
                    $files = glob($promptDir . '/' . $category . '/*.md');
                    if (empty($files)) { echo "<ul><li>No prompts in this category yet.</li></ul>"; } 
                    else { echo "<ul>"; foreach ($files as $file) { $fileName = basename($file); 
                        // CORRECTED: Convert hyphens to spaces for display
                        $promptName = str_replace('-', ' ', pathinfo($fileName, PATHINFO_FILENAME));
                        echo '<li><a href="view.php?category=' . urlencode($category) . '&file=' . urlencode($fileName) . '">' . htmlspecialchars($promptName) . '</a></li>'; } echo "</ul>"; }
                    echo '</div></div>';
                }
            }
            ?>
        </div>
    <?php else: ?>
        <div class="search-results">
            <?php if (empty($results)): ?><p>No prompts found.</p><?php else: ?>
                <ul><?php foreach ($results as $result): ?><li><a href="view.php?category=<?php echo urlencode($result['category']); ?>&file=<?php echo urlencode($result['filename']); ?>"><?php echo htmlspecialchars($result['prompt_name']); ?><span class="category-badge"><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $result['category']))); ?></span></a></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <p><a href="index.php">← Back to full library</a></p>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const accordions = document.querySelectorAll('.accordion-header');
    accordions.forEach(acc => { acc.addEventListener('click', function() { this.classList.toggle('active'); const panel = this.nextElementSibling; if (panel.style.maxHeight) { panel.style.maxHeight = null; } else { panel.style.maxHeight = panel.scrollHeight + "px"; } }); });
});
</script>
<?php include 'templates/footer.php'; ?>