<?php
//======================================================================
// POST INTERACTION HELPERS (Likes & Comments)
//======================================================================

/**
 * Ensure the reactions table exists for storing likes.
 */
function ensure_reactions_table($connection) {
    static $table_ready = null;
    if ($table_ready === true) {
        return true;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS reactions (
            reaction_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            reaction_type VARCHAR(32) NOT NULL DEFAULT 'like',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_reaction_post FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
            CONSTRAINT fk_reaction_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT uq_post_user_reaction UNIQUE (post_id, user_id, reaction_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if ($connection->query($sql) === true) {
        $table_ready = true;
        return true;
    }

    return false;
}

/**
 * Ensure the comments table exists for storing threaded feedback.
 */
function ensure_comments_table($connection) {
    static $table_ready = null;
    if ($table_ready === true) {
        return true;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS comments (
            comment_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            comment_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_comment_post FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
            CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_post_created (post_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if ($connection->query($sql) === true) {
        $table_ready = true;
        return true;
    }

    return false;
}

/**
 * Utility: fetch columns for a table.
 */
function get_table_columns($connection, $table) {
    $columns = [];
    if ($result = $connection->query("SHOW COLUMNS FROM `{$table}`")) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }
        $result->close();
    }
    return $columns;
}

function normalize_column_name($name) {
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
}

function match_columns($columns, array $candidates) {
    $matches = [];
    $normalizedCandidates = array_map('normalize_column_name', $candidates);
    foreach ($columns as $row) {
        $normalizedName = normalize_column_name($row['Field']);
        foreach ($normalizedCandidates as $candidate) {
            if ($normalizedName === $candidate) {
                $matches[] = $row['Field'];
                break;
            }
        }
    }
    return array_unique($matches);
}

function match_column($columns, array $candidates) {
    $matches = match_columns($columns, $candidates);
    return $matches[0] ?? null;
}

function get_primary_key_column($connection, $table) {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    if (!defined('DB_NAME')) {
        return $cache[$table] = null;
    }
    $sql = "
        SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ?
          AND TABLE_NAME = ?
          AND COLUMN_KEY = 'PRI'
        LIMIT 1
    ";
    if ($stmt = $connection->prepare($sql)) {
        $db = DB_NAME;
        $stmt->bind_param("ss", $db, $table);
        $stmt->execute();
        $stmt->bind_result($column_name);
        if ($stmt->fetch()) {
            $cache[$table] = $column_name;
        } else {
            $cache[$table] = null;
        }
        $stmt->close();
    } else {
        $cache[$table] = null;
    }
    return $cache[$table];
}

function get_foreign_key_column($connection, $table, $referenced_table) {
    static $cache = [];
    $cache_key = "{$table}:{$referenced_table}";
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }
    if (!defined('DB_NAME')) {
        return $cache[$cache_key] = null;
    }
    $sql = "
        SELECT COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = ?
          AND TABLE_NAME = ?
          AND REFERENCED_TABLE_NAME = ?
        LIMIT 1
    ";
    if ($stmt = $connection->prepare($sql)) {
        $db = DB_NAME;
        $stmt->bind_param("sss", $db, $table, $referenced_table);
        $stmt->execute();
        $stmt->bind_result($column_name);
        if ($stmt->fetch()) {
            $cache[$cache_key] = $column_name;
        } else {
            $cache[$cache_key] = null;
        }
        $stmt->close();
    } else {
        $cache[$cache_key] = null;
    }
    return $cache[$cache_key];
}

/**
 * Determine available columns for the comments table.
 */
function get_comments_column_info($connection) {
    $columns = get_table_columns($connection, 'comments');
    $user_columns = match_columns($columns, ['user_id', 'userid', 'author_id', 'authorid', 'profile_id', 'profileid', 'member_id']);
    $text_columns = match_columns($columns, ['comment_text', 'commenttext', 'comment', 'content', 'body', 'message']);

    return [
        'user_columns' => $user_columns,
        'text_columns' => $text_columns,
        'primary_user' => $user_columns[0] ?? null,
        'primary_text' => $text_columns[0] ?? null,
    ];
}

/**
 * Determine available columns for the reactions table.
 */
function get_reactions_column_info($connection) {
    $columns = get_table_columns($connection, 'reactions');
    if (empty($columns)) {
        return [
            'primary_key' => null,
            'post_column' => null,
            'user_column' => null,
            'type_column' => null
        ];
    }

    $primary = get_primary_key_column($connection, 'reactions');
    if (!$primary) {
        $primary = $columns[0]['Field'] ?? null;
    }

    $post_column = get_foreign_key_column($connection, 'reactions', 'posts');
    $user_column = get_foreign_key_column($connection, 'reactions', 'users');

    if (!$post_column) {
        $post_column = match_column($columns, ['post_id', 'postid', 'capsule_id', 'capsuleid', 'timecap_id', 'timecapid', 'post']);
    }
    if (!$user_column) {
        $user_column = match_column($columns, ['user_id', 'userid', 'author_id', 'authorid', 'member_id', 'profile_id', 'user', 'author']);
    }

    $type_column = match_column($columns, ['reaction_type', 'reactiontype', 'type', 'reaction', 'status', 'kind', 'flag']);

    return [
        'primary_key' => $primary,
        'post_column' => $post_column,
        'user_column' => $user_column,
        'type_column' => $type_column
    ];
}

/**
 * Retrieve how many likes a post has and whether a user liked it.
 */
function get_like_summary($connection, $post_id, $user_id = null) {
    if (!ensure_reactions_table($connection)) {
        return ['count' => 0, 'liked' => false];
    }
    $reaction_columns = get_reactions_column_info($connection);
    if (empty($reaction_columns['post_column'])) {
        return ['count' => 0, 'liked' => false];
    }

    $summary = ['count' => 0, 'liked' => false];
    $post_col = "`{$reaction_columns['post_column']}`";
    $type_condition = '';
    if (!empty($reaction_columns['type_column'])) {
        $type_col = "`{$reaction_columns['type_column']}`";
        $type_condition = " AND {$type_col} = 'like'";
    }

    $count_stmt = $connection->prepare("
        SELECT COUNT(*) AS like_count
        FROM reactions
        WHERE {$post_col} = ? {$type_condition}
    ");
    if ($count_stmt) {
        if ($type_condition) {
            $count_stmt->bind_param("i", $post_id);
        } else {
            $count_stmt->bind_param("i", $post_id);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        if ($count_result && ($row = $count_result->fetch_assoc())) {
            $summary['count'] = (int)$row['like_count'];
        }
        $count_stmt->close();
    }

    if ($user_id !== null && !empty($reaction_columns['user_column'])) {
        $user_col = "`{$reaction_columns['user_column']}`";
        $liked_stmt = $connection->prepare("
            SELECT 1
            FROM reactions
            WHERE {$post_col} = ? AND {$user_col} = ? {$type_condition}
            LIMIT 1
        ");
        if ($liked_stmt) {
            if ($type_condition) {
                $liked_stmt->bind_param("ii", $post_id, $user_id);
            } else {
                $liked_stmt->bind_param("ii", $post_id, $user_id);
            }
            $liked_stmt->execute();
            $liked_stmt->store_result();
            $summary['liked'] = $liked_stmt->num_rows > 0;
            $liked_stmt->close();
        }
    }

    return $summary;
}

/**
 * Return how many comments a post currently has.
 */
function get_comment_count($connection, $post_id) {
    if (!ensure_comments_table($connection)) {
        return 0;
    }

    $count_stmt = $connection->prepare("
        SELECT COUNT(*) AS comment_count
        FROM comments
        WHERE post_id = ?
    ");
    if (!$count_stmt) {
        return 0;
    }
    $count_stmt->bind_param("i", $post_id);
    $count_stmt->execute();
    $result = $count_stmt->get_result();
    $count = 0;
    if ($result && ($row = $result->fetch_assoc())) {
        $count = (int)$row['comment_count'];
    }
    $count_stmt->close();
    return $count;
}

/**
 * Fetch the most recent comments for a post.
 */
function fetch_recent_comments($connection, $post_id, $limit = 10, $offset = 0) {
    if (!ensure_comments_table($connection)) {
        return [];
    }
    $column_info = get_comments_column_info($connection);
    $comment_column = $column_info['primary_text'];
    $user_column = $column_info['primary_user'];
    if (!$comment_column) {
        return [];
    }

    $limit = max(1, min(50, (int)$limit));
    $offset = max(0, (int)$offset);

    $comment_select = "c.`{$comment_column}` AS comment_text";
    $user_join = "";
    if ($user_column) {
        $user_join = "LEFT JOIN users u ON u.user_id = c.`{$user_column}`";
    } else {
        $user_join = "LEFT JOIN users u ON u.user_id = 0";
    }

    $sql = "
        SELECT 
            c.comment_id,
            {$comment_select},
            c.created_at,
            u.first_name,
            u.last_name,
            u.username
        FROM comments c
        {$user_join}
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("iii", $post_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $comments[] = [
            'comment_id' => (int)$row['comment_id'],
            'comment_text' => (string)$row['comment_text'],
            'created_at' => (string)$row['created_at'],
            'first_name' => (string)($row['first_name'] ?? ''),
            'last_name' => (string)($row['last_name'] ?? ''),
            'username' => (string)($row['username'] ?? '')
        ];
    }
    $stmt->close();
    return $comments;
}

//======================================================================
// FOLLOW HELPERS
//======================================================================

function ensure_followers_table($connection) {
    static $table_ready = null;
    if ($table_ready === true) {
        return true;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS followers (
            follow_id INT AUTO_INCREMENT PRIMARY KEY,
            follower_id INT NOT NULL,
            followee_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_follow_follower FOREIGN KEY (follower_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_follow_followee FOREIGN KEY (followee_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT uq_follow_pair UNIQUE (follower_id, followee_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if ($connection->query($sql) === true) {
        $table_ready = true;
        return true;
    }

    return false;
}

function get_followers_column_info($connection) {
    static $info = false;
    if ($info !== false) {
        return $info;
    }

    if (!ensure_followers_table($connection)) {
        $info = null;
        return $info;
    }

    $columns = get_table_columns($connection, 'followers');
    if (empty($columns)) {
        $info = null;
        return $info;
    }

    $follower_col = get_foreign_key_column($connection, 'followers', 'users');
    $followee_col = null;
    if ($follower_col) {
        $remaining = array_values(array_filter($columns, function ($row) use ($follower_col) {
            return $row['Field'] !== $follower_col;
        }));
        $followee_col = match_column($remaining, [
            'followee_id', 'followeeid', 'followed_id', 'followedid', 'target_user_id', 'targetid', 'follow_user_id', 'followee', 'followed'
        ]);
    } else {
        $follower_col = match_column($columns, [
            'follower_id', 'followerid', 'follower', 'user_id', 'userid', 'member_id'
        ]);
        $remaining = array_values(array_filter($columns, function ($row) use ($follower_col) {
            return $row['Field'] !== $follower_col;
        }));
        $followee_col = match_column($remaining, [
            'followee_id', 'followeeid', 'followed_id', 'followedid', 'target_user_id', 'targetid', 'follow_user_id'
        ]);
    }

    if (!$follower_col || !$followee_col) {
        $info = null;
        return $info;
    }

    $info = [
        'table' => 'followers',
        'follower_column' => $follower_col,
        'followee_column' => $followee_col
    ];
    return $info;
}

function is_following_user($connection, $follower_id, $followee_id) {
    $schema = get_followers_column_info($connection);
    if (!$schema || $follower_id <= 0 || $followee_id <= 0) {
        return false;
    }
    if ($follower_id === $followee_id) {
        return false;
    }
    $table = $schema['table'];
    $follower_col = "`{$schema['follower_column']}`";
    $followee_col = "`{$schema['followee_column']}`";

    $sql = "
        SELECT 1
        FROM {$table}
        WHERE {$follower_col} = ? AND {$followee_col} = ?
        LIMIT 1
    ";
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ii", $follower_id, $followee_id);
    $stmt->execute();
    $stmt->store_result();
    $following = $stmt->num_rows > 0;
    $stmt->close();
    return $following;
}

function toggle_follow_state($connection, $follower_id, $followee_id) {
    $schema = get_followers_column_info($connection);
    if (!$schema || $follower_id <= 0 || $followee_id <= 0 || $follower_id === $followee_id) {
        return false;
    }

    $table = $schema['table'];
    $follower_col = "`{$schema['follower_column']}`";
    $followee_col = "`{$schema['followee_column']}`";

    if (is_following_user($connection, $follower_id, $followee_id)) {
        $stmt = $connection->prepare("
            DELETE FROM {$table}
            WHERE {$follower_col} = ? AND {$followee_col} = ?
        ");
        if ($stmt) {
            $stmt->bind_param("ii", $follower_id, $followee_id);
            $stmt->execute();
            $stmt->close();
        }
        return false;
    }

    $stmt = $connection->prepare("
        INSERT INTO {$table} (`{$schema['follower_column']}`, `{$schema['followee_column']}`)
        VALUES (?, ?)
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $follower_id, $followee_id);
        $stmt->execute();
        $stmt->close();
    }
    return true;
}

function get_follow_totals($connection, $user_id) {
    $schema = get_followers_column_info($connection);
    if (!$schema || $user_id <= 0) {
        return ['followers' => 0, 'following' => 0];
    }
    $table = $schema['table'];
    $follower_col = "`{$schema['follower_column']}`";
    $followee_col = "`{$schema['followee_column']}`";

    $followers = 0;
    $following = 0;

    $followers_stmt = $connection->prepare("
        SELECT COUNT(*) AS total
        FROM {$table}
        WHERE {$followee_col} = ?
    ");
    if ($followers_stmt) {
        $followers_stmt->bind_param("i", $user_id);
        $followers_stmt->execute();
        $result = $followers_stmt->get_result();
        if ($result && ($row = $result->fetch_assoc())) {
            $followers = (int)$row['total'];
        }
        $followers_stmt->close();
    }

    $following_stmt = $connection->prepare("
        SELECT COUNT(*) AS total
        FROM {$table}
        WHERE {$follower_col} = ?
    ");
    if ($following_stmt) {
        $following_stmt->bind_param("i", $user_id);
        $following_stmt->execute();
        $result = $following_stmt->get_result();
        if ($result && ($row = $result->fetch_assoc())) {
            $following = (int)$row['total'];
        }
        $following_stmt->close();
    }

    return ['followers' => $followers, 'following' => $following];
}

function get_following_users($connection, $user_id, $limit = 8) {
    $schema = get_followers_column_info($connection);
    if (!$schema || $user_id <= 0) {
        return [];
    }

    $table = $schema['table'];
    $follower_col = $schema['follower_column'];
    $followee_col = $schema['followee_column'];

    $limit = max(1, min(50, (int)$limit));
    $sql = "
        SELECT u.user_id, u.first_name, u.last_name, u.username
        FROM {$table} f
        INNER JOIN users u ON u.user_id = f.`{$followee_col}`
        WHERE f.`{$follower_col}` = ?
        ORDER BY u.username ASC
        LIMIT ?
    ";
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $users[] = $row;
    }
    $stmt->close();
    return $users;
}
