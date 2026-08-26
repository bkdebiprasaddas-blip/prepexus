<?php
		$host = "localhost";
		$username = "root";
		$password = "";
		$database = "prepexus";

		$conn = mysqli_connect($host, $username, $password, $database);
		if (!$conn) {
			die("database not connected" . mysqli_connect_errno());
		}

		mysqli_set_charset($conn, "utf8mb4");
?>