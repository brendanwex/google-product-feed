<?php
/**
 * Created by PhpStorm.
 * User: BrendanDoyle
 * Date: 05/12/2018
 * Time: 12:37
 */
defined('ABSPATH') or die("Cannot access pages directly.");

?>

<div class="form-field">
    <label for="term_meta[gpf_category]"><?php _e('Google Shopping Category', 'graphedia-base-theme'); ?>    </label>

    <select name="term_meta[gpf_category]" class="gpf_category" style="width: 100%">

        <option></option>

        <?php foreach($google_categories_list as $cats){?>

            <option><?php echo $cats;?></option>

        <?php } ?>

    </select>

    <script>
        jQuery(document).ready(function($){
            $( '.gpf_category' ).select2();

        });
    </script>
</div>