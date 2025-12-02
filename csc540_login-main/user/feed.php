<?php
//======================================================================
// feed PAGE
//======================================================================
/* Quick Paths */
include_once(realpath(dirname(__FILE__) . '/php/path.php'));

/* Page Name */
$page_name = "feed";

include_once "../php/session.php";
include_once "../php/post_interactions.php";

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 2) {
  header("Location: ../index.php");
  exit();
}

ensure_reactions_table($db_connection);
ensure_comments_table($db_connection);
ensure_followers_table($db_connection);

$viewer_id = (int)$_SESSION['user_id'];
$followColumns = get_followers_column_info($db_connection);
$followTable = $followColumns['table'] ?? null;
$followFollowerColumn = null;
$followFolloweeColumn = null;
if ($followTable && !empty($followColumns['follower_column']) && !empty($followColumns['followee_column'])) {
    $followFollowerColumn = $followColumns['follower_column'];
    $followFolloweeColumn = $followColumns['followee_column'];
}

$reactionColumns = get_reactions_column_info($db_connection);
$reactionPostColumn = $reactionColumns['post_column'] ? "`{$reactionColumns['post_column']}`" : null;
$reactionUserColumn = $reactionColumns['user_column'] ? "`{$reactionColumns['user_column']}`" : null;
$reactionTypeColumn = $reactionColumns['type_column'] ? "`{$reactionColumns['type_column']}`" : null;

$likeJoinSql = '';
$likeCountSelect = '0';
if ($reactionPostColumn) {
    $typeFilter = $reactionTypeColumn ? "WHERE {$reactionTypeColumn} = 'like'" : '';
    $likeJoinSql = "
    LEFT JOIN (
        SELECT {$reactionPostColumn} AS rel_post_id, COUNT(*) AS like_count
        FROM reactions
        {$typeFilter}
        GROUP BY {$reactionPostColumn}
    ) likes ON likes.rel_post_id = p.post_id
    ";
    $likeCountSelect = "COALESCE(likes.like_count, 0)";
}

$userLikedSql = "0 AS user_liked";
$postSqlParamTypes = '';
$postSqlParams = [];
if ($reactionPostColumn && $reactionUserColumn) {
    $typeCondition = $reactionTypeColumn ? "AND r2.{$reactionTypeColumn} = 'like'" : '';
    $userLikedSql = "
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM reactions r2
                WHERE r2.{$reactionPostColumn} = p.post_id
                  AND r2.{$reactionUserColumn} = ?
                  {$typeCondition}
            ) THEN 1 ELSE 0
        END AS user_liked
    ";
    $postSqlParamTypes .= 'i';
    $postSqlParams[] = $viewer_id;
}

$publicCondition = "(p.privacy = 'public' AND p.unlock_at <= NOW())";
$followWhereClause = '';
if ($followTable && !empty($followFollowerColumn) && !empty($followFolloweeColumn) && $viewer_id > 0) {
    $followTableSql = "`{$followTable}`";
    $followFollowerExpr = "f.`{$followFollowerColumn}`";
    $followFolloweeExpr = "f.`{$followFolloweeColumn}`";
    $followWhereClause = "(p.unlock_at <= NOW() AND EXISTS (
        SELECT 1 FROM {$followTableSql} f
        WHERE {$followFollowerExpr} = ?
          AND {$followFolloweeExpr} = p.user_id
    ))";
    $postSqlParamTypes .= 'i';
    $postSqlParams[] = $viewer_id;
}
$combinedWhere = $publicCondition;
if ($followWhereClause !== '') {
    $combinedWhere = "({$publicCondition} OR {$followWhereClause})";
}

