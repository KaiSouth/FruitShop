
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "webbanhang";
$connection = new mysqli($servername, $username, $password, $dbname);
// Kiểm tra kết nối, nếu lỗi thì thông báo
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if(isset($_GET['add_to_cart'])) {
    $product_id = intval($_GET['add_to_cart']);
    

    
    if(!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = 1;
    } else {
        $_SESSION['cart'][$product_id]++;
    }
    $_SESSION['product_added'] = true;

}

?>
<html>

<div style="position: fixed; top: 20%; left: 50%; transform: translateX(-50%); background-color: #28a745; opacity: 0.95; color: #ffffff; padding: 20px; z-index: 9999; display: none; border-radius: 5px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);" id="addToCartPopup">
    <i class="fa fa-check-circle" aria-hidden="true"></i>
    Sản phẩm đã được thêm vào giỏ hàng!
</div>
<script>
var savedProductUrl = '';

window.onload = function() {
  var queryString = window.location.search;
  var urlParams = new URLSearchParams(queryString);

  if (urlParams.has('product_id') && !savedProductUrl) {
      savedProductUrl = window.location.href;
  }
};

function reloadSpecificPage() {
  var pathname = window.location.pathname;
  var filename = pathname.substring(pathname.lastIndexOf('/')+1);
  if(filename === 'index.php' || filename === 'shop.php') {
    window.location.href = filename;
  } else {
    console.log('Không tải lại trang, do tên file không khớp.');
  }
  if (filename === 'shop-detail.php') {
    var urlParams = new URLSearchParams(window.location.search);
    var productId = urlParams.get('add_to_cart');
    var redirectUrl = 'shop-detail.php?product_id=' + productId;
    window.location.href = redirectUrl;
}
}

<?php if (isset($_SESSION['product_added'])) : ?>
window.onload = function() {
    var popup = document.getElementById('addToCartPopup');
    if (popup) {
        popup.style.display = 'block';
        popup.style.opacity = 1;
        setTimeout(function() {
            popup.style.opacity = 0;
            setTimeout(function() {
                reloadSpecificPage();
            }, 500);
        }, 3000);
    }

    // Unset the session variable to avoid repeated alerts
    <?php unset($_SESSION['product_added']); ?>
};
<?php endif; ?>
</script>
<!-- <script style="" src=""></script>
<df-messenger
  intent="WELCOME"
  chat-title="FruitShop"
  agent-id=""
  language-code="vi"
 
></df-messenger> -->


<script>
  // Lấy element của button và iframe
  var chatBubble = document.getElementById('chat-bubble');
  var chatIframe = document.getElementById('chat-iframe');
  
  // Định nghĩa hàm hiển thị hoặc ẩn chat iframe
  function toggleChat() {
    if (chatIframe.style.display === 'none') {
      chatIframe.style.display = 'block';
    } else {
      chatIframe.style.display = 'none';
    }
  }
  
  // Gán sự kiện click cho chat bubble
  chatBubble.addEventListener('click', function() {
    toggleChat();
  });
</script>
</html>


