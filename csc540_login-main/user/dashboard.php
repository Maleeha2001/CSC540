<?php
//======================================================================
// DASHBOARD PAGE
//======================================================================
/* Quick Paths */
include_once(realpath(dirname(__FILE__) . '/php/path.php'));

/* Page Name */
$page_name = "dashboard";

/* Start The Session */
//
// if (!isset($_SESSION['login_user'])) {
//   header("Location: http://localhost/csc540_login-main/index.php");
//   exit();

include_once "../php/session.php";
include_once "../php/user_profile_helpers.php";

/**
 * Lightweight cache for information_schema lookups.
 */
function table_exists_cached($connection, $table_name) {
    static $table_cache = [];
    if (isset($table_cache[$table_name])) {
        return $table_cache[$table_name];
    }
    if (!defined('DB_NAME')) {
        return $table_cache[$table_name] = false;
    }

    $stmt = $connection->prepare("
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return $table_cache[$table_name] = false;
    }
    $db_name = DB_NAME;
    $stmt->bind_param("ss", $db_name, $table_name);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $table_cache[$table_name] = $exists;
}

function column_exists_cached($connection, $table_name, $column_name) {
    static $column_cache = [];
    $cache_key = $table_name . '.' . $column_name;
    if (isset($column_cache[$cache_key])) {
        return $column_cache[$cache_key];
    }
    if (!defined('DB_NAME')) {
        return $column_cache[$cache_key] = false;
    }

    $stmt = $connection->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return $column_cache[$cache_key] = false;
    }
    $db_name = DB_NAME;
    $stmt->bind_param("sss", $db_name, $table_name, $column_name);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $column_cache[$cache_key] = $exists;
}

function count_relations($connection, $user_id, $candidates) {
    foreach ($candidates as $table => $columns) {
        if (!table_exists_cached($connection, $table)) {
            continue;
        }
        foreach ($columns as $column) {
            if (!column_exists_cached($connection, $table, $column)) {
                continue;
            }
            $sql = "SELECT COUNT(*) AS total FROM `$table` WHERE `$column` = ?";
            $stmt = $connection->prepare($sql);
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result && ($row = $result->fetch_assoc())) {
                    $count = (int)$row['total'];
                    $stmt->close();
                    return $count;
                }
            }
            $stmt->close();
        }
    }
    return 0;
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$followersCount = 0; // Following system not implemented yet
$followingCount = 0;

$capsulesCount = count_relations($db_connection, $user_id, [
    'posts' => ['user_id']
]);

$tab = strtolower($_GET['tab'] ?? 'locked');
$allowed_tabs = ['locked', 'unlocked'];
if (!in_array($tab, $allowed_tabs, true)) {
  $tab = 'locked';
}

