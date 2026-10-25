<?php
	require 'include/head.php';
	session('WEBSITE-LOGIN');	
    $teamID  = cleanStr($_GET['id']);

    if(isset($_POST['addteam'])){
		$name			= cleanStr($_POST['name']);
        $profession		= cleanStr($_POST['profession']);
	
		
		if(empty($name))
			$error[] = "Please type name == info-circle == danger";

        if(empty($profession))
			$error[] = "Please type profession == info-circle == danger";   
		
		
		if(empty($error)){
			$update = insert_and_update_data('update', 'team', array('name'=> $name,'profession'=> $profession),"id = $teamID", 'image', 'images/team', 'png,jpg,jpeg');
			if($update == true){
				$error[] = 'Changes saved successfully';
			}
		}
	}

    $postQuery = selectQuery("SELECT * FROM team where id ='$teamID' order by id desc");
	
	$name	    = $postQuery['data']['0']['name'];
    $profession	= $postQuery['data']['0']['profession'];
	$image 		= $postQuery['data']['0']['image'];
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Edit Team</h4>
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
							<input type="text" name="name" value="<?php  echo $name; ?>" placeholder="Name"  class="form-control">
						</div>
						<div class="col-md-6 mb-3">
							<label class="label">Profession</label>
							<input type="text" name="profession" value="<?php  echo $profession; ?>" placeholder="Profession"  class="form-control">
						</div>
						<div class="col-md-6 mb-3">
							<label class="label">Image</label>
							<input type="file" id="imgInp" name="image" class="form-control">
							<img id="blah" src="images/team/<?php echo $image;?>" class="mt-3 rounded" width="60px">		
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
<?php
  include 'include/footer.php';
?>