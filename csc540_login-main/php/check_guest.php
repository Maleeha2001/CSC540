<?php

//======================================================================
// CHECK GUEST ROLL
//======================================================================

if($user_role == 3){
  // Everything is working
  // See Console Output
  echo '<script>console.log("Everything is Working");</script>';
} else {
  $_SESSION['message'] = "Access Denied";
  header("location: " . SRC_PATH . "/logout.php");
}

?>