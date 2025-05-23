<?php
// Start output buffering and session
ob_start();
session_start();

// Include configuration and necessary files
include("admin/inc/config.php");
include("admin/inc/functions.php");
include("admin/inc/CSRF_Protect.php");

// Initialize CSRF protection
$csrf = new CSRF_Protect();

// Error and success messages
$error_message = '';
$success_message = '';
$error_message1 = '';
$success_message1 = '';

// Fetch language variables
$i = 1;
$statement = $pdo->prepare("SELECT * FROM tbl_language");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    define('LANG_VALUE_' . $i, $row['lang_value']);
    $i++;
}

// Fetch settings
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    $logo = $row['logo'];
    $favicon = $row['favicon'];
    $contact_email = $row['contact_email'];
    $contact_phone = $row['contact_phone'];
    $meta_title_home = $row['meta_title_home'];
    $meta_keyword_home = $row['meta_keyword_home'];
    $meta_description_home = $row['meta_description_home'];
    $before_head = $row['before_head'];
    $after_body = $row['after_body'];
}

// Fetch page data (for about, faq, contact titles)
$statement = $pdo->prepare("SELECT * FROM tbl_page WHERE id=1");
$statement->execute();
$page_data = $statement->fetch(PDO::FETCH_ASSOC);
$about_title = $page_data['about_title'];
$faq_title = $page_data['faq_title'];
$contact_title = $page_data['contact_title'];

// Clean up pending transactions older than 24 hours
$current_date_time = date('Y-m-d H:i:s');
$statement = $pdo->prepare("SELECT * FROM tbl_payment WHERE payment_status=?");
$statement->execute(['Pending']);
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    $ts1 = strtotime($row['payment_date']);
    $ts2 = strtotime($current_date_time);
    $diff = $ts2 - $ts1;
    $time = $diff / 3600;
    if ($time > 24) {
        // Return stock
        $statement1 = $pdo->prepare("SELECT * FROM tbl_order WHERE payment_id=?");
        $statement1->execute([$row['payment_id']]);
        $result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result1 as $row1) {
            $statement2 = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=?");
            $statement2->execute([$row1['product_id']]);
            $result2 = $statement2->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result2 as $row2) {
                $p_qty = $row2['p_qty'];
            }
            $final = $p_qty + $row1['quantity'];

            $statement = $pdo->prepare("UPDATE tbl_product SET p_qty=? WHERE p_id=?");
            $statement->execute([$final, $row1['product_id']]);
        }

        // Delete pending orders
        $statement1 = $pdo->prepare("DELETE FROM tbl_order WHERE payment_id=?");
        $statement1->execute([$row['payment_id']]);

        $statement1 = $pdo->prepare("DELETE FROM tbl_payment WHERE id=?");
        $statement1->execute([$row['id']]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Tags -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/uploads/<?php echo $favicon; ?>">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="assets/css/jquery.bxslider.min.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/rating.css">
    <link rel="stylesheet" href="assets/css/spacing.css">
    <link rel="stylesheet" href="assets/css/bootstrap-touch-slider.css">
    <link rel="stylesheet" href="assets/css/animate.min.css">
    <link rel="stylesheet" href="assets/css/tree-menu.css">
    <link rel="stylesheet" href="assets/css/select2.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">

    <?php
    // Fetch page meta data
    $statement = $pdo->prepare("SELECT * FROM tbl_page WHERE id=1");
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        $about_meta_title = $row['about_meta_title'];
        $about_meta_keyword = $row['about_meta_keyword'];
        $about_meta_description = $row['about_meta_description'];
        $faq_meta_title = $row['faq_meta_title'];
        $faq_meta_keyword = $row['faq_meta_keyword'];
        $faq_meta_description = $row['faq_meta_description'];
        $blog_meta_title = $row['blog_meta_title'];
        $blog_meta_keyword = $row['blog_meta_keyword'];
        $blog_meta_description = $row['blog_meta_description'];
        $contact_meta_title = $row['contact_meta_title'];
        $contact_meta_keyword = $row['contact_meta_keyword'];
        $contact_meta_description = $row['contact_meta_description'];
        $pgallery_meta_title = $row['pgallery_meta_title'];
        $pgallery_meta_keyword = $row['pgallery_meta_keyword'];
        $pgallery_meta_description = $row['pgallery_meta_description'];
        $vgallery_meta_title = $row['vgallery_meta_title'];
        $vgallery_meta_keyword = $row['vgallery_meta_keyword'];
        $vgallery_meta_description = $row['vgallery_meta_description'];
    }

    // Set page-specific meta tags
    $cur_page = basename($_SERVER['SCRIPT_NAME']);
    if (in_array($cur_page, ['index.php', 'login.php', 'registration.php', 'cart.php', 'checkout.php', 'forget-password.php', 'reset-password.php', 'product-category.php', 'product.php'])) {
        echo "<title>{$meta_title_home}</title>";
        echo "<meta name='keywords' content='{$meta_keyword_home}'>";
        echo "<meta name='description' content='{$meta_description_home}'>";
    } elseif ($cur_page == 'about.php') {
        echo "<title>{$about_meta_title}</title>";
        echo "<meta name='keywords' content='{$about_meta_keyword}'>";
        echo "<meta name='description' content='{$about_meta_description}'>";
    } elseif ($cur_page == 'faq.php') {
        echo "<title>{$faq_meta_title}</title>";
        echo "<meta name='keywords' content='{$faq_meta_keyword}'>";
        echo "<meta name='description' content='{$faq_meta_description}'>";
    } elseif ($cur_page == 'contact.php') {
        echo "<title>{$contact_meta_title}</title>";
        echo "<meta name='keywords' content='{$contact_meta_keyword}'>";
        echo "<meta name='description' content='{$contact_meta_description}'>";
    } elseif ($cur_page == 'product.php') {
        $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=?");
        $statement->execute([$_REQUEST['id']]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $og_photo = $row['p_featured_photo'];
            $og_title = $row['p_name'];
            $og_slug = 'product.php?id=' . $_REQUEST['id'];
            $og_description = substr(strip_tags($row['p_description']), 0, 200) . '...';
        }
        echo "<meta property='og:title' content='{$og_title}'>";
        echo "<meta property='og:type' content='website'>";
        echo "<meta property='og:url' content='" . BASE_URL . $og_slug . "'>";
        echo "<meta property='og:description' content='{$og_description}'>";
        echo "<meta property='og:image' content='assets/uploads/{$og_photo}'>";
    } elseif ($cur_page == 'dashboard.php') {
        echo "<title>Dashboard - {$meta_title_home}</title>";
        echo "<meta name='keywords' content='{$meta_keyword_home}'>";
        echo "<meta name='description' content='{$meta_description_home}'>";
    }
    ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
    <?php echo $before_head; ?>
