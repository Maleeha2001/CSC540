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

$media_stmt = $db_connection->prepare("SELECT media_path FROM posts WHERE post_id = ? AND user_id = ?");
$media_stmt->bind_param("ii", $post_id, $user);
$media_stmt->execute();
$media_result = $media_stmt->get_result();
$media = $media_result->fetch_assoc();
$media_stmt->close();

if (!$media) {
    die("Post not found.");
}

$deleteTables = [
    "DELETE FROM post_tags WHERE post_id = ?",
    "DELETE FROM post_timers WHERE post_id = ?",
    "DELETE FROM post_media WHERE post_id = ?",
    "DELETE FROM comments WHERE post_id = ?",
    "DELETE FROM reactions WHERE post_id = ?"
];

foreach ($deleteTables as $sql) {
    $stmt = $db_connection->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->close();
}

$delete_post = $db_connection->prepare("DELETE FROM posts WHERE post_id = ? AND user_id = ?");
$delete_post->bind_param("ii", $post_id, $user);
$delete_post->execute();
$delete_post->close();

if (!empty($media['media_path'])) {
    $fullPath = ROOT_PATH . '/' . $media['media_path'];
    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }
}

$db_connection->close();

header("Location: " . BASE_URL . "/user/dashboard.php");
exit();
