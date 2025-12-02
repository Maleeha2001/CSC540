<?php
include_once(realpath(dirname(__FILE__) . '/php/path.php'));
$page_name = "profile_view";

include_once "../php/session.php";
include_once "../php/user_profile_helpers.php";
include_once "../php/post_interactions.php";

$viewer_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($target_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

if ($target_id === $viewer_id) {
    header("Location: dashboard.php");
    exit();
}

$profile_stmt = $db_connection->prepare("
    SELECT user_id, first_name, last_name, username
    FROM users
    WHERE user_id = ?
    LIMIT 1
");
if (!$profile_stmt) {
    header("Location: dashboard.php");
    exit();
}
$profile_stmt->bind_param("i", $target_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result ? $profile_result->fetch_assoc() : null;
$profile_stmt->close();

if (!$profile) {
    header("Location: dashboard.php");
    exit();
}

$display_name = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
$display_handle = !empty($profile['username']) ? '@' . $profile['username'] : '';
$bioText = trim(get_user_bio($db_connection, $target_id));
if ($bioText === '') {
    $bioText = 'No bio yet.';
}

ensure_followers_table($db_connection);
$totals = get_follow_totals($db_connection, $target_id);
$followersCount = $totals['followers'] ?? 0;
$followingCount = $totals['following'] ?? 0;
$viewerFollowing = is_following_user($db_connection, $viewer_id, $target_id);

$capsule_stmt = $db_connection->prepare("
    SELECT 
        p.post_id,
        p.title,
        p.unlock_at,
        p.created_at,
        p.has_media,
        p.media_path,
        p.privacy,
        pt.status,
        tz.tz_name,
        tz.utc_offset
    FROM posts p
    LEFT JOIN post_timers pt ON p.post_id = pt.post_id
    LEFT JOIN timezones tz ON pt.timezone_id = tz.timezone_id
    WHERE p.user_id = ?
    ORDER BY p.unlock_at DESC, p.created_at DESC
");
$capsules = [];
if ($capsule_stmt) {
    $capsule_stmt->bind_param("i", $target_id);
    $capsule_stmt->execute();
    $result = $capsule_stmt->get_result();
    $now = new DateTime();
    while ($result && ($row = $result->fetch_assoc())) {
        $unlock_at = new DateTime($row['unlock_at']);
        $row['_unlock_datetime'] = $unlock_at;
        $row['_is_locked'] = $now < $unlock_at;
        $capsules[] = $row;
    }
    $capsule_stmt->close();
}

$visibleCapsules = array_values(array_filter($capsules, function ($row) {
    return !$row['_is_locked'] && strtolower($row['privacy'] ?? 'public') === 'public';
}));

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($display_name); ?> - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <style>
        :root {
            --primary-color: #13a4ec;
            --background-dark: #101c22;
            --text-light: #f8fafc;
        }
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background-color: var(--background-dark);
            color: var(--text-light);
        }
        .profile-card {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 2rem;
        }
        .capsule-card {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .capsule-card img {
            border-radius: 1rem 1rem 0 0;
        }
    </style>
</head>

<body>
<?php include_once "../include/Navbar.php"; ?>
<div class="d-flex min-vh-100">
    <?php include_once "../include/sidebar.php"; ?>
    <main class="flex-grow-1 p-4">
        <div class="container">
            <div class="profile-card mb-4">
                <div class="d-flex flex-column flex-md-row align-items-start gap-4">
                    <img src="https://ui-avatars.com/api/?background=0D8ABC&color=fff&name=<?= urlencode($display_name ?: $display_handle ?: 'User'); ?>"
                         alt="avatar"
                         class="rounded-circle"
                         width="120"
                         height="120"
                         style="object-fit: cover;">
                    <div class="flex-grow-1">
                        <h2 class="fw-bold mb-1"><?= htmlspecialchars($display_name ?: 'TimeCap User'); ?></h2>
                        <p class="text-secondary mb-2"><?= htmlspecialchars($display_handle); ?></p>
                        <p class="text-secondary"><?= nl2br(htmlspecialchars($bioText)); ?></p>
                        <div class="d-flex gap-4 mt-3">
                            <div>
                                <div class="fw-bold"><?= number_format($followersCount); ?></div>
                                <div class="text-secondary small">Followers</div>
                            </div>
                            <div>
                                <div class="fw-bold"><?= number_format($followingCount); ?></div>
                                <div class="text-secondary small">Following</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <?php if ($viewer_id > 0 && $viewer_id !== $target_id): ?>
                            <button
                                type="button"
                                class="btn <?= $viewerFollowing ? 'btn-outline-light' : 'btn-outline-primary'; ?>"
                                id="followToggleBtn"
                                data-user-id="<?= $target_id; ?>"
                                data-following="<?= $viewerFollowing ? '1' : '0'; ?>">
                                <?= $viewerFollowing ? 'Following' : 'Follow'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <h3 class="fw-bold mb-3">Public Capsules</h3>
            <?php if (empty($visibleCapsules)): ?>
                <div class="text-secondary">No unlocked public capsules to display yet.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($visibleCapsules as $capsule): ?>
                        <?php
                            $unlockAt = $capsule['_unlock_datetime'];
                            $time_label = $unlockAt->format('M d, Y h:i A');
                            $mediaPath = $capsule['media_path'] ?? '';
                            $imageSrc = $mediaPath ? BASE_URL . '/' . ltrim($mediaPath, '/') : 'https://via.placeholder.com/300x200';
                        ?>
                        <div class="col-md-6 col-xl-3">
                            <div class="capsule-card h-100">
                                <img src="<?= htmlspecialchars($imageSrc); ?>" class="img-fluid" alt="capsule media">
                                <div class="p-3">
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($capsule['title']); ?></h5>
                                    <div class="text-secondary small mb-1">
                                        <span class="material-symbols-outlined align-middle me-1">schedule</span>
                                        Unlocked on <?= htmlspecialchars($time_label); ?>
                                    </div>
                                    <div class="text-secondary small">
                                        <span class="material-symbols-outlined align-middle me-1">visibility</span>
                                        <?= ucfirst($capsule['privacy']); ?>
                                    </div>
                                    <div class="mt-3">
                                        <a href="post_detail.php?id=<?= $capsule['post_id']; ?>" class="btn btn-outline-light btn-sm">View Capsule</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include_once "../include/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const followBtn = document.getElementById('followToggleBtn');
    if (!followBtn) {
        return;
    }
    followBtn.addEventListener('click', async () => {
        const targetUserId = followBtn.dataset.userId;
        if (!targetUserId) {
            return;
        }
        followBtn.disabled = true;
        const formData = new URLSearchParams();
        formData.append('target_user_id', targetUserId);
        try {
            const response = await fetch('api/toggle_follow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            const data = await response.json();
            if (data.success) {
                const isFollowing = data.is_following;
                followBtn.dataset.following = isFollowing ? '1' : '0';
                followBtn.textContent = isFollowing ? 'Following' : 'Follow';
                followBtn.classList.toggle('btn-outline-light', isFollowing);
                followBtn.classList.toggle('btn-outline-primary', !isFollowing);
            } else if (data.message) {
                alert(data.message);
            }
        } catch (error) {
            console.error(error);
        } finally {
            followBtn.disabled = false;
        }
    });
});
</script>
</body>
</html>
