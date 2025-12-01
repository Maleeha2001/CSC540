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
if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post.']);
    exit();
}

if (!ensure_reactions_table($db_connection)) {
    echo json_encode(['success' => false, 'message' => 'Unable to access reactions table.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$reaction_type = 'like';

$existing_stmt = $db_connection->prepare("
    SELECT reaction_id
    FROM reactions
    WHERE post_id = ? AND user_id = ? AND reaction_type = ?
    LIMIT 1
");
if (!$existing_stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare lookup.']);
    exit();
}
$existing_stmt->bind_param("iis", $post_id, $user_id, $reaction_type);
$existing_stmt->execute();
$existing_result = $existing_stmt->get_result();
$existing = $existing_result ? $existing_result->fetch_assoc() : null;
$existing_stmt->close();

$liked = false;
if ($existing) {
    $delete_stmt = $db_connection->prepare("DELETE FROM reactions WHERE reaction_id = ?");
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $existing['reaction_id']);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
} else {
    $insert_stmt = $db_connection->prepare("
        INSERT INTO reactions (post_id, user_id, reaction_type)
        VALUES (?, ?, ?)
    ");
    if ($insert_stmt) {
        $insert_stmt->bind_param("iis", $post_id, $user_id, $reaction_type);
        $insert_stmt->execute();
        $insert_stmt->close();
        $liked = true;
    }
}

$summary = get_like_summary($db_connection, $post_id, $user_id);

echo json_encode([
    'success' => true,
    'liked' => $liked ? true : $summary['liked'],
    'like_count' => $summary['count']
]);
exit();
