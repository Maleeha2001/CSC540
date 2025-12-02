<?php
//======================================================================
// ADMIN USER ACTIONS
//======================================================================
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once (realpath(dirname(__FILE__, 2).'/php/session.php'));
include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));
include_once (ROOT_SRC_PATH .'/check_admin.php');
include_once (ROOT_PATH . '/php/config.php');
include_once (ROOT_PATH . '/php/post_interactions.php');

$action = $_POST['action'] ?? '';

function redirect_with_message($message) {
    $_SESSION['admin_flash'] = $message;
    header("Location: index.php");
    exit();
}

if ($action === 'create') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 2);
    $status = $_POST['status'] ?? 'active';

    if ($first === '' || $last === '' || $email === '' || $username === '' || $password === '') {
        redirect_with_message("All fields are required.");
    }

    if (preg_match('/\d/', $first) || preg_match('/\d/', $last)) {
        redirect_with_message("Names cannot contain numbers.");
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db_connection->prepare("
        INSERT INTO users (first_name, last_name, email, username, password_hash, role_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssis", $first, $last, $email, $username, $hash, $role_id, $status);
    if ($stmt->execute()) {
        redirect_with_message("User created successfully.");
    } else {
        redirect_with_message("Failed to create user: " . $stmt->error);
    }
}

if ($action === 'update') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 2);
    $status = $_POST['status'] ?? 'active';

    if ($user_id <= 0 || $first === '' || $last === '' || $email === '' || $username === '') {
        redirect_with_message("Invalid form submission.");
    }

    if (preg_match('/\d/', $first) || preg_match('/\d/', $last)) {
        redirect_with_message("Names cannot contain numbers.");
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db_connection->prepare("
            UPDATE users
            SET first_name = ?, last_name = ?, email = ?, username = ?, password_hash = ?, role_id = ?, status = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("sssssisi", $first, $last, $email, $username, $hash, $role_id, $status, $user_id);
    } else {
        $stmt = $db_connection->prepare("
            UPDATE users
            SET first_name = ?, last_name = ?, email = ?, username = ?, role_id = ?, status = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("ssssisi", $first, $last, $email, $username, $role_id, $status, $user_id);
    }

    if ($stmt->execute()) {
        redirect_with_message("User updated successfully.");
    } else {
        redirect_with_message("Failed to update user: " . $stmt->error);
    }
}

if ($action === 'delete') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        redirect_with_message("Invalid user.");
    }

    if ($user_id == $_SESSION['user_id']) {
        redirect_with_message("You cannot delete your own account while logged in.");
    }

    $posts_stmt = $db_connection->prepare("SELECT post_id, media_path FROM posts WHERE user_id = ?");
    $posts_stmt->bind_param("i", $user_id);
    $posts_stmt->execute();
    $posts_result = $posts_stmt->get_result();
    $post_ids = [];
    while ($post = $posts_result->fetch_assoc()) {
        $post_ids[] = $post;
    }
    $posts_stmt->close();

    foreach ($post_ids as $post) {
        $pid = (int)$post['post_id'];
        $deleteTables = [
            "DELETE FROM post_tags WHERE post_id = ?",
            "DELETE FROM post_timers WHERE post_id = ?",
            "DELETE FROM post_media WHERE post_id = ?",
            "DELETE FROM comments WHERE post_id = ?",
            "DELETE FROM reactions WHERE post_id = ?"
        ];
        foreach ($deleteTables as $sql) {
            $stmt = $db_connection->prepare($sql);
            $stmt->bind_param("i", $pid);
            $stmt->execute();
            $stmt->close();
        }

        if (!empty($post['media_path'])) {
            $fullPath = ROOT_PATH . '/' . $post['media_path'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        $delete_post = $db_connection->prepare("DELETE FROM posts WHERE post_id = ?");
        $delete_post->bind_param("i", $pid);
        $delete_post->execute();
        $delete_post->close();
    }

    // Remove follower relationships referencing this user (handles varying column names).
    $fk_columns = [];
    $fk_sql = "
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = ?
          AND TABLE_NAME = 'followers'
          AND REFERENCED_TABLE_NAME = 'users'
          AND REFERENCED_COLUMN_NAME = 'user_id'
    ";
    if ($fk_stmt = $db_connection->prepare($fk_sql)) {
        $db_name = DB_NAME;
        $fk_stmt->bind_param("s", $db_name);
        $fk_stmt->execute();
        $fk_stmt->bind_result($column_name);
        while ($fk_stmt->fetch()) {
            if ($column_name && preg_match('/^[a-zA-Z0-9_]+$/', $column_name)) {
                $fk_columns[] = $column_name;
            }
        }
        $fk_stmt->close();
    }
    if (empty($fk_columns)) {
        $fk_columns[] = 'followee_id';
    }
    foreach ($fk_columns as $column) {
        $sql = "DELETE FROM followers WHERE `{$column}` = ?";
        $stmt = $db_connection->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Remove any comments authored by this user (handles varying column names).
    if (ensure_comments_table($db_connection)) {
        $comment_info = get_comments_column_info($db_connection);
        $author_columns = $comment_info['user_columns'] ?? [];
        if (empty($author_columns)) {
            $author_columns[] = 'user_id';
        }
        $author_columns = array_unique(array_filter($author_columns));
        foreach ($author_columns as $column) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                continue;
            }
            $sql = "DELETE FROM comments WHERE `{$column}` = ?";
            if ($stmt = $db_connection->prepare($sql)) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Remove reactions authored by this user to satisfy FK constraints.
    if (ensure_reactions_table($db_connection)) {
        $reaction_info = get_reactions_column_info($db_connection);
        $user_column = $reaction_info['user_column'] ?? 'user_id';
        if ($user_column && preg_match('/^[a-zA-Z0-9_]+$/', $user_column)) {
            $sql = "DELETE FROM reactions WHERE `{$user_column}` = ?";
            if ($stmt = $db_connection->prepare($sql)) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Remove notifications for the user to satisfy FK constraint.
    $delete_notifications = $db_connection->prepare("DELETE FROM notifications WHERE user_id = ?");
    $delete_notifications->bind_param("i", $user_id);
    $delete_notifications->execute();
    $delete_notifications->close();

    $delete_user = $db_connection->prepare("DELETE FROM users WHERE user_id = ?");
    $delete_user->bind_param("i", $user_id);
    if ($delete_user->execute()) {
        redirect_with_message("User deleted.");
    } else {
        redirect_with_message("Failed to delete user: " . $delete_user->error);
    }
}

redirect_with_message("Unknown action.");
