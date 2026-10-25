<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');
    $blogID = cleanStr($_GET['id']);

    // Fetch current blog data
    $blogQuery = selectQuery("SELECT * FROM blog WHERE id ='$blogID' ORDER BY id DESC");
    $blogData = $blogQuery['data'][0];

    $title       = $blogData['title'];
    $description = $blogData['description'];
    $image       = $blogData['image'];
    $slug        = $blogData['slug'];
    $seotitle    = $blogData['seotitle'];
    $seodes      = $blogData['seodes'];
    $tags        = $blogData['tags'];

    if(isset($_POST['addpost'])) {
        $title        = cleanStr($_POST['title']);
        $description  = cleanStr($_POST['description']);
        $slug         = cleanStr($_POST['slug']);
        $description  = cleanStr($_POST['description']);
        $seotitle     = cleanStr($_POST['seotitle']);
        $seodes       = cleanStr($_POST['seodes']);
        $tags         = cleanStr($_POST['tags']);

        // Handle image upload
        $imageName = $image; // default to existing image
        if(!empty($_FILES['image']['name'])) {
            $imgName   = $_FILES['image']['name'];
            $imgTmp    = $_FILES['image']['tmp_name'];
            $imgExt    = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
            $newName   = uniqid() . '.' . $imgExt;
            $targetDir = "images/blogs/";
            $uploadPath = $targetDir . $newName;

            // Move the file
            if(move_uploaded_file($imgTmp, $uploadPath)) {
                $imageName = $newName;
                // Optionally delete old image
                if(file_exists($targetDir . $image)) {
                    unlink($targetDir . $image);
                }
            } else {
                $error[] = "Image upload failed == info == danger";
            }
        }

        // Update query
        if(update_data_query('blog', array(
            'title'       => $title,
            'description' => $description,
            'slug'        => $slug,
            'seotitle'    => $seotitle,
            'seodes'      => $seodes,
            'tags'        => $tags,
            'image'       => $imageName
        ), "id = '$blogID'") == true) {
        } else {
            $error[] = "Something went wrong == info == danger";
        }
    }
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Edit Blog</h4>
            </div>
            <div class="card-body">
                <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="label">Title</label>
                            <input type="text" name="title" value="<?php echo $title ?>" id="title" class="form-control">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="label">Slug</label>
                            <input type="text" name="slug" value="<?php echo $slug?>" id="slug" class="form-control">
                        </div>
                        <div class="col-md-12 mb-4">
                            <textarea name="description" id="editor" placeholder="Description" class="form-control"><?php echo $description?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="label">Image</label>
                            <input type="file" id="imgInp" name="image" class="form-control p-2 mb-3 bg-white">
                            <img id="blah" src="images/blogs/<?php echo $image; ?>" width="100" class="mt-2 border rounded">
                        </div>
                        <div class="col-md-12 mt-3">
                            <label for="">Seo Title</label>
                            <input type="text" class="form-control" name="seotitle" value="<?php echo $seotitle ?>">
                        </div>
                        <div class="col-md-12 mt-3">
                            <label for="">Seo Description</label>
                            <textarea name="seodes" placeholder="Seo Description" class="form-control p-2 mb-3"><?php echo $seodes ?></textarea>
                        </div>
                        <div class="col-md-12 mb-4">
                            <label for="">Tags</label>
                            <input type="text" class="form-control" name="tags" value="<?php echo $tags ?>">
                        </div>
                        <div class="col-12 text-end">
                            <input type="submit" value="Update Blog" name="addpost" class="btn btn-sm btn-primary px-3">
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
  
  function generateSlug(value) {
        // Replace spaces with hyphens and convert to lowercase
        return value.trim().toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
    }

    document.getElementById('title').addEventListener('input', function () {
        const slugField = document.getElementById('slug');
        slugField.value = generateSlug(this.value);
    });
</script>

<?php include 'include/footer.php'; ?>
