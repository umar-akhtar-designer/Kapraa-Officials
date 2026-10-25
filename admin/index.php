<?php
	include 'include/head.php';
		if (!empty($_SESSION['WEBSITE-LOGIN'])) {
			location('dashboard.php');
		} else {
			include 'include/login.php';
		}

	include 'include/footer.php';
?>
