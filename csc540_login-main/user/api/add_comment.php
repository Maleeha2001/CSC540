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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$comment_text = trim($_POST['comment'] ?? '');

if ($post_id <= 0 || $comment_text === '') {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty.']);
    exit();
}

$comment_text = mb_substr($comment_text, 0, 500);

if (!ensure_comments_table($db_connection)) {
    echo json_encode(['success' => false, 'message' => 'Unable to access comments table.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$insert_stmt = $db_connection->prepare("
    INSERT INTO comments (post_id, user_id, comment_text)
    VALUES (?, ?, ?)
");
if (!$insert_stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare insert.']);
    exit();
}
$insert_stmt->bind_param("iis", $post_id, $user_id, $comment_text);
$insert_stmt->execute();
$comment_id = $insert_stmt->insert_id;
$insert_stmt->close();

$detail_stmt = $db_connection->prepare("
    SELECT 
        c.comment_id,
        c.comment_text,
        c.created_at,
        u.first_name,
        u.last_name,
        u.username
    FROM comments c
    INNER JOIN users u ON c.user_id = u.user_id
    WHERE c.comment_id = ?
    LIMIT 1
");
$comment_data = null;
if ($detail_stmt) {
    $detail_stmt->bind_param("i", $comment_id);
    $detail_stmt->execute();
    $result = $detail_stmt->get_result();
    $comment_data = $result ? $result->fetch_assoc() : null;
    $detail_stmt->close();
}

$comment_count = get_comment_count($db_connection, $post_id);

echo json_encode([
    'success' => true,
    'comment' => $comment_data,
    'comment_count' => $comment_count
]);
exit();
