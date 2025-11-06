

-- 1) users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    last_login_at TIMESTAMP(6) NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    status ENUM('active','inactive','banned') DEFAULT 'active'
) ENGINE=InnoDB;

-- 2) profiles : 1:1 with users (user_id is the PK here, so no alternates)
CREATE TABLE profiles (
    user_id INT PRIMARY KEY,          -- enforces 1:1 by making FK the PK
    display_name VARCHAR(100),
    bio TEXT,
    avatar_media_id INT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    location VARCHAR(100),
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    title VARCHAR(255),
    body TEXT,
    post_type ENUM('text','image','video','audio','link') NOT NULL,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at TIMESTAMP(6) NULL,
    is_deleted BOOLEAN DEFAULT FALSE,    -- soft delete flag
    is_published BOOLEAN DEFAULT FALSE,
    privacy ENUM('public','followers','private') DEFAULT 'public',
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE timers (
    post_id INT PRIMARY KEY,                  -- PK and FK: 1:1 relationship
    scheduled_unlock_at TIMESTAMP(6) NOT NULL,
    opened_at TIMESTAMP(6) NULL,
    status ENUM('scheduled','opened','cancelled') NOT NULL DEFAULT 'scheduled',
    is_searchable_before_open BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE time_zones (
    timezone_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,            -- e.g. 'America/New_York'
    utc_offset_minutes INT NOT NULL,       -- stored as minutes, e.g. -300, 0, 330
    observes_dst BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB;

CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    author_id INT NOT NULL,
    parent_comment_id INT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE RESTRICT,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (parent_comment_id) REFERENCES comments(comment_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 6) reactions : reactions only on posts; app enforces one reaction per user/post if desired
CREATE TABLE reactions (
    reaction_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('like','love','laugh') NOT NULL,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 7) saved_posts : bookmarks (separate from reactions)
CREATE TABLE saved_posts (
    save_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 8) followers : simple follow model (one table)
CREATE TABLE followers (
    follower_record_id INT AUTO_INCREMENT PRIMARY KEY,
    follower_user_id INT NOT NULL,
    followee_user_id INT NOT NULL,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    FOREIGN KEY (follower_user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (followee_user_id) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 9) media_files : store uploaded file metadata
CREATE TABLE media_files (
    media_id INT AUTO_INCREMENT PRIMARY KEY,
    uploader_id INT NULL,
    file_path VARCHAR(1024) NOT NULL,
    mime_type VARCHAR(100),
    size_bytes BIGINT,
    width INT,
    height INT,
    duration INT,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    FOREIGN KEY (uploader_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 10) post_media : link media files to posts (many-to-many)
CREATE TABLE post_media (
    post_media_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    media_id INT NOT NULL,
    position INT NULL,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (media_id) REFERENCES media_files(media_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 11) tags : tag dictionary
CREATE TABLE tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB;

-- 12) post_tags : many-to-many linking posts to tags
CREATE TABLE post_tags (
    post_tag_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE RESTRICT,
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

