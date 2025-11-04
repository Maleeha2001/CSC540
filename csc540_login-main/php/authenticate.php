<?php
//======================================================================
// USER AUTHENTICATE
//======================================================================

include_once (realpath(dirname(__FILE__).'/path.php'));
include_once (realpath(dirname(__FILE__).'/config.php'));


//-----------------------------------------------------
// Authenticate
//-----------------------------------------------------
if (isset($_POST['submit'])) {
  if (empty($_POST['username']) || empty($_POST['password'])) {
    $error = "Username or Password is empty!";
    return $error;
  }
} else {
/* Check the Username and Password */
  if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = $_POST['username'];
    $pass = crypt($_POST["password"], SALT );
    $user_roll = 0;
  
    // Protect against MYSQL injection
    $username = stripslashes($username);
    $pass = stripslashes($pass);
    $username = mysqli_real_escape_string($db_connection, $username);
    $pass = mysqli_real_escape_string($db_connection, $pass);

    // SQL query to fetch information and find match user
    $select_users = $db_connection->prepare(
      //"SELECT user_id, username, role_id FROM user WHERE username = ? AND password = ? LIMIT 1");
        "SELECT u.user_id, u.role_id, cr.username
         FROM Users u
         JOIN Credentials cr ON u.user_id = cr.user_id
         WHERE cr.username = ? AND cr.password_salted = ? LIMIT 1");
    $select_users->bind_param("ss", $username, $pass);
    $select_users->execute();
    $select_users->bind_result($user_id, $user_role, $username);
    $select_users->store_result();
    //Checking the result of the query for a match and results only found 1 match
    if($select_users->num_rows == 1) {
      if($select_users->fetch()) {

        session_start(); 
        # This area needs to send users and admins to there own directory
        $_SESSION['user_id'] = $user_id;
        $_SESSION['login_user']=$username;
        $_SESSION['user_role'] = $user_role;


        if ($_SESSION['user_role'] == 1) {
          header("location: " . BASE_URL . "/admin");
        } elseif ($_SESSION['user_role'] == 2) {
          header("location:" . BASE_URL . "/user");
        } elseif ($_SESSION['user_role'] == 3) {
          header("location:" . BASE_URL . "/guest");
        } else {
          $_SESSION['error'] = "Login Failed";
          
        }
      }
  } else {
    $_SESSION['message'] = "Username or Password did not match!";
    header("location: " . SRC_PATH . "/logout.php"); 
    exit();
  }
  // close the mysql connection
  $select_user->close();
} else {
  header("location: " . SRC_PATH . "/logout.php"); 
}
}