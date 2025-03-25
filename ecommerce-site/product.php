<?php require_once('header.php'); ?>

<?php
// Initialize variables
$error_message = '';
$success_message = '';
$error_message1 = '';
$success_message1 = '';

if(!isset($_REQUEST['id'])) {
    header('location: index.php');
    exit;
} else {
    // Check if product ID is valid
    $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=?");
    $statement->execute(array($_REQUEST['id']));
    $total = $statement->rowCount();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    if($total == 0) {
        header('location: index.php');
        exit;
    }
}

// Get product details
foreach($result as $row) {
    $p_name = $row['p_name'];
    $p_old_price = $row['p_old_price'];
    $p_current_price = $row['p_current_price'];
    $p_qty = $row['p_qty'];
    $p_featured_photo = $row['p_featured_photo'];
    $p_description = $row['p_description'];
    $p_short_description = $row['p_short_description'];
    $p_feature = $row['p_feature'];
    $p_condition = $row['p_condition'];
    $p_return_policy = $row['p_return_policy'];
    $p_total_view = $row['p_total_view'];
    $p_is_featured = $row['p_is_featured'];
    $p_is_active = $row['p_is_active'];
    $ecat_id = $row['ecat_id'];
}

// Get category information for breadcrumb
$statement = $pdo->prepare("SELECT
                        t1.ecat_id, t1.ecat_name, t1.mcat_id,
                        t2.mcat_id, t2.mcat_name, t2.tcat_id,
                        t3.tcat_id, t3.tcat_name
                        FROM tbl_end_category t1
                        JOIN tbl_mid_category t2 ON t1.mcat_id = t2.mcat_id
                        JOIN tbl_top_category t3 ON t2.tcat_id = t3.tcat_id
                        WHERE t1.ecat_id=?");
$statement->execute(array($ecat_id));
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $ecat_name = $row['ecat_name'];
    $mcat_id = $row['mcat_id'];
    $mcat_name = $row['mcat_name'];
    $tcat_id = $row['tcat_id'];
    $tcat_name = $row['tcat_name'];
}

// Update product view count
$p_total_view = $p_total_view + 1;
$statement = $pdo->prepare("UPDATE tbl_product SET p_total_view=? WHERE p_id=?");
$statement->execute(array($p_total_view,$_REQUEST['id']));

// Get available sizes
$statement = $pdo->prepare("SELECT * FROM tbl_product_size WHERE p_id=?");
$statement->execute(array($_REQUEST['id']));
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $size[] = $row['size_id'];
}

// Get available colors
$statement = $pdo->prepare("SELECT * FROM tbl_product_color WHERE p_id=?");
$statement->execute(array($_REQUEST['id']));
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $color[] = $row['color_id'];
}

// Handle review submission
if(isset($_POST['form_review'])) {
    // Check if customer already reviewed this product
    $statement = $pdo->prepare("SELECT * FROM tbl_rating WHERE p_id=? AND cust_id=?");
    $statement->execute(array($_REQUEST['id'],$_SESSION['customer']['cust_id']));
    $total = $statement->rowCount();
    
    if($total) {
        $error_message = "You have already submitted a review for this product."; 
    } else {
        // Process image upload if exists
        $review_image = '';
        if($_FILES['review_image']['name'] != '') {
            // Create directory if it doesn't exist
            $upload_dir = 'assets/uploads/review_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Validate file type and size
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if(!in_array($_FILES['review_image']['type'], $allowed_types)) {
                $error_message = "Only JPG, PNG, and GIF images are allowed.";
            } elseif($_FILES['review_image']['size'] > $max_size) {
                $error_message = "Image size must be less than 2MB.";
            } else {
                $review_image = time().'_'.$_FILES['review_image']['name'];
                $temp = $_FILES['review_image']['tmp_name'];
                $upload_path = $upload_dir.$review_image;
                
                if(!move_uploaded_file($temp, $upload_path)) {
                    $error_message = "Failed to upload image. Please try again.";
                }
            }
        }
        
        if(empty($error_message)) {
            // Insert new review with current timestamp and optional image
            $statement = $pdo->prepare("INSERT INTO tbl_rating (p_id, cust_id, comment, rating, review_image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $statement->execute(array(
                $_REQUEST['id'],
                $_SESSION['customer']['cust_id'],
                $_POST['comment'],
                $_POST['rating'],
                $review_image
            ));
            $success_message = "Thank you for your review!";    
        }
    }
}

