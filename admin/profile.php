<?php
	require 'include/head.php';
	session('WEBSITE-LOGIN');

	$dataArrayAdmin = selectQuery("SELECT * FROM web_settings WHERE id = '1'");
	$currentData = $dataArrayAdmin['data'][0];
	$short_name = short_name($currentData['name']);

	$message = '';

	if (isset($_POST['update-admin'])) {
		$username     = cleanStr($_POST['username']);
		$old_password = cleanStr($_POST['old_password']);
		$new_password = cleanStr($_POST['new_password']);

		// Check if old password matches
		if (md5($old_password) !== $currentData['password']) {
			$message = '<div class="alert alert-danger">Old password is incorrect.</div>';
		} else {
			$updateArray = array(
				'username' => $username
			);

			// Only update password if new password is entered
			if (!empty($new_password)) {
				$updateArray['password'] = md5($new_password);
			}

			// Run update
			$update = insert_and_update_data('update', 'web_settings', $updateArray, "id = '1'");

			if ($update === true) {
				$message = '<div class="alert alert-success">Profile updated successfully.</div>';
				$dataArrayAdmin = selectQuery("SELECT * FROM web_settings WHERE id = '1'");
				$currentData = $dataArrayAdmin['data'][0];
			} else {
				$message = '<div class="alert alert-danger">Something went wrong while updating.</div>';
			}
		}
	}
?>

<div class="content-body">
	<div class="container-fluid">
		<div class="card">
			<div class="card-header">
				<h4 class="card-title">Edit Profile</h4>
			</div>
			<div class="card-body">
				<?php echo $message; ?>
				<form method="post" action="" class="row" autocomplete="off">
					<div class="col-md-6 mb-3">
						<label class="label">Username</label>
						<input type="text" name="username" value="<?php echo $currentData['username']; ?>" class="form-control" required>
					</div>
					<div class="col-md-6 mb-3">
						<label class="label">Old Password <span class="text-danger">*</span></label>
						<input type="password" name="old_password" class="form-control" placeholder="Enter old password" required>
					</div>
					<div class="col-md-6 mb-3">
						<label class="label">New Password (optional)</label>
						<input type="password" name="new_password" class="form-control" placeholder="Enter new password">
					</div>
					<div class="col-md-12 text-end">
						<button type="submit" name="update-admin" class="btn btn-primary btn-sm">Save Changes</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<?php include 'include/footer.php'; ?>
