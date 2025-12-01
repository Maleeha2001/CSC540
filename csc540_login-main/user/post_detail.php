<?php
include_once(realpath(dirname(__FILE__) . '/php/path.php'));
include_once "../php/session.php";
include_once "../php/post_interactions.php";

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($post_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$post_stmt = $db_connection->prepare("
    SELECT 
        p.post_id,
        p.user_id,
        p.title,
        p.caption,
        p.unlock_at,
        p.created_at,
        p.privacy,
        p.media_path,
        p.has_media,
        u.first_name,
        u.last_name,
        u.username
    FROM posts p
    INNER JOIN users u ON p.user_id = u.user_id
    WHERE p.post_id = ?
");
$post_stmt->bind_param("i", $post_id);
$post_stmt->execute();
$post_result = $post_stmt->get_result();
$post = $post_result->fetch_assoc();
$post_stmt->close();

if (!$post) {
    header("Location: dashboard.php");
    exit();
}

$viewer_id = (int)($_SESSION['user_id'] ?? 0);
ensure_reactions_table($db_connection);
ensure_comments_table($db_connection);
$likeSummary = get_like_summary($db_connection, $post_id, $viewer_id);
$commentCount = get_comment_count($db_connection, $post_id);

$timer_stmt = $db_connection->prepare("
    SELECT pt.unlock_at, pt.status, tz.tz_name, tz.utc_offset
    FROM post_timers pt
    LEFT JOIN timezones tz ON pt.timezone_id = tz.timezone_id
    WHERE pt.post_id = ? LIMIT 1
");
$timer_stmt->bind_param("i", $post_id);
$timer_stmt->execute();
$timer_result = $timer_stmt->get_result();
$timer = $timer_result->fetch_assoc();
$timer_stmt->close();

$unlockDateTime = new DateTime($post['unlock_at']);
$now = new DateTime();
$isOwner = ($post['user_id'] === $viewer_id);

if (!$isOwner) {
    $isPublic = strtolower($post['privacy'] ?? 'public') === 'public';
    if (!$isPublic || $now < $unlockDateTime) {
        header("Location: feed.php");
        exit();
    }
}

$isUnlocked = $now >= $unlockDateTime;
$statusLabel = $isUnlocked ? 'Unlocked' : 'Locked';
$statusAccent = $isUnlocked ? 'Unlocked on ' . $unlockDateTime->format('M d, Y h:i A') : 'Unlocks on ' . $unlockDateTime->format('M d, Y h:i A');

$mediaSrc = 'https://via.placeholder.com/600x300';
if (!empty($post['media_path'])) {
    $mediaSrc = BASE_URL . '/' . ltrim($post['media_path'], '/');
}

$authorName = trim(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? ''));
$authorHandle = !empty($post['username']) ? '@' . $post['username'] : '';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unlocked Capsule - TimeCap</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <style>
         :root {
      --primary-color: #13a4ec;
      --background-light: #f6f7f8;
      --background-dark: #101c22;
      --text-light: #ffffff;
      --text-dark: #0f172a;
      --font-display: "Plus Jakarta Sans", "Noto Sans", sans-serif;
    }

    body {
      font-family: var(--font-display);
      background-color: var(--background-light);
      color: var(--text-dark);
      min-height: 100vh;
    }

    [data-bs-theme="dark"] body {
      background-color: var(--background-dark);
      color: var(--text-light);
    }

    .navbar {
      background-color: rgba(16, 28, 34, 0.8);
      backdrop-filter: blur(8px);
    }

    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
        .card-surface {
            background-color: #192b33;
            border: 1px solid #32556780;
        }

        .accent {
            color: #13a4ec;
        }

        .icon {
            font-family: 'Material Symbols Outlined';
            font-size: 28px;
            vertical-align: middle;
        }

        .media-box {
            background-size: cover;
            background-position: center;
            border-radius: 12px;
            height: 220px;
            position: relative;
        }

        .interaction-bar {
            background-color: #192b33;
            border-top: 1px solid #32556780;
        }

        .btn-soft {
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            backdrop-filter: blur(4px);
        }

        .interaction-like.liked .material-symbols-outlined {
            color: #ff5a8d;
            font-variation-settings: 'FILL' 1;
        }

        .comment-card {
            background-color: #13222a;
            border-radius: 0.75rem;
            border: 1px solid #32556760;
        }

        .comment-item + .comment-item {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .comment-item .author {
            font-weight: 600;
        }
    </style>
</head>

<body>
    <!--  Top Navbar -->
    <?php include_once "../include/Navbar.php"; ?>

    <!-- Page Container -->
    <div class="d-flex min-vh-100">
        <!-- Sidebar -->
        <?php include_once "../include/sidebar.php"; ?>
        <!-- Main Content -->
        <main class="flex-grow-1 d-flex flex-column  ">

            <!-- TOP NAV -->
            <div class="d-flex align-items-center px-3 py-3 border-bottom card-surface">
                <a href="dashboard.php" class="text-white">
                    <span class="material-symbols-outlined fs-3">arrow_back</span>
                </a>

                <div class="d-flex align-items-center ms-3 flex-grow-1">
                    <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBx4LQCd_23dIxj5fQVhEAQ7pYdrqByRTn_Y-btuNgYejGguz4UF0ssd0T5LMFghT2CmK7FwfZV1hMyO9Caem_L7Vi05lPVzIrehZD3QwvrjVoidP0aM3cg0Q4jN1l8pWOO3FYN-p84diWShVJN5QCqqy7FlOzQEUcRHagwnBg-0HW8NXhtmVk01gOZ8W5qgUJxg6A6bigLHhyrX8QtBjJfx-1V0e8Qd7espa94Jr6Ic8rBf8nIOw-1ZDZEytjGgAgDADu2YF7kLbE"
                        class="rounded-circle me-3" width="38" height="38" alt="author avatar">

                    <div>
                        <div class="fw-bold small">Capsule by <?= htmlspecialchars($authorName ?: 'You'); ?></div>
                        <div class="text-secondary small"><?= htmlspecialchars($authorHandle); ?></div>
                    </div>
                </div>

                <span class="material-symbols-outlined fs-3">more_horiz</span>
            </div>


            <!-- MAIN CONTENT -->
            <div class="container py-4">

                <?php if (!empty($post['media_path'])): ?>
                <div class="media-box mb-4" style='background-image: url("<?= htmlspecialchars($mediaSrc) ?>");'>
                    <?php if ($post['has_media'] && preg_match('/\.mp4$/i', $post['media_path'])): ?>
                        <span class="badge bg-dark position-absolute top-0 end-0 m-2">Video</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <h2 class="fw-bold mb-1"><?= htmlspecialchars($post['title']); ?></h2>
                <p class="accent small mb-3"><?= htmlspecialchars($statusLabel); ?> · <?= htmlspecialchars($statusAccent); ?></p>

                <?php if (!$isUnlocked): ?>
                    <div class="alert alert-warning text-dark">
                        This capsule is still locked. It will unlock on <?= htmlspecialchars($unlockDateTime->format('M d, Y h:i A')); ?>.
                    </div>
                <?php endif; ?>

                <p class="text-light mb-4">
                    <?= nl2br(htmlspecialchars($post['caption'])); ?>
                </p>
                <div class="container d-flex justify-content-between border pt-3 gap-3 flex-wrap">

                    <button
                        type="button"
                        class="btn btn-soft rounded-pill d-flex align-items-center px-3 gap-2 interaction-like <?= $likeSummary['liked'] ? 'liked' : ''; ?>"
                        data-like-button
                        data-post-id="<?= $post['post_id']; ?>"
                        data-liked="<?= $likeSummary['liked'] ? '1' : '0'; ?>"
                    >
                        <span class="material-symbols-outlined like-icon">favorite</span>
                        <span class="like-count" data-like-count><?= $likeSummary['count']; ?></span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-soft rounded-pill d-flex align-items-center px-3 gap-2 interaction-comment"
                        data-scroll-comments="true"
                    >
                        <span class="material-symbols-outlined">chat_bubble_outline</span>
                        <span class="comment-count" data-comment-count><?= $commentCount; ?></span>
                    </button>

                </div>
            </div>



            <div class="container mt-4 comment-card p-4" id="commentSection">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Conversation</h5>
                    <span class="badge bg-primary rounded-pill">
                        <span data-comment-count><?= $commentCount; ?></span> total
                    </span>
                </div>
                <div id="commentList" class="comment-list mb-4" data-post-id="<?= $post['post_id']; ?>">
                    <div class="text-secondary small">Loading comments...</div>
                </div>
                <form id="commentForm" class="d-flex gap-2" data-post-id="<?= $post['post_id']; ?>">
                    <input type="text" name="comment" class="form-control" placeholder="Share your thoughts..." maxlength="500" required>
                    <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-1">
                        <span class="material-symbols-outlined small">send</span> Post
                    </button>
                </form>
            </div>


            <!-- BOTTOM INTERACTION BAR -->
            <!-- <div class="interaction-bar fixed-bottom py-2">
                
            </div> -->
        </main>
    </div>
    </div>
<footer class="text-center py-3 mt-auto">
      <?php include_once "../include/footer.php"; ?>
    </footer>
<!--  -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const likeButton = document.querySelector('[data-like-button]');
        const commentList = document.getElementById('commentList');
        const commentForm = document.getElementById('commentForm');
        const commentCountEls = document.querySelectorAll('[data-comment-count]');
        const postId = commentForm ? commentForm.dataset.postId : null;

        const renderComments = (comments) => {
            if (!commentList) {
                return;
            }
            if (!comments || comments.length === 0) {
                commentList.innerHTML = '<div class="text-secondary small">No comments yet. Be the first to share something.</div>';
                return;
            }
            commentList.innerHTML = comments.map((comment) => {
                const author = [comment.first_name, comment.last_name].filter(Boolean).join(' ') || comment.username || 'User';
                const when = new Date(comment.created_at).toLocaleString();
                return `
                    <div class="comment-item py-2">
                        <div class="author text-primary small">${author}</div>
                        <div class="text-white">${escapeHtml(comment.comment_text)}</div>
                        <div class="text-secondary small">${when}</div>
                    </div>
                `;
            }).join('');
        };

        const updateCommentCount = (count) => {
            commentCountEls.forEach((el) => {
                el.textContent = count;
            });
        };

        const fetchComments = async () => {
            if (!postId) {
                return;
            }
            try {
                const response = await fetch(`api/get_comments.php?post_id=${encodeURIComponent(postId)}`);
                const data = await response.json();
                if (data.success) {
                    renderComments(data.comments);
                    updateCommentCount(data.comment_count);
                } else if (commentList) {
                    commentList.innerHTML = `<div class="text-danger small">${data.message || 'Failed to load comments.'}</div>`;
                }
            } catch (error) {
                if (commentList) {
                    commentList.innerHTML = '<div class="text-danger small">Unable to load comments.</div>';
                }
            }
        };

        const toggleLike = async () => {
            if (!likeButton) {
                return;
            }
            const postId = likeButton.dataset.postId;
            const formData = new URLSearchParams();
            formData.append('post_id', postId);

            likeButton.disabled = true;
            try {
                const response = await fetch('api/toggle_like.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                const data = await response.json();
                if (data.success) {
                    likeButton.dataset.liked = data.liked ? '1' : '0';
                    likeButton.classList.toggle('liked', data.liked);
                    const countTarget = likeButton.querySelector('[data-like-count]');
                    if (countTarget) {
                        countTarget.textContent = data.like_count;
                    }
                }
            } catch (error) {
                console.error(error);
            } finally {
                likeButton.disabled = false;
            }
        };

        const handleCommentSubmit = async (event) => {
            event.preventDefault();
            if (!commentForm) {
                return;
            }
            const input = commentForm.querySelector('input[name="comment"]');
            if (!input || input.value.trim() === '') {
                return;
            }

            const formData = new URLSearchParams();
            formData.append('post_id', commentForm.dataset.postId);
            formData.append('comment', input.value.trim());

            commentForm.querySelector('button[type="submit"]').disabled = true;
            try {
                const response = await fetch('api/add_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                const data = await response.json();
                if (data.success) {
                    input.value = '';
                    await fetchComments();
                }
            } catch (error) {
                console.error(error);
            } finally {
                commentForm.querySelector('button[type="submit"]').disabled = false;
            }
        };

        if (likeButton) {
            likeButton.addEventListener('click', toggleLike);
        }
        if (commentForm) {
            commentForm.addEventListener('submit', handleCommentSubmit);
        }
        document.querySelectorAll('[data-scroll-comments="true"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('commentSection')?.scrollIntoView({ behavior: 'smooth' });
            });
        });

        fetchComments();
    });

    function escapeHtml(unsafe) {
        const div = document.createElement('div');
        div.textContent = unsafe;
        return div.innerHTML;
    }
</script>
</body>

</html>
