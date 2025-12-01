<?php
require_once(realpath(dirname(__FILE__) . '/../../php/session.php'));
require_once(realpath(dirname(__FILE__) . '/../../php/config.php'));
require_once(realpath(dirname(__FILE__) . '/../../php/path.php'));
require_once(realpath(dirname(__FILE__) . '/../../php/post_interactions.php'));

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post.']);
    exit();
}

$comments = fetch_recent_comments($db_connection, $post_id, $limit, $offset);
$comment_count = get_comment_count($db_connection, $post_id);

echo json_encode([
    'success' => true,
    'comments' => $comments,
    'comment_count' => $comment_count
]);
exit();
