<?php
//======================================================================
// feed PAGE
//======================================================================
  /* Quick Paths */
  include_once (realpath(dirname(__FILE__).'/php/path.php'));


  /* Page Name */
  $page_name = "feed";

  /* Start The Session */
  session_start(); 
  /* Access Control: Only for logged in users with role 'user' (role_id = 2) */
  if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 2) {
    header("Location: ../index.php");
    exit();}

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TimeCap Feed</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>

  <!-- Material Icons -->
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

    .card {
      background-color: rgba(255, 255, 255, 0.1);
      border: none;
      border-radius: 1rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .material-symbols-outlined {
      vertical-align: middle;
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
    <?php include_once "../include/sidebar.php"; ?>
  <!-- Feed Section -->
  <main class="container py-4">
    <?php include_once "../include/sidebar.php"; ?>
    <h2 class="fw-bold mb-4 text-primary">Your Feed</h2>
    
    <!-- Post Card 1 -->
    <div class="card mb-4 p-4">
      <div class="d-flex align-items-center mb-3">
        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBx4LQCd_23dIxj5fQVhEAQ7pYdrqByRTn_Y-btuNgYejGguz4UF0ssd0T5LMFghT2CmK7FwfZV1hMyO9Caem_L7Vi05lPVzIrehZD3QwvrjVoidP0aM3cg0Q4jN1l8pWOO3FYN-p84diWShVJN5QCqqy7FlOzQEUcRHagwnBg-0HW8NXhtmVk01gOZ8W5qgUJxg6A6bigLHhyrX8QtBjJfx-1V0e8Qd7espa94Jr6Ic8rBf8nIOw-1ZDZEytjGgAgDADu2YF7kLbE" 
             class="rounded-circle me-3" style="width:48px;height:48px;object-fit:cover;">
        <div>
          <p class="fw-bold mb-0">Jane Doe</p>
          <small class="text-secondary">Unlocked 2h ago</small>
        </div>
      </div>
      <p>Here's a quick preview of the message that was just unlocked. It's exciting to finally see what was inside the capsule!</p>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
          <button class="btn btn-link text-white"><span class="material-symbols-outlined me-1">favorite</span>12</button>
          <button class="btn btn-link text-white"><span class="material-symbols-outlined me-1">chat_bubble</span>3</button>
          <button class="btn btn-link text-white"><span class="material-symbols-outlined me-1">share</span></button>
        </div>
        <button class="btn btn-primary rounded-pill px-4">View</button>
      </div>
    </div>

    <!-- Post Card 2 -->
    <div class="card mb-4 p-4">
      <div class="d-flex align-items-center mb-3">
        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuDf26ioQbHD9Q4j0CZhVW6igikeo-U-T1HxNA0erQj3YWSL8lv8bvRned-IlUrwrAh_C6zlRaD03bRqgn5N9Zv3ZHf8QkmWKh6unbUTQ_OpM7wsGpJIeEaO_aL67bT5XtENESkMfAzgRJOK-loBPG6AhJlOTs_C6ZXahHtG71CxHEuOWHPNJyTpm6O-CucY3vd5T-FjOMKTnp0uQDLzpTPkx3g2xXOFyobPDktKoa0YMBdIUT4J6emd2HRDf_cz_WaSytdRgoI8-uU"
             class="rounded-circle me-3" style="width:48px;height:48px;object-fit:cover;">
        <div>
          <p class="fw-bold mb-0">John Smith</p>
          <small class="text-secondary">Unlocked 5h ago</small>
        </div>
      </div>
      <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBUFFdeaz4vlJfhXTZnCtLWd1IAX0QLkmVxDBM6rkmRy6NDbnoIE0rWnUp3mqirKqpGCVGezF52xNoOOELog0aSyi13EaO4ZZPeGxh2HS0b_Rs6o92wlo7piVG1yHs5K4Fj0OS1sGbwOKEdCJdJWPEvdReq8501K5ljY_zFNHlpWptp2dFs5fuvqik_hUXR8rPiYi0Bp7X0Oc3SmqMt3yIokgr75UerAvFtAZoZTiAwm9x7UiTjYpC7CxAbTB4qMkKu7cL5oRj02CU"
           class="rounded mb-3 w-100" alt="Watch">
      <p>My favorite watch. Left this for myself a year ago to see if it's still my daily driver. It is.</p>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
          <button class="btn btn-link text-white"><span class="material-symbols-outlined me-1">favorite</span>28</button>
          <button class="btn btn-link text-white"><span class="material-symbols-outlined me-1">chat_bubble</span>7</button>
          <button class="btn btn-link text-white"><span class="material-symbols-outlined me-1">share</span></button>
        </div>
        <button class="btn btn-primary rounded-pill px-4">View</button>
      </div>
    </div>
  </main>

  <!-- Floating Action Button -->
  <button class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-4 shadow-lg d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
    <span class="material-symbols-outlined">add</span>
  </button>
     <!-- Footer -->
  <footer class="text-center py-3 mt-auto">
    <?php include_once "../include/footer.php"; ?>
  </footer>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </div>
</body>
</html>
