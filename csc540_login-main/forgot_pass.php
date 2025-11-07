<?php
//======================================================================
// FORGOT PASSWORD PAGE
//======================================================================

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include_once "./include/head.php"; ?>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password - TimeCap</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

  <style>
    body {
      background-color: #0d1b22;
      color: white;
      font-family: "Plus Jakarta Sans", sans-serif;
    }

    .card {
      background-color: #192b33;
      border: 1px solid rgba(50, 85, 103, 0.5);
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }

    h1, h2, h5 {
      font-weight: 700;
    }

    .material-symbols-outlined {
      color: #13a4ec;
      font-size: 36px;
      margin-bottom: 0.5rem;
    }

    .btn-primary {
      background-color: #13a4ec;
      border-color: #13a4ec;
      font-weight: 600;
    }

    .btn-primary:hover {
      opacity: 0.9;
      background-color: #13a4ec;
      border-color: #13a4ec;
    }

    .form-control {
      background-color: #0d1b22;
      color: white;
      border: 1px solid rgba(146,183,201,0.3);
    }

    .form-control::placeholder {
      color: #92b7c9;
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
  <div class="container d-flex flex-column min-vh-100 justify-content-center align-items-center py-5">

    <!-- Header Icon + Title -->
    <div class="text-center mb-4">
      <span class="material-symbols-outlined">lock_reset</span>
      <h2 class="fw-bold">Account Recovery</h2>
      <p class="text-secondary">Enter your email address to reset your password</p>
    </div>

    <!-- Recovery Form Card -->
    <div class="card col-11 col-sm-8 col-md-5">
      <form action="./db/recovery.php" method="post">
        <div class="mb-3">
          <label for="email" class="form-label fw-semibold text-light">Email Address</label>
          <input 
            type="email" 
            class="form-control" 
            id="email" 
            placeholder="you@example.com" 
            name="email" 
            required>
        </div>

        <?php
          if (isset($error)) {
            echo '<div class="alert alert-danger mt-3" role="alert">' . $error . '</div>';
          }
        ?>

        <button type="submit" class="btn btn-primary w-100 mt-3">Next</button>
      </form>

      <hr class="border-secondary my-4" />

      <p class="text-center text-secondary mb-0">
        <small>
          <a href="./create_account.php">Create an Account</a> | 
          <a href="./index.php">Log In</a>
        </small>
      </p>
    </div>

     <!-- Footer -->
  <footer class="text-center py-3 mt-auto">
    <?php include_once "./include/footer.php"; ?>
  </footer>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
