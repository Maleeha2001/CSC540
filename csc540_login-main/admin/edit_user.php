<?php
//======================================================================
// ADMIN EDIT USER
//======================================================================
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once (realpath(dirname(__FILE__, 2).'/php/session.php'));
include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));
include_once (ROOT_SRC_PATH .'/check_admin.php');
include_once (ROOT_PATH . '/php/config.php');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    header("Location: index.php");
    exit();
}

$user_stmt = $db_connection->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user) {
    $_SESSION['admin_flash'] = "User not found.";
    header("Location: index.php");
    exit();
}

$roles = [];
$roles_rs = $db_connection->query("SELECT role_id, role_type FROM roles ORDER BY role_id ASC");
if ($roles_rs) {
    while ($role_row = $roles_rs->fetch_assoc()) {
        $roles[] = $role_row;
    }
}
$status_options = ['active' => 'Active', 'inactive' => 'Inactive', 'banned' => 'Banned'];
?>
<!doctype html>
<html lang="en">
  <head>
    <?php include_once (ROOT_PATH . '/include/head.php'); ?>
  </head>
  <body class="admin-edit">
    <?php include_once (ROOT_PATH . '/include/header.php'); ?>
    <main role="main" class="container">
      <div class="container">
        <h1>Edit User</h1>
        <p><a href="index.php">&larr; Back to Admin Dashboard</a></p>
        <form action="manage_user.php" method="POST">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
          <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password (leave blank to keep current)</label>
            <input type="password" name="password" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role_id" class="form-control" required>
              <?php foreach ($roles as $role): ?>
                <option value="<?php echo $role['role_id']; ?>" <?php echo ($role['role_id'] == $user['role_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($role['role_type']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach ($status_options as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php echo ($key == $user['status']) ? 'selected' : ''; ?>>
                  <?php echo $label; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
