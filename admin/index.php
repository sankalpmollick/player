<?php
session_start();

// --- Configuration and File Paths ---
$audio_data_file = '../data/audio_data.json';
$audio_target_dir = "../data/audiotrack/";
$thumbnail_target_dir = "../data/thumnilpack/";

// --- SECURE LOGIN CREDENTIALS ---
$username = 'admin';
$hashed_password_from_db = '$2y$10$VnzzWIQFaJVxwxbN8Op9ZOVnycrY2azKkyNhJWO6C8frk69dq2dI2'; // Your working hash

// --- Logout Logic ---
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// --- API Endpoint ---
if (isset($_GET['action']) && $_GET['action'] == 'get_all_audio') {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    if (!file_exists($audio_data_file)) echo json_encode([]);
    else echo file_get_contents($audio_data_file);
    exit;
}
// --- API Endpoint ---
if (isset($_GET['action']) && $_GET['action'] == 'get_all_audio') {
    // ... (aapka purana code waisa hi rahega) ...
}

// ▼▼▼ YEH NAYA CODE ADD KAREIN ▼▼▼
// API Endpoint for a single track's details
if (isset($_GET['action']) && $_GET['action'] == 'get_single_track' && isset($_GET['id'])) {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    
    $track_id = $_GET['id'];
    $all_audio = file_exists($audio_data_file) ? json_decode(file_get_contents($audio_data_file), true) : [];
    
    $found_track = null;
    if (is_array($all_audio)) {
        foreach ($all_audio as $track) {
            if ($track['id'] === $track_id) {
                $found_track = $track;
                break;
            }
        }
    }
    
    if ($found_track) {
        echo json_encode($found_track);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Track not found']);
    }
    exit;
}
// ▲▲▲ NAYA CODE KHATAM ▲▲▲

