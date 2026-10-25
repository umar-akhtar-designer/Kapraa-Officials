<?php
// session already started by config.php (with PHP_SESSION_NONE guard) — do NOT call session_start() again
/*blocking direct access*/
	if (preg_match("functions.php/",$_SERVER['SCRIPT_FILENAME'])){
		die("Access denied");
	}
	
	function session($sessionName){
		if (empty($_SESSION[$sessionName])) {
			header('Location: index.php');
			exit;
		}
	}
	
/*clean string function*/

	function cleanStr($cStr) {
		global $connection_database;
		$cStr = trim($cStr);
		$cStr = strip_tags($cStr);
		// mysqli_real_escape_string return value MUST be used — fixed bug where it was discarded
		$cStr = mysqli_real_escape_string($connection_database, $cStr);
		return $cStr;
	}

/*already added*/
function already_add($table_name, $where){
	global $connection_database;
	$check_field = "select * from $table_name where $where";
	$res = mysqli_query($connection_database, $check_field);
	if(mysqli_num_rows($res) != 0){
		return true;
	}else{
		return false;
	}
}
//total Hours
function sumTime($date, $initialHour, $finalHour) {
	$datetime1 = new DateTime(''.$date.' '.$initialHour.'');
	$datetime2 = new DateTime(''.$date.' '.$finalHour.'');
	$interval = $datetime1->diff($datetime2);
	return $interval->format('%h')." Hours ".$interval->format('%i')." Minutes";
 };


/*insert data query */ 
	function insert_data_query($table, $column_with_value){
		
		global $connection_database;
		
		foreach($column_with_value as $column => $value){
			$column_row		.= "$column,";
			$value_row		.= "'$value',";
		}
			$column_row = substr($column_row, 0, -1);
			$value_row = substr($value_row, 0, -1);
			
			$insert	=	"INSERT INTO $table ($column_row) VALUES ($value_row)";
			if(mysqli_query ($connection_database, $insert) == true){return true;}
			return false;
	}
/*update data query*/  
	function update_data_query($table, $column_with_value, $where){
		global $connection_database;
		
		foreach($column_with_value as $column => $value){
			$column_row		.= "$column='$value',";
		}
		
		$column_row = substr($column_row, 0, -1);
		$update	=	"Update $table set $column_row where $where";
		
			if(mysqli_query ($connection_database, $update) == true){return true;}
			return false;
	}


/*deleted picture in folder
how to use example: if(unlink_file($path, $table,  $column,  $where)) == true){type here update query}*/
function unlink_file($path, $table,  $column,  $where){
	global $connection_database;
	$query	= "SELECT * FROM $table where $where";
	$res	= mysqli_query($connection_database, $query);
	while($row = mysqli_fetch_array($res)){
	  $image	= $row[''.$column.''];
	}
	$file	=	"$path/$image";
	$file2	=	"$path/thumbs/$image";
	unlink($file); 
	unlink($file2); 
	return  'true';
}	

/*get last id form sqli and redirect to other website page white id leave empty location variable and get only id*/
function get_last_id_redirect($table, $location){
	global $connection_database;
	$query	= "SELECT * FROM $table ORDER BY ID DESC LIMIT 1";
	$res = mysqli_query($connection_database, $query);
	if(mysqli_num_rows($res) > 0){
		while($row = mysqli_fetch_array($res)){
			$id= $row['id'];
			
			if(!empty($location)){
				location("$location?id=$id");
			}else{
				return $id;
			}
		}
	}
}

