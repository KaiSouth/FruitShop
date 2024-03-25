<?php
session_start();
require 'database.php';

function getProductDetailsById($connection, $product_id) 
{
    $product_id = (int)$product_id;
    $stmt = $connection->prepare("SELECT p.product_name, p.discount_price, p.image_link, p.price, p.rating, c.category_name, p.product_id, p.description
    FROM product AS p
    JOIN category AS c ON p.category_id = c.category_id WHERE p.product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    return $product;
}


function insertOrderToDatabase($connection, $full_name, $email, $phone_number, $address, $note, $total_cost, $cart_items) {
    $connection->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

    try {
        $stmt = $connection->prepare("INSERT INTO `order` (fullname, email, phone_number, address, note, total, order_date, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), '0')");
        $stmt->bind_param("sssssd", $full_name, $email, $phone_number, $address, $note, $total_cost);
        $stmt->execute();
        
       $order_id = $connection->insert_id;
       $stmt->close();
    $stmt = $connection->prepare("INSERT INTO order_detail (order_id, product_id, price, number, total_money) VALUES (?, ?, ?, ?, ?)");
    foreach ($cart_items as $product_id => $quantity) {
        $product_details = getProductDetailsById($connection, $product_id);
        
        if ($product_details) {
            $price = $product_details['discount_price'];
            $total_money = $price * $quantity;

            $stmt->bind_param("iiddd", $order_id, $product_id, $price, $quantity, $total_money);
            $stmt->execute();
        }
    }

      $connection->commit();
      return $order_id; 

  } catch (mysqli_sql_exception $e) {
      $connection->rollback();
      return false;
  } finally {
      if($stmt) {
          $stmt->close();
      }
      $connection->autocommit(TRUE);
  }
}


function processPendingOrder($connection) {
    if (isset($_SESSION['pending_order'])) {
        $pending_order = $_SESSION['pending_order'];
        
        $order_id = insertOrderToDatabase(
            $connection, 
            $pending_order['full_name'], 
            $pending_order['email'], 
            $pending_order['phone_number'], 
            $pending_order['address'], 
            $pending_order['note'], 
            $_SESSION['checkout_total'],
            $pending_order['cart_items']
        );
        
        if ($order_id) {
            // Xóa phiên đơn hàng đang chờ
            unset($_SESSION['pending_order']);
            unset($_SESSION['cart']);
            unset($_SESSION['checkout_total']);
            return true;
        } else {
            // Có thể log lỗi hoặc thực hiện các bước hỗ trợ lỗi
            return false;
        }
    }
    return false; // Không có pending_order để xử lý
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'processPendingOrder') {
    header('Content-Type: application/json');
    $success = processPendingOrder($connection);
    echo json_encode(['success' => $success]);
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dynamic Image URL</title>
    <style>
    body { 
        font-family: Arial, sans-serif;
        background-image: url('./img/hero-img-2.jpg');
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;
        height: 100vh;
        display: grid;
        align-content: stretch;
        justify-content: space-around;
    }

    #qrCodeImage {
        width: auto;
        height: 80vh; 
        margin-right: 20px;
        border-radius: 10px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
    }

    button {
        width: 100px;
        height: 100px;
        background-color: rgba(255, 255, 255, 0.5);
        border: none;
        border-radius: 10px;
        cursor: pointer;
    }

    button:hover {
        background-color: rgba(255, 255, 255, 0.7); /* Màu nền khi hover */
    }

    .loader-container {
        position: relative;
        width: 100%;
        height: 50px;
        background-color: #f0f0f0;
        overflow: hidden;
        border-radius: 5px;
    }

    .loader-bar {
        position: absolute;
        top: 0;
        left: 100%;
        width: 100%;
        height: 100%;
        background-color: #4CAF50;
        animation: loading 300s linear forwards;
    }

    @keyframes loading {
        0% { left: 100%; }
        100% { left: 0%; }
    }
    .loader-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #333;
        font-size: 16px;
    }
</style>
</head>
<body>
    <img id="qrCodeImage" src="" alt="QR Code Image">
    <div class="loader-container">
        <div class="loader-bar"></div>
        <div class="loader-text">Đang chờ thanh toán</div>
    </div>
    <!-- <button onclick="initiatePayment()">
        Thanh Toán
    </button> -->
    
    
    <script>
    
    var BANK_ID = "";
    var ACCOUNT_NO = "";
    var TEMPLATE = "";
    var AMOUNT = <?php echo json_encode($_SESSION['checkout_total']); ?>;
    var DESCRIPTION = "Cua hang hoa qua Fruittable";
    var ACCOUNT_NAME = "";
    var TRANSACTION_ID = Math.random().toString(36).substr(2, 9);
    var imageURL = "https://img.vietqr.io/image/" + BANK_ID + "-" + ACCOUNT_NO + "-" + TEMPLATE + ".jpg?amount=" + AMOUNT + "&addInfo=" + encodeURIComponent(DESCRIPTION + " " + TRANSACTION_ID) + "&accountName=" + encodeURIComponent(ACCOUNT_NAME);
    document.getElementById("qrCodeImage").src = imageURL;
    
    var paidcontent = "Cua hang hoa qua Fruittable";
    var paidprice = <?php echo (int)($_SESSION['checkout_total']); ?>;
    var checkCount = 0;
    var maxChecks = 90;

    document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initiatePayment, 20000);
});

    async function completeOrderPHP() {
    try {
        const response = await fetch('bank.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=processPendingOrder'
        });
        const result = await response.json();
        if(result) {
            console.log('Đơn hàng đã được xử lý thành công.');
        } else {
            console.error('Có lỗi xảy ra khi xử lý đơn hàng.');
        }
    } catch (error) {
        console.error('Có lỗi xảy ra:', error);
    }
}
    function initiatePayment() {
        checkPaid(paidcontent, paidprice, TRANSACTION_ID);
    }

    async function checkPaid(paidcontent, paidprice, transactionId) {
        if(checkCount >= maxChecks) {
            console.log("Thanh toán không thành công hoặc quá thời gian chờ.");
            document.querySelector('.loader-bar').style.width = '100%';
            document.querySelector('.loader-bar').style.backgroundColor = 'red';
            document.querySelector('.loader-text').textContent = "Thanh toán thất bại";
            return;
        }
        checkCount++;
        setTimeout(async function() {
            try {
                var response = await fetch("");
                var data = await response.json();
                // Vòng lặp qua từng giao dịch trong data
                for (var i = 0; i < data.data.length; i++) {
                    var transaction = data.data[i];
                    if (transaction["Mô tả"].includes(transactionId) &&
                        paidprice >= transaction["Giá trị"] &&
                        transaction["Mô tả"].includes(paidcontent)) {
                            // alert("Thanh toán thành công với mã giao dịch: " + transactionId);
                            var loaderBar = document.querySelector('.loader-bar');
                            loaderBar.style.width = '100%';
                            loaderBar.style.backgroundColor = '#333';
                            loaderBar.style.animation = 'none'; 
                            var loaderText = document.querySelector('.loader-text');
                            loaderText.textContent = "Thanh toán thành công";
                            completeOrderPHP();
                            return;
                    }
                }
                console.log("Không tìm thấy giao dịch phù hợp, kiểm tra lại sau.");
                checkPaid(paidcontent, paidprice, transactionId); // Kiểm tra lại sau một thời gian
            } catch (error) {
                console.log("Lỗi: " + error);
            }
        }, 1000);
    }
</script>
</body>
</html>