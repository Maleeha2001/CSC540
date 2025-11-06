-- ===================================
-- 1. USERS (Parent Table)
-- ===================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    roll_number VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    status ENUM('active','inactive','banned') DEFAULT 'active'
) ENGINE=InnoDB;

-- ===================================
-- 2. TIMEZONES (Parent Table)
-- ===================================
CREATE TABLE timezones (
    timezone_id INT AUTO_INCREMENT PRIMARY KEY,
    tz_name VARCHAR(100) NOT NULL,
    utc_offset VARCHAR(10),
    dst_rules VARCHAR(100)
) ENGINE=InnoDB;

-- ===================================
-- 3. MEDIA FILES
-- ===================================
CREATE TABLE media_files (
    media_id INT AUTO_INCREMENT PRIMARY KEY,
    uploader_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100),
    size_bytes INT,
    width INT,
    height INT,
    duration INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploader_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ===================================
-- 4. PROFILES
-- ===================================
CREATE TABLE profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    display_name VARCHAR(100),
    bio TEXT,
    avatar_media_id INT,
    is_public BOOLEAN DEFAULT TRUE,
    location VARCHAR(100),
    timezone_id INT,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (avatar_media_id) REFERENCES media_files(media_id),
    FOREIGN KEY (timezone_id) REFERENCES timezones(timezone_id)
) ENGINE=InnoDB;

-- ===================================
-- 5. POSTS
-- ===================================
CREATE TABLE posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255),
    caption TEXT,
    privacy ENUM('public', 'private') NOT NULL DEFAULT 'public',
    unlock_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    has_media BOOLEAN NOT NULL DEFAULT FALSE,
    searchable_after_unlock BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ===================================
-- 6. TAGS
-- ===================================
CREATE TABLE tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================================
-- 7. POST_MEDIA
-- ===================================
CREATE TABLE post_media (
    post_media_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    media_id INT NOT NULL,
    display_order INT DEFAULT 0,
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (media_id) REFERENCES media_files(media_id)
) ENGINE=InnoDB;

-- ===================================
-- 8. POST_TAGS
-- ===================================
CREATE TABLE post_tags (
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id)
) ENGINE=InnoDB;

-- ===================================
-- 9. COMMENTS
-- ===================================
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    author_id INT NOT NULL,
    parent_comment_id INT NULL,
    body TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (author_id) REFERENCES users(user_id),
    FOREIGN KEY (parent_comment_id) REFERENCES comments(comment_id)
) ENGINE=InnoDB;

-- ===================================
-- 10. REACTIONS
-- ===================================
CREATE TABLE reactions (
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    reaction_type ENUM('like','love','laugh','angry','sad') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (post_id) REFERENCES posts(post_id)
) ENGINE=InnoDB;

-- ===================================
-- 11. FOLLOWERS
-- ===================================
CREATE TABLE followers (
    user_id INT NOT NULL,
    followee_id INT NOT NULL,
    status ENUM('requested','active','blocked') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, followee_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (followee_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ===================================
-- 12. NOTIFICATIONS
-- ===================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_user_id INT NULL,
    type VARCHAR(100),
    target_type VARCHAR(50),
    target_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (actor_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ===================================
-- 13. AUDIT_LOGS
-- ===================================
CREATE TABLE audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_type VARCHAR(100),
    entity_type VARCHAR(50),
    entity_id INT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    meta_json JSON,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ===================================
-- 14. POST_TIMERS
-- ===================================
CREATE TABLE post_timers (
    timer_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    unlock_at DATETIME NOT NULL,
    timezone_id INT NOT NULL,
    status ENUM('scheduled','unlocked','expired') DEFAULT 'scheduled',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (timezone_id) REFERENCES timezones(timezone_id)
) ENGINE=InnoDB;
