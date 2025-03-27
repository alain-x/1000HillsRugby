<?php
require_once('header.php');

// Fetch settings
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$settings = $statement->fetch(PDO::FETCH_ASSOC);

// Extract settings
extract($settings);

// Define constants for better readability
define('CURRENCY', 'RWF');

// Function to truncate long product names
function truncateProductName($name, $maxLength = 50) {
    if (strlen($name) > $maxLength) {
        return substr($name, 0, $maxLength) . '...';
    }
    return $name;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $meta_title ? $meta_title : 'Allbaba.com - Your Business Marketplace'; ?></title>
    <meta name="description" content="<?php echo $meta_description ? $meta_description : 'Allbaba.com - Premium marketplace for business products and services'; ?>">
    
    <style>
        :root {
            --primary-color: #ff6600;
            --primary-dark: #e65c00;
            --secondary-color: #333;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #6c757d;
            --white: #ffffff;
            --danger: #dc3545;
            --success: #28a745;
            --transition: all 0.3s ease;
            --border-radius: 20px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --box-shadow-hover: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        
        /* Base Typography */
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--secondary-color);
            background-color: #f5f5f5;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            margin-top: 0;
        }
        
        a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        a:hover {
            color: var(--primary-dark);
        }
        
        /* Utility Classes */
        .container {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }
        
        @media (min-width: 576px) {
            .container {
                max-width: 540px;
            }
        }
        
        @media (min-width: 768px) {
            .container {
                max-width: 720px;
            }
        }
        
        @media (min-width: 992px) {
            .container {
                max-width: 960px;
            }
        }
        
        @media (min-width: 1200px) {
            .container {
                max-width: 1140px;
            }
        }
        
        .py-4 {
            padding-top: 1.5rem;
            padding-bottom: 1.5rem;
        }
        
        .py-5 {
            padding-top: 3rem;
            padding-bottom: 3rem;
        }
        
        .bg-light {
            background-color: var(--light-gray) !important;
        }
        
        .bg-white {
            background-color: var(--white) !important;
        }
        
        .text-center {
            text-align: center !important;
        }
        
        /* Product Grid System */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        @media (min-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (min-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
            }
        }
        
        @media (min-width: 1200px) {
            .product-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }
        
        /* Professional Product Cards */
        .product-card {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            background: var(--white);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .product-thumb {
            position: relative;
            padding-top: 100%; /* 1:1 Aspect Ratio */
            background-color: var(--light-gray);
        }
        
        .product-thumb img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 15px;
        }
        
        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            z-index: 1;
        }
        
        .product-badge.new {
            background: var(--success);
        }
        
        .product-badge.hot {
            background: var(--danger);
        }
        
        .product-details {
            padding: 15px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .product-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #0066cc; 
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 50px;
        }
        
        /* This fixes the color issue by specifically targeting links inside product titles */
        .product-title a {
            color: #0066cc !important;
        }
        
        .product-title a:hover {
            color: #004499 !important;
        }
        
        .product-price {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .current-price {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-color);
            margin-right: 8px;
        }
        
        .old-price {
            font-size: 13px;
            color: var(--dark-gray);
            text-decoration: line-through;
        }
        
        .product-rating {
            color: #ffc107;
            margin-bottom: 12px;
            font-size: 13px;
        }
        
        .product-actions {
            margin-top: auto;
        }
        
        .btn {
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            line-height: 1.5;
            border-radius: var(--border-radius);
            transition: var(--transition);
            cursor: pointer;
            width: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            color: var(--white);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }
        
        /* Modern Slider */
        .hero-slider {
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }
        
        .hero-slide {
            height: 300px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .hero-content {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: var(--border-radius);
            color: white;
            max-width: 500px;
        }
        
        .hero-content h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: white;
        }
        
        .hero-content p {
            margin-bottom: 15px;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        /* Section Styling */
        .section {
            position: relative;
            padding: 3rem 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .section-title h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .section-title h3 {
            font-size: 1.1rem;
            color: var(--dark-gray);
            font-weight: 400;
        }
        
        /* Services Section */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 20px;
        }
        
        @media (min-width: 768px) {
            .services-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        .service-card {
            text-align: center;
            padding: 30px 20px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            background: var(--white);
            box-shadow: var(--box-shadow);
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
        }
        
        .service-card h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        
        /* Carousel Controls */
        .carousel-control-prev,
        .carousel-control-next {
            width: 40px;
            height: 40px;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.8;
        }
        
        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            opacity: 1;
        }
        
        /* Responsive Adjustments */
        @media (min-width: 768px) {
            .hero-slide {
                height: 400px;
            }
            
            .hero-content {
                left: 40px;
                bottom: 40px;
            }
            
            .hero-content h1 {
                font-size: 2.2rem;
            }
        }
        
        @media (min-width: 992px) {
            .hero-slide {
                height: 500px;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Modern Hero Slider -->
<div id="heroSlider" class="carousel slide hero-slider" data-ride="carousel">
    <ol class="carousel-indicators">
        <?php
        $i = 0;
        $statement = $pdo->prepare("SELECT * FROM tbl_slider");
        $statement->execute();
        $sliders = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sliders as $slider) {
            echo '<li data-target="#heroSlider" data-slide-to="' . $i . '"' . ($i == 0 ? ' class="active"' : '') . '></li>';
            $i++;
        }
        ?>
    </ol>
    
    <div class="carousel-inner">
        <?php
        $i = 0;
        foreach ($sliders as $slider) {
            echo '<div class="carousel-item' . ($i == 0 ? ' active' : '') . '">';
            echo '<div class="hero-slide" style="background-image: url(assets/uploads/' . $slider['photo'] . ');">';
            echo '<div class="hero-content">';
            echo '<h1>' . $slider['heading'] . '</h1>';
            echo '<p>' . nl2br($slider['content']) . '</p>';
            echo '<a href="' . $slider['button_url'] . '" class="btn btn-primary">' . $slider['button_text'] . '</a>';
            echo '</div></div></div>';
            $i++;
        }
        ?>
    </div>
    
    <a class="carousel-control-prev" href="#heroSlider" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#heroSlider" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>
</div>

<!-- Services Section -->
<?php if ($home_service_on_off == 1) : ?>
<section class="section bg-light">
    <div class="container">
        <div class="services-grid">
            <?php
            $statement = $pdo->prepare("SELECT * FROM tbl_service");
            $statement->execute();
            $services = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($services as $service) {
                echo '<div class="service-card">';
                echo '<div class="service-icon"><img src="assets/uploads/' . $service['photo'] . '" alt="' . $service['title'] . '" class="img-fluid"></div>';
                echo '<h3>' . $service['title'] . '</h3>';
                echo '<p>' . nl2br($service['content']) . '</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Products Section -->
<?php if ($home_featured_product_on_off == 1) : ?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2><?php echo $featured_product_title; ?></h2>
            <h3><?php echo $featured_product_subtitle; ?></h3>
        </div>
        
        <div class="product-grid">
            <?php
            $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_featured=? AND p_is_active=? LIMIT " . $total_featured_product_home);
            $statement->execute([1, 1]);
            $products = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $product) {
                $truncatedName = truncateProductName($product['p_name']);
                echo '<div class="product-card">';
                echo '<div class="product-thumb">';
                echo '<img src="assets/uploads/' . $product['p_featured_photo'] . '" alt="' . $product['p_name'] . '">';
                echo '<div class="product-badge">Featured</div>';
                echo '</div>';
                echo '<div class="product-details">';
                echo '<h3 class="product-title" title="' . htmlspecialchars($product['p_name']) . '"><a href="product.php?id=' . $product['p_id'] . '">' . $truncatedName . '</a></h3>';
                echo '<div class="product-price">';
                echo '<span class="current-price">' . CURRENCY . ' ' . number_format($product['p_current_price'], 0) . '</span>';
                if (!empty($product['p_old_price']) && $product['p_old_price'] > $product['p_current_price']) {
                    echo '<span class="old-price">' . CURRENCY . ' ' . number_format($product['p_old_price'], 0) . '</span>';
                }
                echo '</div>';
                echo '<div class="product-rating">';
                $t_rating = 0;
                $statement1 = $pdo->prepare("SELECT * FROM tbl_rating WHERE p_id=?");
                $statement1->execute([$product['p_id']]);
                $tot_rating = $statement1->rowCount();
                if ($tot_rating > 0) {
                    $ratings = $statement1->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($ratings as $rating) {
                        $t_rating += $rating['rating'];
                    }
                    $avg_rating = $t_rating / $tot_rating;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $avg_rating) {
                            echo '<i class="fa fa-star"></i>';
                        } else {
                            echo '<i class="fa fa-star-o"></i>';
                        }
                    }
                    echo ' <span>(' . $tot_rating . ')</span>';
                } else {
                    echo '<span>No reviews yet</span>';
                }
                echo '</div>';
                echo '<div class="product-actions">';
                if ($product['p_qty'] == 0) {
                    echo '<span class="btn btn-danger">Out Of Stock</span>';
                } else {
                    echo '<a href="product.php?id=' . $product['p_id'] . '" class="btn btn-primary"><i class="fa fa-shopping-cart"></i> Add to Cart</a>';
                }
                echo '</div></div></div>';
            }
            ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Latest Products Section -->