$profile_stmt = $db_connection->prepare("
    SELECT first_name, last_name, username
    FROM users
    WHERE user_id = ?
    LIMIT 1
");
$profile_stmt->bind_param("i", $_SESSION['user_id']);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();
$profile_stmt->close();

$display_name = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
$display_handle = !empty($profile['username']) ? '@' . $profile['username'] : '';
$bioText = trim(get_user_bio($db_connection, $user_id));
if ($bioText === '') {
    $bioText = 'Time traveling through memories. Sealing moments for the future.';
}
$dashboard_flash = $_SESSION['dashboard_flash'] ?? '';
unset($_SESSION['dashboard_flash']);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Time Capsules - TimeCap</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet" />

  <!-- Material Symbols -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

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

    .btn-primary:hover {
      background-color: #0e8ecf;
      border-color: #0e8ecf;
    }

    .card .capsule-card {
      background-color: rgba(255, 255, 255, 0.1);
      border: none;
      border-radius: 1rem;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .material-symbols-outlined {
      vertical-align: middle;
    }

    .tab-nav a {
      font-weight: 700;
      border-bottom: 3px solid transparent;
      color: rgba(255, 255, 255, 0.7);
      padding: 0.75rem 0;
      display: inline-block;
      margin-right: 2rem;
      text-decoration: none;
    }

    .tab-nav a.active {
      border-color: var(--primary-color);
      color: var(--primary-color);
    }
            .profile-card {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .stat-card {
            background-color: rgba(255, 255, 255, 0.07);
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
  </style>
</head>

<body>
  <!-- Top Navbar -->
  <?php include_once "../include/Navbar.php"; ?>
  <div class="d-flex min-vh-100">
    <!-- Sidebar -->
    <?php include_once "../include/sidebar.php"; ?>

    <!-- Main -->
    <main class="flex-grow-1 d-flex flex-column">

      <?php if (!empty($dashboard_flash)): ?>
      <div class="alert alert-success alert-dismissible fade show mx-4 mt-3" role="alert">
        <?php echo htmlspecialchars($dashboard_flash); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php endif; ?>

      <!-- Profile Top -->
      <div class="text-center mb-4">
        <div class="position-relative mx-auto" style="width: 130px;z-index: 3;">
          <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAhoO3Kn5s8uuPQSl0sdMUer7ggJOvCdb9BFND9ObG_d3uiWQcp_7sKRHGXVLXZRzvx7PMAKihbAr_R5BV_pvawClBLQNrmRyir4go_liiflkL4iz9lM71SbTaT6i9JYT5K3bdCgcHw4W6PD7MFlkiFZW_8I_5b6sn-TlMSR4SP0lMm5xXzjFsRjRCfTCfML_87kZsiyEaPQehKMfAmz9teB6fZ5E_7_9OEQyJ403APiD4_Wmo2xfc-zGBtjdc0OKvcDlKhjWWf3aI"
            class="rounded-circle w-100 shadow-sm" alt="avatar">
          <label class="btn btn-primary position-absolute bottom-0 end-0 rounded-circle p-1"
                        style="width: 36px; height: 36px;">
                        <span class="material-symbols-outlined">edit</span>
                        <input type="file" name="profile_pic" accept="image/*" class="d-none" id="profilePicInput">
      </label>
        </div>

        <h3 class="fw-bold mt-3"><?= htmlspecialchars($display_name ?: $_SESSION['user_first']); ?></h3>
        <p class="text-secondary"><?= htmlspecialchars($display_handle); ?></p>
        <p class="text-secondary small">
          <?= nl2br(htmlspecialchars($bioText)); ?>
        </p>
        <a href="profile.php" class="btn btn-secondary fw-bold px-4 mt-2">Edit Profile</a>
      </div>

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-md stat-card">
          <h3 class="fw-bold"><?= number_format($capsulesCount); ?></h3>
          <p class="text-secondary mb-0">Capsules</p>
        </div>
        <div class="col-md stat-card">
          <h3 class="fw-bold"><?= number_format($followersCount); ?></h3>
          <p class="text-secondary mb-0">Followers</p>
        </div>
        <div class="col-md stat-card">
          <h3 class="fw-bold"><?= number_format($followingCount); ?></h3>
          <p class="text-secondary mb-0">Following</p>
        </div>
      </div>
      <header class="navbar sticky-top px-4 py-3">
        <div class="d-flex justify-content-between align-items-center w-100">
          <h2 class="fw-bold mb-0 text-primary">My Time Capsules</h2>
          <button onclick="location.href='create_post.php'" class="btn btn-primary d-flex align-items-center gap-2 rounded-pill">
            <span class="material-symbols-outlined">add</span> Create New
          </button>
        </div>
      </header>

      <!-- Tabs -->
      <div class="px-4 border-bottom border-secondary tab-nav mt-3">
        <a href="?tab=locked" class="<?php echo $tab === 'locked' ? 'active' : ''; ?>">Locked</a>
        <a href="?tab=unlocked" class="<?php echo $tab === 'unlocked' ? 'active' : ''; ?>">Unlocked</a>
      </div>

      <!-- Controls -->
      <!-- <div class="p-4 d-flex flex-column flex-sm-row gap-3 align-items-start align-items-sm-center">
          <div class="input-group w-auto">
            <span class="input-group-text bg-dark border-0 text-white">
              <span class="material-symbols-outlined">search</span>
            </span>
            <input type="text" class="form-control bg-dark border-0 text-white" placeholder="Search by title..." />
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-dark d-flex align-items-center gap-1 border">
              Sort by: Unlock Date <span class="material-symbols-outlined">keyboard_arrow_down</span>
            </button>
            <button class="btn btn-dark d-flex align-items-center gap-1 border">
              Sort by: Creation Date <span class="material-symbols-outlined">keyboard_arrow_down</span>
            </button>
          </div>
        </div> -->

      <!-- Capsule Cards -->
      <div class="container-fluid p-4">
        <div class="row g-4">
          <?php
// Fetch user’s capsules from database
$whereClause = $tab === 'locked' ? 'p.unlock_at > NOW()' : 'p.unlock_at <= NOW()';
$sql = "
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
        tz.utc_offset,
        GROUP_CONCAT(DISTINCT tg.name ORDER BY tg.name SEPARATOR ', ') AS tags
    FROM posts p
    LEFT JOIN post_timers pt ON p.post_id = pt.post_id
    LEFT JOIN timezones tz ON pt.timezone_id = tz.timezone_id
    LEFT JOIN post_tags ptg ON p.post_id = ptg.post_id
    LEFT JOIN tags tg ON ptg.tag_id = tg.tag_id
    WHERE p.user_id = ? AND $whereClause
    GROUP BY p.post_id, p.title, p.unlock_at, p.created_at, p.has_media, p.media_path, p.privacy, pt.status, tz.tz_name, tz.utc_offset
    ORDER BY p.unlock_at ASC
";
$stmt = $db_connection->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0):
    $emptyMessage = $tab === 'locked' ? 'No locked capsules right now.' : 'No unlocked capsules yet.';
?>
<div class="col-12 text-center text-secondary py-5">
    <?php echo $emptyMessage; ?>
</div>
<?php else:
while ($row = $result->fetch_assoc()):
    $unlock_at = new DateTime($row['unlock_at']);
    $now = new DateTime();

    // Determine status (Locked or Unlocked)
    if ($now < $unlock_at) {
        $status = "Locked";
        $time_left = $now->diff($unlock_at)->format('%dd %hh %im');
    } else {
        $status = "Unlocked";
        $time_left = "Available 🎉";
    }

    $mediaPath = $row['media_path'] ?? '';
    if (!empty($mediaPath)) {
        $imageSrc = BASE_URL . '/' . ltrim($mediaPath, '/');
    } else {
        $imageSrc = 'https://via.placeholder.com/300x200';
    }

    $timezoneLabel = !empty($row['tz_name'])
        ? $row['tz_name'] . (!empty($row['utc_offset']) ? ' (UTC' . $row['utc_offset'] . ')' : '')
        : 'No timezone set';
    $tagLabel = !empty($row['tags']) ? $row['tags'] : 'No tags';
?>
<div class="col-md-6 col-xl-3">
    <div class="capsule-card">
        <img src="<?= htmlspecialchars($imageSrc) ?>" class="img-fluid rounded-top" alt="capsule media">

        <div class="p-3">
            <h5 class="fw-bold"><?= htmlspecialchars($row['title']) ?></h5>

            <div class="d-flex align-items-center text-primary mb-2">
                <span class="material-symbols-outlined me-1">lock_clock</span>
                <small>
                    <span class="countdown" data-unlock="<?= $row['unlock_at'] ?>">
                        <?= $time_left ?>
                    </span>
                </small>
            </div>

            <div class="text-secondary small">
                <span class="material-symbols-outlined me-1 small">visibility</span>
                <?= ucfirst($row['privacy']) ?> · Created: <?= date("M d, Y", strtotime($row['created_at'])) ?>
            </div>
            <div class="text-secondary small mt-1">
                <span class="material-symbols-outlined me-1 small">schedule</span>
                <?= htmlspecialchars($timezoneLabel) ?>
            </div>
            <div class="text-secondary small mt-1">
                <span class="material-symbols-outlined me-1 small">sell</span>
                <?= htmlspecialchars($tagLabel) ?>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <button class="btn btn-link text-primary p-0 d-flex align-items-center gap-1">
                    <span class="material-symbols-outlined">favorite</span> 320
                </button>
                <a href="post_detail.php?id=<?= $row['post_id'] ?>" class="btn btn-primary rounded-pill px-4">View</a>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <a href="edit_post.php?id=<?= $row['post_id'] ?>" class="btn btn-outline-light btn-sm">Edit</a>
                <form action="capsules/delete.php" method="POST" onsubmit="return confirm('Delete this capsule?');">
                    <input type="hidden" name="post_id" value="<?= $row['post_id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endwhile; ?>
<?php endif; ?>

        </div>
      </div>
    </main>
  </div>
  <footer class="text-center py-3 mt-auto">
    <?php include_once "../include/footer.php"; ?>
  </footer>
  <!-- Bootstrap JS -->
  <script 
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
  
</script>
<script>
document.querySelectorAll('.countdown').forEach(function(el) {
    let unlockTime = new Date(el.dataset.unlock).getTime();

    let timer = setInterval(function() {
        let now = new Date().getTime();
        let distance = unlockTime - now;

        if (distance < 0) {
            el.innerHTML = "Unlocked 🎉";
            el.classList.add("text-success", "fw-bold");
            clearInterval(timer);
            return;
        }

        let days = Math.floor(distance / (1000 * 60 * 60 * 24));
        let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

        el.innerHTML = `${days}d ${hours}h ${mins}m`;
    }, 1000);
});
</script>

</body>

</html>
