<?php
/**
 * Created by PhpStorm.
 * User: BrendanDoyle
 * Date: 05/12/2018
 * Time: 15:11
 */
defined('ABSPATH') or die("Cannot access pages directly.");

$gfeeds = new FeedTable();

$gfeeds->prepare_items();
?>

<div class="wrap">

    <h1 class="wp-heading-inline">Google Product Feeds</h1>
    <a href="edit-tags.php?taxonomy=product_cat&post_type=product" class="page-title-action">Add New Feed</a>





    <form id="bookings-filter" method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php $gfeeds->display() ?>
    </form>


    <?php
    include(GPF_BASE."/views/parts/about.php");

    ?>
</div>