/*Error Massage*/
function error_msg($array){
	$error_msg .= '<div class="notification">';
	$distants = 20;
	foreach($array as $data){
		$data_array = explode(' == ', $data);
		$msg = $data_array[0];
		$icon = $data_array[1];
		$alert = $data_array[2];
		if(empty($icon)){
			$icon = 'check';
		}
		if(empty($alert)){
			$alert = 'primary';
		}
		$error_msg .= '<div class="text-right">
							<div class="d-inline-flex mb-2 alert alert-'.$alert.' py-0 pl-0 align-items-center border-0 m-0 alert-dismissible fade show" role="alert" style="right:-'.$distants.'%;">
								<div class="bg-'.$alert.' px-2 py-3 mr-2 text-white "><i class="fa fa-'.$icon.' fa-lg"></i></div>
								<p class="font-weight-bold m-0 text-center">'.$msg.'</p>
								<button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
						</div>';
						
	$distants = $distants + 20;
	}
	$error_msg .= '</div>';
	return ($error_msg);
}

/*------------------------water Mark------------------------*/
function watermark_image($target, $wtrmrk_file, $newcopy) {
	
	$explode_exe	=	end(explode('.', $target));	
	$image = imagecreatefromjpeg($target);
	
	if($explode_exe == 'png'){
		$image = imagecreatefrompng($target);
	}
	if($explode_exe == 'gif'){
		$image = imagecreatefromgif($target);
	}
	
	$imageSize = getimagesize($target);
	$watermark = imagecreatefrompng($wtrmrk_file);
	$watermark_o_width = imagesx($watermark);
	$watermark_o_height = imagesy($watermark);
	$newWatermarkWidth = $imageSize[0]-20;
	
	$newWatermarkHeight = $watermark_o_height * $newWatermarkWidth / $watermark_o_width;
	imagecopyresized($image, $watermark, $imageSize[0]/2 - $newWatermarkWidth/2, $imageSize[1]/2 - $newWatermarkHeight/2, 0, 0, $newWatermarkWidth, $newWatermarkHeight, imagesx($watermark), imagesy($watermark));
	
	imagejpeg($image, $newcopy, 100);
	imagedestroy($image);
	imagedestroy($watermark);
		
}
	
