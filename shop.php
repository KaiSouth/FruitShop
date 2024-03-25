<?php
session_start();
require 'database.php';


function getProductsBySearch($connection, $searchValue) {
    $searchValue = mysqli_real_escape_string($connection, $searchValue);
    $sql = "SELECT p.product_name, p.description, p.image_link, p.price, p.rating, c.category_name, p.product_id
            FROM product AS p
            JOIN category AS c ON p.category_id = c.category_id
            WHERE p.product_name LIKE ?";
    $stmt = mysqli_prepare($connection, $sql);
    $like_search = '%' . $searchValue . '%';
    mysqli_stmt_bind_param($stmt, 's', $like_search);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $products;
}

function getMaxPrice($connection) {
    $sql = "SELECT MAX(price) as max_price FROM product";
    $result = mysqli_query($connection, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['max_price'];
}

function getAllProducts($connection) {
    $sql = "SELECT p.product_name, p.description, p.image_link, p.price, p.rating, c.category_name, p.product_id
            FROM product p
            JOIN category c ON p.category_id = c.category_id";
    $result = mysqli_query($connection, $sql);
    $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
    return $products;
}

function getAllCategories($connection) {
    $sql = "SELECT * FROM category";
    $result = mysqli_query($connection, $sql);
    $categories = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }

    return $categories;
}

function getTopRatedProducts($connection) {
    $sql = "SELECT p.product_name, p.discount_price, p.image_link, p.price, p.rating, c.category_name, p.product_id
            FROM product AS p
            JOIN category AS c ON p.category_id = c.category_id
            ORDER BY p.rating DESC
            LIMIT 3";
    $result = mysqli_query($connection, $sql);
    $products = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    return $products;
}



function getPaginatedSortedProducts($connection, $start, $productsPerPage, $searchValue, $sortOption) {
    $sql = "SELECT p.product_name, p.description, p.image_link, p.price, p.rating, c.category_name, p.product_id
            FROM product p
            JOIN category c ON p.category_id = c.category_id ";

    if ($searchValue) {
        $sql .= "WHERE p.product_name LIKE ?";
    }

    switch ($sortOption) {
        case 'lowTOhigh':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'highTOlow':
            $sql .= " ORDER BY p.price DESC";
            break;
    }

    $sql .= " LIMIT ?, ?";

    $stmt = mysqli_prepare($connection, $sql);

    if ($searchValue) {
        $like_search = '%' . mysqli_real_escape_string($connection, $searchValue) . '%';
        mysqli_stmt_bind_param($stmt, 'sii', $like_search, $start, $productsPerPage);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $start, $productsPerPage);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $products;
}

function getTotalProducts($connection, $searchValue = null) {
    $sql = "SELECT COUNT(*) as total FROM product p ";
    if ($searchValue) {
        $like_search = '%' . mysqli_real_escape_string($connection, $searchValue) . '%';
        $sql .= "WHERE product_name LIKE '{$like_search}' ";
    }
    $result = mysqli_query($connection, $sql);
    $totalRow = mysqli_fetch_assoc($result);
    return intval($totalRow['total']);
}

function getProductsByCategoryId($connection, $category_id, $start, $productsPerPage, $sortOption) {
    $sql = "SELECT p.product_name, p.description, p.image_link, p.price, p.rating, c.category_name, p.product_id
            FROM product p
            INNER JOIN category c ON p.category_id = c.category_id
            WHERE p.category_id = ?";

    switch ($sortOption) {
        case 'lowTOhigh':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'highTOlow':
            $sql .= " ORDER BY p.price DESC";
            break;
    }
    $sql .= " LIMIT ?, ?";
    $stmt = mysqli_prepare($connection, $sql);
    if ($stmt === false) {
        throw new Exception(mysqli_error($connection));
    }
    
    mysqli_stmt_bind_param($stmt, 'iii', $category_id, $start, $productsPerPage);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result === false) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    
    $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    return $products;
}


$productsPerPage = 6; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; 
$start = ($page - 1) * $productsPerPage;
$totalProducts = getTotalProducts($connection, $searchValue);
$totalPages = ceil($totalProducts / $productsPerPage);
$searchValue = isset($_GET['search']) ? trim($_GET['search']) : null; // Lấy từ khóa tìm kiếm
$category_id = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? intval($_GET['category_id']) : null;
$sortOption = isset($_GET['sortOption']) ? $_GET['sortOption'] : '';
$products = [];
$categories = getAllCategories($connection);
$topRatedProducts = getTopRatedProducts($connection);
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

if (!is_null($searchValue) && $searchValue !== '') {
    $products = getProductsBySearch($connection, $searchValue);
} elseif (!is_null($category_id)) {
    $products = getProductsByCategoryId($connection, $category_id, $start, $productsPerPage, $sortOption);
} else {
    $products = getPaginatedSortedProducts($connection, $start, $productsPerPage, null, $sortOption);
}

