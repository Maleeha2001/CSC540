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
$column_info = get_reactions_column_info($db_connection);
$pk_column = $column_info['primary_key'];
$post_column = $column_info['post_column'];
$user_column = $column_info['user_column'];
$type_column = $column_info['type_column'];

if (!$pk_column || !$post_column || !$user_column) {
    echo json_encode(['success' => false, 'message' => 'Reactions table missing required columns.']);
    exit();
}

$pk_column = "`{$pk_column}`";
$post_column = "`{$post_column}`";
$user_column = "`{$user_column}`";
$type_clause = '';
if ($type_column) {
    $type_column = "`{$type_column}`";
    $type_clause = " AND {$type_column} = ?";
}

$existing_stmt = $db_connection->prepare("
    SELECT {$pk_column} AS reaction_id
    FROM reactions
    WHERE {$post_column} = ? AND {$user_column} = ?" . ($type_clause ? " AND {$type_column} = ?" : "") . "
    LIMIT 1
");
if (!$existing_stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare lookup.']);
    exit();
}
if ($type_clause) {
    $existing_stmt->bind_param("iis", $post_id, $user_id, $reaction_type);
} else {
    $existing_stmt->bind_param("ii", $post_id, $user_id);
}
$existing_stmt->execute();
$existing_result = $existing_stmt->get_result();
$existing = $existing_result ? $existing_result->fetch_assoc() : null;
$existing_stmt->close();

$liked = false;
if ($existing) {
    $delete_stmt = $db_connection->prepare("DELETE FROM reactions WHERE {$pk_column} = ?");
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $existing['reaction_id']);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
} else {
    $insert_columns = "{$post_column}, {$user_column}" . ($type_clause ? ", {$type_column}" : "");
    $insert_values = "?, ?" . ($type_clause ? ", ?" : "");
    $insert_stmt = $db_connection->prepare("
        INSERT INTO reactions ({$insert_columns})
        VALUES ({$insert_values})
    ");
    if ($insert_stmt) {
        if ($type_clause) {
            $insert_stmt->bind_param("iis", $post_id, $user_id, $reaction_type);
        } else {
            $insert_stmt->bind_param("ii", $post_id, $user_id);
        }
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
