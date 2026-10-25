<?php
	require 'include/head.php';
	session('WEBSITE-LOGIN');
	
	if(isset($_POST['addteam'])){
		$name			= cleanStr($_POST['name']);
		$profession		= cleanStr($_POST['profession']);
	
		if(empty($name))
			$error[] = "Please type Name";
		if(empty($profession))
			$error[] = "Please type Profession";	
		
		if(empty($error)){
			$insert = insert_and_update_data('insert', 'team', array('name'=> $name,'profession'=> $profession),'', 'image', 'images/team', 'png,jpg,jpeg,webp');
			if($insert == true){
				$error[] = 'Team Member added successfully';
			}
		}
	}
?>
<div class="content-body">
	<div class="container-fluid">
		<div class="card">
			<div class="card-header">
				<h4 class="card-title">Add Team</h4>
			</div>
			<div class="card-body">
				<?php if (!empty($error)) : ?>
                    <?php foreach ($error as $msg): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?php echo $msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
				<form method="post" action="" enctype="multipart/form-data" autocomplete="off">
					<div class="row">
						<div class="col-md-6 mb-3">
							<label class="label">Name</label>
							<input type="text" name="name" placeholder="Name"  class="form-control">
						</div>
						<div class="col-md-6 mb-3">
							<label class="label">Profession</label>
							<input type="text" name="profession" placeholder="Profession"  class="form-control">
						</div>
						<div class="col-md-6 mb-3">
							<label class="label">Image</label>
							<input type="file" id="imgInp" name="image" class="form-control">
							<img id="blah" width="10%" class="mt-4">		
						</div>
						<div class="col-12 text-end">
							<input type="submit" value="Add Team" name="addteam" class="btn btn-sm btn-primary px-3">
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script>
	// Preview single image (for main image)
	const imgInp = document.getElementById('imgInp');
	const blah = document.getElementById('blah');

	imgInp.addEventListener('change', function(event) {
		const file = event.target.files[0];
		if (file) {
			blah.src = URL.createObjectURL(file);
			blah.style.width = '60px';
			blah.style.height = 'auto';
		}
	});
</script>
<?php
  include 'include/footer.php';
?>