<?php
require_once(realpath(dirname(__FILE__) . '/../../php/api_auth.php'));
require_once(realpath(dirname(__FILE__) . '/../../php/post_interactions.php'));

header('Content-Type: application/json');

if (!api_ensure_authenticated_user($db_connection)) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$viewer_id = (int)$_SESSION['user_id'];
$query = trim($_GET['q'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
$limit = max(1, min(25, $limit));

if ($query === '') {
    echo json_encode(['success' => true, 'users' => []]);
    exit();
}

$like = '%' . $query . '%';
$follow_info = get_followers_column_info($db_connection);
$follow_join = '';
$select_follow = '0 AS is_following';
$bind_types = '';
$params = [];

if ($follow_info && $follow_info['follower_column'] && $follow_info['followee_column']) {
    $table = $follow_info['table'];
    $follower_col = "`{$follow_info['follower_column']}`";
    $followee_col = "`{$follow_info['followee_column']}`";

    $follow_join = "
        LEFT JOIN {$table} f
            ON f.{$followee_col} = u.user_id
            AND f.{$follower_col} = ?
    ";
    $select_follow = "CASE WHEN f.{$followee_col} IS NULL THEN 0 ELSE 1 END AS is_following";
    $bind_types .= 'i';
    $params[] = $viewer_id;
}

$sql = "
    SELECT
        u.user_id,
        u.first_name,
        u.last_name,
        u.username,
        {$select_follow}
    FROM users u
    {$follow_join}
    WHERE (u.username LIKE ? OR CONCAT_WS(' ', u.first_name, u.last_name) LIKE ?)
      AND u.user_id <> ?
    ORDER BY u.username ASC
    LIMIT ?
";
$bind_types .= 'ssi';
$params[] = $like;
$params[] = $like;
$params[] = $viewer_id;
$bind_types .= 'i';
$params[] = $limit;

$stmt = $db_connection->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Unable to run search.']);
    exit();
}

$bindArgs = array_merge([$bind_types], $params);
foreach ($bindArgs as $idx => $value) {
    $bindArgs[$idx] = &$bindArgs[$idx];
}
call_user_func_array([$stmt, 'bind_param'], $bindArgs);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($result && ($row = $result->fetch_assoc())) {
    $users[] = [
        'user_id' => (int)$row['user_id'],
        'first_name' => (string)($row['first_name'] ?? ''),
        'last_name' => (string)($row['last_name'] ?? ''),
        'username' => (string)($row['username'] ?? ''),
        'is_following' => (bool)$row['is_following']
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'users' => $users]);
exit();
