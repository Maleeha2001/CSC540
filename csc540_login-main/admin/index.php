<?php
//======================================================================
// ADMIN DASHBOARD PAGE
//======================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // set to 1 to display errors, 0 to hide them

  /* Quick Paths */
  /* note the 2 after __FILE__, because it's 2 directories deep */
  include_once (realpath(dirname(__FILE__, 2).'/php/session.php'));
  include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));
  // Session will be included in header.php
  
  /* Check Role */
  include_once (ROOT_SRC_PATH .'/check_admin.php');

  /* Page Name */
  $page_name = "admin";

  include_once (ROOT_PATH . '/php/config.php');

  $roles = [];
  $roles_rs = $db_connection->query("SELECT role_id, role_type FROM roles ORDER BY role_id ASC");
  if ($roles_rs) {
    while ($role_row = $roles_rs->fetch_assoc()) {
      $roles[] = $role_row;
    }
  }
  $status_options = ['active' => 'Active', 'inactive' => 'Inactive', 'banned' => 'Banned'];
  $flash = $_SESSION['admin_flash'] ?? '';
  unset($_SESSION['admin_flash']);

?>
<!doctype html>
<html lang="en">
  <head>
  <?php include_once (ROOT_PATH . '/include/head.php'); ?>
  </head>
  <body class="<?php echo $page_name; ?>">

  <?php include_once (ROOT_PATH . '/include/header.php'); ?>
    <main role="main" class="container">

    <div class="container text-center">
  <div class="row align-items-start">
    
    <div class="col-md-4">
      <div class="mb-4">
      <h1>Welcome <?php echo htmlspecialchars($_SESSION['user_first']); ?></h1>
      <p class="lead">This is the <?php echo htmlspecialchars($_SESSION['user_type']); ?> dashboard.</p>
      <p>Only users with the <?php echo htmlspecialchars($_SESSION['user_type']); ?> role can access this page.</p>
      </div>
      <?php if (!empty($flash)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($flash); ?></div>
      <?php endif; ?>
      <section class="mb-4">
        <h2>Create User</h2>
        <form action="manage_user.php" method="POST">
          <input type="hidden" name="action" value="create">
          <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role_id" class="form-control" required>
              <?php foreach ($roles as $role): ?>
                <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_type']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach ($status_options as $key => $label): ?>
                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary w-100">Create User</button>
        </form>
      </section>
    </div>

    <div class="col-md-8">
      <hr class="mb-4">
      <section class="mb-4">
      <h2>All System Users</h2>
      <table class="table">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">First</th>
      <th scope="col">Last</th>
      <th scope="col">Email</th>
      <th scope="col">Username</th>
      <th scope="col">Role</th>
      <th scope="col">Status</th>
      <th scope="col">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
     $result = $db_connection->query("
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.username,
        u.status,
        r.role_type
    FROM users u
    INNER JOIN roles r 
        ON u.role_id = r.role_id
    ORDER BY u.user_id ASC
");


     if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<th scope='row'>" . $row["user_id"] . "</th>";
        echo "<td>" . htmlspecialchars($row["first_name"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["last_name"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["username"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["role_type"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["status"]) . "</td>";
        echo "<td class='d-flex gap-2'>";
        echo "<a class='btn btn-sm btn-outline-primary' href='edit_user.php?id=" . $row['user_id'] . "'>Edit</a>";
        echo "<form action='manage_user.php' method='POST' onsubmit=\"return confirm('Are you sure you want to delete this user?');\">";
        echo "<input type='hidden' name='action' value='delete'>";
        echo "<input type='hidden' name='user_id' value='" . $row['user_id'] . "'>";
        echo "<button type='submit' class='btn btn-sm btn-outline-danger'>Delete</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='8'>No users found</td></tr>";
}

$db_connection->close();

    ?>

  </tbody>
</table>
</section>
      
    </div>
    
  </div>
</div>  
       
    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
