<?php
//======================================================================
// EDIT CAPSULE PAGE
//======================================================================
include_once(realpath(dirname(__FILE__) . '/php/path.php'));
include_once "../php/session.php";

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$post_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

require_once "../php/config.php";

$post_stmt = $db_connection->prepare("
    SELECT 
        p.post_id,
        p.title,
        p.caption,
        p.unlock_at,
        p.media_path,
        p.has_media,
        pt.timezone_id
    FROM posts p
    LEFT JOIN post_timers pt ON p.post_id = pt.post_id
    WHERE p.post_id = ? AND p.user_id = ?
    LIMIT 1
");
$post_stmt->bind_param("ii", $post_id, $user_id);
$post_stmt->execute();
$post_result = $post_stmt->get_result();
$post = $post_result->fetch_assoc();
$post_stmt->close();

if (!$post) {
    header("Location: dashboard.php");
    exit();
}

$tags_stmt = $db_connection->prepare("
    SELECT t.name
    FROM tags t
    INNER JOIN post_tags pt ON t.tag_id = pt.tag_id
    WHERE pt.post_id = ?
");
$tags_stmt->bind_param("i", $post_id);
$tags_stmt->execute();
$tags_result = $tags_stmt->get_result();
$tag_names = [];
while ($tag_row = $tags_result->fetch_assoc()) {
    $tag_names[] = $tag_row['name'];
}
$tags_stmt->close();

$timezone_query = $db_connection->query("SELECT timezone_id, tz_name, utc_offset FROM timezones ORDER BY tz_name ASC");

$unlockDate = date('Y-m-d', strtotime($post['unlock_at']));
$unlockTime = date('H:i', strtotime($post['unlock_at']));
$tagValue = implode(', ', $tag_names);

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit TimeCap</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <style>
        :root {
            --primary-color: #13a4ec;
            --background-dark: #101c22;
            --text-light: #ffffff;
        }

        body {
            font-family: "Plus Jakarta Sans", "Noto Sans", sans-serif;
            background-color: var(--background-dark);
            color: var(--text-light);
        }

        .card-section {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>
    <?php include_once "../include/Navbar.php"; ?>
    <div class="d-flex min-vh-100">
        <?php include_once "../include/sidebar.php"; ?>
        <main class="flex-grow-1 p-4 d-flex flex-column">
            <header class="navbar sticky-top px-4 py-3">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h2 class="fw-bold mb-0 text-primary">Edit Capsule</h2>
                    <a href="dashboard.php" class="btn btn-outline-light">Back to Dashboard</a>
                </div>
            </header>

            <form action="./capsules/update.php" method="POST" enctype="multipart/form-data" class="mt-4">
                <input type="hidden" name="post_id" value="<?= htmlspecialchars($post_id) ?>">

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card-section mb-4">
                            <label class="form-label fw-bold">Post Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title']) ?>" required>
                        </div>

                        <div class="card-section mb-4">
                            <label class="form-label fw-bold">Message / Description</label>
                            <textarea name="message" class="form-control" rows="6" required><?= htmlspecialchars($post['caption']) ?></textarea>
                        </div>

                        <div class="card-section mb-4">
                            <label class="form-label fw-bold">Tags (comma separated)</label>
                            <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($tagValue) ?>">
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card-section mb-4">
                            <h5 class="fw-bold mb-3">Current Media</h5>
                            <?php if (!empty($post['media_path'])): ?>
                                <img src="<?= BASE_URL . '/' . htmlspecialchars($post['media_path']) ?>" class="img-fluid rounded mb-2" alt="Existing media">
                                <p class="small text-secondary mb-0">Upload a new file to replace the current one.</p>
                            <?php else: ?>
                                <p class="small text-secondary mb-0">No media attached yet.</p>
                            <?php endif; ?>
                            <label class="w-100 p-4 border border-secondary border-dashed rounded text-center mt-3" style="cursor:pointer;">
                                <span class="material-symbols-outlined text-primary fs-1">upload_file</span>
                                <p class="mt-2 mb-1">Upload new media (optional)</p>
                                <p class="small text-secondary mb-0" id="edit_media_status" data-default-text="No file selected yet.">
                                    No file selected yet.
                                </p>
                                <input type="file" name="media" accept="image/*,video/*" class="d-none" id="edit_media_input">
                            </label>
                        </div>

                        <div class="card-section mb-4">
                            <h5 class="fw-bold mb-3">Unlock Details</h5>
                            <label class="form-label">Timezone</label>
                            <select name="timezone_id" class="form-control mb-3" required>
                                <?php while ($tz = $timezone_query->fetch_assoc()): ?>
                                    <option value="<?= $tz['timezone_id']; ?>" <?= ($tz['timezone_id'] == $post['timezone_id']) ? 'selected' : ''; ?>>
                                        <?= $tz['tz_name']; ?> (UTC<?= $tz['utc_offset']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>

                            <label class="form-label">Date</label>
                            <input type="date" name="unlock_date" class="form-control mb-3" value="<?= $unlockDate ?>" required>

                            <label class="form-label">Time</label>
                            <input type="time" name="unlock_time" class="form-control" value="<?= $unlockTime ?>" required>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top border-secondary">
                    <a href="dashboard.php" class="btn btn-outline-light px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5">Save Changes</button>
                </div>
            </form>
        </main>
    </div>

    <footer class="text-center py-3 mt-auto">
        <?php include_once "../include/footer.php"; ?>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mediaInput = document.getElementById('edit_media_input');
            const mediaStatus = document.getElementById('edit_media_status');

            if (!mediaInput || !mediaStatus) {
                return;
            }

            const defaultText = mediaStatus.dataset.defaultText || mediaStatus.textContent;
            const updateStatus = () => {
                if (mediaInput.files && mediaInput.files.length > 0) {
                    mediaStatus.textContent = mediaInput.files[0].name;
                    mediaStatus.classList.remove('text-secondary');
                    mediaStatus.classList.add('text-info');
                } else {
                    mediaStatus.textContent = defaultText;
                    mediaStatus.classList.add('text-secondary');
                    mediaStatus.classList.remove('text-info');
                }
            };

            mediaInput.addEventListener('change', updateStatus);
            updateStatus();
        });
    </script>
</body>

</html>