// Calculate average rating and get reviews
$t_rating = 0;
$statement = $pdo->prepare("SELECT r.*, c.cust_name FROM tbl_rating r 
                           JOIN tbl_customer c ON r.cust_id = c.cust_id
                           WHERE p_id=? ORDER BY r.rt_id DESC");
$statement->execute(array($_REQUEST['id']));
$tot_rating = $statement->rowCount();
$reviews = $statement->fetchAll(PDO::FETCH_ASSOC);

if($tot_rating == 0) {
    $avg_rating = 0;
} else {
    foreach ($reviews as $row) {
        $t_rating = $t_rating + $row['rating'];
    }
    $avg_rating = $t_rating / $tot_rating;
}

// Handle add to cart
if(isset($_POST['form_add_to_cart'])) {
    // Check stock
    $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE p_id=?");
    $statement->execute(array($_REQUEST['id']));
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);							
    foreach ($result as $row) {
        $current_p_qty = $row['p_qty'];
    }
    
    if($_POST['p_qty'] > $current_p_qty) {
        $error_message1 = 'Sorry! There are only '.$current_p_qty.' item(s) in stock';
    } else {
        // Add to cart logic
        if(isset($_SESSION['cart_p_id'])) {
            // Existing cart logic
            $arr_cart_p_id = $_SESSION['cart_p_id'];
            $arr_cart_size_id = $_SESSION['cart_size_id'];
            $arr_cart_color_id = $_SESSION['cart_color_id'];
            
            $added = 0;
            $size_id = isset($_POST['size_id']) ? $_POST['size_id'] : 0;
            $color_id = isset($_POST['color_id']) ? $_POST['color_id'] : 0;
            
            for($i=0; $i<count($arr_cart_p_id); $i++) {
                if($arr_cart_p_id[$i] == $_REQUEST['id'] && 
                   $arr_cart_size_id[$i] == $size_id && 
                   $arr_cart_color_id[$i] == $color_id) {
                    $added = 1;
                    break;
                }
            }
            
            if($added == 0) {
                $new_key = count($arr_cart_p_id);
                
                if(isset($_POST['size_id'])) {
                    $size_id = $_POST['size_id'];
                    $statement = $pdo->prepare("SELECT * FROM tbl_size WHERE size_id=?");
                    $statement->execute(array($size_id));
                    $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
                    foreach ($result as $row) {
                        $size_name = $row['size_name'];
                    }
                } else {
                    $size_id = 0;
                    $size_name = '';
                }
                
                if(isset($_POST['color_id'])) {
                    $color_id = $_POST['color_id'];
                    $statement = $pdo->prepare("SELECT * FROM tbl_color WHERE color_id=?");
                    $statement->execute(array($color_id));
                    $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
                    foreach ($result as $row) {
                        $color_name = $row['color_name'];
                    }
                } else {
                    $color_id = 0;
                    $color_name = '';
                }

                $_SESSION['cart_p_id'][] = $_REQUEST['id'];
                $_SESSION['cart_size_id'][] = $size_id;
                $_SESSION['cart_size_name'][] = $size_name;
                $_SESSION['cart_color_id'][] = $color_id;
                $_SESSION['cart_color_name'][] = $color_name;
                $_SESSION['cart_p_qty'][] = $_POST['p_qty'];
                $_SESSION['cart_p_current_price'][] = $_POST['p_current_price'];
                $_SESSION['cart_p_name'][] = $_POST['p_name'];
                $_SESSION['cart_p_featured_photo'][] = $_POST['p_featured_photo'];

                $success_message1 = 'Product added to cart successfully!';
            } else {
                $error_message1 = 'This product is already in your cart.';
            }
        } else {
            // New cart logic
            if(isset($_POST['size_id'])) {
                $size_id = $_POST['size_id'];
                $statement = $pdo->prepare("SELECT * FROM tbl_size WHERE size_id=?");
                $statement->execute(array($size_id));
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
                foreach ($result as $row) {
                    $size_name = $row['size_name'];
                }
            } else {
                $size_id = 0;
                $size_name = '';
            }
            
            if(isset($_POST['color_id'])) {
                $color_id = $_POST['color_id'];
                $statement = $pdo->prepare("SELECT * FROM tbl_color WHERE color_id=?");
                $statement->execute(array($color_id));
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
                foreach ($result as $row) {
                    $color_name = $row['color_name'];
                }
            } else {
                $color_id = 0;
                $color_name = '';
            }

            $_SESSION['cart_p_id'] = array($_REQUEST['id']);
            $_SESSION['cart_size_id'] = array($size_id);
            $_SESSION['cart_size_name'] = array($size_name);
            $_SESSION['cart_color_id'] = array($color_id);
            $_SESSION['cart_color_name'] = array($color_name);
            $_SESSION['cart_p_qty'] = array($_POST['p_qty']);
            $_SESSION['cart_p_current_price'] = array($_POST['p_current_price']);
            $_SESSION['cart_p_name'] = array($_POST['p_name']);
            $_SESSION['cart_p_featured_photo'] = array($_POST['p_featured_photo']);

            $success_message1 = 'Product added to cart successfully!';
        }
    }
}
?>

