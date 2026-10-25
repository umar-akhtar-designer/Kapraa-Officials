<?php
// Buffer all output — many pages call header("Location: ...") to redirect
// AFTER already including head.php (which starts printing HTML immediately).
// Without buffering, those redirects silently fail ("headers already sent")
// and the page renders blank instead of redirecting.
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

// ── OTP gate: only on admin pages ────────────────────────────
$_otp_script  = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$_is_admin_pg = strpos($_otp_script, '/admin/') !== false;
$_otp_excluded = ['index.php', 'verify_otp.php', 'send_otp.php', 'logout.php'];
$_otp_current  = basename($_otp_script);
if ($_is_admin_pg
    && !in_array($_otp_current, $_otp_excluded)
    && !empty($_SESSION['WEBSITE-LOGIN'])
    && empty($_SESSION['otp_verified'])) {
    header('Location: verify_otp.php');
    exit;
}
// ─────────────────────────────────────────────────────────────
error_reporting(0);
	//blocking direct access
	if (preg_match("/config.php/",$_SERVER['SCRIPT_FILENAME']))
	{
		die("Access denied");
	}
	
	//check mysqli installed
	if (!function_exists('mysqli_connect')){
		die("mysqli is not installed<br>");
	}
	
	// ── DB: auto-switch localhost vs live ──────────────────────
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$isLocal = (strpos($host,'localhost') !== false
	         || strpos($host,'127.0.0.1') !== false
	         || strpos($host,'192.168.')  !== false
	         || strpos($host,'ngrok')     !== false);

	if ($isLocal) {
		// Local XAMPP
		$server    = 'localhost';
		$user      = 'root';
		$password  = '';
		$data_base = 'kapraa';
	} else {
		// Hostinger live server — kapraa.pk
		$server    = 'localhost';
		$user      = 'u167258434_kapraa';
		$password  = 'Kapraa@2026';
		$data_base = 'u167258434_kapraa';
	}
		
	
		global  $connection_database;
		$connection_database		=	mysqli_connect($server,$user,$password,$data_base);	

	// Detect HTTPS correctly — also handles reverse proxies & ngrok
	// ngrok terminates SSL and forwards plain HTTP to Apache, but sets
	// HTTP_X_FORWARDED_PROTO: https so we must check that header too.
	if (
		(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
		(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
		(isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
		(isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
	) {
		$http = 'https';
	} else {
		$http = 'http';
	}
	
	$url_domain 				= strtolower($_SERVER['HTTP_HOST']); //saleem.pk
	$url_dir_file 				= strtolower($_SERVER['PHP_SELF']);//saleem.pk/directory/file
	$url_dir_file_var			= strtolower($_SERVER['REQUEST_URI']);//saleem.pk/directory/file/veriable
	$url_domain_dir_file_var	= strtolower(''.$http.'://'.$_SERVER['HTTP_HOST'].''.$_SERVER['REQUEST_URI'].'');
	
	$url_file_name 				= end(explode('/', $url_dir_file));//first file name
	
	

	// Localhost → add /kapraa subfolder. Live server → root only
	if ($isLocal) {
		$url_domain = "$http://" . $_SERVER['HTTP_HOST'] . "/kapraa";
	} else {
		$url_domain = "$http://" . $_SERVER['HTTP_HOST'];
	}


//utf8 format connection
	mysqli_query($connection_database, "set character_set_results='utf8'");
	mysqli_set_charset($connection_database, "utf8");

//time zone
	date_default_timezone_set("Asia/Karachi");
	include 'functions.php';

// Auto-migrate: add discount_text column to brand if it doesn't exist
$_colCheck = $connection_database->query("SHOW COLUMNS FROM brand LIKE 'discount_text'");
if ($_colCheck && $_colCheck->num_rows === 0) {
    $connection_database->query("ALTER TABLE brand ADD COLUMN discount_text VARCHAR(60) DEFAULT NULL");
}
?>