<?php
//======================================================================
// CREATE CAPSULE PAGE
//======================================================================
include_once(realpath(dirname(__FILE__) . '/php/path.php'));

$page_name = "create_post";

include_once "../php/session.php";

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create TimeCap</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">

    <!-- Icons -->
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
        }

        [data-bs-theme="dark"] body {
            background-color: var(--background-dark);
            color: var(--text-light);
        }

        .material-symbols-outlined {
            vertical-align: middle;
        }

        .form-control {
            background-color: #192b33;
            border: 1px solid #325567;
            color: white;
        }

        .form-control:focus {
            background-color: #1b3b49;
            border-color: var(--primary-color);
            color: white;
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

     <!-- NAVBAR -->
  <?php include_once "../include/Navbar.php"; ?>

  <div class="d-flex min-vh-100">

    <!-- SIDEBAR -->
    <?php include_once "../include/sidebar.php"; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 p-4 d-flex flex-column">
            <!-- Header -->
      <header class="navbar sticky-top px-4 py-3">
        <div class="d-flex justify-content-between align-items-center w-100">
          
          <h2 class="fw-bold mb-0 text-primary">Create a New TimeCap</h2>
        </div>
      </header>

<?php
// Fetch timezones for dropdown
$tz_query = $db_connection->query("SELECT timezone_id, tz_name, utc_offset FROM timezones ORDER BY tz_name ASC");
?>

            <form action="./capsules/create.php" method="POST" enctype="multipart/form-data">
<div class="mb-3">
    <label for="timezone_id" class="form-label">Timezone</label>
    <select name="timezone_id" id="timezone_id" class="form-control" required>
        <?php while ($row = $tz_query->fetch_assoc()): ?>
            <option value="<?= $row['timezone_id']; ?>">
                <?= $row['tz_name']; ?> (UTC<?= $row['utc_offset']; ?>)
            </option>
        <?php endwhile; ?>
    </select>
</div>

                <div class="row g-4">

                    <!-- LEFT SIDE -->
                    <div class="col-lg-8">

                        <div class="card-section mb-4">
                            <label class="form-label fw-bold">Post Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="card-section mb-4">
                            <label class="form-label fw-bold">Message / Description</label>
                            <textarea name="message" class="form-control" rows="6"></textarea>
                        </div>

                        <div class="card-section mb-4">
                            <label class="form-label fw-bold">Tags</label>
                            <input type="text" name="tags" class="form-control">
                        </div>

                    </div>

                    <!-- RIGHT SIDE -->
                    <div class="col-lg-4">

                        <div class="card-section mb-4 text-center">
                            <h5 class="fw-bold mb-2">Add Photos or Videos</h5>
                            <label class="w-100 p-4 border border-secondary border-dashed rounded text-center" style="cursor:pointer;">
                                <span class="material-symbols-outlined text-primary fs-1">upload_file </span>
                                <p class="mt-2">Click to upload file size 5 MB</p>
                                <input type="file" name="media" accept="image/*,video/*" class="d-none">
                            </label>
                        </div>

                        <div class="card-section mb-4">
                            <h5 class="fw-bold mb-3">Set Unlock Date</h5>

                            <label class="form-label">Date</label>
                            <input type="text" id="unlock_date" name="unlock_date" class="form-control mb-3" required>

                            <label class="form-label">Time</label>
                            <input type="text" id="unlock_time" name="unlock_time" class="form-control" required>

                        </div>

                    </div>

                </div>
                <!-- ACTION BUTTONS -->
                <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top border-secondary">

                    <a href="dashboard.php" class="btn btn-outline-light px-4">Cancel</a>

                    <button type="submit" name="submit" class="btn btn-primary px-5">
                        Seal TimeCap
                    </button>

                </div>

            </form>



        </main>

    </div>

    <footer class="text-center py-3 mt-auto">
        <?php include_once "../include/footer.php"; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    // Date Picker (Shows MM/DD/YYYY but submits YYYY-MM-DD)
    flatpickr("#unlock_date", {
        altInput: true,
        altFormat: "m/d/Y",     // User sees (12/03/2025)
        dateFormat: "Y-m-d",    // PHP/MySQL receives (2025-12-03)
        minDate: "today",
    });

    // Time Picker
    flatpickr("#unlock_time", {
        enableTime: true,
        noCalendar: true,
        altInput: true,
        altFormat: "h:i K",     // Shows user-friendly 08:30 PM
        dateFormat: "H:i:S",    // Submits 20:30:00 to MySQL
        time_24hr: false,       // Change to true if you prefer 24hr format
    });
</script>

</body>

</html>