<!-- CSS Styles -->
<style>
    /* Product Page Styles */
    .prod-slider li {
        height: 400px;
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        cursor: pointer;
    }
    
    .prod-pager-thumb {
        width: 60px;
        height: 60px;
        background-size: cover;
        background-position: center;
        display: inline-block;
        margin: 5px;
        cursor: pointer;
    }
    
    /* Mobile-specific styles */
    @media (max-width: 767px) {
        .prod-slider li {
            height: 300px;
        }
        
        .prod-pager-thumb {
            width: 40px;
            height: 40px;
        }
        
        /* Make sure popup is full screen on mobile */
        .mfp-img {
            max-height: 100% !important;
            width: auto !important;
        }
        
        .mfp-container {
            padding: 0 !important;
        }
        
        .mfp-figure {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mfp-close {
            top: 15px !important;
            right: 15px !important;
        }
    }
    
    /* Rating Styles */
    .rating {
        color: #ffc107;
        font-size: 16px;
        margin: 10px 0;
    }
    
    .rating i {
        margin-right: 2px;
    }
    
    .rating-value {
        font-size: 14px;
        color: #666;
        margin-left: 5px;
    }
    
    /* Review Section */
    .review-section {
        margin: 30px 0;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 5px;
    }
    
    .review-item {
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }
    
    .review-author {
        font-weight: bold;
    }
    
    .review-date {
        color: #999;
        font-size: 12px;
    }
    
    .review-comment {
        margin-top: 10px;
    }
    
    .review-image {
        margin-top: 10px;
        max-width: 200px;
        max-height: 200px;
    }
    
    /* Review Form */
    .star-rating {
        direction: rtl;
        display: inline-block;
    }
    
    .star-rating input[type=radio] {
        display: none;
    }
    
    .star-rating label {
        color: #bbb;
        font-size: 18px;
        padding: 0;
        cursor: pointer;
    }
    
    .star-rating label:before {
        content: "\2605";
    }
    
    .star-rating input[type=radio]:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
        color: #ffc107;
    }
    
    /* Add to Cart Button */
    .btn-cart input[type="submit"] {
        background: #ff6600;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-cart input[type="submit"]:hover {
        background: #e65c00;
    }
    
    /* Error/Success Messages */
    .error {
        color: #dc3545;
        margin-bottom: 10px;
    }
    
    .success {
        color: #28a745;
        margin-bottom: 10px;
    }
    
    /* Image Upload Preview */
    .image-preview {
        width: 100px;
        height: 100px;
        border: 1px solid #ddd;
        margin-top: 10px;
        display: none;
    }
    
    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

