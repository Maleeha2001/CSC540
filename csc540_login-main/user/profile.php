<?php
include_once(realpath(dirname(__FILE__) . '/../php/path.php'));
$page_name = "profile";

include_once "../php/session.php";
include_once "../php/user_profile_helpers.php";

function load_user_record($connection, $user_id) {
    $stmt = $connection->prepare("SELECT first_name, last_name, username, email FROM users WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $user ?: null;
}

$feedback = ['error' => ''];
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$userRecord = $user_id ? load_user_record($db_connection, $user_id) : null;

if (!$userRecord) {
    header("Location: dashboard.php");
    exit();
}

$formValues = [
    'first_name' => $userRecord['first_name'] ?? '',
    'last_name' => $userRecord['last_name'] ?? '',
    'username' => $userRecord['username'] ?? '',
    'email' => $userRecord['email'] ?? ''
];
$bioValue = get_user_bio($db_connection, $user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues['first_name'] = trim($_POST['first_name'] ?? '');
    $formValues['last_name'] = trim($_POST['last_name'] ?? '');
    $postedUsername = trim($_POST['username'] ?? '');
    $formValues['username'] = ltrim($postedUsername, '@');
    $bioValue = sanitize_bio_input($_POST['bio'] ?? '');

    if ($formValues['first_name'] === '' || $formValues['last_name'] === '' || $formValues['username'] === '') {
        $feedback['error'] = 'First name, last name, and username are required.';
    } else {
        $check_stmt = $db_connection->prepare("
            SELECT user_id FROM users
            WHERE username = ? AND user_id <> ?
            LIMIT 1
        ");
        if ($check_stmt) {
            $check_stmt->bind_param("si", $formValues['username'], $user_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $feedback['error'] = 'That username is already in use.';
            } else {
                $update_stmt = $db_connection->prepare("
                    UPDATE users
                    SET first_name = ?, last_name = ?, username = ?
                    WHERE user_id = ?
                ");
                if ($update_stmt) {
                    $update_stmt->bind_param(
                        "sssi",
                        $formValues['first_name'],
                        $formValues['last_name'],
                        $formValues['username'],
                        $user_id
                    );
                    if ($update_stmt->execute()) {
                        $bioSaved = save_user_bio($db_connection, $user_id, $bioValue);
                        if ($bioSaved) {
                            $_SESSION['user_first'] = $formValues['first_name'];
                            $_SESSION['user_name'] = $formValues['username'];
                            $_SESSION['login_user'] = $formValues['username'];
                            $_SESSION['dashboard_flash'] = 'Profile updated successfully.';
                            header("Location: dashboard.php");
                            exit();
                        }
                        $feedback['error'] = 'Profile saved but we could not update your bio.';
                    } else {
                        $feedback['error'] = 'Unable to save your profile right now. Please try again.';
                    }
                    $update_stmt->close();
                } else {
                    $feedback['error'] = 'Unable to prepare the update statement.';
                }
            }
            $check_stmt->close();
        } else {
            $feedback['error'] = 'Unable to validate the username right now.';
        }
    }
}

$bioCharCount = strlen($bioValue);
$displayName = trim(($formValues['first_name'] ?? '') . ' ' . ($formValues['last_name'] ?? ''));
$displayHandle = $formValues['username'] !== '' ? '@' . $formValues['username'] : '@' . ($_SESSION['user_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Profile - TimeCap</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
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

        .tc-card {
            background-color: #192b33;
            border: 1px solid #325567;
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .form-control,
        .form-control:focus,
        textarea {
            background-color: #192b33;
            border: 1px solid #325567;
            color: white;
        }

        .form-control:focus {
            border-color: #13a4ec;
            box-shadow: 0 0 0 0.2rem rgba(19, 164, 236, 0.25);
        }

        .profile-img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #325567;
        }
    </style>
</head>

<body>

    <?php include_once "../include/Navbar.php"; ?>

    <div class="d-flex min-vh-100">
        <?php include_once "../include/sidebar.php"; ?>
        <main class="flex-grow-1 d-flex flex-column">
            <header class="navbar sticky-top px-4 py-3">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h2 class="fw-bold mb-0 text-primary">Edit Profile</h2>
                </div>
            </header>

            <div class="tc-card mx-auto" style="max-width: 700px;">

                <div class="d-flex align-items-center gap-4 mb-4">
                    <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAhoO3Kn5s8uuPQSl0sdMUer7ggJOvCdb9BFND9ObG_d3uiWQcp_7sKRHGXVLXZRzvx7PMAKihbAr_R5BV_pvawClBLQNrmRyir4go_liiflkL4iz9lM71SbTaT6i9JYT5K3bdCgcHw4W6PD7MFlkiFZW_8I_5b6sn-TlMSR4SP0lMm5xXzjFsRjRCfTCfML_87kZsiyEaPQehKMfAmz9teB6fZ5E_7_9OEQyJ403APiD4_Wmo2xfc-zGBtjdc0OKvcDlKhjWWf3aI"
                        class="profile-img shadow"
                        id="profilePhoto"
                        style="cursor:pointer;"
                        data-bs-toggle="modal"
                        data-bs-target="#profilePhotoModal"
                        alt="profile photo">
                    <div class="modal fade" id="profilePhotoModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-transparent border-0 shadow-none">
                                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAhoO3Kn5s8uuPQSl0sdMUer7ggJOvCdb9BFND9ObG_d3uiWQcp_7sKRHGXVLXZRzvx7PMAKihbAr_R5BV_pvawClBLQNrmRyir4go_liiflkL4iz9lM71SbTaT6i9JYT5K3bdCgcHw4W6PD7MFlkiFZW_8I_5b6sn-TlMSR4SP0lMm5xXzjFsRjRCfTCfML_87kZsiyEaPQehKMfAmz9teB6fZ5E_7_9OEQyJ403APiD4_Wmo2xfc-zGBtjdc0OKvcDlKhjWWf3aI"
                                    id="modalProfilePhoto"
                                    class="img-fluid rounded-3 shadow-lg"
                                    alt="Profile Photo Preview">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($displayName !== '' ? $displayName : $_SESSION['user_first']); ?></h4>
                        <p class="text-secondary mb-2"><?= htmlspecialchars($displayHandle); ?></p>
                        <label class="text-primary small text-decoration-underline" style="cursor:pointer;">
                            Change Profile Photo
                            <input type="file" name="profile_pic" accept="image/*" class="d-none" id="profilePicInput">
                        </label>
                    </div>
                </div>

                <?php if ($feedback['error']): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($feedback['error']); ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-semibold" for="firstName">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="first_name" value="<?= htmlspecialchars($formValues['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="lastName">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="last_name" value="<?= htmlspecialchars($formValues['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="username">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary text-secondary">@</span>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($formValues['username']); ?>" required>
                        </div>
                        <small class="text-secondary">This is how others find you.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold d-flex justify-content-between" for="bio">
                            <span>Bio</span>
                            <span class="text-secondary small" id="bioCounter"><?= $bioCharCount; ?> / 150</span>
                        </label>
                        <textarea class="form-control" id="bio" name="bio" rows="4" maxlength="150"><?= htmlspecialchars($bioValue); ?></textarea>
                    </div>

                    <hr class="border-secondary mb-4">

                    <div class="mb-4">
                        <a href="settings.php" class="text-primary fw-semibold text-decoration-underline">
                            Manage Privacy &amp; Security Settings
                        </a>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-3">
                        <button type="button" class="btn btn-outline-light px-4" onclick="window.location.href='dashboard.php'">Cancel</button>
                        <button type="submit" class="btn btn-primary px-5">Save Changes</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <footer class="text-center py-3 mt-auto">
        <?php include_once "../include/footer.php"; ?>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const profileInput = document.getElementById("profilePicInput");
        if (profileInput) {
            profileInput.addEventListener("change", function() {
                const file = this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById("profilePhoto");
                    const modalPreview = document.getElementById("modalProfilePhoto");
                    if (preview) {
                        preview.src = e.target.result;
                    }
                    if (modalPreview) {
                        modalPreview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            });
        }
        const bioField = document.getElementById("bio");
        const bioCounter = document.getElementById("bioCounter");
        if (bioField && bioCounter) {
            bioField.addEventListener("input", function() {
                bioCounter.textContent = `${this.value.length} / 150`;
            });
        }
    </script>
</body>

</html>
