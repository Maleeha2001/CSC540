<?php
//======================================================================
// USER AUTHENTICATE
//======================================================================

include_once (realpath(dirname(__FILE__) . '/path.php'));
include_once (realpath(dirname(__FILE__) . '/config.php'));

session_start(); // Ensure session is active

//-----------------------------------------------------
// Authenticate
//-----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $_SESSION['error'] = "Username or password cannot be empty.";
        header("location: " . BASE_URL . "/home.php");
        exit();
    }

    // Clean input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Protect against SQL injection
    $username = stripslashes($username);
    $username = mysqli_real_escape_string($db_connection, $username);

    // Fetch user details including role and password hash
    $check_user = $db_connection->prepare("
        SELECT user_id, username, password_hash, role_id
        FROM users
        WHERE username = ?
    ");

    // Bind parameter
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $check_user->store_result();

    // Bind results
    $check_user->bind_result($user_id, $db_username, $db_password_hash, $user_role);

    if ($check_user->num_rows === 1 && $check_user->fetch()) {

        // Verify password
        if (password_verify($password, $db_password_hash)) {

            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['login_user'] = $db_username;
            $_SESSION['user_role'] = $user_role;

            // Redirect users based on role
            if ($user_role == 1) {
                header("location: " . BASE_URL . "/admin");
            } elseif ($user_role == 2) {
                header("location: " . BASE_URL . "/user/feed.php");
            } elseif ($user_role == 3) {
                header("location: " . BASE_URL . "/guest");
            } else {
                $_SESSION['message'] = "Invalid user role!";
                $_SESSION['error'] = "Invalid user role!";
                header("location: " . BASE_URL . "/home.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid password!";
            header("location: " . BASE_URL . "/home.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Username not found!";
        header("location: " . BASE_URL . "/home.php");
        exit();
    }

    $check_user->close();
} else {
    header("location: " . BASE_URL . "/home.php");
    exit();
}
?>
