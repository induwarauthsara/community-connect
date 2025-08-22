<?php
session_start();

// Database connection
$servername = "localhost";
$db_username = "root";     // your DB username
$db_password = "";         // your DB password
$dbname = "volunteer_db"; // your database name

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Form submission
if(isset($_POST['email'], $_POST['password'])){
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Fetch user
    $sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $result = $conn->query($sql);

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();

        // Optional: block inactive users
        if(isset($user['status']) && $user['status'] != 'active'){
            echo "<h2>Account is inactive. Contact admin.</h2>";
            echo '<a href="login.html">Back to Login</a>';
            exit();
        }

        // Verify password
        if(password_verify($password, $user['password'])){
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php"); // redirect to dashboard
            exit();
        } else {
            echo "<h2>Incorrect password!</h2>";
            echo '<a href="login.html">Back to Login</a>';
        }
    } else {
        // User not registered
        echo "<h2>Email not found!</h2>";
        echo '<a href="signup.html">Sign Up</a>';
    }
}

$conn->close();
?>
