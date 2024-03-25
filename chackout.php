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
        // Insert order
        $stmt = $connection->prepare("INSERT INTO `order` (fullname, email, phone_number, address, note, total, order_date, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), '0')");
        $stmt->bind_param("sssssd", $full_name, $email, $phone_number, $address, $note, $total_cost);
        $stmt->execute();
        
       $order_id = $connection->insert_id;
       $stmt->close();
    // Chèn từng sản phẩm trong giỏ hàng vào bảng `order_detail`
    $stmt = $connection->prepare("INSERT INTO order_detail (order_id, product_id, price, number, total_money) VALUES (?, ?, ?, ?, ?)");
    foreach ($cart_items as $product_id => $quantity) {
        // Lấy thông tin chi tiết sản phẩm bằng product_id
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
    $full_name = $_POST['full_name'] ?? null;
    $address = $_POST['address'] ?? null;
    $phone_number = $_POST['phone_number'] ?? null;
    $email = $_POST['email'] ?? null;
    $note = $_POST['note'] ?? null;
    $payment_option = $_POST['paymentOption'] ?? null;
    $cart_items = $_SESSION['cart'] ?? [];
    $total_cost = $_SESSION['checkout_total'] ?? 0;

    if ($payment_option === 'Cost') { 
        
        $order_id = insertOrderToDatabase($connection, $full_name, $email, $phone_number, $address, $note, $total_cost, $cart_items);
            
        if ($order_id) {
            echo "<script>alert('Đơn hàng của bạn đã được đặt thành công và đã thành toán tiền mặt.');</script>";
            unset($_SESSION['cart']);
            unset($_SESSION['checkout_total']);
            header('Location: index.php');
            exit;
        } else {
            echo "<script>alert('Có lỗi xảy ra trong quá trình đặt hàng. Vui lòng thử lại.');</script>";
        }
            
        } elseif ($payment_option === 'QRCode') {
            $_SESSION['pending_order'] = [
                'full_name' => $full_name,
                'email' => $email,
                'phone_number' => $phone_number,
                'address' => $address,
                'note' => $note,
                'cart_items' => $cart_items
            ];
            
            header('Location: bank.php');
            exit;
        }
}

$errors = [];
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Fruitables - Vegetable Website Template</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="" name="keywords">
        <meta content="" name="description">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 

        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Libraries Stylesheet -->
        <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
        <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">


        <!-- Customized Bootstrap Stylesheet -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="css/style.css" rel="stylesheet">
    </head>

    <body>

        <!-- Spinner Start -->
        <div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50  d-flex align-items-center justify-content-center">
            <div class="spinner-grow text-primary" role="status"></div>
        </div>
        <!-- Spinner End -->


        <!-- Navbar start -->
        <div class="container-fluid fixed-top">
            <div class="container topbar bg-primary d-none d-lg-block">
                <div class="d-flex justify-content-between">
                    <div class="top-info ps-2">
                        <small class="me-3"><i class="fas fa-map-marker-alt me-2 text-secondary"></i> <a href="#" class="text-white">123 Street, New York</a></small>
                        <small class="me-3"><i class="fas fa-envelope me-2 text-secondary"></i><a href="#" class="text-white">Email@Example.com</a></small>
                    </div>
                    <div class="top-link pe-2">
                        <a href="#" class="text-white"><small class="text-white mx-2">Privacy Policy</small>/</a>
                        <a href="#" class="text-white"><small class="text-white mx-2">Terms of Use</small>/</a>
                        <a href="#" class="text-white"><small class="text-white ms-2">Sales and Refunds</small></a>
                    </div>
                </div>
            </div>
            <div class="container px-0">
                <nav class="navbar navbar-light bg-white navbar-expand-xl">
                    <a href="index.php" class="navbar-brand"><h1 class="text-primary display-6">Fruitables</h1></a>
                    <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars text-primary"></span>
                    </button>
                    <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                        <div class="navbar-nav mx-auto">
                            <a href="index.php" class="nav-item nav-link">Home</a>
                            <a href="shop.php" class="nav-item nav-link">Shop</a>
                            <!-- <a href="shop-detail.php" class="nav-item nav-link">Shop Detail</a>
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle active" data-bs-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu m-0 bg-secondary rounded-0">
                                    <a href="cart.php" class="dropdown-item">Cart</a>
                                    <a href="chackout.php" class="dropdown-item active">Chackout</a>
                                    <a href="testimonial.php" class="dropdown-item">Testimonial</a>
                                    <a href="404.php" class="dropdown-item">404 Page</a>
                                </div>
                            </div> -->
                            <a href="contact.php" class="nav-item nav-link">Contact</a>
                        </div>
                        <div class="d-flex m-3 me-0" style="align-items:center">
                            <button class="btn-search btn border border-secondary btn-md-square rounded-circle bg-white me-4" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="fas fa-search text-primary"></i></button>
                            <a href="cart.php" class="position-relative me-4 my-auto">
    <i class="fa fa-shopping-bag fa-2x"></i>
    <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1" style="top: -5px; left: 15px; height: 20px; min-width: 20px;"><?php echo $cart_count; ?></span>
