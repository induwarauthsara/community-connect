<?php
// Database connection
$conn = new mysqli("localhost","root","","volunteer_db");
if($conn->connect_error){
    die("Connection failed: ".$conn->connect_error);
}

// Get POST data
$username = $_POST['username'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Basic validation
if($password !== $confirm_password){
    die("Passwords do not match!");
}

// Check if email exists
$sql = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($sql);
if($result->num_rows > 0){
    die("Email already registered!");
}

// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Insert into database
$sql = "INSERT INTO users (username,email,phone,password) VALUES ('$username','$email','$phone','$hashed')";
if($conn->query($sql) === TRUE){
    echo "Signup successful! <a href='login.html'>Login here</a>";
}else{
    echo "Error: ".$conn->error;
}

$conn->close();
?>
