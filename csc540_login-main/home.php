<?php
//======================================================================
// LOGIN PAGE
//======================================================================
include_once (realpath(dirname(__FILE__).'/php/path.php'));
$page_name = "login";
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include_once "./include/head.php"; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TimeCap - Log In</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #101c22;
      color: #fff;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .login-card {
      background-color: #192b33;
      border: 1px solid rgba(50, 85, 103, 0.5);
      border-radius: 1rem;
      padding: 2.5rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    }
    .btn-primary {
      background-color: #13a4ec !important;
      border: none;
      font-weight: 600;
    }
    .btn-primary:hover {
      background-color: #0f8fcc !important;
    }
    a {
      color: #13a4ec;
      text-decoration: underline;
    }
    a:hover {
      color: #0f8fcc;
    }
  </style>
</head>

<body>
  <div class="login-card text-center">
    <!-- Logo -->
    <div class="mb-4 d-flex justify-content-center align-items-center gap-2">
      <span class="material-symbols-outlined text-primary fs-1">hourglass_top</span>
      <h1 class="fw-bold fs-3">TimeCap</h1>
    </div>

    <!-- Heading -->
    <h2 class="fw-bold mb-3">Welcome Back</h2>
    <p class="text-secondary mb-4">Log in to access your time capsules</p>

    <!-- Login Form -->
    <form action="./php/authenticate.php" method="post">
      <div class="mb-3">
        <input type="text" class="form-control bg-light text-black border-secondary" placeholder="Username" name="username" required>
      </div>
      <div class="mb-4">
        <input type="password" class="form-control bg-light text-black border-secondary" placeholder="Password" name="password" required>
      </div>

      <?php include_once (ROOT_PATH . '/db/error_rprt.php'); ?>

      <button type="submit" name="submit" class="btn btn-primary w-100">Log In</button>
    </form>

    <!-- Links -->
    <div class="mt-4">
      <a href="./create_account.php" class="small">Create an Account</a>
      <span class="mx-2 text-secondary">|</span>
      <a href="./forgot_pass.php" class="small">Forgot Password?</a>
    </div>
  </div>

  <?php include_once "./include/footer.php"; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