<!-- JavaScript -->
<script>
$(document).ready(function() {
    // Initialize image zoom with mobile-friendly settings
    $('.popup').magnificPopup({
        type: 'image',
        closeOnContentClick: true,
        closeBtnInside: false,
        fixedContentPos: true,
        mainClass: 'mfp-no-margins mfp-with-zoom',
        image: {
            verticalFit: true
        },
        zoom: {
            enabled: true,
            duration: 300
        },
        callbacks: {
            open: function() {
                // Adjust for mobile devices
                if(window.innerWidth <= 767) {
                    $.magnificPopup.instance.st.closeOnBgClick = true;
                    $.magnificPopup.instance.st.closeBtnInside = false;
                }
            }
        }
    });
    
    // Show alerts
    <?php if($error_message1 != ''): ?>
        alert('<?php echo $error_message1; ?>');
    <?php endif; ?>
    
    <?php if($success_message1 != ''): ?>
        alert('<?php echo $success_message1; ?>');
    <?php endif; ?>
    
    // Image upload preview
    $("#review_image").change(function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                $('.image-preview').show();
                $('.image-preview img').attr('src', e.target.result);
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Mobile touch events for product images
    if ('ontouchstart' in window) {
        $('.prod-slider li').on('click touchstart', function(e) {
            e.preventDefault();
            $(this).find('.popup').trigger('click');
        });
    }
});
</script>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb mb_30">
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li>></li>
                        <li><a href="<?php echo BASE_URL.'product-category.php?id='.$tcat_id.'&type=top-category' ?>"><?php echo $tcat_name; ?></a></li>
                        <li>></li>
                        <li><a href="<?php echo BASE_URL.'product-category.php?id='.$mcat_id.'&type=mid-category' ?>"><?php echo $mcat_name; ?></a></li>
                        <li>></li>
                        <li><a href="<?php echo BASE_URL.'product-category.php?id='.$ecat_id.'&type=end-category' ?>"><?php echo $ecat_name; ?></a></li>
                        <li>></li>
                        <li><?php echo $p_name; ?></li>
                    </ul>
                </div>

                <div class="product" style="margin-top: 60px;">
                    <div class="row">
                        <div class="col-md-5">
                            <ul class="prod-slider">
                                <li style="background-image: url(assets/uploads/<?php echo $p_featured_photo; ?>);">
                                    <a class="popup" href="assets/uploads/<?php echo $p_featured_photo; ?>"></a>
                                </li>
                                <?php
                                $statement = $pdo->prepare("SELECT * FROM tbl_product_photo WHERE p_id=?");
                                $statement->execute(array($_REQUEST['id']));
                                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($result as $row) {
                                    ?>
                                    <li style="background-image: url(assets/uploads/product_photos/<?php echo $row['photo']; ?>);">
                                        <a class="popup" href="assets/uploads/product_photos/<?php echo $row['photo']; ?>"></a>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                            <div id="prod-pager">
                                <a data-slide-index="0" href=""><div class="prod-pager-thumb" style="background-image: url(assets/uploads/<?php echo $p_featured_photo; ?>"></div></a>
                                <?php
                                $i=1;
                                $statement = $pdo->prepare("SELECT * FROM tbl_product_photo WHERE p_id=?");
                                $statement->execute(array($_REQUEST['id']));
                                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($result as $row) {
                                    ?>
                                    <a data-slide-index="<?php echo $i; ?>" href=""><div class="prod-pager-thumb" style="background-image: url(assets/uploads/product_photos/<?php echo $row['photo']; ?>"></div></a>
                                    <?php
                                    $i++;
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div  style="margin-top:80px;" class="p-title"><h2><?php echo $p_name; ?></h2></div>
                            
                            <!-- Rating Display -->
                            <div class="p-review">
                                <div class="rating">
                                    <?php
                                    if($avg_rating == 0) {
                                        echo 'No ratings yet';
                                    } else {
                                        $full_stars = floor($avg_rating);
                                        $half_star = ($avg_rating - $full_stars) >= 0.5;
                                        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                        
                                        // Full stars
                                        for($i=0; $i<$full_stars; $i++) {
                                            echo '<i class="fa fa-star"></i>';
                                        }
                                        
                                        // Half star
                                        if($half_star) {
                                            echo '<i class="fa fa-star-half-o"></i>';
                                        }
                                        
                                        // Empty stars
                                        for($i=0; $i<$empty_stars; $i++) {
                                            echo '<i class="fa fa-star-o"></i>';
                                        }
                                        
                                        echo '<span class="rating-value">(' . number_format($avg_rating, 1) . ' based on ' . $tot_rating . ' reviews)</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="p-short-des">
                                <p>
                                    <?php echo $p_short_description; ?>
                                </p>
                            </div>
                            
                            <form action="" method="post">
                                <div class="p-quantity">
                                    <div class="row">
                                        <?php if(isset($size)): ?>
                                        <div class="col-md-12 mb_20">
                                            <?php echo LANG_VALUE_52; ?> <br>
                                            <select name="size_id" class="form-control select2" style="width:auto;">
                                                <?php
                                                $statement = $pdo->prepare("SELECT * FROM tbl_size");
                                                $statement->execute();
                                                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($result as $row) {
                                                    if(in_array($row['size_id'],$size)) {
                                                        ?>
                                                        <option value="<?php echo $row['size_id']; ?>"><?php echo $row['size_name']; ?></option>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <?php endif; ?>

                                        <?php if(isset($color)): ?>
                                        <div class="col-md-12">
                                            <?php echo LANG_VALUE_53; ?> <br>
                                            <select name="color_id" class="form-control select2" style="width:auto;">
                                                <?php
                                                $statement = $pdo->prepare("SELECT * FROM tbl_color");
                                                $statement->execute();
                                                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($result as $row) {
                                                    if(in_array($row['color_id'],$color)) {
                                                        ?>
                                                        <option value="<?php echo $row['color_id']; ?>"><?php echo $row['color_name']; ?></option>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="p-price">
                                    <span style="font-size:14px;"><?php echo LANG_VALUE_54; ?></span><br>
                                    <span>
                                        <?php if($p_old_price!=''): ?>
                                            <del>RWF <?php echo $p_old_price; ?></del>
                                        <?php endif; ?> 
                                        RWF <?php echo $p_current_price; ?>
                                    </span>
                                </div>
                                
                                <input type="hidden" name="p_current_price" value="<?php echo $p_current_price; ?>">
                                <input type="hidden" name="p_name" value="<?php echo $p_name; ?>">
                                <input type="hidden" name="p_featured_photo" value="<?php echo $p_featured_photo; ?>">
                                
                                <div class="p-quantity">
                                    <?php echo LANG_VALUE_55; ?> <br>
                                    <input type="number" class="input-text qty" step="1" min="1" max="" name="p_qty" value="1" title="Qty" size="4" pattern="[0-9]*" inputmode="numeric">
                                </div>
                                
                                <div class="btn-cart">
                                    <input type="submit" value="<?php echo LANG_VALUE_154; ?>" name="form_add_to_cart">
                                </div>
                            </form>
                            
                            <div class="share">
                                <?php echo LANG_VALUE_58; ?> <br>
                                <div class="sharethis-inline-share-buttons"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li role="presentation" class="active"><a href="#description" aria-controls="description" role="tab" data-toggle="tab"><?php echo LANG_VALUE_59; ?></a></li>
                                <li role="presentation"><a href="#feature" aria-controls="feature" role="tab" data-toggle="tab"><?php echo LANG_VALUE_60; ?></a></li>
                                <li role="presentation"><a href="#condition" aria-controls="condition" role="tab" data-toggle="tab"><?php echo LANG_VALUE_61; ?></a></li>
                                <li role="presentation"><a href="#return_policy" aria-controls="return_policy" role="tab" data-toggle="tab"><?php echo LANG_VALUE_62; ?></a></li>
                                <li role="presentation"><a href="#reviews" aria-controls="reviews" role="tab" data-toggle="tab">Reviews</a></li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content">
                                <div role="tabpanel" class="tab-pane active" id="description" style="margin-top: -30px;">
                                    <p>
                                        <?php
                                        if($p_description == '') {
                                            echo LANG_VALUE_70;
                                        } else {
                                            echo $p_description;
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div role="tabpanel" class="tab-pane" id="feature" style="margin-top: -30px;">
                                    <p>
                                        <?php
                                        if($p_feature == '') {
                                            echo LANG_VALUE_71;
                                        } else {
                                            echo $p_feature;
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div role="tabpanel" class="tab-pane" id="condition" style="margin-top: -30px;">
                                    <p>
                                        <?php
                                        if($p_condition == '') {
                                            echo LANG_VALUE_72;
                                        } else {
                                            echo $p_condition;
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div role="tabpanel" class="tab-pane" id="return_policy" style="margin-top: -30px;">
                                    <p>
                                        <?php
                                        if($p_return_policy == '') {
                                            echo LANG_VALUE_73;
                                        } else {
                                            echo $p_return_policy;
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div role="tabpanel" class="tab-pane" id="reviews" style="margin-top: -30px;">
                                    <div class="review-section">
                                        <h3>Customer Reviews</h3>
                                        <div class="average-rating">
                                            <h4>Average Rating: 
                                                <span class="rating">
                                                    <?php
                                                    if($avg_rating == 0) {
                                                        echo 'No ratings yet';
                                                    } else {
                                                        $full_stars = floor($avg_rating);
                                                        $half_star = ($avg_rating - $full_stars) >= 0.5;
                                                        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                                        
                                                        // Full stars
                                                        for($i=0; $i<$full_stars; $i++) {
                                                            echo '<i class="fa fa-star"></i>';
                                                        }
                                                        
                                                        // Half star
                                                        if($half_star) {
                                                            echo '<i class="fa fa-star-half-o"></i>';
                                                        }
                                                        
                                                        // Empty stars
                                                        for($i=0; $i<$empty_stars; $i++) {
                                                            echo '<i class="fa fa-star-o"></i>';
                                                        }
                                                        
                                                        echo ' ' . number_format($avg_rating, 1) . ' out of 5';
                                                    }
                                                    ?>
                                                </span>
                                            </h4>
                                            <p>Based on <?php echo $tot_rating; ?> review(s)</p>
                                        </div>
                                        
                                        <?php if(isset($_SESSION['customer'])): ?>
                                        <div class="review-form">
                                            <h4>Write a Review</h4>
                                            <?php if($error_message != ''): ?>
                                                <div class="error"><?php echo $error_message; ?></div>
                                            <?php endif; ?>
                                            <?php if($success_message != ''): ?>
                                                <div class="success"><?php echo $success_message; ?></div>
                                            <?php endif; ?>
                                            
                                            <form method="post" enctype="multipart/form-data">
                                                <div class="form-group">
                                                    <label>Your Rating</label>
                                                    <div class="star-rating">
                                                        <input id="star5" type="radio" name="rating" value="5">
                                                        <label for="star5" title="5 stars"></label>
                                                        <input id="star4" type="radio" name="rating" value="4">
                                                        <label for="star4" title="4 stars"></label>
                                                        <input id="star3" type="radio" name="rating" value="3">
                                                        <label for="star3" title="3 stars"></label>
                                                        <input id="star2" type="radio" name="rating" value="2">
                                                        <label for="star2" title="2 stars"></label>
                                                        <input id="star1" type="radio" name="rating" value="1">
                                                        <label for="star1" title="1 star"></label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Your Review</label>
                                                    <textarea name="comment" class="form-control" rows="5" required></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Upload Product Image (Optional)</label>
                                                    <input type="file" name="review_image" id="review_image" class="form-control">
                                                    <small class="text-muted">Upload an image of the product you're reviewing (Max 2MB, JPG/PNG/GIF)</small>
                                                    <div class="image-preview">
                                                        <img src="" alt="Preview">
                                                    </div>
                                                </div>
                                                <button type="submit" name="form_review" style="background-color: #ff6600; border-radius:20px; border-color: #ff6600;" class="btn btn-primary">Submit Review</button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-info">
                                            Please <a href="login.php">login</a> to write a review.
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="review-list">
                                            <h4>Customer Reviews</h4>
                                            <?php if(count($reviews) == 0): ?>
                                                <p>No reviews yet.</p>
                                            <?php else: ?>
                                                <?php foreach($reviews as $review): ?>
                                                    <div class="review-item">
                                                        <div class="review-header">
                                                            <h5><?php echo $review['cust_name']; ?></h5>
                                                            <div class="rating">
                                                                <?php
                                                                for($i=1; $i<=5; $i++) {
                                                                    if($i <= $review['rating']) {
                                                                        echo '<i class="fa fa-star"></i>';
                                                                    } else {
                                                                        echo '<i class="fa fa-star-o"></i>';
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                        <?php if(!empty($review['created_at'])): ?>
                                                        <div class="review-date">
                                                            <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="review-content">
                                                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                            <?php if(!empty($review['review_image'])): ?>
                                                            <div class="review-image">
                                                                <a class="popup" href="assets/uploads/review_images/<?php echo $review['review_image']; ?>">
                                                                    <img src="assets/uploads/review_images/<?php echo $review['review_image']; ?>" alt="Review Image" class="img-thumbnail">
                                                                </a>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
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
</div>

<div class="product bg-gray pt_70 pb_70">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="headline">
                    <h2><?php echo LANG_VALUE_155; ?></h2>
                    <h3><?php echo LANG_VALUE_156; ?></h3>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="product-carousel">
                    <?php
                    $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE ecat_id=? AND p_id!=?");
                    $statement->execute(array($ecat_id,$_REQUEST['id']));
                    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($result as $row) {
                        ?>
                        <div class="item">
                            <div class="thumb">
                                <div class="photo" style="background-image:url(assets/uploads/<?php echo $row['p_featured_photo']; ?>);"></div>
                                <div class="overlay"><a href="product.php?id=<?php echo $row['p_id']; ?>" class="popup"><i class="fa fa-search-plus"></i></a></div>
                            </div>
                            <div class="text">
                                <h3><a href="product.php?id=<?php echo $row['p_id']; ?>"><?php echo $row['p_name']; ?></a></h3>
                                <h4>
                                    RWF <?php echo $row['p_current_price']; ?> 
                                    <?php if($row['p_old_price'] != ''): ?>
                                    <del>
                                        <?php echo $row['p_old_price']; ?>
                                    </del>
                                    <?php endif; ?>
                                </h4>
                                <div class="rating">
                                    <?php
                                    $t_rating = 0;
                                    $statement1 = $pdo->prepare("SELECT * FROM tbl_rating WHERE p_id=?");
                                    $statement1->execute(array($row['p_id']));
                                    $tot_rating = $statement1->rowCount();
                                    if($tot_rating == 0) {
                                        $avg_rating = 0;
                                    } else {
                                        $result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($result1 as $row1) {
                                            $t_rating = $t_rating + $row1['rating'];
                                        }
                                        $avg_rating = $t_rating / $tot_rating;
                                    }
                                    ?>
                                    <?php
                                    if($avg_rating == 0) {
                                        echo 'No ratings yet';
                                    } else {
                                        $full_stars = floor($avg_rating);
                                        $half_star = ($avg_rating - $full_stars) >= 0.5;
                                        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                        
                                        // Full stars
                                        for($i=0; $i<$full_stars; $i++) {
                                            echo '<i class="fa fa-star"></i>';
                                        }
                                        
                                        // Half star
                                        if($half_star) {
                                            echo '<i class="fa fa-star-half-o"></i>';
                                        }
                                        
                                        // Empty stars
                                        for($i=0; $i<$empty_stars; $i++) {
                                            echo '<i class="fa fa-star-o"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <p>
                                    <a href="product.php?id=<?php echo $row['p_id']; ?>" class="add-to-cart-button" style="background-color: #ff6600; border-radius:20px; border-color: #ff6600;">
                                        <?php echo LANG_VALUE_154; ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>