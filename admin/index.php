<?php
session_start();

$audio_data_file = '../data/audio_data.json';
$audio_target_dir = "../data/audiotrack/";
$thumbnail_target_dir = "../data/thumnilpack/";

$username = 'admin';
$hashed_password_from_db = '$2y$10$VnzzWIQFaJVxwxbN8Op9ZOVnycrY2azKkyNhJWO6C8frk69dq2dI2';

if (isset($_GET['action']) && $_GET['action'] == 'logout') { session_destroy(); header('Location: index.php'); exit; }

if (isset($_GET['action']) && $_GET['action'] == 'get_all_audio') {
    header('Access-Control-Allow-Origin: *'); header('Content-Type: application/json; charset=utf-8');
    if (!file_exists($audio_data_file)) echo json_encode([]); else echo file_get_contents($audio_data_file); exit;
}

if (isset($_POST['login_submit'])) {
    if ($_POST['username'] === $username && password_verify($_POST['password'], $hashed_password_from_db)) {
        $_SESSION['loggedin'] = true; header('Location: index.php'); exit;
    } else { $error_message = "Invalid username or password!"; }
}

$item_to_edit = null; $upload_error_message = null;

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
    $current_data = [];
    if (file_exists($audio_data_file)) {
        $decoded = json_decode(file_get_contents($audio_data_file), true);
        if (is_array($decoded)) $current_data = $decoded;
    }

    $categories = [];
    foreach ($current_data as $item) { if (!empty($item['category'])) $categories[] = $item['category']; }
    $unique_categories = array_unique($categories);

    if ($view === 'edit' && isset($_GET['id'])) {
        foreach ($current_data as $item) { if ($item['id'] === $_GET['id']) { $item_to_edit = $item; break; } }
    }

    if (isset($_POST['update_submit'])) {
        $id_to_update = $_POST['edit_id'];
        $final_category = !empty($_POST['new_category']) ? $_POST['new_category'] : $_POST['existing_category'];
        foreach ($current_data as $index => $item) {
            if ($item['id'] === $id_to_update) {
                $current_data[$index]['title'] = htmlspecialchars($_POST['audio_title']);
                $current_data[$index]['category'] = htmlspecialchars($final_category);
                if (isset($_FILES['new_audio_file']) && $_FILES['new_audio_file']['error'] === 0) {
                    if (file_exists('../' . $item['audioUrl'])) unlink('../' . $item['audioUrl']);
                    $new_name = uniqid('audio_', true) . '_' . basename($_FILES['new_audio_file']['name']);
                    move_uploaded_file($_FILES['new_audio_file']['tmp_name'], $audio_target_dir . $new_name);
                    $current_data[$index]['audioUrl'] = 'data/audiotrack/' . $new_name;
                }
                if (isset($_FILES['new_thumbnail_file']) && $_FILES['new_thumbnail_file']['error'] === 0) {
                    if (file_exists('../' . $item['thumbnailUrl'])) unlink('../' . $item['thumbnailUrl']);
                    $new_thumb_name = uniqid('thumb_', true) . '_' . basename($_FILES['new_thumbnail_file']['name']);
                    move_uploaded_file($_FILES['new_thumbnail_file']['tmp_name'], $thumbnail_target_dir . $new_thumb_name);
                    $current_data[$index]['thumbnailUrl'] = 'data/thumnilpack/' . $new_thumb_name;
                }
                break;
            }
        }
        file_put_contents($audio_data_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: index.php?view=list&status=updated'); exit;
    }

    if (isset($_POST['upload_submit'])) {
        if (!empty($_POST['audio_title']) && $_FILES['audio_file']['error'] === 0 && $_FILES['thumbnail_file']['error'] === 0) {
            $final_category = !empty($_POST['new_category']) ? $_POST['new_category'] : $_POST['existing_category'];
            if (empty($final_category)) $final_category = "General";
            $audio_name = uniqid('audio_', true) . '_' . basename($_FILES['audio_file']['name']);
            $thumb_name = uniqid('thumb_', true) . '_' . basename($_FILES['thumbnail_file']['name']);
            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $audio_target_dir . $audio_name) && move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $thumbnail_target_dir . $thumb_name)) {
                $new_entry = [ 'id' => uniqid(), 'audioUrl' => 'data/audiotrack/' . $audio_name, 'thumbnailUrl' => 'data/thumnilpack/' . $thumb_name, 'title' => htmlspecialchars($_POST['audio_title']), 'category' => htmlspecialchars($final_category) ];
                array_unshift($current_data, $new_entry);
                file_put_contents($audio_data_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                header('Location: index.php?view=list&status=success'); exit;
            } else { $upload_error_message = "File upload failed."; }
        } else { $upload_error_message = "All fields are required!"; }
    }

    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        foreach ($current_data as $index => $item) {
            if ($item['id'] === $_GET['id']) {
                if (file_exists('../' . $item['audioUrl'])) unlink('../' . $item['audioUrl']);
                if (file_exists('../' . $item['thumbnailUrl'])) unlink('../' . $item['thumbnailUrl']);
                array_splice($current_data, $index, 1);
                file_put_contents($audio_data_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
            }
        }
        header('Location: index.php?view=list&status=deleted'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Thetrue Player</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css">
    <style>
        /* Top Navigation Buttons Styling */
        .top-nav { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .top-nav a { flex: 1; text-align: center; padding: 10px; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 0.95rem; }
        .nav-dash { background: #e9ecef; color: #007bff; }
        .nav-site { background: #28a745; color: #fff; }
        .nav-dash:hover { background: #007bff; color: #fff; }
        .nav-site:hover { background: #218838; }
        /* Filter Box Styling */
        .filter-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef; }
        .filter-box select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ced4da; font-size: 1rem; outline: none; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isset($_SESSION['loggedin'])) : ?>
            <h2>Admin Login</h2>
            <?php if (isset($error_message)) echo "<p class='error'>$error_message</p>"; ?>
            <form action="index.php" method="post">
                <div class="input-group"><input type="text" name="username" placeholder="Username" required></div>
                <div class="input-group"><input type="password" name="password" placeholder="Password" required></div>
                <input type="submit" name="login_submit" value="Login">
            </form>
            <div class="page-footer" style="margin-top: 25px; text-align: center;">
                <a href="../" style="color: #007bff; text-decoration: none; font-weight: 600;"><i class="fas fa-home"></i> Back to Main Website</a>
            </div>
        <?php else: ?>

            <?php if ($view === 'dashboard'): ?>
                <h2>Admin Dashboard</h2>
                <div class="dashboard-buttons">
                    <a href="index.php?view=upload" class="dash-button"><i class="fas fa-upload"></i> Upload New Track</a>
                    <a href="index.php?view=list" class="dash-button"><i class="fas fa-list"></i> Manage Tracks</a>
                    <a href="../" class="dash-button" style="background-color: #28a745; color: white;" target="_blank"><i class="fas fa-external-link-alt"></i> Go to Website</a>
                </div>
            <?php endif; ?>

            <?php if ($view === 'upload'): ?>
                <div class="top-nav">
                    <a href="index.php?view=dashboard" class="nav-dash"><i class="fas fa-arrow-left"></i> Dashboard</a>
                    <a href="../" target="_blank" class="nav-site"><i class="fas fa-external-link-alt"></i> Main Website</a>
                </div>
                <h2>Upload Audio</h2>
                <?php if ($upload_error_message) echo "<p class='error'>$upload_error_message</p>"; ?>
                <form action="index.php?view=upload" method="post" enctype="multipart/form-data">
                    <div class="input-group"><label>Audio Title:</label><input type="text" name="audio_title" required></div>
                    <div class="input-group">
                        <label>Category:</label>
                        <select name="existing_category" style="margin-bottom: 10px;">
                            <option value="">-- Select Existing Category --</option>
                            <option value="General">General</option>
                            <?php foreach($unique_categories as $cat): if($cat !== 'General'): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                        <input type="text" name="new_category" placeholder="Or Type New Category Name">
                    </div>
                    <div class="input-group"><label>Audio File (.mp3, .wav):</label><input type="file" name="audio_file" required accept="audio/*"></div>
                    <div class="input-group"><label>Thumbnail Image (.jpg, .png):</label><input type="file" name="thumbnail_file" required accept="image/*"></div>
                    <input type="submit" name="upload_submit" value="Upload Audio">
                </form>
            <?php endif; ?>

            <?php if ($view === 'list'): ?>
                <div class="top-nav">
                    <a href="index.php?view=dashboard" class="nav-dash"><i class="fas fa-arrow-left"></i> Dashboard</a>
                    <a href="../" target="_blank" class="nav-site"><i class="fas fa-external-link-alt"></i> Main Website</a>
                </div>
                
                <h2>Manage Audio List</h2>
                
                <?php if (isset($_GET['status']) && $_GET['status'] == 'success') echo "<p class='success'>Uploaded successfully!</p>"; ?>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'updated') echo "<p class='success'>Updated successfully!</p>"; ?>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted') echo "<p class='success'>Deleted successfully!</p>"; ?>
                
                <?php
                // --- CATEGORY FILTER LOGIC ---
                $selected_category = isset($_GET['cat']) ? $_GET['cat'] : 'All';
                $filtered_data = [];
                
                // Track list ko filter karna
                foreach ($current_data as $item) {
                    $item_cat = !empty($item['category']) ? $item['category'] : 'General';
                    if ($selected_category === 'All' || $item_cat === $selected_category) {
                        $filtered_data[] = $item;
                    }
                }

                // --- PAGINATION (Ab filtered data pe kaam karega) ---
                $items_per_page = 10;
                $total_items = count($filtered_data);
                $total_pages = ceil($total_items / $items_per_page);
                $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                
                if ($current_page < 1) $current_page = 1;
                if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
                
                $offset = ($current_page - 1) * $items_per_page;
                $paginated_data = array_slice($filtered_data, $offset, $items_per_page);
                ?>

                <div class="filter-box">
                    <form action="index.php" method="GET">
                        <input type="hidden" name="view" value="list">
                        <label for="cat" style="font-weight: bold; display: block; margin-bottom: 5px;">Filter by Category:</label>
                        <select name="cat" id="cat" onchange="this.form.submit()">
                            <option value="All" <?= $selected_category === 'All' ? 'selected' : '' ?>>-- All Categories --</option>
                            <option value="General" <?= $selected_category === 'General' ? 'selected' : '' ?>>General</option>
                            <?php 
                            // Ensure General is not repeated and list is sorted
                            $all_dropdown_cats = array_filter($unique_categories, function($c) { return $c !== 'General'; });
                            sort($all_dropdown_cats);
                            foreach($all_dropdown_cats as $cat): 
                            ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $selected_category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="audio-manage-list">
                    <?php if (empty($paginated_data)): ?>
                        <p style="text-align: center; padding: 20px; color: #777;">No tracks found in this category.</p>
                    <?php else: ?>
                        <?php foreach ($paginated_data as $audio_item) : ?>
                            <div class="audio-item">
                                <img src="../<?= $audio_item['thumbnailUrl'] ?>" alt="Thumb" class="item-thumb">
                                <div style="flex-grow: 1;">
                                    <p class="item-title"><?= $audio_item['title'] ?> <br><small>Category: <?= $audio_item['category'] ?? 'General' ?></small></p>
                                </div>
                                <div class="item-actions">
                                    <a href="index.php?view=edit&id=<?= $audio_item['id'] ?>" class="btn-edit">Edit</a>
                                    <a href="index.php?action=delete&id=<?= $audio_item['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this track?');">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="admin-pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="index.php?view=list&cat=<?= urlencode($selected_category) ?>&page=<?= $current_page - 1 ?>" class="page-btn"><i class="fas fa-chevron-left"></i> Prev</a>
                        <?php else: ?>
                            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i> Prev</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="index.php?view=list&cat=<?= urlencode($selected_category) ?>&page=<?= $i ?>" class="page-btn <?= ($i === $current_page) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="index.php?view=list&cat=<?= urlencode($selected_category) ?>&page=<?= $current_page + 1 ?>" class="page-btn">Next <i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="page-btn disabled">Next <i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($view === 'edit' && $item_to_edit): ?>
                <div class="top-nav">
                    <a href="index.php?view=list" class="nav-dash"><i class="fas fa-arrow-left"></i> Back to List</a>
                    <a href="../" target="_blank" class="nav-site"><i class="fas fa-external-link-alt"></i> Main Website</a>
                </div>
                <h2>Edit Audio</h2>
                <form action="index.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" value="<?= $item_to_edit['id'] ?>">
                    <div class="input-group"><label>Title:</label><input type="text" name="audio_title" value="<?= $item_to_edit['title'] ?>" required></div>
                    <div class="input-group">
                        <label>Category:</label>
                        <select name="existing_category" style="margin-bottom: 10px;">
                            <option value="<?= htmlspecialchars($item_to_edit['category'] ?? 'General') ?>"><?= htmlspecialchars($item_to_edit['category'] ?? 'General') ?> (Current)</option>
                            <?php foreach($unique_categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_category" placeholder="Or Type New Category Name">
                    </div>
                    <div class="input-group"><label>Replace Audio File:</label><input type="file" name="new_audio_file" accept="audio/*"></div>
                    <div class="input-group"><label>Replace Thumbnail:</label><input type="file" name="new_thumbnail_file" accept="image/*"></div>
                    <input type="submit" name="update_submit" value="Update Track">
                </form>
            <?php endif; ?>

            <?php if ($view !== 'dashboard'): ?>
                <div class="page-footer" style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; text-align: center;">
                    <a href="index.php?action=logout" style="color: red;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            <?php else: ?>
                <div class="page-footer" style="margin-top: 30px;">
                    <a href="index.php?action=logout" style="color: red;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="script.js"></script>
</body>
</html>
