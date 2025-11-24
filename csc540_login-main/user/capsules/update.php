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

$post_id = (int)($_POST['post_id'] ?? 0);
if ($post_id <= 0) {
    die("Invalid post.");
}

$user = (int)$_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$date = trim($_POST['unlock_date'] ?? '');
$time = trim($_POST['unlock_time'] ?? '');
$timezone_id = trim($_POST['timezone_id'] ?? '1');

if ($title === '' || $message === '' || $date === '' || $time === '') {
    die("Missing required fields.");
}

$dt = date_create_from_format('Y-m-d H:i', "$date $time") ?:
      date_create_from_format('Y-m-d H:i:s', "$date $time");
if ($dt === false) {
    die("Invalid date/time format.");
}
$unlock_datetime = $dt->format('Y-m-d H:i:s');

$existing_stmt = $db_connection->prepare("
    SELECT media_path, has_media
    FROM posts
    WHERE post_id = ? AND user_id = ?
    LIMIT 1
");
$existing_stmt->bind_param("ii", $post_id, $user);
$existing_stmt->execute();
$existing_result = $existing_stmt->get_result();
$existing = $existing_result->fetch_assoc();
$existing_stmt->close();

if (!$existing) {
    die("Post not found.");
}

$media_path = $existing['media_path'];
$has_media = (int)$existing['has_media'];

if (!empty($_FILES['media']['name']) && is_uploaded_file($_FILES['media']['tmp_name'])) {
    $maxSize = 5 * 1024 * 1024;
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

    if (!empty($media_path) && file_exists(ROOT_PATH . '/' . $media_path)) {
        @unlink(ROOT_PATH . '/' . $media_path);
    }

    $media_path = "uploads/" . $file_name;
    $has_media = 1;
}

$update_post = $db_connection->prepare("
    UPDATE posts
    SET title = ?, caption = ?, unlock_at = ?, has_media = ?, media_path = ?
    WHERE post_id = ? AND user_id = ?
");
$update_post->bind_param("sssissi", $title, $message, $unlock_datetime, $has_media, $media_path, $post_id, $user);
$update_post->execute();
$update_post->close();

$status = (new DateTime() >= new DateTime($unlock_datetime)) ? 'unlocked' : 'scheduled';
$timer_exists_stmt = $db_connection->prepare("SELECT timer_id FROM post_timers WHERE post_id = ? LIMIT 1");
$timer_exists_stmt->bind_param("i", $post_id);
$timer_exists_stmt->execute();
$timer_exists = $timer_exists_stmt->get_result()->fetch_assoc();
$timer_exists_stmt->close();

if ($timer_exists) {
    $update_timer = $db_connection->prepare("
        UPDATE post_timers
        SET unlock_at = ?, timezone_id = ?, status = ?
        WHERE post_id = ?
    ");
    $update_timer->bind_param("sisi", $unlock_datetime, $timezone_id, $status, $post_id);
    $update_timer->execute();
    $update_timer->close();
} else {
    $insert_timer = $db_connection->prepare("
        INSERT INTO post_timers (post_id, unlock_at, timezone_id, status)
        VALUES (?, ?, ?, ?)
    ");
    $insert_timer->bind_param("isis", $post_id, $unlock_datetime, $timezone_id, $status);
    $insert_timer->execute();
    $insert_timer->close();
}

$delete_tags = $db_connection->prepare("DELETE FROM post_tags WHERE post_id = ?");
$delete_tags->bind_param("i", $post_id);
$delete_tags->execute();
$delete_tags->close();

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

header("Location: " . BASE_URL . "/user/dashboard.php");
exit();
