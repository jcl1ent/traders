<?php
$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$name = getenv("DB_NAME");

$con = mysqli_connect($host,$user,$pass,$name);

?>