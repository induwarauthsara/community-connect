<?php
// Test database connection
$servername = "localhost";
$username = "root";
$password = "";

// Test connection to MySQL server
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("❌ Connection to MySQL failed: " . $conn->connect_error);
} else {
    echo "✅ Connected to MySQL server successfully!<br>";
}

// Test if community_connect database exists
$db_check = $conn->query("SHOW DATABASES LIKE 'community_connect'");
if ($db_check->num_rows > 0) {
    echo "✅ Database 'community_connect' exists!<br>";
} else {
    echo "❌ Database 'community_connect' does not exist!<br>";
    echo "👉 Please run: <a href='setup_database.php'>setup_database.php</a><br>";
}

// Test connection to community_connect database
$conn->select_db("community_connect");
if ($conn->error) {
    echo "❌ Cannot connect to community_connect database: " . $conn->error . "<br>";
} else {
    echo "✅ Connected to community_connect database successfully!<br>";
    
    // Check if users table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'users'");
    if ($table_check->num_rows > 0) {
        echo "✅ Users table exists!<br>";
        
        // Count users
        $user_count = $conn->query("SELECT COUNT(*) as count FROM users");
        $count = $user_count->fetch_assoc();
        echo "👥 Total users: " . $count['count'] . "<br>";
    } else {
        echo "❌ Users table does not exist!<br>";
    }
}

$conn->close();
?>

<h3>Next Steps:</h3>
<p>1. If you see all green checkmarks ✅, your database is ready!</p>
<p>2. If you see red X marks ❌, run <a href="setup_database.php">setup_database.php</a> first</p>
<p>3. Then try <a href="home.php">home.php</a> to test your application</p>
