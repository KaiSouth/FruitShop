<?php
    session_start();
    require 'database.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: auth.php");
        exit();
    }
    $user_id = $_SESSION['user_id'];
    $stmt = $connection->prepare("SELECT * FROM user WHERE id = ?");
        if (!$stmt) {
        echo "Error in query preparation: " . htmlspecialchars($connection->error);
        exit();
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();

    if (!$user_info) {
        echo "Không tìm thấy người dùng.";
        exit();
    }
?>
<!Doctype html>
<html lang="en">
<head>
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f2f2f2;
}

.container {
    display: flex;
    justify-content: space-between;
    padding: 20px;
}

.user-info {
    width: 70%;
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.voucher-list {
    width: 30%;
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            <h1>Thông Tin Người Dùng</h1>
            <p>ID: <?php echo $user_info['id']; ?></p>
            <p>Họ và Tên: <?php echo htmlspecialchars($user_info['fullname']); ?></p>
            <p>Email: <?php echo htmlspecialchars($user_info['email']); ?></p>
            <p>Phone number: <?php echo htmlspecialchars($user_info['phone_number']); ?></p>
            <p>Address: <?php echo htmlspecialchars($user_info['address']); ?></p>
            <!-- Các trường thông tin khác nếu cần -->
        </div>
        <div class="voucher-list">
           <h2>Voucher sẵn có</h2>
        </div>
    </div>
</body>

</html>