<?php
include "db.php";
$id = $_GET['id'];
$conn->query("DELETE FROM volunteers WHERE id=$id");
header("Location: dashboard.php");
?>
