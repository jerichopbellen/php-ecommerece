<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  $_SESSION['flash'] = "Please log in to access this page.";
  header("Location: ../../user/login.php");
  exit;
}

if ($_SESSION['role'] !== 'admin') {
  echo "
  <html>
  <head>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <title>Access Denied</title>
  </head>
  <body class='bg-light'>
    <div class='container py-5'>
      <div class='alert alert-danger text-center'>
        Access denied. This page is restricted to administrators.
      </div>
    </div>
  </body>
  </html>";
  exit;
}

include '../../includes/config.php';
include '../../includes/adminHeader.php';
include '../../includes/alert.php';

// Sanitize previous input if exists
$oldName = isset($_SESSION['old_name']) ? htmlspecialchars($_SESSION['old_name'], ENT_QUOTES, 'UTF-8') : '';
unset($_SESSION['old_name']);

?>

<div class="container my-5">
  <div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card shadow-sm">
    <div class="card-body">
      <h4 class="card-title mb-4">
      <i class="bi bi-bookmarks me-2 text-dark"></i>Create Tag
      </h4>
      <form action="store.php" method="POST">
      <div class="mb-3">
        <label for="name" class="form-label">Tag Name</label>
        <small class="text-danger"><?php if (isset($_SESSION['nameError'])) { echo $_SESSION['nameError']; unset($_SESSION['nameError']); } ?></small>
        <input type="text" class="form-control" id="name" name="name" placeholder="Enter tag name" maxlength="255" value="<?php if (isset($_SESSION['tagName'])) echo $_SESSION['tagName']; ?>">
      </div>

      <div class="d-flex justify-content-between">
        <button type="submit" class="btn btn-primary" name="submit" value="submit">
        <i class="bi bi-check-circle me-1"></i>Save
        </button>
        <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
      </div>
      </form>
    </div>
    </div>
  </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>