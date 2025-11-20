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

include '../../includes/adminHeader.php';
include '../../includes/config.php';
include '../../includes/alert.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
  $_SESSION['flash'] = "Invalid tag ID.";
  header("Location: index.php");
  exit;
}

$tag_id = intval($_GET['id']);

$stmt = mysqli_prepare($conn, "SELECT tag_id, name FROM tags WHERE tag_id = ?");
mysqli_stmt_bind_param($stmt, "i", $tag_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tag = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$tag) {
  $_SESSION['flash'] = "Tag not found.";
  header("Location: index.php");
  exit;
}
?>

<div class="container my-5">
  <div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card shadow-sm">
    <div class="card-body">
      <h4 class="card-title mb-4">
      <i class="bi bi-bookmarks me-2 text-dark"></i>Edit Tag
      </h4>
      <form action="update.php" method="POST">
        <input type="hidden" name="tag_id" value="<?=htmlspecialchars($tag['tag_id'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
          <label for="name" class="form-label">Tag Name</label>
          <small class="text-danger"><?php if (isset($_SESSION['nameError'])) { echo $_SESSION['nameError']; unset($_SESSION['nameError']); } ?></small>
          <input type="text" class="form-control" id="name" name="name" value="<?=htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="d-flex justify-content-between">
          <button type="submit" class="btn btn-primary" name="submit" value="submit">
          <i class="bi bi-check-circle me-1"></i>Update
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

<?php include '../../includes/footer.php'; ?>red>