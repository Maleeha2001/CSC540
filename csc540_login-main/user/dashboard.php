<?php
//======================================================================
// DASHBOARD PAGE
//======================================================================
  /* Quick Paths */
  include_once (realpath(dirname(__FILE__).'/php/path.php'));

  /* Page Name */
  $page_name = "dashboard";

  /* Start The Session */
  session_start(); 
  
  if (!isset($_SESSION['login_user'])) {
    header("Location: http://localhost/csc540_login-main/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Time Capsules - TimeCap</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>

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

    .card .capsule-card{
      background-color: rgba(255, 255, 255, 0.1);
      border: none;
      border-radius: 1rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .material-symbols-outlined {
      vertical-align: middle;
    }
    .tab-nav a {
      font-weight: 700;
      border-bottom: 3px solid transparent;
      color: rgba(255,255,255,0.7);
      padding: 0.75rem 0;
      display: inline-block;
      margin-right: 2rem;
      text-decoration: none;
    }

    .tab-nav a.active {
      border-color: var(--primary-color);
      color: var(--primary-color);
    }
    </style>
  </head>

  <body>
    <!-- Top Navbar -->
  <nav class="navbar navbar-dark sticky-top">
    <div class="container-fluid px-4 py-2 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center mb-4">
            <span class="material-symbols-outlined text-primary fs-2 me-2">hourglass_top</span>
            <h1 class="h5 fw-bold mb-0">TimeCap</h1>
        </div>
      <div>
        <button class="btn btn-link text-white me-2"><span class="material-symbols-outlined">notifications</span></button>
        <button class="btn btn-link text-white"><span class="material-symbols-outlined">account_circle</span></button>
      </div>
    </div>
  </nav>
    <div class="d-flex min-vh-100">
      <!-- Sidebar -->
        <?php include_once "../include/sidebar.php"; ?>

      <!-- Main -->
      <main class="flex-grow-1 d-flex flex-column">
        <!-- Header -->
        <header class="navbar sticky-top px-4 py-3">
          <div class="d-flex justify-content-between align-items-center w-100">
            <h2 class="fw-bold mb-0 text-primary">My Time Capsules</h2>
            <button class="btn btn-primary d-flex align-items-center gap-2 rounded-pill">
              <span class="material-symbols-outlined">add</span> Create New
            </button>
          </div>
        </header>

        <!-- Tabs -->
        <div class="px-4 border-bottom border-secondary tab-nav mt-3">
          <a href="#" class="active">Locked</a>
          <a href="#">Unlocked</a>
        </div>

        <!-- Controls -->
        <div class="p-4 d-flex flex-column flex-sm-row gap-3 align-items-start align-items-sm-center">
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
        </div>

        <!-- Capsule Cards -->
        <div class="container-fluid p-4">
          <div class="row g-4">
            <!-- Card 1 -->
            <div class="col-md-6 col-xl-3">
              <div class="capsule-card">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAvGM9LhPpC44cP3iYuSnrt2PxMpuc5Ihals9FP-sxBhWHiLi69-3VJoVVYYH6dTGEmwU2ZP21eAG4HkNQgsSOQ9bBNSoz4gftrjk8fXYTAUCJCFkf7aJsKbBcCKYuEONjef5iX8MQggXA73Nns2yn6J_1zdeN7pykXcGHzAjPLzXhbSedlAwD7asvhP70eYF27jRZuzLQW3uBPCC0ShFbYP3nDbK7uCLkHFjnxtTAVeIg1ya3zSUBYAWLyGqdqdnUuaobwfyD91z4" class="img-fluid rounded-top" alt="mountains"/>
                <div class="p-3">
                  <h5 class="fw-bold">Trip to the Mountains</h5>
                  <div class="d-flex align-items-center text-primary mb-2">
                    <span class="material-symbols-outlined me-1">lock_clock</span>
                    <small>Unlocks in 125d 4h 15m</small>
                  </div>
                  <div class="text-secondary small">
                    <span class="material-symbols-outlined me-1 small">visibility</span>
                    Public · Created: Aug 23, 2023
                  </div>
                </div>
              </div>
            </div>
            
          </div>
        </div>
      </main>
    </div>
    <footer class="text-center py-3 mt-auto">
    <?php include_once "../include/footer.php"; ?>
    </footer>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
