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

        #media_dropzone.dropzone-active {
            border-color: var(--primary-color);
            background-color: rgba(19, 164, 236, 0.1);
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
                            <label id="media_dropzone" class="w-100 p-4 border border-secondary border-dashed rounded text-center" style="cursor:pointer;">
                                <span class="material-symbols-outlined text-primary fs-1">upload_file </span>
                                <p class="mt-2 mb-1">Click or drop a file (max 5 MB)</p>
                                <p class="small text-secondary mb-0" id="media_status" data-default-text="No file selected yet.">
                                    No file selected yet.
                                </p>
                                <input type="file" name="media" accept="image/*,video/*" class="d-none" id="media_input">
                            </label>
                        </div>

                        <div class="card-section mb-4">
                            <input type="hidden" name="is_sealed" id="is_sealed_field" value="1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="fw-bold mb-0">Seal this TimeCap?</h5>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="is_sealed_toggle" role="switch" checked>
                                </div>
                            </div>
                            <p class="text-secondary small mb-0">
                                Sealed capsules stay hidden until the unlock date. Unsealed capsules post immediately.
                            </p>
                        </div>

                        <div class="card-section mb-4" id="scheduleSection">
                            <h5 class="fw-bold mb-3">Set Unlock Date</h5>

                            <label class="form-label">Date</label>
                            <input type="text" id="unlock_date" name="unlock_date" class="form-control mb-3" required>

                            <label class="form-label">Time</label>
                            <input
                                type="text"
                                id="unlock_time"
                                name="unlock_time"
                                class="form-control"
                                inputmode="numeric"
                                pattern="^([01]\d|2[0-3]):[0-5]\d$"
                                maxlength="5"
                                placeholder="HH:MM"
                                required
                            >

                        </div>

                    </div>

                </div>
                <!-- ACTION BUTTONS -->
                <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top border-secondary">

                    <a href="dashboard.php" class="btn btn-outline-light px-4">Cancel</a>

                    <button type="submit" name="submit" class="btn btn-primary px-5" id="primaryActionButton">
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
    document.addEventListener('DOMContentLoaded', () => {
        const datePicker = flatpickr("#unlock_date", {
            altInput: true,
            altFormat: "m/d/Y",
            dateFormat: "Y-m-d",
            minDate: "today",
        });

        const timePicker = flatpickr("#unlock_time", {
            enableTime: true,
            noCalendar: true,
            altInput: true,
            altFormat: "h:i K",
            dateFormat: "H:i",
            time_24hr: false,
            minuteIncrement: 5,
            allowInput: false,
        });

        const sealToggle = document.getElementById('is_sealed_toggle');
        const sealField = document.getElementById('is_sealed_field');
        const scheduleSection = document.getElementById('scheduleSection');
        const dateInput = document.getElementById('unlock_date');
        const timeInput = document.getElementById('unlock_time');
        const submitButton = document.getElementById('primaryActionButton');
        const mediaInput = document.getElementById('media_input');
        const mediaStatus = document.getElementById('media_status');
        const mediaDropzone = document.getElementById('media_dropzone');

        const syncSealState = () => {
            const isSealed = sealToggle.checked;
            sealField.value = isSealed ? '1' : '0';
            scheduleSection.classList.toggle('d-none', !isSealed);
            dateInput.required = isSealed;
            timeInput.required = isSealed;
            dateInput.disabled = !isSealed;
            timeInput.disabled = !isSealed;

            if (datePicker.altInput) {
                datePicker.altInput.disabled = !isSealed;
            }
            if (timePicker.altInput) {
                timePicker.altInput.disabled = !isSealed;
            }

            if (!isSealed) {
                datePicker.clear();
                timePicker.clear();
            }

            submitButton.textContent = isSealed ? 'Seal TimeCap' : 'Post TimeCap';
        };

        const attachFileStatus = () => {
            if (!mediaInput || !mediaStatus) {
                return;
            }
            const defaultText = mediaStatus.dataset.defaultText || mediaStatus.textContent;
            const updateStatus = () => {
                if (mediaInput.files && mediaInput.files.length > 0) {
                    mediaStatus.textContent = mediaInput.files[0].name;
                    mediaStatus.classList.remove('text-secondary');
                    mediaStatus.classList.add('text-info');
                } else {
                    mediaStatus.textContent = defaultText;
                    mediaStatus.classList.add('text-secondary');
                    mediaStatus.classList.remove('text-info');
                }
            };
            mediaInput.addEventListener('change', updateStatus);
            updateStatus();
        };

        const attachDropzone = () => {
            if (!mediaDropzone || !mediaInput) {
                return;
            }

            const preventDefaults = (event) => {
                event.preventDefault();
                event.stopPropagation();
            };

            const highlight = () => mediaDropzone.classList.add('dropzone-active');
            const unhighlight = () => mediaDropzone.classList.remove('dropzone-active');

            ['dragenter', 'dragover'].forEach((eventName) => {
                mediaDropzone.addEventListener(eventName, (event) => {
                    preventDefaults(event);
                    highlight();
                });
            });

            ['dragleave', 'dragend'].forEach((eventName) => {
                mediaDropzone.addEventListener(eventName, (event) => {
                    preventDefaults(event);
                    unhighlight();
                });
            });

            mediaDropzone.addEventListener('drop', (event) => {
                preventDefaults(event);
                const droppedFiles = event.dataTransfer?.files;
                if (!droppedFiles || droppedFiles.length === 0) {
                    unhighlight();
                    return;
                }

                let assigned = false;
                if (typeof DataTransfer !== 'undefined') {
                    try {
                        const dataTransfer = new DataTransfer();
                        Array.from(droppedFiles).forEach((file) => dataTransfer.items.add(file));
                        mediaInput.files = dataTransfer.files;
                        assigned = true;
                    } catch (error) {
                        assigned = false;
                    }
                }

                if (!assigned) {
                    try {
                        mediaInput.files = droppedFiles;
                        assigned = true;
                    } catch (error) {
                        assigned = false;
                    }
                }

                if (assigned) {
                    mediaInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                unhighlight();
            });
        };

        document.addEventListener('dragover', (event) => event.preventDefault());
        document.addEventListener('drop', (event) => {
            if (!mediaDropzone || !mediaDropzone.contains(event.target)) {
                event.preventDefault();
            }
        });

        sealToggle.addEventListener('change', syncSealState);
        attachFileStatus();
        attachDropzone();
        syncSealState();
    });
</script>

</body>

</html>