</a>
                           <a href="<?php echo isset($_SESSION['fullname']) ? 'information.php' : 'auth.php'; ?>" class="my-auto user-info">
                                <?php if(isset($_SESSION['fullname'])): ?>
                                    <span>Xin chào <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                                    <!-- Nút đăng xuất, trong href thêm tham số action=logout để xử lý việc đăng xuất -->
                                    <a href="?action=logout" class="logout-button">&ensp;Đăng xuất</a>
                                <?php else: ?>
                                    <i class="fas fa-user fa-2x"></i>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
        <!-- Navbar End -->


        <!-- Modal Search Start -->
        <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Search by keyword</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body d-flex align-items-center">
                        <div class="input-group w-75 mx-auto d-flex">
                            <input type="search" class="form-control p-3" placeholder="keywords" aria-describedby="search-icon-1">
                            <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Search End -->


        <!-- Single Page Header start -->
        <div class="container-fluid page-header py-5">
            <h1 class="text-center text-white display-6">Checkout</h1>
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pages</a></li>
                <li class="breadcrumb-item active text-white">Checkout</li>
            </ol>
        </div>
        <!-- Single Page Header End -->



        <!-- Checkout Page Start -->
        <div class="container-fluid py-5">
            <div class="container py-5">
                <h1 class="mb-4">Billing details</h1>
                <form name="checkoutForm" action="chackout.php" method="post" onsubmit="return validateForm()">                    
                <div class="row g-5">
                        <div class="col-md-12 col-lg-6 col-xl-7">
                            <div class="row">                  
                                <div class="col-md-12 col-lg-6">
                                    <div class="form-item w-100">
                                        <label class="form-label my-3">Full Name<sup>*</sup></label>
                                        <input name="full_name" type="text" class="form-control" required>                                        
                                        <span class="error-message" style="color: red;"><?php if (isset($errors['full_name'])) { echo $errors['full_name']; } ?></span>                                    </div>
                                </div>
                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Address <sup>*</sup></label>
                                <input name="address" type="text" class="form-control" required>                                
                                <span class="error-message" style="color: red;"><?php if (isset($errors['address'])) { echo $errors['address']; } ?></span>                            
                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Phone number<sup>*</sup></label>
                                <input name="phone_number" type="tel" class="form-control" required pattern="[0-9]+" title="Phone number must be numeric">                                
                                <span class="error-message" style="color: red;"><?php if (isset($errors['phone_number'])) { echo $errors['phone_number']; } ?></span>                            </div>
                            <div class="form-item">
                                <label class="form-label my-3">Email Address<sup>*</sup></label>
                                <input name="email" type="email" class="form-control" required>                                
                                <span class="error-message" style="color: red;"><?php if (isset($errors['email'])) { echo $errors['email']; } ?></span>                            </div>
                           
                            <div class="form-item">
                                <label class="form-label my-3">Note<sup>*</sup></label>
                                <textarea name="note" class="form-control" spellcheck="false" cols="30" rows="11" placeholder="Oreder Notes (Optional)"></textarea>
                            </div>
                        </div>

                <!-- Cart Summary Right Column -->
                <div class="col-md-12 col-lg-6 col-xl-5">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Products</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($_POST['cart']) && is_array($_POST['cart'])) {
                                    foreach ($_POST['cart'] as $product_id => $quantity) {
                                      
                                        $product = getProductDetailsById($connection, $product_id);
                                        if ($product) {
                                           
                                            $line_total = $product['discount_price'] * $quantity;  
                                            ?>
                                            <tr>
                                                <th scope="row">
                                                    <div class="d-flex align-items-center mt-2">
                                                        <img src="img/<?php echo htmlspecialchars($product['image_link']); ?>" class="img-fluid rounded-circle" style="width: 90px; height: 90px;" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                                    </div>
                                                </th>
                                                <td class="py-5"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                <td class="py-5"><?php echo number_format($product['discount_price']); ?> VND</td>
                                                <td class="py-5"><?php echo htmlspecialchars($quantity); ?></td>
                                                <td class="py-5"><?php echo number_format($line_total); ?> VND</td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center">Your cart is empty.</td></tr>';
                                }
                                ?>
                            </tbody>
                            <tr>
                        <td colspan="4" class="text-right"><strong>Total Cost</strong></td>
                        <td>
                            <?php
                            // Kiểm tra xem giá trị total có trong session không và in nó ra
                            if (isset($_SESSION['checkout_total'])) {
                                echo number_format($_SESSION['checkout_total']) . ' VND';
                            } else {
                                echo "Unable to retrieve total cost";
                            }
                            ?>
                        </td>
                    </tr>
                        </table>
                    </div>
                    <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                        <div class="col-12">
                        <div class="form-check text-start my-3">
                            <input type="radio" class="form-check-input bg-primary border-0" id="QRCode" name="paymentOption" value="QRCode">
                            <label class="form-check-label" for="QRCode">QR Code</label>
                        </div>
                        <div class="form-check text-start my-3">
                            <input type="radio" class="form-check-input bg-primary border-0" id="Cost" name="paymentOption" value="Cost">
                            <label class="form-check-label" for="Cost">Cost</label>
                        </div>
                        </div>
                    </div>     
                    <div class="row g-4 text-center align-items-center justify-content-center pt-4">
                        <input type="submit" value="Place Order" class="btn border-secondary py-3 px-4 text-uppercase w-100 text-primary">
                    </div>
                   
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Checkout Page End -->


        <!-- Footer Start -->
        <div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
            <div class="container py-5">
                <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5) ;">
                    <div class="row g-4">
                        <div class="col-lg-3">
                            <a href="#">
                                <h1 class="text-primary mb-0">Fruitables</h1>
                                <p class="text-secondary mb-0">Fresh products</p>
                            </a>
                        </div>
                        <div class="col-lg-6">
                            <div class="position-relative mx-auto">
                                <input class="form-control border-0 w-100 py-3 px-4 rounded-pill" type="number" placeholder="Your Email">
                                <button type="submit" class="btn btn-primary border-0 border-secondary py-3 px-4 position-absolute rounded-pill text-white" style="top: 0; right: 0;">Subscribe Now</button>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="d-flex justify-content-end pt-3">
                                <a class="btn  btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-youtube"></i></a>
                                <a class="btn btn-outline-secondary btn-md-square rounded-circle" href=""><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-5">
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-item">
                            <h4 class="text-light mb-3">Why People Like us!</h4>
                            <p class="mb-4">typesetting, remaining essentially unchanged. It was 
                                popularised in the 1960s with the like Aldus PageMaker including of Lorem Ipsum.</p>
                            <a href="" class="btn border-secondary py-2 px-4 rounded-pill text-primary">Read More</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex flex-column text-start footer-item">
                            <h4 class="text-light mb-3">Shop Info</h4>
                            <a class="btn-link" href="">About Us</a>
                            <a class="btn-link" href="">Contact Us</a>
                            <a class="btn-link" href="">Privacy Policy</a>
                            <a class="btn-link" href="">Terms & Condition</a>
                            <a class="btn-link" href="">Return Policy</a>
                            <a class="btn-link" href="">FAQs & Help</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex flex-column text-start footer-item">
                            <h4 class="text-light mb-3">Account</h4>
                            <a class="btn-link" href="">My Account</a>
                            <a class="btn-link" href="">Shop details</a>
                            <a class="btn-link" href="">Shopping Cart</a>
                            <a class="btn-link" href="">Wishlist</a>
                            <a class="btn-link" href="">Order History</a>
                            <a class="btn-link" href="">International Orders</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-item">
                            <h4 class="text-light mb-3">Contact</h4>
                            <p>Address: 1429 Netus Rd, NY 48247</p>
                            <p>Email: Example@gmail.com</p>
                            <p>Phone: +0123 4567 8910</p>
                            <p>Payment Accepted</p>
                            <img src="img/payment.png" class="img-fluid" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->

        <!-- Copyright Start -->
        <div class="container-fluid copyright bg-dark py-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        <span class="text-light"><a href="#"><i class="fas fa-copyright text-light me-2"></i>Your Site Name</a>, All right reserved.</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Copyright End -->



        <!-- Back to Top -->
        <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   

        
    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <script>
function validateForm() {
    var full_name = document.forms["checkoutForm"]["full_name"].value;
    var address = document.forms["checkoutForm"]["address"].value;
    var phone_number = document.forms["checkoutForm"]["phone_number"].value;
    var email = document.forms["checkoutForm"]["email"].value;
    var paymentOption = document.forms["checkoutForm"]["paymentOption"].value;

    if (full_name == "" || address == "" || phone_number == "" || email == "") {
        alert("All fields must be filled out");
        return false;
    }
    
    if (!/^\d+$/.test(phone_number)) {
        alert("Phone number must be numeric");
        return false;
    }
    
    if (!/^\S+@\S+\.\S+$/.test(email)) {
        alert("Email must be a valid email address");
        return false;
    }

    if (paymentOption === undefined || paymentOption === "") {
        alert("Please select a payment option");
        return false;
    }
    
    return true; // Form is valid
}
</script>
    </body>

</html>