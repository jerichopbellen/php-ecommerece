<?php
session_start();
include("../includes/config.php");
include("../includes/header.php");

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect'] = "You need to login to view your profile.";
    header("Location: ../user/login.php");
    exit;
}

// Fetch current profile
$userId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if (!$userId) {
    die("Invalid user ID");
}

$profile_sql = "SELECT first_name, last_name, email, img_path FROM users WHERE user_id = ?";
$profile_stmt = mysqli_prepare($conn, $profile_sql);
if (!$profile_stmt) {
    die("Profile query preparation failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($profile_stmt, 'i', $userId);
mysqli_stmt_execute($profile_stmt);
$profile_result = mysqli_stmt_get_result($profile_stmt);
$profile = mysqli_fetch_assoc($profile_result);
mysqli_stmt_close($profile_stmt);

if (!$profile) {
    die("Profile not found");
}

// Fetch saved addresses
$addresses = [];
$address_sql = "SELECT address_id, recipient, street, barangay, city, province, zipcode, country, phone FROM addresses WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $address_sql);
if (!$stmt) {
    die("Address query preparation failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $addresses[] = $row;
}
mysqli_stmt_close($stmt);
?>
<div class="container my-5">
    <?php include("../includes/alert.php"); ?>
    <div class="row g-4">
        <!-- Profile Picture -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <?php
                        $imgPath = !empty($profile['img_path']) 
                            ? htmlspecialchars($profile['img_path'], ENT_QUOTES, 'UTF-8')
                            : '/Furnitures/user/avatars/default-avatar.png';
                    ?>
                    <img src="<?= $imgPath ?>" 
                        class="rounded-circle mb-3 border shadow-sm" 
                        style="width:120px; height:120px; object-fit:cover;" 
                        alt="Profile Picture">

                    <h4><?= htmlspecialchars($profile['first_name'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($profile['last_name'], ENT_QUOTES, 'UTF-8') ?></h4>

                    <!-- Upload Form -->
                    <form method="POST" action="update_profile.php" enctype="multipart/form-data" novalidate>
                        <small class="text-danger">
                            <?php if(isset($_SESSION['imageError'])) { echo htmlspecialchars($_SESSION['imageError']); unset($_SESSION['imageError']); } ?>
                        </small>
                        <input type="file" name="avatar" class="form-control mb-2">
                        <button type="submit" name="submit_avatar" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-upload me-1"></i> Upload New Image
                        </button>
                    </form>

                    <!-- Remove Form -->
                    <form method="POST" action="update_profile.php" class="mt-2" novalidate>
                        <button type="submit" name="remove_avatar" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Remove your profile picture?');">
                            <i class="bi bi-trash me-1"></i> Remove Profile Picture
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Profile Info -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h4 class="mb-4"><i class="bi bi-person-lines-fill me-2"></i>Update Profile</h4>
                    <form method="POST" action="update_profile.php">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="fname" class="form-label">First Name</label>
                                <input type="text" name="fname" id="fname" class="form-control" value="<?= htmlspecialchars($profile['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lname" class="form-label">Last Name</label>
                                <input type="text" name="lname" id="lname" class="form-control" value="<?= htmlspecialchars($profile['last_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                        </div>
                        <button type="submit" name="submit_profile" class="btn btn-outline-primary w-100">
                            <i class="bi bi-pencil-square me-1"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4"><i class="bi bi-key me-2"></i>Change Password</h4>
                    <form method="POST" action="update_profile.php">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="submit_password" class="btn btn-outline-warning w-100">
                            <i class="bi bi-shield-lock me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Saved Addresses -->
    <div class="row mt-5">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4"><i class="bi bi-house-door me-2"></i>Saved Addresses</h4>
                    <?php if (empty($addresses)): ?>
                        <p class="text-muted">No addresses saved yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Recipient</th>
                                        <th>Street</th>
                                        <th>Barangay</th>
                                        <th>City</th>
                                        <th>Province</th>
                                        <th>Zipcode</th>
                                        <th>Country</th>
                                        <th>Phone</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($addresses as $addr): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($addr['recipient'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($addr['street'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($addr['barangay'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($addr['city'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($addr['province'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($addr['zipcode'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($addr['country'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($addr['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <a href="update_profile.php?delete_address=<?= (int)$addr['address_id'] ?>" class="text-danger" onclick="return confirm('Remove this address?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Address -->
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4"><i class="bi bi-geo-alt me-2"></i>Add New Address</h4>
                    <form method="POST" action="update_profile.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="recipient" class="form-label">Recipient Name</label>
                                <input type="text" name="recipient" id="recipient" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control" required
                                    pattern="^\d+$"
                                    title="Enter numbers only">
                            </div>
                            <div class="col-md-6">
                                <label for="street" class="form-label">Street</label>
                                <input type="text" name="street" id="street" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="barangay" class="form-label">Barangay</label>
                                <input type="text" name="barangay" id="barangay" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" name="city" id="city" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="province" class="form-label">Province</label>
                                <input type="text" name="province" id="province" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="zipcode" class="form-label">Zipcode</label>
                                <input type="text" name="zipcode" id="zipcode" class="form-control" required
                                    pattern="^\d+$"
                                    title="Enter numbers only">
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" name="country" id="country" class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" name="submit_address" class="btn btn-outline-success w-100">
                                <i class="bi bi-plus-circle me-1"></i> Add Address
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("../includes/footer.php"); ?>