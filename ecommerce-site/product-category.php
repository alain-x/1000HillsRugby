<?php require_once('header.php'); ?>

<?php
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_product_category = $row['banner_product_category'];
}
?>

<?php
if( !isset($_REQUEST['id']) || !isset($_REQUEST['type']) ) {
    header('location: index.php');
    exit;
} else {
    if( ($_REQUEST['type'] != 'top-category') && ($_REQUEST['type'] != 'mid-category') && ($_REQUEST['type'] != 'end-category') ) {
        header('location: index.php');
        exit;
    } else {
        $statement = $pdo->prepare("SELECT * FROM tbl_top_category");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $top[] = $row['tcat_id'];
            $top1[] = $row['tcat_name'];
        }

        $statement = $pdo->prepare("SELECT * FROM tbl_mid_category");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $mid[] = $row['mcat_id'];
            $mid1[] = $row['mcat_name'];
            $mid2[] = $row['tcat_id'];
        }

        $statement = $pdo->prepare("SELECT * FROM tbl_end_category");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
        foreach ($result as $row) {
            $end[] = $row['ecat_id'];
            $end1[] = $row['ecat_name'];
            $end2[] = $row['mcat_id'];
        }

        if($_REQUEST['type'] == 'top-category') {
            if(!in_array($_REQUEST['id'],$top)) {
                header('location: index.php');
                exit;
            } else {
                // Getting Title
                for ($i=0; $i < count($top); $i++) { 
                    if($top[$i] == $_REQUEST['id']) {
                        $title = $top1[$i];
                        break;
                    }
                }
                $arr1 = array();
                $arr2 = array();
                // Find out all ecat ids under this
                for ($i=0; $i < count($mid); $i++) { 
                    if($mid2[$i] == $_REQUEST['id']) {
                        $arr1[] = $mid[$i];
                    }
                }
                for ($j=0; $j < count($arr1); $j++) {
                    for ($i=0; $i < count($end); $i++) { 
                        if($end2[$i] == $arr1[$j]) {
                            $arr2[] = $end[$i];
                        }
                    }   
                }
                $final_ecat_ids = $arr2;
            }   
        }

        if($_REQUEST['type'] == 'mid-category') {
            if(!in_array($_REQUEST['id'],$mid)) {
                header('location: index.php');
                exit;
            } else {
                // Getting Title
                for ($i=0; $i < count($mid); $i++) { 
                    if($mid[$i] == $_REQUEST['id']) {
                        $title = $mid1[$i];
                        break;
                    }
                }
                $arr2 = array();        
                // Find out all ecat ids under this
                for ($i=0; $i < count($end); $i++) { 
                    if($end2[$i] == $_REQUEST['id']) {
                        $arr2[] = $end[$i];
                    }
                }
                $final_ecat_ids = $arr2;
            }
        }

        if($_REQUEST['type'] == 'end-category') {
            if(!in_array($_REQUEST['id'],$end)) {
                header('location: index.php');
                exit;
            } else {
                // Getting Title
                for ($i=0; $i < count($end); $i++) { 
                    if($end[$i] == $_REQUEST['id']) {
                        $title = $end1[$i];
                        break;
                    }
                }
                $final_ecat_ids = array($_REQUEST['id']);
            }
        }
    }   
}
?>

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
        --border-radius: 8px;
        --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    /* Page Banner */
    .page-banner {
        background-size: cover;
        background-position: center;
        padding: 60px 0;
        position: relative;
        margin-bottom: 30px;
    }
    
    .page-banner .inner {
        position: relative;
        z-index: 2;
        text-align: center;
        color: white;
    }
    
    .page-banner h1 {
        font-size: 28px;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    /* Page Layout */
    .page {
        padding: 30px 0;
    }
    
    /* Product Grid */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    @media (min-width: 768px) {
        .product-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
    }
    
    @media (min-width: 992px) {
        .product-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    
    /* Product Card */
    .product-card {
        background: var(--white);
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
        transition: all 0.3s ease;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
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
    
    .product-details {
        padding: 15px;
    }
    
    .product-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--secondary-color);
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 50px;
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
        border-radius: 20px;
        transition: all 0.3s ease;
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
    
    /* Category Page Specific */
    .category-header {
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--medium-gray);
    }
    
    .category-header h3 {
        font-size: 22px;
        font-weight: 700;
        color: var(--secondary-color);
    }
    
    /* No Products Message */
    .no-products {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px 0;
        color: var(--dark-gray);
    }
</style>

<div class="page-banner" style="background-image: url('assets/uploads/<?php echo htmlspecialchars($banner_product_category, ENT_QUOTES, 'UTF-8'); ?>');">     
    <div class="inner">         
        <h1><?php echo LANG_VALUE_50; ?> <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>     
    </div> 
</div>  

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <?php require_once('sidebar-category.php'); ?>
            </div>
            <div class="col-md-9">
                <div class="category-header">
                    <h3><?php echo LANG_VALUE_51; ?> "<?php echo $title; ?>"</h3>
                </div>
                
                <div class="product-grid">
                    <?php
                    // Checking if any product is available or not
                    $prod_count = 0;
                    $statement = $pdo->prepare("SELECT * FROM tbl_product");
                    $statement->execute();
                    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($result as $row) {
                        $prod_table_ecat_ids[] = $row['ecat_id'];
                    }

                    for($ii=0;$ii<count($final_ecat_ids);$ii++):
                        if(in_array($final_ecat_ids[$ii],$prod_table_ecat_ids)) {
                            $prod_count++;
                        }
                    endfor;

                    if($prod_count==0) {
                        echo '<div class="no-products">'.LANG_VALUE_153.'</div>';
                    } else {
                        for($ii=0;$ii<count($final_ecat_ids);$ii++) {
                            $statement = $pdo->prepare("SELECT * FROM tbl_product WHERE ecat_id=? AND p_is_active=?");
                            $statement->execute(array($final_ecat_ids[$ii],1));
                            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($result as $row) {
                                ?>
                                <div class="product-card">
                                    <div class="product-thumb">
                                        <img src="assets/uploads/<?php echo $row['p_featured_photo']; ?>" alt="<?php echo htmlspecialchars($row['p_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="product-details">
                                        <h3 class="product-title"><a href="product.php?id=<?php echo $row['p_id']; ?>"><?php echo $row['p_name']; ?></a></h3>
                                        <div class="product-price">
                                            <span class="current-price">RWF <?php echo number_format($row['p_current_price'], 0); ?></span>
                                            <?php if($row['p_old_price'] != '' && $row['p_old_price'] > $row['p_current_price']): ?>
                                            <span class="old-price">RWF <?php echo number_format($row['p_old_price'], 0); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-rating">
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
                                            
                                            if($avg_rating == 0) {
                                                echo '<span>No reviews</span>';
                                            } else {
                                                for($i=1;$i<=5;$i++) {
                                                    if($i <= floor($avg_rating)) {
                                                        echo '<i class="fa fa-star"></i>';
                                                    } elseif($i == ceil($avg_rating) && $avg_rating - floor($avg_rating) >= 0.5) {
                                                        echo '<i class="fa fa-star-half-o"></i>';
                                                    } else {
                                                        echo '<i class="fa fa-star-o"></i>';
                                                    }
                                                }
                                                echo ' <span>('.$tot_rating.')</span>';
                                            }
                                            ?>
                                        </div>
                                        <?php if($row['p_qty'] == 0): ?>
                                            <button class="btn btn-danger">Out Of Stock</button>
                                        <?php else: ?>
                                            <a href="product.php?id=<?php echo $row['p_id']; ?>" class="btn btn-primary"><i class="fa fa-shopping-cart"></i> <?php echo LANG_VALUE_154; ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>