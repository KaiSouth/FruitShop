<?php
session_start();
require "database.php";

function getProductDetailsById($connection, $product_id)
{
    $product_id = (int) $product_id;
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
function getCouponValue($connection, $couponCode)
{
    $stmt = $connection->prepare(
        "SELECT value FROM coupon WHERE coupon_id = ? LIMIT 1"
    );

    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($connection->error));
    }

    $stmt->bind_param("s", $couponCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $coupon = $result->fetch_assoc();
    $stmt->close();

    return $coupon ? $coupon["value"] : null;
}

if (isset($_GET["product_id"]) && isset($_GET["new_value"])) {
    $product_id = intval($_GET["product_id"]);
    $new_value = intval($_GET["new_value"]);

    if ($new_value > 0 && isset($_SESSION["cart"][$product_id])) {
        $_SESSION["cart"][$product_id] = $new_value;
    }
    echo json_encode($_SESSION["cart"]);
    exit();
}
if (isset($_GET["remove_product"])) {
    $product_id = intval($_GET["remove_product"]);

    if (isset($_SESSION["cart"][$product_id])) {
        unset($_SESSION["cart"][$product_id]);
    }
    header("Location: cart.php");
    exit();
}

$cart_count = isset($_SESSION["cart"]) ? array_sum($_SESSION["cart"]) : 0;
$subtotal = 0;

foreach ($_SESSION["cart"] as $product_id => $quantity) {
    $product_details = getProductDetailsById($connection, $product_id);
    if ($product_details) {
        $product_price =
            isset($product_details["discount_price"]) &&
            $product_details["discount_price"] > 0
                ? $product_details["discount_price"]
                : $product_details["price"];
        $subtotal += $product_price * $quantity;
        $total += $product_price * $quantity;
        $_SESSION["checkout_total"] = $total;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["apply_coupon"]) && !empty($_POST["coupon_code"])) {
        $coupon_code = trim($_POST["coupon_code"]);
        $coupon_value = getCouponValue($connection, $coupon_code);

        if ($coupon_value) {
            $discount = ($subtotal * $coupon_value) / 100;
            $total = $subtotal - $discount;
            $_SESSION["checkout_total"] = $total; // Cập nhật tổng số vào session
        } else {
            $total = $subtotal;
            $_SESSION["checkout_total"] = $total; // Cập nhật tổng số vào session
            $error_message = "Invalid coupon code";
        }
    } else {
        $total = $subtotal;
    }
    if (
        isset($_POST["proceed_checkout"]) &&
        $_POST["proceed_checkout"] == "1"
    ) {
        $_SESSION["checkout_total"] = $total; // Cập nhật tổng số vào session
        header("Location: chackout.php");
        exit();
    }
}
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
                            <!-- <a href="shop-detail.php" class="nav-item nav-link">Shop Detail</a> -->
                            <!-- <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle active" data-bs-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu m-0 bg-secondary rounded-0">
                                    <a href="cart.php" class="dropdown-item active">Cart</a>
                                    <a href="chackout.php" class="dropdown-item">Chackout</a>
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
                           <a href="<?php echo isset($_SESSION["fullname"])
                               ? "information.php"
                               : "auth.php"; ?>" class="my-auto user-info">
                                <?php if (isset($_SESSION["fullname"])): ?>
                                    <span>Xin chào <?php echo htmlspecialchars(
                                        $_SESSION["fullname"]
                                    ); ?></span>
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
            <h1 class="text-center text-white display-6">Cart</h1>
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pages</a></li>
                <li class="breadcrumb-item active text-white">Cart</li>
            </ol>
        </div>
        <!-- Single Page Header End -->


        <!-- Cart Page Start -->
        <div class="container-fluid py-5">
            <div class="container py-5">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                          <tr>
                            <th scope="col">Products</th>
                            <th scope="col">Name</th>
                            <th scope="col">Price</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Total</th>
                            <th scope="col">Handle</th>
                          </tr>
                        </thead>
                        <tbody>
                                <?php if (!empty($_SESSION["cart"])) {
                                    foreach ($_SESSION["cart"] as $product_id => $quantity) {

                                        $product = getProductDetailsById($connection, $product_id);

                                        $line_price = $product["discount_price"] * $quantity;
                                        ?>
                                <tr>
                                    <th scope="row">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars(
                                                "img/" . $product["image_link"]
                                            ); ?>" class="img-fluid me-5 rounded-circle" style="width: 80px; height: 80px;" alt="">
                                        </div>
                                    </th>
                                    <td>
                                        <p class="mb-0 mt-4"><?php echo htmlspecialchars(
                                            $product["product_name"]
                                        ); ?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 mt-4"><?php echo htmlspecialchars(
                                            $product["discount_price"]
                                        ); ?> $</p>
                                    </td>
                                    <td>
                                        <div class="input-group quantity mt-4" style="width: 100px;">
                                            <div class="input-group-btn">
                                            <a href="#" data-product-id="<?php echo $product_id; ?>" class="btn btn-sm btn-minus rounded-circle bg-light border">
                            <i class="fa fa-minus"></i>
                        </a>
                                            </div>
                                            <input type="text" class="form-control form-control-sm text-center border-0" value="<?php echo $quantity; ?>">
                                            <div class="input-group-btn">
                                            <a href="#" data-product-id="<?php echo $product_id; ?>" class="btn btn-sm btn-plus rounded-circle bg-light border">
                            <i class="fa fa-plus"></i>
                        </a>
                    </div>
                </div>
            </td>
            <td>
                <p class="mb-0 mt-4"><?php echo $line_price; ?> VND</p>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                    <!-- Link để xóa sản phẩm khỏi giỏ hàng -->
                    <a href="cart.php?remove_product=<?php echo $product_id; ?>" class="btn btn-danger">Remove</a>
                </td>
                        </tr>
                        <?php
                            }
                        } ?>
                    </tbody>
                    </table>
                </div>
                <div class="mt-5">
    <form action="cart.php" method="post">
        <input type="text" name="coupon_code" class="border-0 border-bottom rounded me-5 py-3 mb-4" placeholder="Coupon Code">
        <button class="btn border-secondary rounded-pill px-4 py-3 text-primary" type="submit" name="apply_coupon">Apply Coupon</button>
    </form>
