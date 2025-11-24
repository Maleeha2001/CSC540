<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(realpath(dirname(__FILE__) . '/../../php/session.php'));
require_once(realpath(dirname(__FILE__) . '/../../php/config.php'));
require_once(realpath(dirname(__FILE__) . '/../../php/path.php'));

if (!isset($_SESSION['user_id'])) {
    die("Not authenticated.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$user = (int)$_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$date = trim($_POST['unlock_date'] ?? '');
$time = trim($_POST['unlock_time'] ?? '');
$timezone_id = trim($_POST['timezone_id'] ?? '1');
$media_path = null;

// Basic validation
if ($title === '' || $message === '' || $date === '' || $time === '') {
    die("Missing required fields.");
}

// Convert date + time into DATETIME format
$dt = date_create_from_format('Y-m-d H:i', "$date $time") ?: 
      date_create_from_format('Y-m-d H:i:s', "$date $time");
if ($dt === false) {
    die("Invalid date/time format.");
}
$unlock_datetime = $dt->format('Y-m-d H:i:s');

// Handle optional media upload
if (!empty($_FILES['media']['name']) && is_uploaded_file($_FILES['media']['tmp_name'])) {

    $maxSize = 5 * 1024 * 1024; // 5 MB
    if ($_FILES['media']['size'] > $maxSize) {
        die("Uploaded file is too large.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['media']['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4'
    ];
    if (!isset($allowed[$mime])) {
        die("Unsupported file type.");
    }

    $ext = $allowed[$mime];
    $upload_dir = ROOT_PATH . "/uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = time() . "_" . bin2hex(random_bytes(6)) . ".$ext";
    $target_path = $upload_dir . $file_name;

    if (!move_uploaded_file($_FILES['media']['tmp_name'], $target_path)) {
        die("Failed to move uploaded file.");
    }

    $media_path = "uploads/" . $file_name;
}

// Insert into posts table
$stmt1 = $db_connection->prepare("
    INSERT INTO posts (user_id, title, caption, unlock_at, privacy, has_media, media_path)
    VALUES (?, ?, ?, ?, 'public', ?, ?)
");
$has_media = ($media_path !== null) ? 1 : 0;
$stmt1->bind_param("isssis", $user, $title, $message, $unlock_datetime, $has_media, $media_path);
$stmt1->execute();

$post_id = $stmt1->insert_id;
$stmt1->close();

// Insert into post_timers table (your actual table)
$stmt2 = $db_connection->prepare("
    INSERT INTO post_timers (post_id, unlock_at, timezone_id, status)
    VALUES (?, ?, ?, 'scheduled')
");
$stmt2->bind_param("isi", $post_id, $unlock_datetime, $timezone_id);
$stmt2->execute();
$stmt2->close();

// Handle tags relationship
$tagNames = array_unique(array_filter(array_map('trim', explode(',', $tags))));
if (!empty($tagNames)) {
    $selectTag = $db_connection->prepare("SELECT tag_id FROM tags WHERE name = ? LIMIT 1");
    $insertTag = $db_connection->prepare("INSERT INTO tags (name) VALUES (?)");
    $insertPostTag = $db_connection->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");

    foreach ($tagNames as $tagName) {
        if ($tagName === '') {
            continue;
        }

        $tag_id = null;
        $selectTag->bind_param("s", $tagName);
        $selectTag->execute();
        $result = $selectTag->get_result();
        if ($existing = $result->fetch_assoc()) {
            $tag_id = (int)$existing['tag_id'];
        } else {
            $insertTag->bind_param("s", $tagName);
            $insertTag->execute();
            $tag_id = $insertTag->insert_id;
        }

        if ($tag_id) {
            $insertPostTag->bind_param("ii", $post_id, $tag_id);
            $insertPostTag->execute();
        }
    }

    $selectTag->close();
    $insertTag->close();
    $insertPostTag->close();
}

$db_connection->close();

// Final redirect using full BASE_URL
header("Location: " . BASE_URL . "/user/dashboard.php");
exit();
?>
