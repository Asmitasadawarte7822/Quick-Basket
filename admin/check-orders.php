<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check all orders with user info
$sql = "SELECT o.*, u.name, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.id DESC 
        LIMIT 20";
$result = mysqli_query($conn, $sql);

echo "<h2>Recent Orders</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
echo "<tr style='background:#333; color:#fff;'>
        <th>Order ID</th>
        <th>User ID</th>
        <th>User Name</th>
        <th>User Email</th>
        <th>Total</th>
        <th>Status</th>
        <th>Date</th>
      </tr>";

while ($row = mysqli_fetch_assoc($result)) {
    $color = $row['user_id'] ? '#d4edda' : '#f8d7da';
    echo "<tr style='background:{$color};'>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['user_id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>₹{$row['total_amount']}</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>{$row['order_date']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><p style='color:green;'>✅ Green rows = Orders with valid user_id</p>";
echo "<p style='color:red;'>❌ Red rows = Orders with missing user_id (0 or NULL)</p>";
?>