<?php
//======================================================================
// LIGHTWEIGHT SESSION GUARD FOR API ENDPOINTS
//======================================================================

require_once(realpath(dirname(__FILE__) . '/path.php'));
require_once(ROOT_PATH . '/php/config.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensure $_SESSION contains the authenticated user's core fields without
 * triggering redirects like php/session.php does.
 */
function api_ensure_authenticated_user($db_connection) {
    if (!empty($_SESSION['user_id'])) {
        return true;
    }

    if (empty($_SESSION['login_user'])) {
        return false;
    }

    $stmt = $db_connection->prepare("
        SELECT 
            u.user_id,
            u.role_id,
            u.first_name,
            u.username,
            r.role_type
        FROM users u
        INNER JOIN roles r ON u.role_id = r.role_id
        WHERE u.username = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $_SESSION['login_user']);
    $stmt->execute();
    $stmt->bind_result($user_id, $role_id, $first_name, $username, $role_type);
    $stmt->fetch();
    $stmt->close();

    if (empty($user_id)) {
        return false;
    }

    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_role'] = $role_id;
    $_SESSION['user_first'] = $first_name;
    $_SESSION['user_name'] = $username;
    $_SESSION['user_type'] = $role_type;

    return true;
}
