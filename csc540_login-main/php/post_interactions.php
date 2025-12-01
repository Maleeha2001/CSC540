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
 * Retrieve how many likes a post has and whether a user liked it.
 */
function get_like_summary($connection, $post_id, $user_id = null) {
    if (!ensure_reactions_table($connection)) {
        return ['count' => 0, 'liked' => false];
    }

    $summary = ['count' => 0, 'liked' => false];

    $count_stmt = $connection->prepare("
        SELECT COUNT(*) AS like_count
        FROM reactions
        WHERE post_id = ? AND reaction_type = 'like'
    ");
    if ($count_stmt) {
        $count_stmt->bind_param("i", $post_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        if ($count_result && ($row = $count_result->fetch_assoc())) {
            $summary['count'] = (int)$row['like_count'];
        }
        $count_stmt->close();
    }

    if ($user_id !== null) {
        $liked_stmt = $connection->prepare("
            SELECT 1
            FROM reactions
            WHERE post_id = ? AND user_id = ? AND reaction_type = 'like'
            LIMIT 1
        ");
        if ($liked_stmt) {
            $liked_stmt->bind_param("ii", $post_id, $user_id);
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

    $limit = max(1, min(50, (int)$limit));
    $offset = max(0, (int)$offset);

    $sql = "
        SELECT 
            c.comment_id,
            c.comment_text,
            c.created_at,
            u.first_name,
            u.last_name,
            u.username
        FROM comments c
        INNER JOIN users u ON c.user_id = u.user_id
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