/* my-function image upload and resize
------------------------------Query Picture resize and upload Funticon-----------------------------------------*/	
	
	function image_resize($originalFile, $targetFile, $newWidth) {
		
		$info = getimagesize($originalFile);
		$mime = $info['mime'];
		switch ($mime) {
			case 'image/jpeg':
				$image_create_func = 'imagecreatefromjpeg'; 
				$image_save_func = 'imagejpeg';
				break;
			case 'image/png':
				$image_create_func = 'imagecreatefrompng';
				$image_save_func = 'imagepng';
				break;
			case 'image/gif':
				$image_create_func = 'imagecreatefromgif';
				$image_save_func = 'imagegif';
				break;
			default: 
		}

		$img = $image_create_func($originalFile);
		list($width, $height) = getimagesize($originalFile);

		$newHeight = ($height / $width) * $newWidth;
		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		
		$background = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
		imagecolortransparent($tmp, $background);
		imagealphablending($tmp, false);
		imagesavealpha($tmp, true);
		
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

		if (file_exists($targetFile)) {
			unlink($targetFile);
		}
		$image_save_func($tmp, "$targetFile");
	}
	function multiple_images($field_name, $temp_name, $target_folder, $rename_file, $allowed_ext, $resize_lg, $resize_sm){
			
		$file_name	=	$field_name;
		$tem_name	=	$temp_name; 
			
		if(!empty($file_name)){
			$file_ext			=	end(explode('.', strtolower($file_name)));
			$file_actual_ext	=	explode(',', $allowed_ext);
			
			
			$org_file_with_path = "$target_folder/$rename_file.$file_ext";
			
			
			if(in_array($file_ext, $file_actual_ext) == true){
				if(move_uploaded_file($tem_name,"$org_file_with_path")){
					if(!empty($resize_lg)){
						image_resize($org_file_with_path, "$target_folder/$rename_file.$file_ext", $resize_lg);
					}
					if(!empty($resize_sm)){
						
						if(!is_dir("$target_folder/thumbs")){
							mkdir("$target_folder/thumbs", 0777, true);
						}
						
						image_resize($org_file_with_path, "$target_folder/thumbs/$rename_file.$file_ext", $resize_sm);
					}
				$success = "$rename_file.$file_ext";	
				}
			}else{
				$error = 'You cannot upload files of this type!';
			}
		}
		
		return array('file_name'=>$success, 'error_msg' => $error);
	}
	function upload_file($field_name, $target_folder, $rename_file, $allowed_ext, $resize_lg, $resize_sm, $watermark_file='', $watermark_size_lg='', $watermark_size_sm=''){
		
		
		$file_name	=	$_FILES["$field_name"]["name"];
		$tem_name	=	$_FILES["$field_name"]['tmp_name']; 
			
		if(!empty($file_name)){
			$file_ext			= end(explode('.', strtolower($file_name)));
			$file_actual_ext	= explode(',', $allowed_ext);
			$org_file_with_path = "$target_folder/$rename_file.$file_ext";
			if(in_array($file_ext, $file_actual_ext) == true){
				if(move_uploaded_file($tem_name,"$org_file_with_path")){
					if(!empty($resize_lg)){
						image_resize($org_file_with_path, "$target_folder/$rename_file.$file_ext", $resize_lg);
					}
					if(!empty($resize_sm)){
						if(!is_dir("$target_folder/thumbs")){
							mkdir("$target_folder/thumbs", 0777, true);
						}
						image_resize($org_file_with_path, "$target_folder/thumbs/$rename_file.$file_ext", $resize_sm);
					}
					if($watermark_size_lg == true){
						watermark_image("$target_folder/$rename_file.$file_ext", $watermark_file, "$target_folder/$rename_file.$file_ext");
					}
					if($watermark_size_sm == true){
						watermark_image("$target_folder/thumbs/$rename_file.$file_ext", $watermark_file, "$target_folder/thumbs/$rename_file.$file_ext");
					}
				$success = "$rename_file.$file_ext";	
				}
			}else{
				
				
				$error = 'You cannot upload files of this type!';
			}
		}
		
		return array('file_name'=>$success, 'error_msg' => $error);
	}			




/*salt password function */
	
	function salt($password){
		$pwd = $password;
		$hash_format = "$2y$10$";
		$salt = md5("Techware house chiniot");
			for($j=1; $j<=5; $j++)
			{
				$salt = md5($salt);
				$formated_salt = $hash_format . $salt;
				$pwd = crypt($pwd, $formated_salt);
			}
		return $pwd;
	}

/*character replace*/
	function replace_to_dash($data){
		$data = str_replace(' ', '-', $data);
		return preg_replace('/[^A-Za-z0-9\-]/', '', $data);
	}
	function replace_to_space($data){
		return preg_replace('/[^A-Za-z0-9]/', ' ', $data);
	}
	function replace_to_plus($data){
		$data = str_replace(' ', '+', $data);
		return preg_replace('/[^A-Za-z0-9\-+]/', '', $data);
	}
	function replace_to_koma($data){
		$data = str_replace(' ', ',', $data);
		return preg_replace('/[^A-Za-z0-9\-,]/', '', $data);
	}
	

/* published time ago*/
	function timeago($date) {
	   $timestamp = strtotime($date);	
	   
	   $strTime = array("second", "minute", "hour", "day", "month", "year");
	   $length = array("60","60","24","30","12","10");

	   $currentTime = time();
	   if($currentTime >= $timestamp) {
			$diff     = time()- $timestamp;
			for($i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++) {
			$diff = $diff / $length[$i];
			}

			$diff = round($diff);
			return $diff . " " . $strTime[$i] . "(s) ago ";
	   }
	}

