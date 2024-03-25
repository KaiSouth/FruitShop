<?php
    session_start();
    require 'database.php';

    function registerUser($connection) {
        $defaultRoleId = 1;
        $defaultDeleted = 0;
        
        $fullname = $connection->real_escape_string($_POST['fullname']);
        $email = $connection->real_escape_string($_POST['email']);
        $phone = $connection->real_escape_string($_POST['phone']);
        $address = $connection->real_escape_string($_POST['address']);
        $password = $connection->real_escape_string($_POST['password']);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $checkEmailPhoneStmt = $connection->prepare("SELECT * FROM user WHERE email = ? OR phone_number = ?");
        
        if ($checkEmailPhoneStmt === false) {
          die("Lỗi khi chuẩn bị câu truy vấn: " . $connection->error);
        }
        
        $checkEmailPhoneStmt->bind_param("ss", $email, $phone);
        $checkEmailPhoneStmt->execute();
        $result = $checkEmailPhoneStmt->get_result();
        $checkEmailPhoneStmt->close();
      
        if ($result->num_rows === 0) 
        {
            $stmt = $connection->prepare("INSERT INTO user (fullname, email, phone_number, address, password, role_id, deleted) VALUES (?, ?, ?, ?, ?, ?, ?)");          if ($stmt === false) {
            die("Lỗi khi chuẩn bị câu truy vấn: " . $connection->error);
          }
          
          $stmt->bind_param("sssssii", $fullname, $email, $phone, $address, $hashed_password, $defaultRoleId, $defaultDeleted);          
          if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Đăng ký thành công!';
            sleep(1);
            header("Location:auth.php"); 
            exit();
          } else {
            $_SESSION['error_message'] = "Đăng ký thất bại. Lỗi: ". $stmt->error;
          }
          $stmt->close();
        } else {
          session_start();
          $_SESSION['error_message'] = "Email hoặc số điện thoại đã tồn tại!";
        }
      }

      function loginUser($connection) {
        $email = $connection->real_escape_string($_POST['email']);
        $password = $_POST['password'];
      
        $stmt = $connection->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
          $user = $result->fetch_assoc();
          if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            header("Location:index.php");
            exit();
          } else {
            echo "Mật khẩu không chính xác.";
          }
        } else {
          echo "Không tìm thấy người dùng với email này.";
        }
    }

      if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'register') {
        registerUser($connection);
      }
      if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'login') {
        loginUser($connection);
      }


  $connection->close();

?>


<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đăng nhập và Đăng ký</title>
<style>
  body {
  background-image: url('./img/hero-img-2.jpg');
  background-size: cover;
  background-position: center;
}
  .container {
    width: 300px;
    margin: 100px auto 0;
    background-color: rgba(255, 255, 255, 0.8);
    border-radius: 8px;
    padding: 20px;
  }
  input[type="text"], input[type="password"], input[type="email"], input[type="submit"] {
    width: calc(100% - 22px);
    margin-bottom: 20px;
    height: 36px;
    border: 1px solid #ccc;
    padding: 0 10px;
    border-radius: 5px;
  }
  input[type="submit"] {
    background-color: #4c8b2b; 
    color: white;
    cursor: pointer;
  }
  input[type="submit"]:hover {
    background-color: #3a6f1e;
  }
  .actions {
    text-align: center;
  }
  .actions a {
    color: #7bb92f;
    text-decoration: none;
    font-size: .9em;
    cursor: pointer;
  }


  .form-hidden {
    display: none;
  }
</style>
</head>
<body>

<div class="container">
   
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <div id="login-form">
        <h2>Đăng nhập</h2>
        <input type="hidden" name="action" value="login">
        <input type="email" name="email" id="login-email" placeholder="Email" required>
        <input type="password" name="password" id="login-password" placeholder="Mật khẩu" required>
        <input type="submit" value="Đăng nhập">
        <p class="actions"><span style="cursor: pointer" onclick="toggleForms()">Bạn cần tạo tài khoản?</span></p>
    </div>
</form>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" >
    <div id="register-form" class="form-hidden">
        <h2>Đăng ký</h2>
        <input type="hidden" name="action" value="register">
        <input type="text" name="fullname" id="register-username" placeholder="Tên người dùng" required>
        <input type="email" name="email" id="register-email" placeholder="Email" required>
        <input type="text" name="phone" id="register-phone" placeholder="Số điện thoại" required>
        <input type="text" name="address" id="register-address" placeholder="Địa chỉ">
        <input type="password" name="password" id="register-password" placeholder="Mật khẩu" required>
        <input type="submit" value="Đăng ký">
        <p class="actions"><span style="cursor: pointer" onclick="toggleForms()">Bạn đã có tài khoản?</span></p>
    </div>
</form>

</div>

<script>


function toggleForms() {
  var loginForm = document.getElementById('login-form');
  var registerForm = document.getElementById('register-form');
  
  if (loginForm.classList.contains('form-hidden')) {
    loginForm.classList.remove('form-hidden');
    registerForm.classList.add('form-hidden');
  } else {
    registerForm.classList.remove('form-hidden');
    loginForm.classList.add('form-hidden');
  }
}

window.onload = function() {
  <?php if (isset($_SESSION['success_message'])) { ?>
    // Hiển thị thông báo thành công nếu có
    alert('<?php echo $_SESSION['success_message']; ?>');
    <?php unset($_SESSION['success_message']); ?>
  <?php } else if (isset($_SESSION['error_message'])) { ?>
    // Hiển thị thông báo lỗi nếu có
    alert('<?php echo $_SESSION['error_message']; ?>');
    <?php unset($_SESSION['error_message']); ?>
  <?php } ?>
};


</script>

</body>
</html>