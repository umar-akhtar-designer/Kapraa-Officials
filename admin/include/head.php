<?php
 include 'include/config.php';	
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta -->
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="author" content="DexignLab">
	<meta name="robots" content="" >
	<meta name="keywords" content="">
    <meta name="description" content="">
	
	<!-- Page Title -->
	<title>Kapraa</title>
	
	<!-- Favicon icon -->
	<link rel="icon" type="image/png" sizes="192x192" href="images/512.png?v=4">
	<link rel="shortcut icon" type="image/png" href="images/512.png?v=4">

    <!-- Montserrat font-family (non-blocking so slow internet doesn't hang the page) -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;1,300&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;1,300&display=swap"></noscript>
	
	<!-- All StyleSheet -->
	<link href="vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
	<link href="vendor/owl-carousel/owl.carousel.css" rel="stylesheet">
	
	<!-- Globle CSS -->
    <link href="css/style.css" rel="stylesheet">
	
</head>
<body>
<div id="preloader">
    <div class="lds-ripple">
        <div></div>
        <div></div>
    </div>
</div>
<div id="main-wrapper" >
	<?php
      if($_SESSION['WEBSITE-LOGIN']!=false){
        include 'include/sidebar.php';
      }
    ?>
   