/* view and like long to short number*/
	function short_count($n) {
        /*first strip any formatting;*/
        $n = (0+str_replace(",", "", $n));

        /*is this a number?*/ 
        if (!is_numeric($n)) return false;

        /*now filter it;*/ 
        if ($n > 1000000000000) return round(($n/1000000000000), 2).' T';
        elseif ($n > 1000000000) return round(($n/1000000000), 2).' B';
        elseif ($n > 1000000) return round(($n/1000000), 2).' M';
        elseif ($n > 1000) return round(($n/1000), 2).' K';

        return number_format($n);
    }
	

/*click links able*/
	function links_clickable($text){
		return preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1" target="_blank" class="text-primary">$1</a>', $text);
	}		

 
/*pagination*/
function pagination($query_select,$pagination_links,$row_limit,$other_link=''){
	global $connection_database;
	$row_count_query = ("$query_select");
	$row_count_result = mysqli_query($connection_database, $row_count_query) or die (mysqli_error());
	if(mysqli_num_rows($row_count_result) >= 0)
	{
		$total_rows = mysqli_num_rows($row_count_result);
	}
	$stages = 1;
	
	if(!empty($other_link)){
		$get_page 	= $_GET[$other_link];
	}else{
		$get_page 	= $_GET['page'];
	}
	
	if(!empty($get_page))
	{
		$row_start = ($get_page - 1) * $row_limit;
	}else{
		$row_start = 0;
	}
	if ($get_page == 0){
		$get_page = 1;
	}
	$prev 		= $get_page - 1;
	$next 		= $get_page + 1;
	$lastpage 	= ceil($total_rows/$row_limit);
	$LastPagem1 = $lastpage - 1;
	$pagination = '';
	
	if($lastpage > 1){
		$pagination .= '<ul class="pagination  mb-3">';
		if ($get_page > 1){
			$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.''.$prev.'"><span aria-label="Prev">&laquo;</span></a></li>';
		}else{
			$pagination.= '<li class="page-item disabled"><span class="page-link"  aria-hidden="true">&laquo;</span></li>';
		}
			
		if ($lastpage < 7 + ($stages * 2)){
			for ($counter = 1; $counter <= $lastpage; $counter++){
				if ($counter == $get_page){
					$pagination.= '<li class="page-item active"><span class="page-link current">'.$counter.'</span></li>';
				}else{
					$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.''.$counter.'">'.$counter.'</a></li>';
				}
			}
		}else if($lastpage > 5 + ($stages * 2)){
			if($get_page < 1 + ($stages * 2)){
				for ($counter = 1; $counter < 4 + ($stages * 2); $counter++){
					if ($counter == $get_page){
						$pagination.= '<li class="page-item active"><span class="page-link current">'.$counter.'</span></li>';
					}else{
						$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.''.$counter.'">'.$counter.'</a></li>';
					}
				}
				$pagination.= '<li class="page-item disabled"><a class="page-link">...</a></li>';
				$pagination.= '<li class="page-item d-md-block d-none"><a class="page-link" href="'.$pagination_links.''.$LastPagem1.'">'.$LastPagem1.'</a></li>';
				$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.''.$lastpage.'">'.$lastpage.'</a></li>';
			}else if($lastpage - ($stages * 2) > $get_page && $get_page > ($stages * 2)){
				$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.'1">1</a></li>';
				$pagination.= '<li class="page-item d-md-block d-none"><a class="page-link" href="'.$pagination_links.'2">2</a></li>';
				$pagination.= '<li class="page-item disabled"><a class="page-link">...</a></li>';
				for ($counter = $get_page - $stages; $counter <= $get_page + $stages; $counter++){
					if ($counter == $get_page){
						$pagination.= '<li class="page-item active"><span class="page-link current">'.$counter.'</span></li>';
					}else{
						$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.''.$counter.'">'.$counter.'</a></li>';
					}
				}
				$pagination.= '<li class="page-item disabled"><a class="page-link">...</a></li>';
				$pagination.= '<li class="page-item d-md-block d-none"><a class="page-link" href="'.$pagination_links.''.$LastPagem1.'">'.$LastPagem1.'</a></li>';
				$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.''.$lastpage.'">'.$lastpage.'</a></li>';
			}else{
				$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.'1">1</a></li>';
				$pagination.= '<li class="page-item d-md-block d-none"><a class="page-link" href="'.$pagination_links.'2">2</a></li>';
				$pagination.= '<li class="page-item disabled"><a class="page-link">...</a></li>';
				for ($counter = $lastpage - (2 + ($stages * 2)); $counter <= $lastpage; $counter++){
					if ($counter == $get_page){
						$pagination.= '<li class="page-item active"><span class="page-link current">'.$counter.'</span></li>';
					}else{
						$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.''.$counter.'">'.$counter.'</a></li>';
					}
				}
			}
		}
			
		/* Next*/
		if ($get_page < $counter - 1){
			$pagination.= '<li class="page-item"><a class="page-link" href="'.$pagination_links.''.$next.'"  aria-label="Next">&raquo;</a></li>';
		}else{
			$pagination.= '<li class="page-item disabled"><span class="page-link"  aria-hidden="true">&raquo;</span></li>';
		}
		$pagination.= '</ul>';	
	}
	
	return array('start' => $row_start, 'pagination' => $pagination, 'total' => $total_rows);
}




