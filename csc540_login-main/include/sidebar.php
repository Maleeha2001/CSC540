<!-- Sidebar -->
 <!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sidebar - TimeCap</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<style> 
        .sidebar {
        background-color: rgba(16, 28, 34, 0.85);
        backdrop-filter: blur(8px);
        min-width: 250px;
        color: var(--text-light);
        border-right: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .nav-link {
        color: rgba(255,255,255,0.75);
        font-weight: 500;
        border-radius: 0.5rem;
        }
        .sidebar .nav-link:hover {
        color: #ffffff;
        background-color: rgba(255,255,255,0.05);
        }
        .sidebar .nav-link.active {
        color: var(--primary-color);
        background-color: rgba(19,164,236,0.1);
        }
</style>
  </head>
  <body>
<!-- Sidebar -->
<aside class="sidebar d-none d-lg-flex flex-column p-3">


  <nav class="nav flex-column gap-2">
    <a href="dashboard.php" class="nav-link <?php echo ($page_name == 'profile') ? 'active' : ''; ?>">
      <span class="material-symbols-outlined me-2">inventory_2</span> Profile
    </a>
    <a href="create_capsule.php" class="nav-link <?php echo ($page_name == 'create_capsule') ? 'active' : ''; ?>">
      <span class="material-symbols-outlined me-2">add_circle</span> Create Capsule
    </a>
    <a href="feed.php" class="nav-link <?php echo ($page_name == 'feed') ? 'active' : ''; ?>">
      <span class="material-symbols-outlined me-2">explore</span> Feed
    </a>
    </a>
    <a href="settings.php" class="nav-link <?php echo ($page_name == 'settings') ? 'active' : ''; ?>">
      <span class="material-symbols-outlined me-2">settings</span> Settings
    </a>
  </nav>
</aside>

<!-- End Sidebar -->        
