<?php
include "db.php";
$id = $_GET['id'];
$action = $_GET['action'];

if ($action == "accept") {
    $conn->query("UPDATE proposals SET status='accepted' WHERE id=$id");
} else if ($action == "reject") {
    $conn->query("UPDATE proposals SET status='rejected' WHERE id=$id");
}
header("Location: dashboard.php");
?>
