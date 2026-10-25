<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');    

    if(isset($_POST['addpost'])){
        
        $title          = cleanStr($_POST['title']);
        $description    = cleanStr($_POST['description']);
        $slug           = cleanStr($_POST['slug']);
        $seotitle       = cleanStr($_POST['seotitle']);
        $seodes         = cleanStr($_POST['seodes']);
        $tags           = cleanStr($_POST['tags']);
    
        if(empty($title))
            $error[] = "Please type post Title";
        
        if(empty($description))
            $error[] = "Please type post Description";
        
        if(empty($error)){

            $uploadPath = 'images/blogs/';
            $allowedExts = array('png', 'jpg', 'jpeg','webp');
        
            if (!empty($_FILES['image']['tmp_name'])) {
                $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
                if (!in_array($fileExt, $allowedExts)) {
                    $error[] = "File type not allowed. Only PNG, JPG, and JPEG are allowed.";
                } else {
                    $filename = uniqid() . '.' . $fileExt;
                    $destination_img = $uploadPath . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination_img)) {
                        $insert = insert_and_update_data('insert', 'blog', array(
                            'slug'=>$slug,
                            'title'=> $title,
                            'description'=> $description , 
                            'seotitle' => $seotitle,
                            'seodes' => $seodes,
                            'tags' => $tags,
                            'image'=> $filename
                        ));
                        if($insert == true){
                            $error[] = 'Blog added successfully';
                        }
                    }
                }
            }
        }
    }
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Add Blog</h4>
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
                        <div class="col-md-6 mb-4">
                            <label class="label">Title</label>
                            <input type="text" name="title" id="title" class="form-control">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="label">Slug</label>
                            <input type="text" name="slug" id="slug" class="form-control">
                        </div>
                        <div class="col-md-12 mb-4">
                            <textarea name="description" id="editor" placeholder="Description" class="form-control"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="label">Image</label>
                            <input type="file" id="imgInp" name="image" class="form-control bg-white">
                            <img id="blah" width="10%">
                        </div>
                        <div class="col-md-12 mb-4">
                            <label for="">Seo Title</label>
                            <input type="text" class="form-control" name="seotitle">
                        </div>
                        <div class="col-md-12 mb-4">
                            <label for="">Seo Description</label>
                            <textarea name="seodes" placeholder="Seo Description" class="form-control" required></textarea>
                        </div>
                        <div class="col-md-12 mb-4">
                            <label for="">Tags</label>
                            <input type="text" class="form-control" name="tags">
                        </div>
                        <div class="col-12 text-end">
                            <input type="submit" value="Add Blog" name="addpost" class="btn btn-sm btn-primary px-3">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.ckeditor.com/4.19.0/standard/ckeditor.js"></script>
<script>
	CKEDITOR.replace('editor');

	// Preview single image (for main image)
	const imgInp = document.getElementById('imgInp');
	const blah = document.getElementById('blah');

	imgInp.addEventListener('change', function(event) {
		const file = event.target.files[0];
		if (file) {
			blah.src = URL.createObjectURL(file);
			blah.style.width = '100px';
			blah.style.height = 'auto';
		}
	});


	function generateSlug(value) {
		// Replace spaces with hyphens and convert to lowercase
		return value.trim().toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
	}

	document.getElementById('title').addEventListener('input', function() {
		const slugField = document.getElementById('slug');
		slugField.value = generateSlug(this.value);
	});
</script>
<?php
  include 'include/footer.php';
?>