function format_feed_timestamp($dateString) {
    try {
        $target = new DateTime($dateString);
    } catch (Exception $e) {
        return '';
    }
    $now = new DateTime();
    $diff = $now->diff($target);
    $units = [
        'y' => 'yr',
        'm' => 'mo',
        'd' => 'd',
        'h' => 'h',
        'i' => 'm'
    ];

    foreach ($units as $property => $suffix) {
        if ($diff->$property > 0) {
            $value = $diff->$property;
            return ($now >= $target)
                ? "Unlocked {$value}{$suffix} ago"
                : "Unlocks in {$value}{$suffix}";
        }
    }
    return ($now >= $target) ? 'Unlocked moments ago' : 'Unlocks soon';
}

$post_sql = "
    SELECT 
        p.post_id,
        p.user_id,
        p.title,
        p.caption,
        p.media_path,
        p.has_media,
        p.unlock_at,
        p.created_at,
        u.first_name,
        u.last_name,
        u.username,
        {$likeCountSelect} AS like_count,
        COALESCE(c.comment_count, 0) AS comment_count,
        {$userLikedSql}
    FROM posts p
    INNER JOIN users u ON p.user_id = u.user_id
    {$likeJoinSql}
    LEFT JOIN (
        SELECT post_id, COUNT(*) AS comment_count
        FROM comments
        GROUP BY post_id
    ) c ON c.post_id = p.post_id
    WHERE {$combinedWhere}
    ORDER BY p.unlock_at DESC, p.created_at DESC
    LIMIT 25
";