// Đóng kết nối database
mysqli_close($connection);
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
                            <a href="shop.php" class="nav-item nav-link active">Shop</a>
                            <!-- <a href="shop-detail.php" class="nav-item nav-link">Shop Detail</a> -->
                            <!-- <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu m-0 bg-secondary rounded-0">
                                    <a href="cart.php" class="dropdown-item">Cart</a>
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
        <form action="shop.php" method="get">
        <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Search by keyword</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                  
                 
                    <div class="modal-body d-flex align-items-center">
                        <div class="input-group w-75 mx-auto d-flex">
                            <input name="search" type="text" class="form-control p-3" placeholder="keywords" aria-describedby="search-icon-1">
                            <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
        <!-- Modal Search End -->


        <!-- Single Page Header start -->
        <div class="container-fluid page-header py-5">
            <h1 class="text-center text-white display-6">Shop</h1>
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pages</a></li>
                <li class="breadcrumb-item active text-white">Shop</li>
            </ol>
        </div>
        <!-- Single Page Header End -->


        <!-- Fruits Shop Start-->
        <div class="container-fluid fruite py-5">
            <div class="container py-5">
                <h1 class="mb-4">Fresh fruits shop</h1>

                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="row g-4">
                            <div class="col-xl-3">
                                <form action="shop.php" method="get">
                                <div class="input-group w-100 mx-auto d-flex">
                                    <input  type="text" 
                                    name="search" 
                                    placeholder="Enter Product Name" class="form-control p-3" aria-describedby="search-icon-1">
                                    <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                                </div>
                                </form>
                                
                            </div>
                            <div class="col-6"></div>
                            <div class="col-xl-3">
                               <!-- The form defines where to send the data on submit (it will send the data to the same page in this case). -->
                               <form action="" method="get" id="sortForm">
                               <div class="bg-light ps-3 py-3 rounded d-flex justify-content-between mb-4">
    <label for="fruits">Sắp xếp mặc định:</label>
    <select id="fruits" name="sortOption" class="border-0 form-select-sm bg-light me-3" onchange="this.form.submit()">
        <option value="" <?php echo (!isset($_GET['sortOption']) || $_GET['sortOption'] == '') ? 'selected' : ''; ?>>Không sắp xếp</option>
        <option value="lowTOhigh" <?php echo (isset($_GET['sortOption']) && $_GET['sortOption'] == 'lowTOhigh') ? 'selected' : ''; ?>>Thấp đến cao</option>
        <option value="highTOlow" <?php echo (isset($_GET['sortOption']) && $_GET['sortOption'] == 'highTOlow') ? 'selected' : ''; ?>>Cao đến thấp</option>
    </select>
</div>
</form>
                            </div>
                        </div>
                        <div class="row g-4">
                            <div class="col-lg-3">
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <h4>Categories</h4>
                                      <!-- Kiểm tra xem mảng $categories có phần tử nào không -->