// --- Login Logic ---
// ... (baaki ka code waisa hi rahega) ...
// --- Login Logic ---
if (isset($_POST['login_submit'])) {
    if ($_POST['username'] === $username && password_verify($_POST['password'], $hashed_password_from_db)) {
        $_SESSION['loggedin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error_message = "Invalid username or password!";
    }
}

// Initialize variables
$item_to_edit = null;
$upload_error_message = null;

// Ensure user is logged in for actions below
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    
    $view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

    // --- Logic to fetch data for the edit form ---
    if ($view === 'edit' && isset($_GET['id'])) {
        $id_to_find = $_GET['id'];
        $current_data = file_exists($audio_data_file) ? json_decode(file_get_contents($audio_data_file), true) : [];
        foreach ($current_data as $item) {
            if ($item['id'] === $id_to_find) {
                $item_to_edit = $item;
                break;
            }
        }
    }

    // --- Handle UPDATE Logic ---
    if (isset($_POST['update_submit'])) {
        $id_to_update = $_POST['edit_id'];
        $new_title = $_POST['audio_title'];
        $current_data = file_exists($audio_data_file) ? json_decode(file_get_contents($audio_data_file), true) : [];

        foreach ($current_data as $index => $item) {
            if ($item['id'] === $id_to_update) {
                // 1. Update the title
                $current_data[$index]['title'] = htmlspecialchars($new_title);

                // 2. Check if a new audio file was uploaded
                if (isset($_FILES['new_audio_file']) && $_FILES['new_audio_file']['error'] === 0) {
                    if (file_exists('../' . $item['audioUrl'])) unlink('../' . $item['audioUrl']);
                    
                    $new_audio_name = uniqid('audio_', true) . '_' . basename($_FILES['new_audio_file']['name']);
                    $new_audio_path = $audio_target_dir . $new_audio_name;
                    move_uploaded_file($_FILES['new_audio_file']['tmp_name'], $new_audio_path);
                    
                    $current_data[$index]['audioUrl'] = 'data/audiotrack/' . $new_audio_name;
                }

                // 3. Check if a new thumbnail file was uploaded
                if (isset($_FILES['new_thumbnail_file']) && $_FILES['new_thumbnail_file']['error'] === 0) {
                    if (file_exists('../' . $item['thumbnailUrl'])) unlink('../' . $item['thumbnailUrl']);
                    
                    $new_thumb_name = uniqid('thumb_', true) . '_' . basename($_FILES['new_thumbnail_file']['name']);
                    $new_thumb_path = $thumbnail_target_dir . $new_thumb_name;
                    move_uploaded_file($_FILES['new_thumbnail_file']['tmp_name'], $new_thumb_path);

                    $current_data[$index]['thumbnailUrl'] = 'data/thumnilpack/' . $new_thumb_name;
                }
                break;
            }
        }
        file_put_contents($audio_data_file, json_encode($current_data, JSON_PRETTY_PRINT));
        header('Location: index.php?view=list&status=updated');
        exit;
    }
    
    // --- Handle Delete Logic ---
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id_to_delete = $_GET['id'];
        $current_data = file_exists($audio_data_file) ? json_decode(file_get_contents($audio_data_file), true) : [];
        foreach ($current_data as $index => $item) {
            if ($item['id'] === $id_to_delete) {
                if (file_exists('../' . $item['audioUrl'])) unlink('../' . $item['audioUrl']);
                if (file_exists('../' . $item['thumbnailUrl'])) unlink('../' . $item['thumbnailUrl']);
                array_splice($current_data, $index, 1);
                file_put_contents($audio_data_file, json_encode($current_data, JSON_PRETTY_PRINT));
                break;
            }
        }
        header('Location: index.php?view=list&status=deleted');
        exit;
    }

    // --- Handle Upload Logic ---
    if (isset($_POST['upload_submit'])) {
        if (!empty($_POST['audio_title']) && isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === 0 && isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === 0) {
            $audio_title = $_POST['audio_title'];
            $audio_file_name = uniqid('audio_', true) . '_' . basename($_FILES['audio_file']['name']);
            $thumbnail_file_name = uniqid('thumb_', true) . '_' . basename($_FILES['thumbnail_file']['name']);
            $audio_target_path = $audio_target_dir . $audio_file_name;
            $thumbnail_target_path = $thumbnail_target_dir . $thumbnail_file_name;

            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $audio_target_path) && move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $thumbnail_target_path)) {
                $current_data = file_exists($audio_data_file) ? json_decode(file_get_contents($audio_data_file), true) : [];
                if (!is_array($current_data)) $current_data = [];
                
                $new_audio_data = [
                    'id' => uniqid(),
                    'audioUrl' => 'data/audiotrack/' . $audio_file_name,
                    'thumbnailUrl' => 'data/thumnilpack/' . $thumbnail_file_name,
                    'title' => htmlspecialchars($audio_title)
                ];
                
                array_unshift($current_data, $new_audio_data);
                file_put_contents($audio_data_file, json_encode($current_data, JSON_PRETTY_PRINT));
                
                header('Location: index.php?view=list&status=success');
                exit;
            } else {
                $upload_error_message = "Error uploading files. Check folder permissions.";
            }
        } else {
            $upload_error_message = "Please provide a title, audio file, and thumbnail.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) : ?>
            
            <h2>Admin Login</h2>
            <?php if (isset($error_message)) echo "<p class='error'>$error_message</p>"; ?>
            <form action="index.php" method="post">
                <div class="input-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <input type="submit" name="login_submit" value="Login">
            </form>
            <a href="../" class="back-to-site-link">
                <i class="fas fa-arrow-left"></i> Back to Main Site
            </a>

        <?php else: ?>

            <?php if ($view === 'dashboard'): ?>
                <h2>Admin Dashboard</h2>
                <div class="dashboard-buttons">
                    <a href="index.php?view=upload" class="dash-button">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>New Upload</span>
                        <small>Add a new audio track</small>
                    </a>
                    <a href="index.php?view=list" class="dash-button">
                        <i class="fas fa-list-ul"></i>
                        <span>Manage List</span>
                        <small>Edit or delete existing tracks</small>
                    </a>
                </div>
                <div class="page-footer">
                     <a class="logout-link" href="index.php?action=logout">Logout</a>
                </div>
            <?php endif; ?>

            <?php if ($view === 'upload'): ?>
            <div class="view-container">
                <h2><i class="fas fa-cloud-upload-alt"></i> New Upload</h2>
                <?php if (isset($upload_error_message)) echo "<p class='error'>$upload_error_message</p>"; ?>
                <form action="index.php?view=upload" method="post" enctype="multipart/form-data">
                    <div class="input-group">
                        <label for="audio_title">Audio Title:</label>
                        <input type="text" id="audio_title" name="audio_title" required>
                    </div>
                    <div class="input-group">
                        <label for="audio_file">Audio File:</label>
                        <input type="file" id="audio_file" name="audio_file" required accept="audio/*">
                    </div>
                    <div class="input-group">
                        <label for="thumbnail_file">Thumbnail:</label>
                        <input type="file" id="thumbnail_file" name="thumbnail_file" required accept="image/*">
                    </div>
                    <input type="submit" name="upload_submit" value="Upload Audio">
                </form>
                 <div class="page-footer">
                    <a href="index.php?view=dashboard" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                    <a class="logout-link" href="index.php?action=logout">Logout</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($view === 'list'): ?>
            <div class="view-container">
                <h2><i class="fas fa-list-ul"></i> Manage Audio List</h2>
                 <?php 
                    if (isset($_GET['status'])) {
                        if ($_GET['status'] === 'success') echo "<p class='success'>Audio uploaded successfully!</p>";
                        if ($_GET['status'] === 'deleted') echo "<p class='success'>Audio deleted successfully!</p>";
                        if ($_GET['status'] === 'updated') echo "<p class='success'>Audio updated successfully!</p>";
                    }
                ?>
                <div class="audio-manage-list">
                    <?php
                    $all_audio = file_exists($audio_data_file) ? json_decode(file_get_contents($audio_data_file), true) : [];
                    if (!empty($all_audio)) :
                        foreach ($all_audio as $audio_item) :
                    ?>
                            <div class="audio-item">
                                <img src="../<?= htmlspecialchars($audio_item['thumbnailUrl']) ?>" class="item-thumb" alt="thumb">
                                <p class="item-title"><?= htmlspecialchars($audio_item['title']) ?></p>
                                <div class="item-actions">
                                    <a href="index.php?view=edit&id=<?= $audio_item['id'] ?>" class="btn-edit">Edit</a>
                                    <a href="index.php?action=delete&id=<?= $audio_item['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure?');">Delete</a>
                                </div>
                            </div>
                    <?php
                        endforeach;
                    else :
                        echo "<p class='no-audio-message'>No audio found.</p>";
                    endif;
                    ?>
                </div>
                <div class="page-footer">
                    <a href="index.php?view=dashboard" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                    <a class="logout-link" href="index.php?action=logout">Logout</a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($view === 'edit' && $item_to_edit): ?>
            <div class="view-container">
                <h2><i class="fas fa-edit"></i> Edit Audio</h2>
                <form action="index.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" value="<?= htmlspecialchars($item_to_edit['id']) ?>">
                    <div class="input-group">
                        <label for="audio_title">Audio Title:</label>
                        <input type="text" id="audio_title" name="audio_title" value="<?= htmlspecialchars($item_to_edit['title']) ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="new_audio_file">Replace Audio File (Optional):</label>
                        <input type="file" id="new_audio_file" name="new_audio_file" accept="audio/*">
                        <small class="current-file-note">Current file: <?= basename($item_to_edit['audioUrl']) ?></small>
                    </div>
                    <div class="input-group">
                        <label for="new_thumbnail_file">Replace Thumbnail (Optional):</label>
                        <input type="file" id="new_thumbnail_file" name="new_thumbnail_file" accept="image/*">
                        <small class="current-file-note">Current file: <?= basename($item_to_edit['thumbnailUrl']) ?></small>
                    </div>
                    <input type="submit" name="update_submit" value="Update Audio">
                </form>
                <div class="page-footer">
                    <a href="index.php?view=list" class="back-link"><i class="fas fa-arrow-left"></i> Back to List</a>
                    <a class="logout-link" href="index.php?action=logout">Logout</a>
                </div>
            </div>
            <?php elseif ($view === 'edit'): ?>
                <p class='error'>Error: Could not find the audio item to edit.</p>
            <?php endif; ?>

        <?php endif; ?>
    </div>
    <script src="script.js"></script>
</body>
</html>
