<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.html");
    exit();
}
include "db.php";

// Fetch proposals
$proposals = $conn->query("SELECT * FROM proposals");

// Fetch volunteers
$volunteers = $conn->query("SELECT * FROM volunteers");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="sidebar">
    <h2>Admin Panel</h2>
    <ul>
      <li><a href="#proposals">Proposals</a></li>
      <li><a href="#volunteers">Volunteers</a></li>
      <li><a href="#reports">Reports</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<div class="main">
    <h1>Welcome, <?php echo $_SESSION['admin']; ?></h1>

    <!-- Proposals -->
    <h2 id="proposals">Proposals</h2>
    <table>
        <tr><th>Title</th><th>By</th><th>Status</th><th>Action</th></tr>
        <?php while($p = $proposals->fetch_assoc()): ?>
        <tr>
            <td><?php echo $p['title']; ?></td>
            <td><?php echo $p['proposer']; ?></td>
            <td><?php echo $p['status']; ?></td>
            <td>
                <a href="handle_proposal.php?id=<?php echo $p['id']; ?>&action=accept">Accept</a>
                <a href="handle_proposal.php?id=<?php echo $p['id']; ?>&action=reject">Reject</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Volunteers -->
    <h2 id="volunteers">Volunteers</h2>
    <form action="add_volunteer.php" method="POST">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit">Add</button>
    </form>
    <table>
        <tr><th>Name</th><th>Email</th><th>Action</th></tr>
        <?php while($v = $volunteers->fetch_assoc()): ?>
        <tr>
            <td><?php echo $v['name']; ?></td>
            <td><?php echo $v['email']; ?></td>
            <td><a href="remove_volunteer.php?id=<?php echo $v['id']; ?>">Remove</a></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Reports -->
    <h2 id="reports">Reports</h2>
    <p>Total Volunteers: 
      <?php echo $conn->query("SELECT COUNT(*) as c FROM volunteers")->fetch_assoc()['c']; ?>
    </p>
    <p>Proposals Reviewed: 
      <?php echo $conn->query("SELECT COUNT(*) as c FROM proposals WHERE status!='pending'")->fetch_assoc()['c']; ?>
    </p>
</div>
</body>
</html>
