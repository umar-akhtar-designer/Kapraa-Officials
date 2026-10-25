<?php
	require 'include/head.php';
	session('WEBSITE-LOGIN');	
	
	$sizeId = cleanStr($_GET['id']);
	
	$sizeQuery = selectQuery("SELECT * FROM size WHERE id ='$sizeId'");
    $sizeData  = $sizeQuery['data'][0];

    $name      = $sizeData['name'];
    
    if(isset($_POST['update'])){

        $name		  = cleanStr($_POST['name']);

        if (update_data_query('size', array(
            
            'name'=> $name,
            
        ),"id = '$sizeId'") == true) {

            $error[] = 'Save Changes Successfully';
        
        } else {
                $error[] = "Something went wrong == info == danger";
        }
            
    }
   
?>
<div class="content-body">
	<div class="container-fluid">
		<div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="" method="post">
                        <div class="row">
                            <div class="col-md-12">
                                <label for="" class="font-weight-bold">Size Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo $name?>" placeholder="Enter Name">
                            </div>
                            <div class="col-md-12 text-end">
                                <button class="btn btn-primary mt-3" name="update">Save Change</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
		</div>
	</div>
</div>
<?php
  include 'include/footer.php';
?>