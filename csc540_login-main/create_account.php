<?php
//======================================================================
// CREATE ACCOUNT PAGE
//======================================================================
include_once(realpath(dirname(__FILE__).'/php/path.php'));

$page_name = "create_account";
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include_once "./include/head.php"; ?>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TimeCap - Create Account</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

  <style>
    body {
      background-color: #0d1b22;
      color: white;
      font-family: "Plus Jakarta Sans", sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .card {
      background-color: #192b33;
      border: 1px solid rgba(50, 85, 103, 0.5);
      border-radius: 12px;
    }

    .btn-primary {
      background-color: #13a4ec;
      border-color: #13a4ec;
    }

    .btn-primary:hover {
      background-color: #13a4ec;
      border-color: #13a4ec;
      opacity: 0.9;
    }

    a {
      color: #92b7c9;
      text-decoration: none;
    }

    a:hover {
      color: #13a4ec;
      text-decoration: underline;
    }

    .form-control {
      background-color: #0d1b22;
      color: white;
      border: 1px solid #325567;
    }

    .form-control::placeholder {
      color: #92b7c9;
    }

    .form-control:focus {
      box-shadow: none;
      border-color: #13a4ec;
    }

    .material-symbols-outlined {
      color: #13a4ec;
      font-size: 32px;
    }
  </style>
</head>

<body>
  <div class="container d-flex flex-column justify-content-center align-items-center flex-grow-1 py-5">
    <!-- Logo + Header -->
    <div class="text-center">
      <div class="d-flex justify-content-center align-items-center mb-4">
        <span class="material-symbols-outlined">hourglass_top</span>
        <h1 class="ms-2 fw-bold">TimeCap</h1>
      </div>
      <h2 class="fw-bold mb-3">Create Your Account</h2>
      <p class="text-secondary mb-4">Join us and start capturing your moments today!</p>
    </div>

    <!-- Form Card -->
    <div class="card p-4 shadow-lg" style="max-width: 480px; width: 100%;">
      <form action="./php/create_account.php" method="post">
        <div class="row">
          <div class="col-md-6 mb-3">
            <input type="text" class="form-control" name="first_name" placeholder="First Name" required>
          </div>
          <div class="col-md-6 mb-3">
            <input type="text" class="form-control" name="last_name" placeholder="Last Name" required>
          </div>
        </div>

        <div class="mb-3">
          <input type="email" class="form-control" name="email" placeholder="Email Address" required>
        </div>
        <div class="mb-3">
          <input type="tel" class="form-control" name="phone" placeholder="Phone Number" required>
        </div>
        <div class="mb-3">
          <input type="text" class="form-control" name="username" placeholder="Username" required>
        </div>
        <div class="mb-3">
          <input type="password" class="form-control" name="password" placeholder="Password" required>
        </div>
        <div class="mb-3">
          <input type="text" class="form-control" name="street" placeholder="Street Address">
        </div>
        <div class="mb-3">
          <input type="text" class="form-control" name="street_additional" placeholder="Apartment, suite, etc. (optional)">
        </div>
        <div class="mb-3">
          <input type="text" class="form-control" name="city" placeholder="City">
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <input type="text" class="form-control" name="state" placeholder="State">
          </div>
          <div class="col-md-6 mb-3">
            <input type="text" class="form-control" name="zip" placeholder="Zip Code">
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Create Account</button>
      </form>

      <hr class="border-secondary">

      <p class="text-secondary mb-4 text-center mt-3">
        Already have an account? <a href="./index.php">Log In</a>
      </p>
      <p class="text-center mt-1">
        <a href="./forgot_pass.php">Forgot Password?</a>
      </p>
    </div>
  </div>

  <!-- Footer -->
  <footer class="text-center py-3 mt-auto">
    <?php include_once "./include/footer.php"; ?>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
