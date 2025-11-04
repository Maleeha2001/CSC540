<?php

//======================================================================
// CHECK USER ROLL
//======================================================================

if($user_role == 2){
  // Everything is working
  echo '<script>console.log("Everything is Working");</script>';
} else {
  $_SESSION['message'] = "Access Denied";
  header("location: " . SRC_PATH . "/logout.php");
}

?>