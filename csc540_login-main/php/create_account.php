<?php
//======================================================================
// Create Account
//======================================================================

include_once (realpath(dirname(__FILE__).'/path.php'));
include_once (realpath(dirname(__FILE__).'/config.php'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect form values
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $street = $_POST['street'];
    $street_additional = $_POST['street_additional'];
    $city = $_POST['city'];
    $state_code = $_POST['state'];
    $post_code = $_POST['zip'];

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($username) || empty($password)) {
        echo "All fields are required.";
        exit();
    }

    // Check if email or username already exists
    $check_user = $db_connection->prepare("
        SELECT user_id 
        FROM users 
        WHERE email = ? OR username = ?
    ");
    $check_user->bind_param("ss", $email, $username);
    $check_user->execute();
    $check_user->store_result();

    if ($check_user->num_rows > 0) {
        echo "Email or username already exists. Please use a different one.";
        $check_user->close();
        exit();
    }
    $check_user->close();

    // Hash the password securely
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert into users table
    $insert_user = $db_connection->prepare("
        INSERT INTO users (username, email, password_hash, created_at, is_verified, status)
        VALUES (?, ?, ?, NOW(), 0, 'active')
    ");
    $insert_user->bind_param("sss", $username, $email, $password_hash);

    if ($insert_user->execute()) {

        $user_id = $insert_user->insert_id; // Get the new user ID
        $insert_user->close();

        // Insert into profiles table
        $insert_profile = $db_connection->prepare("
            INSERT INTO profiles (user_id, first_name, last_name, phone, street, street_additional, city, state_code, post_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insert_profile->bind_param(
            "issssssss",
            $user_id, $first_name, $last_name, $phone,
            $street, $street_additional, $city, $state_code, $post_code
        );

        if ($insert_profile->execute()) {
            $insert_profile->close();

            // Redirect immediately to login, optionally flagging success.
            header("Location: " . BASE_URL . "/index.php?signup=success");
            exit();
        } else {
            echo "Error creating profile: " . $db_connection->error;
        }
    } else {
        echo "Error creating user: " . $db_connection->error;
    }

    $db_connection->close();
} else {
    echo "Invalid request method.";
}

exit();
?>
