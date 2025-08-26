<?php
<?php
echo "<h2>Database Connection Test</h2>";

// Test different passwords
$passwords = ['', 'hirushan', 'password', 'root'];
$host = 'localhost';
$username = 'root';

foreach ($passwords as $pass) {
    echo "<h3>Testing with password: '" . ($pass === '' ? 'empty' : $pass) . "'</h3>";
    
    $connection = @mysqli_connect($host, $username, $pass);
    
    if ($connection) {
        echo "✅ Connection successful!<br>";
        
        // Try to show databases
        $result = mysqli_query($connection, "SHOW DATABASES");
        if ($result) {
            echo "✅ Can execute queries<br>";
            echo "Databases found:<br>";
            while ($row = mysqli_fetch_array($result)) {
                echo "- " . $row[0] . "<br>";
            }
        }
        
        mysqli_close($connection);
        echo "<strong>✅ This password works! Use: '$pass'</strong><br><br>";
        break;
    } else {
        echo "❌ Connection failed: " . mysqli_connect_error() . "<br><br>";
    }
}
?>