</head>
<body>
<?php echo $after_body; ?>

<!-- Top Bar -->
<div class="top" style="background-color: #ff6600;">
    <div class="container" >
        <div class="row">
            <div class="col-md-6 col-sm-6 col-xs-12">
                <div class="left">
                    <ul>
                        <li><i class="fa fa-phone"></i> <?php echo $contact_phone; ?></li>
                        <li><i class="fa fa-envelope-o"></i> <?php echo $contact_email; ?></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 col-sm-6 col-xs-12">
                <div class="right">
                    <ul>
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM tbl_social");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result as $row) {
                            if (!empty($row['social_url'])) {
                                echo "<li><a href='{$row['social_url']}'><i class='{$row['social_icon']}'></i></a></li>";
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<div class="header">
    <div class="container">
        <div class="row inner">
            <div class="col-md-4 logo">
                <a href="index.php"><img src="assets/uploads/<?php echo $logo; ?>" alt="logo image"></a>
            </div>
            <div class="col-md-5 right">
                <ul>
                    <?php if (isset($_SESSION['customer'])) : ?>
                        <li><i class="fa fa-user"></i> <?php echo LANG_VALUE_13; ?> <?php echo $_SESSION['customer']['cust_name']; ?></li>
                        <li><a href="dashboard.php"><i class="fa fa-home"></i> <?php echo LANG_VALUE_89; ?></a></li>
                    <?php else : ?>
                        <li><a href="login.php"><i class="fa fa-sign-in"></i> <?php echo LANG_VALUE_9; ?></a></li>
                        <li><a href="registration.php"><i class="fa fa-user-plus"></i> <?php echo LANG_VALUE_15; ?></a></li>
                    <?php endif; ?>
                    <li><a href="cart.php"><i class="fa fa-shopping-cart"></i> <?php echo LANG_VALUE_18; ?> (<?php echo LANG_VALUE_1; ?><?php
                        if (isset($_SESSION['cart_p_id'])) {
                            $table_total_price = 0;
                            $i = 0;
                            foreach ($_SESSION['cart_p_qty'] as $key => $value) {
                                $i++;
                                $arr_cart_p_qty[$i] = $value;
                            }
                            $i = 0;
                            foreach ($_SESSION['cart_p_current_price'] as $key => $value) {
                                $i++;
                                $arr_cart_p_current_price[$i] = $value;
                            }
                            for ($i = 1; $i <= count($arr_cart_p_qty); $i++) {
                                $row_total_price = $arr_cart_p_current_price[$i] * $arr_cart_p_qty[$i];
                                $table_total_price += $row_total_price;
                            }
                            echo $table_total_price;
                        } else {
                            echo ' 0.00';
                        }
                        ?>)</a></li>
                </ul>
            </div>
            <div class="col-md-3 search-area">
                <form class="navbar-form navbar-left" role="search" action="search-result.php" method="get">
                    <?php $csrf->echoInputField(); ?>
                    <div class="form-group">
                        <input type="text" class="form-control search-top" style="border-radius:20px;" placeholder="<?php echo LANG_VALUE_2; ?>" name="search_text">
                    </div>
                    <button type="submit" class="btn btn-danger" style="background-color: #ff6600; border-radius:20px; border-color: #ff6600;"><?php echo LANG_VALUE_3; ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Navigation -->
<nav class="navigation">
    <div class="nav-container">
        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="mobile-menu" aria-label="Toggle Menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <!-- Menu Wrapper -->
        <div class="menu-wrapper" id="menu-wrapper">
            <ul class="main-menu">
                <li><a href="index.php">Home</a></li>
                <?php
                $statement = $pdo->prepare("SELECT * FROM tbl_top_category WHERE show_on_menu=1");
                $statement->execute();
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($result as $row) {
                    echo "<li class='dropdown'><a href='product-category.php?id={$row['tcat_id']}&type=top-category'>{$row['tcat_name']}</a>";
                    echo "<ul class='sub-menu'>";
                    $statement1 = $pdo->prepare("SELECT * FROM tbl_mid_category WHERE tcat_id=?");
                    $statement1->execute([$row['tcat_id']]);
                    $result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($result1 as $row1) {
                        echo "<li class='dropdown'><a href='product-category.php?id={$row1['mcat_id']}&type=mid-category'>{$row1['mcat_name']}</a>";
                        echo "<ul class='sub-menu'>";
                        $statement2 = $pdo->prepare("SELECT * FROM tbl_end_category WHERE mcat_id=?");
                        $statement2->execute([$row1['mcat_id']]);
                        $result2 = $statement2->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result2 as $row2) {
                            echo "<li><a href='product-category.php?id={$row2['ecat_id']}&type=end-category'>{$row2['ecat_name']}</a></li>";
                        }
                        echo "</ul></li>";
                    }
                    echo "</ul></li>";
                }
                ?>
                <li><a href="about.php"><?php echo $about_title; ?></a></li>
                <li><a href="faq.php"><?php echo $faq_title; ?></a></li>
                <li><a href="contact.php"><?php echo $contact_title; ?></a></li>
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Navigation Styles */
    .navigation {
        background-color: #ff6600;
        padding: 10px 0;
        position: relative;
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Mobile Menu Toggle */
    .menu-toggle {
        display: none;
        flex-direction: column;
        justify-content: space-around;
        width: 30px;
        height: 24px;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0;
        z-index: 1001;
    }

    .menu-toggle .bar {
        width: 100%;
        height: 3px;
        background-color: #fff;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    /* Close Icon (X) */
    .menu-toggle.active .bar:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .menu-toggle.active .bar:nth-child(2) {
        opacity: 0;
    }

    .menu-toggle.active .bar:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }

    /* Menu Wrapper */
    .menu-wrapper {
        width: 100%;
        display: flex;
        justify-content: flex-end;
    }

    .main-menu {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
    }

    .main-menu li {
        position: relative;
    }

    .main-menu li a {
        color: #fff;
        text-decoration: none;
        padding: 10px 15px;
        display: block;
        transition: background-color 0.3s ease;
    }

    .main-menu li a:hover {
        background-color: #e65c00;
    }

    /* Dropdown Arrows */
    .main-menu .dropdown > a::after {
        content: "▼";
        font-size: 10px;
        margin-left: 5px;
    }

    /* Sub-Menu */
    .sub-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background-color: #ff6600;
        list-style: none;
        padding: 0;
        margin: 0;
        min-width: 200px;
        z-index: 1000;
    }

    .sub-menu li a {
        padding: 10px;
        white-space: nowrap;
    }

    .main-menu li:hover > .sub-menu {
        display: block;
    }

    .sub-menu .sub-menu {
        left: 100%;
        top: 0;
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .menu-toggle {
            display: flex;
        }

        .menu-wrapper {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #ff6600;
            width: 100%;
            flex-direction: column;
            z-index: 1000;
        }

        .menu-wrapper.active {
            display: flex;
        }

        .main-menu {
            flex-direction: column;
            align-items: flex-start;
        }

        .main-menu li {
            width: 100%;
        }

        .main-menu .dropdown > a::after {
            content: "►";
        }

        .sub-menu {
            position: static;
            display: none;
        }

        .main-menu li.active > .sub-menu {
            display: block;
        }
    }
</style>

<script>
    // Toggle Mobile Menu and Change Icon
    const mobileMenu = document.getElementById('mobile-menu');
    const menuWrapper = document.getElementById('menu-wrapper');

    mobileMenu.addEventListener('click', () => {
        // Toggle menu visibility
        menuWrapper.classList.toggle('active');
        // Toggle menu icon (hamburger to close)
        mobileMenu.classList.toggle('active');
    });

    // Handle Dropdowns on Mobile
    document.querySelectorAll('.main-menu .dropdown > a').forEach((dropdown) => {
        dropdown.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const parentLi = e.target.parentElement;
                parentLi.classList.toggle('active');
            }
        });
    });
</script>