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

$viewer_id = (int)$_SESSION['user_id'];
$target_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;

if ($target_id <= 0 || $target_id === $viewer_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user selection.']);
    exit();
}

if (!ensure_followers_table($db_connection) || !get_followers_column_info($db_connection)) {
    echo json_encode(['success' => false, 'message' => 'Following is unavailable.']);
    exit();
}

$is_following = toggle_follow_state($db_connection, $viewer_id, $target_id);
$totals = get_follow_totals($db_connection, $viewer_id);

echo json_encode([
    'success' => true,
    'is_following' => $is_following,
    'following_count' => $totals['following'] ?? 0
]);
exit();