<?php if (!empty($categories)): ?>
    <ul class="list-unstyled category-list">
        <?php foreach ($categories as $category): ?> <!-- Lặp qua từng danh mục và hiển thị thông tin -->
            <li>
                <!-- Hiển thị tên danh mục và ID. Bạn có thể thêm liên kết tới trang danh mục nếu cần -->
                <a href="shop.php?category_id=<?php echo $category['category_id']; ?>">
                    <?php echo htmlspecialchars($category['category_name']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?> <!-- Hiển thị thông báo nếu không có danh mục nào -->
    <p>There are no categories available.</p>
<?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <h4 class="mb-2">Price</h4>
                                            <input type="range" class="form-range w-100" id="rangeInput" name="rangeInput" min="0" max="500" value="0" oninput="amount.value=rangeInput.value">
                                            <output id="amount" name="amount" min-velue="0" max-value="500" for="rangeInput">0</output>
                                        </div>
                                    </div>
                                    <!-- <div class="col-lg-12">
                                        <div class="mb-3">
                                            <h4>Additional</h4>
                                            <div class="mb-2">
                                                <input type="radio" class="me-2" id="Categories-1" name="Categories-1" value="Beverages">
                                                <label for="Categories-1"> Organic</label>
                                            </div>
                                            <div class="mb-2">
                                                <input type="radio" class="me-2" id="Categories-2" name="Categories-1" value="Beverages">
                                                <label for="Categories-2"> Fresh</label>
                                            </div>
                                            <div class="mb-2">
                                                <input type="radio" class="me-2" id="Categories-3" name="Categories-1" value="Beverages">
                                                <label for="Categories-3"> Sales</label>
                                            </div>
                                            <div class="mb-2">
                                                <input type="radio" class="me-2" id="Categories-4" name="Categories-1" value="Beverages">
                                                <label for="Categories-4"> Discount</label>
                                            </div>
                                            <div class="mb-2">
                                                <input type="radio" class="me-2" id="Categories-5" name="Categories-1" value="Beverages">
                                                <label for="Categories-5"> Expired</label>
                                            </div>
                                        </div>
                                    </div> -->
                                    <div class="col-lg-12">
                                        <h4 class="mb-3">Featured products</h4>

                                        <?php foreach ($topRatedProducts as $product): ?>
    <div class="d-flex align-items-center justify-content-start mb-4"> <!-- Thêm lớp mb-4 cho khoảng cách dưới cùng -->
        <div class="rounded me-4" style="width: 100px; height: 100px;">
            <a href="shop-detail.php?product_id=<?php echo $product['product_id']; ?>">
                <img src="img/<?php echo $product['image_link']; ?>" class="img-fluid rounded" alt="<?php echo $product['product_name']; ?>">
            </a>
        </div>
        <div>
            <h6 class="mb-2"><?php echo $product['product_name']; ?></h6>
            <div class="d-flex mb-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php if ($i <= $product['rating']): ?>
                        <i class="fa fa-star text-secondary"></i>
                    <?php else: ?>
                        <i class="fa fa-star"></i>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <div class="d-flex mb-2">
                <h5 class="fw-bold me-2"><?php echo $product['discount_price']; ?> $</h5>
                <?php if ($product['price']): ?>
                    <h5 class="text-danger text-decoration-line-through"><?php echo $product['price']; ?> $</h5>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>


                  
                                     
                                        <div class="d-flex justify-content-center my-4">
                                            <a href="shop.php" class="btn border border-secondary px-4 py-3 rounded-pill text-primary w-100">View More</a>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="position-relative">
                                            <img src="img/banner-fruits.jpg" class="img-fluid w-100 rounded" alt="">
                                            <div class="position-absolute" style="top: 50%; right: 10px; transform: translateY(-50%);">
                                                <h3 class="text-secondary fw-bold">Fresh <br> Fruits <br> Banner</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9">
                                <div class="row g-4 justify-content-center">

                                <?php if (!empty($products)): ?>
    <!-- Dùng vòng lặp foreach để đọc từng sản phẩm trong mảng $products -->
    <?php foreach ($products as $product): ?>
        <div class="col-md-6 col-lg-6 col-xl-4">
            <div class="rounded position-relative fruite-item">
                <!-- Link to product detail page wrapped on the image -->
                <a href="shop-detail.php?product_id=<?php echo $product['product_id']; ?>">
                    <div class="fruite-img">
                        <img src="img/<?php echo htmlspecialchars($product['image_link']); ?>" class="img-fluid w-100 rounded-top" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    </div>
                </a>
                <!-- Product category name display -->
                <div class="text-white bg-secondary px-3 py-1 rounded position-absolute" style="top: 10px; left: 10px;">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </div>
                <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                    <!-- ProductService name and description -->
                    <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="d-flex justify-content-between flex-lg-wrap">
                        <!-- Product price display -->
                        <p class="text-dark fs-5 fw-bold mb-0"><?php echo htmlspecialchars($product['price']); ?> / kg</p>
                        <!-- Link to add product to the shopping cart -->
                        <!-- Example: replace '#' with your specific add to cart path -->
                        <a href="shop.php?add_to_cart=<?php echo $product['product_id']; ?>" class="btn border border-secondary rounded-pill px-3 text-primary">
    <i class="fa fa-shopping-bag me-2 text-primary"></i> Add to cart
</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <!-- Display a message when there are no products found -->
    <p>No products found.</p>
<?php endif; ?>

                               
    <!-- Phần phân trang -->
<div class="col-12">
    <div class="pagination d-flex justify-content-center mt-5">
        <!-- Nút điều hướng Trang Trước -->
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&sortOption=<?php echo htmlspecialchars($sortOption); ?>" class="rounded">&laquo;</a>
        <?php endif; ?>

        <!-- Các nút số trang -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&sortOption=<?php echo htmlspecialchars($sortOption); ?>" class="rounded <?php echo $page == $i ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <!-- Nút điều hướng Trang Sau -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&sortOption=<?php echo htmlspecialchars($sortOption); ?>" class="rounded">&raquo;</a>
        <?php endif; ?>
    </div>
</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fruits Shop End-->


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
    <script src="js/main.js"></script>
        
    <script>
        // This script will trigger the form submission when the selection changes.
        document.getElementById('fruits').addEventListener('change', function() {
            document.getElementById('sortForm').submit();
        });
    </script>
    </body>

</html>