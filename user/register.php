<?php
session_start();
include("../includes/header.php"); 
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="text-center mb-4"><i class="bi bi-person-plus me-2"></i>Create an Account</h4>

                    <?php include("../includes/alert.php"); ?>

                    <form action="store.php" method="POST" enctype="multipart/form-data" novalidate>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <small class="text-danger">
                                    <?php if(isset($_SESSION['firstNameError'])) { echo htmlspecialchars($_SESSION['firstNameError']); unset($_SESSION['firstNameError']); } ?>
                                </small>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                       value="<?= isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <small class="text-danger">
                                    <?php if(isset($_SESSION['lastNameError'])) { echo htmlspecialchars($_SESSION['lastNameError']); unset($_SESSION['lastNameError']); } ?>
                                </small>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                       value="<?= isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : '' ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['emailError'])) { echo htmlspecialchars($_SESSION['emailError']); unset($_SESSION['emailError']); } ?>
                            </small>
                            <input type="text" class="form-control" id="email" name="email"
                                   value="<?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['passwordError'])) { echo htmlspecialchars($_SESSION['passwordError']); unset($_SESSION['passwordError']); } ?>
                            </small>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>

                        <div class="mb-3">
                            <label for="password2" class="form-label">Confirm Password</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['confirmError'])) { echo htmlspecialchars($_SESSION['confirmError']); unset($_SESSION['confirmError']); } ?>
                            </small>
                            <input type="password" class="form-control" id="password2" name="confirmPass">
                        </div>

                        <div class="mb-3">
                            <label for="profile_photo" class="form-label">Profile Photo</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['photoError'])) { echo htmlspecialchars($_SESSION['photoError']); unset($_SESSION['photoError']); } ?>
                            </small>
                            <div class="d-flex align-items-center gap-3">
                                <div class="flex-shrink-0">
                                    <img id="photoPreview" src="avatars/default-avatar.png" alt="Preview" class="rounded-circle border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control" id="profile_photo" name="profile_photo">
                                    <small class="text-muted">Optional. JPG or PNG only.</small>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-outline-primary w-100" name="submit">
                            <i class="bi bi-person-plus-fill me-1"></i> Register
                        </button>

                        <div class="text-center mt-3">
                            <p class="mb-0">Already have an account? <a href="login.php">Login</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include("../includes/footer.php");
?>

<script>
document.getElementById('profile_photo').addEventListener('change', function (e) {
    const preview = document.getElementById('photoPreview');
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (event) {
            preview.src = event.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = 'avatars/default-avatar.png';
    }
});
</script>