/* Select query function and pagination */
/* function calling

$data_array = selectQuery("Query",[
							'dataPerPage'	=>	'10',
							'getVariable'	=>	'shakir',
							'PageUrl'		=>	'index.php',
							
						]);
 */
function selectQuery($query, $paginationArray=''){
	global $connection_database; 
	if(!empty($paginationArray['dataPerPage'])){
		$other_link = $paginationArray['getVariable'];
		$searchVariable = $paginationArray['searchVariable'];
		
		$pagination_links	= ''.$paginationArray['PageUrl'].'?'.$searchVariable.''.$other_link.'=';
		$row_limit			= $paginationArray['dataPerPage'];
		$pagination_result	= pagination($query, $pagination_links, $row_limit,$other_link);
		$product_query		= "$query LIMIT $pagination_result[start], $row_limit";
	}else{
		$product_query = $query;
	}
	$res = mysqli_query($connection_database, $product_query);
	if(mysqli_num_rows($res)>0){
		$sr=0;
		while($row = mysqli_fetch_assoc($res)){
			$res_array[$sr] = $row;
			$sr++; 
		}
		return array('status' => true, 'data' => $res_array, 'dataFound'=> $sr, 'pagination' => $pagination_result['pagination'], 'totalData'=>$pagination_result['total']);
	}
}
	
	


/*email*/
	function email_send($email_to, $email_from, $email_reply_to, $subject, $message){
			
		$headers = '';
		$headers .= 'From: '.$email_from.'' . "\r\n" . 
					'Reply-To: '.$email_reply_to.'' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		
		if(mail($email_to, $subject, $message, $headers)){
			
			return true;
			
		}else{
			
			return false;
		
		}
		
	}

	
