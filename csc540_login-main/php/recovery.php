<?php
//======================================================================
// PASSWORD RECOVERY HANDLER
//======================================================================
include_once(realpath(dirname(__FILE__) . '/path.php'));
include_once(realpath(dirname(__FILE__) . '/config.php'));

session_start();

/**
 * Store a flash message in the session and redirect back to the forgot page.
 */
function redirect_with_feedback(string $type, string $message): void {
    if ($type === 'error') {
        $_SESSION['recovery_error'] = $message;
    } else {
        $_SESSION['recovery_success'] = $message;
    }
    header("Location: " . BASE_URL . "/forgot_pass.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_feedback('error', 'Invalid request method.');
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    redirect_with_feedback('error', 'Please enter the email associated with your account.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_feedback('error', 'Please enter a valid email address.');
}

$findUser = $db_connection->prepare("
    SELECT user_id
    FROM users
    WHERE email = ?
    LIMIT 1
");

if (!$findUser) {
    redirect_with_feedback('error', 'Unable to start the recovery process. Please try again.');
}

$findUser->bind_param('s', $email);
$findUser->execute();
$findUser->store_result();

if ($findUser->num_rows === 0) {
    $findUser->close();
    redirect_with_feedback('error', 'No account was found with that email.');
}

$findUser->bind_result($userId);
$findUser->fetch();
$findUser->close();

try {
    $temporaryPassword = bin2hex(random_bytes(4)); // 8 hex chars
} catch (Exception $e) {
    $temporaryPassword = substr(md5(uniqid('', true)), 0, 10);
}

$passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
$updateUser = $db_connection->prepare("
    UPDATE users
    SET password_hash = ?
    WHERE user_id = ?
");

if (!$updateUser) {
    redirect_with_feedback('error', 'We could not reset the password right now. Please try later.');
}

$updateUser->bind_param('si', $passwordHash, $userId);

if ($updateUser->execute()) {
    $updateUser->close();
    redirect_with_feedback(
        'success',
        'A temporary password has been created. Use "' . $temporaryPassword . '" to log in, then update your password from your profile settings.'
    );
}

$updateUser->close();
redirect_with_feedback('error', 'We could not reset the password right now. Please try later.');
