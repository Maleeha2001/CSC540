<?php
//======================================================================
// USER PROFILE HELPER FUNCTIONS
//======================================================================

/**
 * Ensure the user_bios table exists (created on demand).
 */
function ensure_user_bio_table($connection) {
    static $table_ready = null;
    if ($table_ready === true) {
        return true;
    }
    if ($connection->connect_error) {
        return false;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS user_bios (
            user_id INT NOT NULL PRIMARY KEY,
            bio TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_bios_user
                FOREIGN KEY (user_id) REFERENCES users(user_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if ($connection->query($sql) === true) {
        $table_ready = true;
        return true;
    }

    return false;
}

/**
 * Retrieve the stored bio for a user.
 */
function get_user_bio($connection, $user_id) {
    if (!ensure_user_bio_table($connection)) {
        return '';
    }
    $stmt = $connection->prepare("SELECT bio FROM user_bios WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        return '';
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bio = '';
    if ($result && ($row = $result->fetch_assoc())) {
        $bio = (string)($row['bio'] ?? '');
    }
    $stmt->close();
    return $bio;
}

/**
 * Persist a bio for a user (insert/update on demand).
 */
function save_user_bio($connection, $user_id, $bio) {
    if (!ensure_user_bio_table($connection)) {
        return false;
    }

    $stmt = $connection->prepare("
        INSERT INTO user_bios (user_id, bio)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE bio = VALUES(bio)
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("is", $user_id, $bio);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Clamp a bio to the supported character count.
 */
function sanitize_bio_input($bio_text) {
    $bio_text = trim($bio_text ?? '');
    if (strlen($bio_text) > 150) {
        $bio_text = mb_substr($bio_text, 0, 150);
    }
    return $bio_text;
}