/*redirect function */
function location($url){
	header('Location:'.$url.'');
	echo'<script>window.location = "'.$url.'";</script>';
}
/*deleted function*/		
if (isset($_POST['deleted'])) {
    // Delete action after confirmation
    $table_name = $_POST['table'][0] ?? null;    // We expect single deletion
    $column = $_POST['column'][0] ?? null;
    $value = $_POST['value'][0] ?? null;

    if ($table_name && $column && $value) {
        // Prepare statement for safety
        $stmt = $connection_database->prepare("DELETE FROM `$table_name` WHERE `$column` = ?");
        if ($stmt) {
            $stmt->bind_param('s', $value);
            if ($stmt->execute()) {
                $error[] = "Successfully Deleted";
            } else {
                $error[] = "Delete query failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error[] = "Prepare failed: " . $connection_database->error;
        }
    } else {
        $error[] = "Invalid delete parameters.";
    }
}

/*selection checked, selected
 how to use selection($new_id, $old_id, $status);*/
 
function selection($new_id, $old_id, $status){									
	$array = explode(',', $old_id);
	
	foreach($array as $x){
	
		if($new_id == $x){
			
			return "$status";
		}
		
	}
}

/*insert and update data function*/ 
	function insert_and_update_data($status, $table, $column_with_value, $where='', $input_name='', $folder_loction='', $file_extension='', $size_lg='', $size_sm='', $watermark_image='', $watermark_size_lg='', $watermark_size_sm=''){
		global $connection_database;
		
		/*image upload function*/
		$rename_file		= rand(111,999) . time() . rand(111,999);
		$result				= upload_file($input_name, $folder_loction, $rename_file, $file_extension, $size_lg, $size_sm, $watermark_image, $watermark_size_lg, $watermark_size_sm);
		$file_name 			= $result['file_name'];
		if(!empty($file_name)){
			$column_with_value += [$input_name => $file_name];
		}
		/*insert data*/
		if($status == 'insert'){
			return insert_data_query($table, $column_with_value);
		}
		/*update data*/
		else{
			if(!empty($file_name) and unlink_file($folder_loction, $table, $input_name, $where) == true){
				return 	update_data_query($table, $column_with_value, $where);
			}else{
				return	update_data_query($table, $column_with_value, $where);	
			}	
		}
	}


//name first letter function
function short_name($f_name='', $l_name=''){
	$first_ch	= ucfirst($l_name[0]);
	$last_ch	= ucfirst($f_name[0]);
	
	if($first_ch == 'A'){
		$bg_color = '#bf0000';
	}else if($first_ch == 'B'){
		$bg_color = '#bc2800';
	}else if($first_ch == 'C'){
		$bg_color = '#ba5000';
	}else if($first_ch == 'D'){
		$bg_color = '#b77700';
	}else if($first_ch == 'E'){
		$bg_color = '#b59c00';
	}else if($first_ch == 'F'){
		$bg_color = '#a3b200';
	}else if($first_ch == 'G'){
		$bg_color = '#7cb200';
	}else if($first_ch == 'H'){
		$bg_color = '#56b200';
	}else if($first_ch == 'I'){
		$bg_color = '#2fb200';
	}else if($first_ch == 'J'){
		$bg_color = '#b23200';
	}else if($first_ch == 'K'){
		$bg_color = '#00b21d';
	}else if($first_ch == 'L'){
		$bg_color = '#00b244';
	}else if($first_ch == 'M'){
		$bg_color = '#00b26b';
	}else if($first_ch == 'N'){
		$bg_color = '#00b291';
	}else if($first_ch == 'O'){
		$bg_color = '#00acb2';
	}else if($first_ch == 'P'){
		$bg_color = '#0085b2';
	}else if($first_ch == 'Q'){
		$bg_color = '#005fb2';
	}else if($first_ch == 'R'){
		$bg_color = '#0038b2';
	}else if($first_ch == 'S'){
		$bg_color = '#0011b2';
	}else if($first_ch == 'T'){
		$bg_color = '#1400b2';
	}else if($first_ch == 'U'){
		$bg_color = '#3b00b2';
	}else if($first_ch == 'V'){
		$bg_color = '#6200b2';
	}else if($first_ch == 'W'){
		$bg_color = '#8800b2';
	}else if($first_ch == 'X'){
		$bg_color = '#af00b2';
	}else if($first_ch == 'Y'){
		$bg_color = '#b2008e';
	}else if($first_ch == 'Z'){
		$bg_color = '#b20068';
	}else{
		$bg_color = '#007fad';
	}

	return array ('letter'	=> "$last_ch$first_ch", 'bgcolor' => $bg_color);
}

//print_r
function printr($data){
	echo '<pre>';
		print_r($data);
	echo '</pre>';
}
