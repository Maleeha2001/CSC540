<?php
include_once(realpath(dirname(__FILE__) . '/php/path.php'));
include_once "../php/session.php";

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
    WHERE p.post_id = ? AND p.user_id = ?
");
$post_stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$post_stmt->execute();
$post_result = $post_stmt->get_result();
$post = $post_result->fetch_assoc();
$post_stmt->close();

if (!$post) {
    header("Location: dashboard.php");
    exit();
}

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
                <div class="container d-flex justify-content-between border pt-3">

                    <button class="btn btn-soft rounded-pill d-flex align-items-center px-3 gap-2">
                        <span class="material-symbols-outlined text-danger" style="font-variation-settings:'FILL' 1;">favorite</span>
                        <span>1.2k</span>
                    </button>

                    <button class="btn btn-soft rounded-pill d-flex align-items-center px-3 gap-2">
                        <span class="material-symbols-outlined">chat_bubble_outline</span>
                        <span>24</span>
                    </button>

                </div>
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
</body>

</html>
