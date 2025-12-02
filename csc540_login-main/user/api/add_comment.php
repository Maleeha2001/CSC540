<?php
require_once(realpath(dirname(__FILE__) . '/../../php/api_auth.php'));
require_once(realpath(dirname(__FILE__) . '/../../php/post_interactions.php'));

header('Content-Type: application/json');

if (!api_ensure_authenticated_user($db_connection)) {
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
$column_info = get_comments_column_info($db_connection);
$user_columns = $column_info['user_columns'];
$text_columns = $column_info['text_columns'];

if (empty($user_columns)) {
    echo json_encode(['success' => false, 'message' => 'Comments table is missing a user column.']);
    exit();
}
if (empty($text_columns)) {
    echo json_encode(['success' => false, 'message' => 'Comments table is missing a text column.']);
    exit();
}

$columns = array_merge(['post_id'], $user_columns, $text_columns);
$placeholders = implode(', ', array_fill(0, count($columns), '?'));
$column_list = implode(', ', array_map(fn($col) => "`{$col}`", $columns));
$types = str_repeat('i', 1 + count($user_columns)) . str_repeat('s', count($text_columns));
$params = array_merge(
    [$post_id],
    array_fill(0, count($user_columns), $user_id),
    array_fill(0, count($text_columns), $comment_text)
);

$insert_stmt = $db_connection->prepare("
    INSERT INTO comments ({$column_list})
    VALUES ({$placeholders})
");
if (!$insert_stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare insert.']);
    exit();
}
$insert_stmt->bind_param($types, ...$params);
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
