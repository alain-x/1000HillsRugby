<?php
require_once('header.php');

// Fetch settings
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$settings = $statement->fetch(PDO::FETCH_ASSOC);

// Define settings variables
$cta_title = $settings['cta_title'];
$cta_content = $settings['cta_content'];
$cta_read_more_text = $settings['cta_read_more_text'];
$cta_read_more_url = $settings['cta_read_more_url'];
$cta_photo = $settings['cta_photo'];
$featured_product_title = $settings['featured_product_title'];
$featured_product_subtitle = $settings['featured_product_subtitle'];
$latest_product_title = $settings['latest_product_title'];
$latest_product_subtitle = $settings['latest_product_subtitle'];
$popular_product_title = $settings['popular_product_title'];
$popular_product_subtitle = $settings['popular_product_subtitle'];
$total_featured_product_home = $settings['total_featured_product_home'];
$total_latest_product_home = $settings['total_latest_product_home'];
$total_popular_product_home = $settings['total_popular_product_home'];
$home_service_on_off = $settings['home_service_on_off'];
$home_welcome_on_off = $settings['home_welcome_on_off'];
$home_featured_product_on_off = $settings['home_featured_product_on_off'];
$home_latest_product_on_off = $settings['home_latest_product_on_off'];
$home_popular_product_on_off = $settings['home_popular_product_on_off'];
?>

<!-- Bootstrap Touch Slider -->
<div id="bootstrap-touch-slider" class="carousel bs-slider fade control-round indicators-line" data-ride="carousel" data-pause="hover" data-interval="false">
    <!-- Indicators -->
    <ol class="carousel-indicators">
        <?php
        $i = 0;
        $statement = $pdo->prepare("SELECT * FROM tbl_slider");
        $statement->execute();
        $sliders = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sliders as $slider) {
            echo '<li data-target="#bootstrap-touch-slider" data-slide-to="' . $i . '"' . ($i == 0 ? ' class="active"' : '') . '></li>';
            $i++;
        }
        ?>
    </ol>

    <!-- Wrapper For Slides -->
    <div class="carousel-inner" role="listbox">
        <?php
        $i = 0;
        foreach ($sliders as $slider) {
            echo '<div class="item' . ($i == 0 ? ' active' : '') . '" style="background-image:url(assets/uploads/' . $slider['photo'] . ');">';
            echo '<div class="bs-slider-overlay"></div>';
            echo '<div class="container">';
            echo '<div class="row">';
            echo '<div class="slide-text ' . ($slider['position'] == 'Left' ? 'slide_style_left' : ($slider['position'] == 'Center' ? 'slide_style_center' : 'slide_style_right')) . '">';
            echo '<h1 data-animation="animated ' . ($slider['position'] == 'Left' ? 'zoomInLeft' : ($slider['position'] == 'Center' ? 'flipInX' : 'zoomInRight')) . '">' . $slider['heading'] . '</h1>';
            echo '<p data-animation="animated ' . ($slider['position'] == 'Left' ? 'fadeInLeft' : ($slider['position'] == 'Center' ? 'fadeInDown' : 'fadeInRight')) . '">' . nl2br($slider['content']) . '</p>';
            echo '<a href="' . $slider['button_url'] . '" target="_blank" class="btn btn-primary" data-animation="animated ' . ($slider['position'] == 'Left' ? 'fadeInLeft' : ($slider['position'] == 'Center' ? 'fadeInDown' : 'fadeInRight')) . '">' . $slider['button_text'] . '</a>';
            echo '</div></div></div></div>';
            $i++;
        }
        ?>
    </div>

    <!-- Slider Controls -->
    <a class="left carousel-control" href="#bootstrap-touch-slider" role="button" data-slide="prev">
        <span class="fa fa-angle-left" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="right carousel-control" href="#bootstrap-touch-slider" role="button" data-slide="next">
        <span class="fa fa-angle-right" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>
</div>

<!-- Services Section -->
<?php if ($home_service_on_off == 1) : ?>
    <div class="service bg-gray">
        <div class="container">
            <div class="row">
                <?php
                $statement = $pdo->prepare("SELECT * FROM tbl_service");
                $statement->execute();
                $services = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($services as $service) {
                    echo '<div class="col-md-4">';
                    echo '<div class="item">';
                    echo '<div class="photo"><img src="assets/uploads/' . $service['photo'] . '" width="150px" alt="' . $service['title'] . '"></div>';
                    echo '<h3>' . $service['title'] . '</h3>';
                    echo '<p>' . nl2br($service['content']) . '</p>';
                    echo '</div></div>';
                }
                ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Featured Products Section -->