</div>

                <div class="row g-4 justify-content-end">
                    <div class="col-8"></div>
                    <div class="col-sm-8 col-md-7 col-lg-6 col-xl-4">
                        <div class="bg-light rounded">
                            <div class="p-4">
                                <h1 class="display-6 mb-4">Cart <span class="fw-normal">Total</span></h1>
                                <div class="d-flex justify-content-between mb-4">
                                    <h5 class="mb-0 me-4">Subtotal:</h5>
                                    <p class="mb-0"><?php echo $subtotal; ?> VND</p>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <h5 class="mb-0 me-4">Coupon</h5>
                                    <div class="">
                                    <p class="mb-0">-<?php echo isset($discount) ? $discount : 0; ?> VND</p>
                                    </div>
                                </div>
                                                            </div>
                                                            <div class="py-4 mb-4 border-top border-bottom d-flex justify-content-between">
                                                <h5 class="mb-0 ps-4 me-4">Total</h5>
                                                <p class="mb-0 pe-4"> <?php echo isset($total) ? $total : 0; ?>
                                VND</p>
                                            </div>
                                            <form action="chackout.php" method="post">
                                            <?php if (isset($_SESSION["cart"]) && !empty($_SESSION["cart"])): ?>
                                    <?php foreach ($_SESSION["cart"] as $product_id => $quantity): ?>
                                        <input type="hidden" name="cart[<?php echo $product_id; ?>]" value="<?php echo $quantity; ?>">
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <input type="hidden" name="proceed_checkout" value="1">

                                <button class="btn border-secondary rounded-pill px-4 py-3 text-primary text-uppercase mb-4 ms-4" type="submit">Proceed Checkout</button>
                            </form>                  
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        <!-- Cart Page End -->


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
                    <div class="col-md-6 my-auto text-center text-md-end text-white">
                        <!--/*** This template is free as long as you keep the below author’s credit link/attribution link/backlink. ***/-->
                        <!--/*** If you'd like to use the template without the below author’s credit link/attribution link/backlink, ***/-->
                        <!--/*** you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a> Distributed By <a class="border-bottom" href="https://themewagon.com">ThemeWagon</a>
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
    <script>
// Xử lý sự kiện khi nút tăng/giảm được nhấn
$('.quantity .btn-minus, .quantity .btn-plus').on('click', function (e) {
    e.preventDefault(); // Ngăn không cho trang reload khi nhấn
    var button = $(this);
    var oldValue = button.closest('.quantity').find('input').val();
    var productId = button.data('product-id'); // Lấy product_id từ nút được nhấn
    var newVal = 0;

    if (button.hasClass('btn-plus')) {
        newVal = parseFloat(oldValue) + 1;
    } else {
        // Chỉ cho phép giảm nếu giá trị lớn hơn 1
        if (oldValue > 1) {
            newVal = parseFloat(oldValue) - 1;
        } else {
            newVal = 1; // Nếu số lượng là 1, không giảm xuống 0 nữa mà giữ nguyên ở 1
        }
    }

    button.closest('.quantity').find('input').val(newVal);

    // Cập nhật số lượng sản phẩm trong giỏ hàng bằng AJAX
    $.ajax({
        url: 'cart.php', // Điểm endpoint để xử lý logic PHP
        method: 'GET', // Sử dụng phương thức GET cho ví dụ này
        data: {product_id: productId, new_value: newVal}, // Gửi thông tin id và số lượng mới đến server
        success: function(response) {
            // Xử lý sau khi response được trả về từ server
            // Bạn có thể cập nhật thông tin tại đây
            
        }
    });
});
</script>

    <script src="js/main.js"></script>
    </body>

</html>