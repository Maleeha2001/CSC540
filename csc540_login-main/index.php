<?php
//======================================================================
// Home PAGE
//======================================================================
include_once(realpath(dirname(__FILE__).'/php/path.php'));

$page_name = "home";
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include_once "./include/head.php"; ?>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TimeCap - Welcome</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  
  <style>
    body {
      background-color: #0d1b22;
      color: white;
      font-family: "Plus Jakarta Sans", sans-serif;
    }
    .feature-box {
      background-color: #192b33;
      border: 1px solid rgba(50, 85, 103, 0.5);
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
      transition: transform 0.2s ease;
    }
    .feature-box:hover {
      transform: translateY(-5px);
    }
    .material-symbols-outlined {
      color: #13a4ec;
      font-size: 32px;
      margin-bottom: 0.5rem;
    }
    .btn-primary {
      background-color: #13a4ec;
      border-color: #13a4ec;
    }
    .btn-primary:hover {
      opacity: 0.9;
      background-color: #13a4ec;
      border-color: #13a4ec;
    }
    a {
      color: #92b7c9;
      text-decoration: none;
    }
    a:hover {
      color: #13a4ec;
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="container d-flex flex-column min-vh-100 justify-content-between align-items-center py-5">
    
    <!-- Hero Section -->
    <div class="text-center">
      <div class="d-flex justify-content-center align-items-center mb-4">
        <span class="material-symbols-outlined">hourglass_top</span>
        <h1 class="ms-2 fw-bold">TimeCap</h1>
      </div>

      <h2 class="fw-bold mb-3">Capture Today, Unbox Tomorrow</h2>
      <p class="text-secondary mx-auto mb-4" style="max-width: 420px;">
        Create digital time capsules with messages and photos. Set a future date to unlock and share them with friends — or just yourself.
      </p>

      <div class="row g-3 justify-content-center mb-5">
        <div class="col-4 col-md-3">
          <div class="feature-box">
            <span class="material-symbols-outlined">add_circle</span>
            <h6 class="fw-bold mt-2">Create</h6>
          </div>
        </div>
        <div class="col-4 col-md-3">
          <div class="feature-box">
            <span class="material-symbols-outlined">lock_clock</span>
            <h6 class="fw-bold mt-2">Lock</h6>
          </div>
        </div>
        <div class="col-4 col-md-3">
          <div class="feature-box">
            <span class="material-symbols-outlined">send</span>
            <h6 class="fw-bold mt-2">Share</h6>
          </div>
        </div>
      </div>

      <!-- CTA Buttons -->
      <div class="d-grid gap-3 col-10 col-sm-6 mx-auto">
        <a href="./create_account.php" class="btn btn-primary btn-lg">Get Started</a>
      </div>

      <div class="mt-4">
        <a href="./forgot_pass.php">Forgot Password</a>
      </div>

      <p class="text-secondary mt-3">
        Already have an account? <a href="./home.php">Log In</a>
      </p>
    </div>

    <footer class="footer bg-background-light dark:bg-background-dark font-display text-center py-3 mt-auto">
      <?php include_once "./include/footer.php"; ?>
    </footer>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