<?php if ($home_featured_product_on_off == 1) : ?>
    <div class="product pt_70 pb_70">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="headline">
                        <h2><?php echo $featured_product_title; ?></h2>
                        <h3><?php echo $featured_product_subtitle; ?></h3>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="product-carousel">
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_featured=? AND p_is_active=? LIMIT " . $total_featured_product_home);
                        $statement->execute([1, 1]);
                        $products = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($products as $product) {
                            echo '<div class="item">';
                            echo '<div class="thumb">';
                            echo '<div class="photo" style="background-image:url(assets/uploads/' . $product['p_featured_photo'] . ');"></div>';
                            echo '</div>';
                            echo '<div class="text">';
                            echo '<h3><a href="product.php?id=' . $product['p_id'] . '">' . $product['p_name'] . '</a></h3>';
                            echo '<h4>RWF ' . $product['p_current_price'];
                            if (!empty($product['p_old_price'])) {
                                echo '<del> ' . $product['p_old_price'] . '</del>';
                            }
                            echo '</h4>';
                            echo '<div class="rating">';
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
                            }
                            echo '</div>';
                            if ($product['p_qty'] == 0) {
                                echo '<div class="out-of-stock"><div class="inner">Out Of Stock</div></div>';
                            } else {
                                echo '<p><a href="product.php?id=' . $product['p_id'] . '" class="add-to-cart-button" style="background-color: #ff6600; border-radius:20px; border-color: #ff6600;"><i class="fa fa-shopping-cart"></i> Add to Cart</a></p>';
                            }
                            echo '</div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Latest Products Section -->
<?php if ($home_latest_product_on_off == 1) : ?>
    <div class="product bg-gray pt_70 pb_30">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="headline">
                        <h2><?php echo $latest_product_title; ?></h2>
                        <h3><?php echo $latest_product_subtitle; ?></h3>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="product-carousel">
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_active=? ORDER BY p_id DESC LIMIT " . $total_latest_product_home);
                        $statement->execute([1]);
                        $products = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($products as $product) {
                            echo '<div class="item">';
                            echo '<div class="thumb">';
                            echo '<div class="photo" style="background-image:url(assets/uploads/' . $product['p_featured_photo'] . ');"></div>';
                            echo '</div>';
                            echo '<div class="text">';
                            echo '<h3><a href="product.php?id=' . $product['p_id'] . '">' . $product['p_name'] . '</a></h3>';
                            echo '<h4>RWF ' . $product['p_current_price'];
                            if (!empty($product['p_old_price'])) {
                                echo '<del> ' . $product['p_old_price'] . '</del>';
                            }
                            echo '</h4>';
                            echo '<div class="rating">';
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
                            }
                            echo '</div>';
                            if ($product['p_qty'] == 0) {
                                echo '<div class="out-of-stock"><div class="inner">Out Of Stock</div></div>';
                            } else {
                                echo '<p><a href="product.php?id=' . $product['p_id'] . '" class="add-to-cart-button" style="background-color: #ff6600; border-radius:20px; border-color: #ff6600;"><i class="fa fa-shopping-cart"></i> Add to Cart</a></p>';
                            }
                            echo '</div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Popular Products Section -->
<?php if ($home_popular_product_on_off == 1) : ?>
    <div class="product pt_70 pb_70">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="headline">
                        <h2><?php echo $popular_product_title; ?></h2>
                        <h3><?php echo $popular_product_subtitle; ?></h3>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="product-carousel">
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_is_active=? ORDER BY p_total_view DESC LIMIT " . $total_popular_product_home);
                        $statement->execute([1]);
                        $products = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($products as $product) {
                            echo '<div class="item">';
                            echo '<div class="thumb">';
                            echo '<div class="photo" style="background-image:url(assets/uploads/' . $product['p_featured_photo'] . ');"></div>';
                            echo '</div>';
                            echo '<div class="text">';
                            echo '<h3><a href="product.php?id=' . $product['p_id'] . '">' . $product['p_name'] . '</a></h3>';
                            echo '<h4>RWF ' . $product['p_current_price'];
                            if (!empty($product['p_old_price'])) {
                                echo '<del> ' . $product['p_old_price'] . '</del>';
                            }
                            echo '</h4>';
                            echo '<div class="rating">';
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
                            }
                            echo '</div>';
                            if ($product['p_qty'] == 0) {
                                echo '<div class="out-of-stock"><div class="inner">Out Of Stock</div></div>';
                            } else {
                                echo '<p><a href="product.php?id=' . $product['p_id'] . '" class="add-to-cart-button" style="background-color: #ff6600; border-radius:20px; border-color: #ff6600;"><i class="fa fa-shopping-cart"></i> Add to Cart</a></p>';
                            }
                            echo '</div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once('footer.php'); ?>