$post_stmt = $db_connection->prepare($post_sql);
$posts = [];
if ($post_stmt) {
    if ($postSqlParamTypes !== '') {
        $bindArgs = array_merge([$postSqlParamTypes], $postSqlParams);
        foreach ($bindArgs as $idx => $value) {
            $bindArgs[$idx] = &$bindArgs[$idx];
        }
        call_user_func_array([$post_stmt, 'bind_param'], $bindArgs);
    }
    $post_stmt->execute();
    $result = $post_stmt->get_result();
    while ($result && ($row = $result->fetch_assoc())) {
        $posts[] = $row;
    }
    $post_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TimeCap Feed</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet" />

  <!-- Material Icons -->
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

    .card {
      background-color: rgba(255, 255, 255, 0.1);
      border: none;
      border-radius: 1rem;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .material-symbols-outlined {
      vertical-align: middle;
    }

    .like-toggle.liked .material-symbols-outlined {
      color: #ff5a8d;
      font-variation-settings: 'FILL' 1;
    }

    .comment-panel {
      background-color: rgba(0, 0, 0, 0.25);
      border-radius: 1rem;
      border: 1px solid rgba(255, 255, 255, 0.08);
      padding: 1rem;
    }

    .comments-list .comment-item + .comment-item {
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      margin-top: 0.5rem;
      padding-top: 0.5rem;
    }

    .comment-item .author {
      font-weight: 600;
    }

    .search-card {
      background-color: rgba(0, 0, 0, 0.25);
      border-radius: 1rem;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .search-results {
      min-height: 48px;
    }

    .search-result-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .search-result-item:first-child {
      border-top: none;
    }
  </style>
</head>

<body>
  <!-- NAVBAR -->
  <?php include_once "../include/Navbar.php"; ?>

  <div class="d-flex min-vh-100">
    <?php include_once "../include/sidebar.php"; ?>
    <!-- Feed Section -->
    <main class="flex-grow-1 container py-4" id="feed">
      <!-- Header -->
      <header class="navbar sticky-top px-4 py-3 mb-4">
        <div class="d-flex justify-content-between align-items-center w-100">
          <h2 class="fw-bold mb-0 text-primary">Your Feed</h2>
        </div>
      </header>

      <section class="card mb-4 p-4 search-card">
        <h4 class="fw-bold mb-3">Find creators</h4>
        <p class="text-secondary small mb-3">Search by username to follow new friends. Following someone shows their info on your dashboard.</p>
        <input type="text" id="userSearchInput" class="form-control mb-3" placeholder="Search by username..." autocomplete="off">
        <div id="userSearchResults" class="search-results text-secondary small">
          Start typing to search for people.
        </div>
      </section>

      <?php if (empty($posts)): ?>
        <div class="card mb-4 p-4 text-center text-secondary">
          No unlocked capsules are available yet. Check back soon!
        </div>
      <?php else: ?>
        <?php foreach ($posts as $post):
            $authorName = trim(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? ''));
            $authorHandle = !empty($post['username']) ? '@' . $post['username'] : '';
            $avatar = 'https://ui-avatars.com/api/?background=0D8ABC&color=fff&name=' . urlencode($authorName ?: $authorHandle ?: 'User');
            $relativeTime = format_feed_timestamp($post['unlock_at']);
            $mediaPath = !empty($post['media_path']) ? BASE_URL . '/' . ltrim($post['media_path'], '/') : '';
            $metaLine = implode(' · ', array_filter([$authorHandle, $relativeTime]));
        ?>
        <div class="card mb-4 p-4 post-card" data-post-id="<?= $post['post_id']; ?>">
          <a href="profile_view.php?id=<?= $post['user_id']; ?>" class="d-flex align-items-center mb-3 text-decoration-none text-white">
            <img src="<?= htmlspecialchars($avatar); ?>" class="rounded-circle me-3" style="width:48px;height:48px;object-fit:cover;" alt="author avatar">
            <div>
              <p class="fw-bold mb-0 text-white"><?= htmlspecialchars($authorName ?: 'TimeCap User'); ?></p>
              <small class="text-secondary"><?= htmlspecialchars($metaLine ?: $relativeTime); ?></small>
            </div>
          </a>
          <?php if (!empty($mediaPath)): ?>
            <?php if (!empty($post['has_media']) && preg_match('/\.mp4$/i', $post['media_path'])): ?>
              <video class="rounded mb-3 w-100" controls preload="metadata">
                <source src="<?= htmlspecialchars($mediaPath); ?>" type="video/mp4">
              </video>
            <?php else: ?>
              <img src="<?= htmlspecialchars($mediaPath); ?>" class="rounded mb-3 w-100" alt="Capsule media">
            <?php endif; ?>
          <?php endif; ?>
          <h4 class="fw-bold"><?= htmlspecialchars($post['title']); ?></h4>
          <p><?= nl2br(htmlspecialchars($post['caption'])); ?></p>
          <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-3">
            <div class="d-flex gap-3">
              <button type="button" class="btn btn-link text-white like-toggle <?= $post['user_liked'] ? 'liked' : ''; ?>" data-post-id="<?= $post['post_id']; ?>" data-liked="<?= $post['user_liked']; ?>">
                <span class="material-symbols-outlined me-1">favorite</span>
                <span class="like-count" data-like-count data-count-for="<?= $post['post_id']; ?>"><?= $post['like_count']; ?></span>
              </button>
              <button type="button" class="btn btn-link text-white comment-toggle" data-post-id="<?= $post['post_id']; ?>">
                <span class="material-symbols-outlined me-1">chat_bubble</span>
                <span class="comment-count" data-comment-count data-count-for="<?= $post['post_id']; ?>"><?= $post['comment_count']; ?></span>
              </button>
            </div>
            <a href="post_detail.php?id=<?= $post['post_id']; ?>" class="btn btn-primary rounded-pill px-4">View</a>
          </div>
          <div class="comment-panel d-none mt-3" data-comment-panel="<?= $post['post_id']; ?>">
            <div class="comments-list small mb-3" data-comments-list="<?= $post['post_id']; ?>">
              <div class="text-secondary">No comments yet.</div>
            </div>
            <form class="comment-form d-flex gap-2" data-post-id="<?= $post['post_id']; ?>">
              <input type="text" name="comment" class="form-control" placeholder="Add a comment..." maxlength="500" required>
              <button type="submit" class="btn btn-primary">Send</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>

  <!-- Floating Action Button -->
  <button class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-4 shadow-lg d-flex align-items-center justify-content-center"
    style="width:56px;height:56px;" onclick="location.href='create_post.php'">
    <span class="material-symbols-outlined">add</span>
  </button>
  <!-- Footer -->
  <footer class="text-center py-3 mt-auto">
    <?php include_once "../include/footer.php"; ?>
  </footer>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const feed = document.getElementById('feed');
      const searchInput = document.getElementById('userSearchInput');
      const searchResults = document.getElementById('userSearchResults');
      let searchTimer = null;

      const updateCountDisplays = (selector, postId, newValue) => {
        document.querySelectorAll(`${selector}[data-count-for="${postId}"]`).forEach((el) => {
          el.textContent = newValue;
        });
      };

      const renderComments = (listEl, comments) => {
        if (!listEl) {
          return;
        }
        if (!comments || comments.length === 0) {
          listEl.innerHTML = '<div class="text-secondary">No comments yet.</div>';
          return;
        }
        listEl.innerHTML = comments.map((comment) => {
          const author = [comment.first_name, comment.last_name].filter(Boolean).join(' ') || comment.username || 'User';
          const when = new Date(comment.created_at).toLocaleString();
          return `
            <div class="comment-item">
              <div class="author text-primary small">${escapeHtml(author)}</div>
              <div>${escapeHtml(comment.comment_text)}</div>
              <div class="text-secondary small">${when}</div>
            </div>
          `;
        }).join('');
      };

      const fetchComments = async (postId, listEl) => {
        try {
          const response = await fetch(`api/get_comments.php?post_id=${encodeURIComponent(postId)}`);
          const data = await response.json();
          if (data.success) {
            renderComments(listEl, data.comments);
            updateCountDisplays('[data-comment-count]', postId, data.comment_count);
          } else {
            listEl.innerHTML = `<div class="text-danger small">${data.message || 'Unable to load comments.'}</div>`;
          }
        } catch (error) {
          listEl.innerHTML = '<div class="text-danger small">Unable to load comments.</div>';
        }
      };

      const handleLikeClick = async (button) => {
        const postId = button.dataset.postId;
        const formData = new URLSearchParams();
        formData.append('post_id', postId);
        button.disabled = true;
        try {
          const response = await fetch('api/toggle_like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
          });
          const data = await response.json();
          if (data.success) {
            button.dataset.liked = data.liked ? '1' : '0';
            button.classList.toggle('liked', data.liked);
            updateCountDisplays('[data-like-count]', postId, data.like_count);
          }
        } catch (error) {
          console.error(error);
        } finally {
          button.disabled = false;
        }
      };

      const toggleCommentsPanel = (button) => {
        const postId = button.dataset.postId;
        const panel = document.querySelector(`[data-comment-panel="${postId}"]`);
        if (!panel) {
          return;
        }
        const listEl = panel.querySelector(`[data-comments-list="${postId}"]`);
        panel.classList.toggle('d-none');
        if (!panel.classList.contains('d-none')) {
          fetchComments(postId, listEl);
        }
      };

      const handleCommentSubmit = async (form) => {
        const postId = form.dataset.postId;
        const input = form.querySelector('input[name="comment"]');
        if (!input || input.value.trim() === '') {
          return;
        }
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new URLSearchParams();
        formData.append('post_id', postId);
        formData.append('comment', input.value.trim());
        submitBtn.disabled = true;
        try {
          const response = await fetch('api/add_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
          });
          const data = await response.json();
          if (data.success) {
            input.value = '';
            const panel = document.querySelector(`[data-comment-panel="${postId}"]`);
            if (panel && !panel.classList.contains('d-none')) {
              const listEl = panel.querySelector(`[data-comments-list="${postId}"]`);
              fetchComments(postId, listEl);
            } else {
              updateCountDisplays('[data-comment-count]', postId, data.comment_count);
            }
          }
        } catch (error) {
          console.error(error);
        } finally {
          submitBtn.disabled = false;
        }
      };

      const renderUserSearchResults = (users) => {
        if (!searchResults) {
          return;
        }
        if (!users || users.length === 0) {
          searchResults.innerHTML = '<div class="text-secondary small">No users found.</div>';
          return;
        }
        searchResults.innerHTML = users.map((user) => {
          const displayName = [user.first_name, user.last_name].filter(Boolean).join(' ') || user.username || 'TimeCap user';
          const following = user.is_following ? '1' : '0';
          const buttonClass = user.is_following ? 'btn-outline-light' : 'btn-outline-primary';
          const buttonLabel = user.is_following ? 'Following' : 'Follow';
          return `
            <div class="search-result-item">
              <a href="profile_view.php?id=${user.user_id}" class="text-decoration-none text-white flex-grow-1">
                <div class="fw-semibold">${escapeHtml(displayName)}</div>
                <div class="text-secondary small">@${escapeHtml(user.username)}</div>
              </a>
              <button type="button"
                      class="btn btn-sm ${buttonClass} follow-toggle"
                      data-user-id="${user.user_id}"
                      data-following="${following}">
                ${buttonLabel}
              </button>
            </div>
          `;
        }).join('');
      };

      const performUserSearch = async (term) => {
        if (!searchResults) {
          return;
        }
        if (term.length < 2) {
          searchResults.innerHTML = '<div class="text-secondary small">Keep typing to search...</div>';
          return;
        }
        searchResults.innerHTML = '<div class="text-secondary small">Searching...</div>';
        try {
          const response = await fetch(`api/search_users.php?q=${encodeURIComponent(term)}`);
          const data = await response.json();
          if (data.success) {
            renderUserSearchResults(data.users);
          } else {
            searchResults.innerHTML = `<div class="text-danger small">${escapeHtml(data.message || 'Unable to search right now.')}</div>`;
          }
        } catch (error) {
          searchResults.innerHTML = '<div class="text-danger small">Unable to search right now.</div>';
        }
      };

      const handleFollowToggle = async (button) => {
        const userId = button.dataset.userId;
        if (!userId) {
          return;
        }
        const formData = new URLSearchParams();
        formData.append('target_user_id', userId);
        button.disabled = true;
        try {
          const response = await fetch('api/toggle_follow.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
          });
          const data = await response.json();
        if (data.success) {
            const isFollowing = data.is_following ? '1' : '0';
            button.dataset.following = isFollowing;
            button.textContent = data.is_following ? 'Following' : 'Follow';
            button.classList.toggle('btn-outline-primary', !data.is_following);
            button.classList.toggle('btn-outline-light', !!data.is_following);
            if (data.is_following) {
              window.location.reload();
            }
        } else if (data.message) {
            alert(data.message);
        }
        } catch (error) {
          console.error(error);
        } finally {
          button.disabled = false;
        }
      };

      feed?.addEventListener('click', (event) => {
        const likeBtn = event.target.closest('.like-toggle');
        if (likeBtn) {
          event.preventDefault();
          handleLikeClick(likeBtn);
          return;
        }

        const commentToggle = event.target.closest('.comment-toggle');
        if (commentToggle) {
          event.preventDefault();
          toggleCommentsPanel(commentToggle);
          return;
        }

        const followBtn = event.target.closest('.follow-toggle');
        if (followBtn) {
          event.preventDefault();
          handleFollowToggle(followBtn);
        }
      });

      document.querySelectorAll('.comment-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
          event.preventDefault();
          handleCommentSubmit(form);
        });
      });

      if (searchInput) {
        searchInput.addEventListener('input', () => {
          const term = searchInput.value.trim();
          clearTimeout(searchTimer);
          if (term === '') {
            if (searchResults) {
              searchResults.innerHTML = '<div class="text-secondary small">Start typing to search for people.</div>';
            }
            return;
          }
          if (term.length < 2) {
            if (searchResults) {
              searchResults.innerHTML = '<div class="text-secondary small">Keep typing to search...</div>';
            }
            return;
          }
          searchTimer = setTimeout(() => performUserSearch(term), 250);
        });
      }
    });

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
</body>

</html>