<?php if ($home_latest_product_on_off == 1) : ?>
<section class="section bg-light">
    <div class="container">
        <div class="section-title">
            <h2><?php echo $latest_product_title; ?></h2>
            <h3><?php echo $latest_product_subtitle; ?></h3>
        </div>
        
        <div class="product-grid">
            <?php
            $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_active=? ORDER BY p_id DESC LIMIT " . $total_latest_product_home);
            $statement->execute([1]);
            $products = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $product) {
                $truncatedName = truncateProductName($product['p_name']);
                echo '<div class="product-card">';
                echo '<div class="product-thumb">';
                echo '<img src="assets/uploads/' . $product['p_featured_photo'] . '" alt="' . $product['p_name'] . '">';
                echo '<div class="product-badge new">New</div>';
                echo '</div>';
                echo '<div class="product-details">';
                echo '<h3 class="product-title" title="' . htmlspecialchars($product['p_name']) . '"><a href="product.php?id=' . $product['p_id'] . '">' . $truncatedName . '</a></h3>';
                echo '<div class="product-price">';
                echo '<span class="current-price">' . CURRENCY . ' ' . number_format($product['p_current_price'], 0) . '</span>';
                if (!empty($product['p_old_price']) && $product['p_old_price'] > $product['p_current_price']) {
                    echo '<span class="old-price">' . CURRENCY . ' ' . number_format($product['p_old_price'], 0) . '</span>';
                }
                echo '</div>';
                echo '<div class="product-rating">';
                $t_rating = 0;
                $statement1 = $pdo->prepare("SELECT * FROM tbl_rating WHERE p_id=?");
                $statement1->execute([$product['p_id']]);
                $tot_rating = $statement1->rowCount();
                if ($tot_rating > 0) {
                    $ratings = $statement1->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($ratings as $rating) {
                        $t_rating += $rating['rating'];
                    }
                    $avg_rating = $t_rating / $tot_rating;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $avg_rating) {
                            echo '<i class="fa fa-star"></i>';
                        } else {
                            echo '<i class="fa fa-star-o"></i>';
                        }
                    }
                    echo ' <span>(' . $tot_rating . ')</span>';
                } else {
                    echo '<span>No reviews yet</span>';
                }
                echo '</div>';
                echo '<div class="product-actions">';
                if ($product['p_qty'] == 0) {
                    echo '<span class="btn btn-danger">Out Of Stock</span>';
                } else {
                    echo '<a href="product.php?id=' . $product['p_id'] . '" class="btn btn-primary"><i class="fa fa-shopping-cart"></i> Add to Cart</a>';
                }
                echo '</div></div></div>';
            }
            ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Popular Products Section -->
<?php if ($home_popular_product_on_off == 1) : ?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2><?php echo $popular_product_title; ?></h2>
            <h3><?php echo $popular_product_subtitle; ?></h3>
        </div>
        
        <div class="product-grid">
            <?php
            $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_active=? ORDER BY p_total_view DESC LIMIT " . $total_popular_product_home);
            $statement->execute([1]);
            $products = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $product) {
                $truncatedName = truncateProductName($product['p_name']);
                echo '<div class="product-card">';
                echo '<div class="product-thumb">';
                echo '<img src="assets/uploads/' . $product['p_featured_photo'] . '" alt="' . $product['p_name'] . '">';
                echo '<div class="product-badge hot">Hot</div>';
                echo '</div>';
                echo '<div class="product-details">';
                echo '<h3 class="product-title" title="' . htmlspecialchars($product['p_name']) . '"><a href="product.php?id=' . $product['p_id'] . '">' . $truncatedName . '</a></h3>';
                echo '<div class="product-price">';
                echo '<span class="current-price">' . CURRENCY . ' ' . number_format($product['p_current_price'], 0) . '</span>';
                if (!empty($product['p_old_price']) && $product['p_old_price'] > $product['p_current_price']) {
                    echo '<span class="old-price">' . CURRENCY . ' ' . number_format($product['p_old_price'], 0) . '</span>';
                }
                echo '</div>';
                echo '<div class="product-rating">';
                $t_rating = 0;
                $statement1 = $pdo->prepare("SELECT * FROM tbl_rating WHERE p_id=?");
                $statement1->execute([$product['p_id']]);
                $tot_rating = $statement1->rowCount();
                if ($tot_rating > 0) {
                    $ratings = $statement1->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($ratings as $rating) {
                        $t_rating += $rating['rating'];
                    }
                    $avg_rating = $t_rating / $tot_rating;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $avg_rating) {
                            echo '<i class="fa fa-star"></i>';
                        } else {
                            echo '<i class="fa fa-star-o"></i>';
                        }
                    }
                    echo ' <span>(' . $tot_rating . ')</span>';
                } else {
                    echo '<span>No reviews yet</span>';
                }
                echo '</div>';
                echo '<div class="product-actions">';
                if ($product['p_qty'] == 0) {
                    echo '<span class="btn btn-danger">Out Of Stock</span>';
                } else {
                    echo '<a href="product.php?id=' . $product['p_id'] . '" class="btn btn-primary"><i class="fa fa-shopping-cart"></i> Add to Cart</a>';
                }
                echo '</div></div></div>';
            }
            ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once('footer.php'); ?>