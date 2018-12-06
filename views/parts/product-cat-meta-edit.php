<?php
/**
 * Created by PhpStorm.
 * User: BrendanDoyle
 * Date: 05/12/2018
 * Time: 12:37
 */
defined('ABSPATH') or die("Cannot access pages directly.");

$t_id = $term->term_id;
$term_meta = get_option("taxonomy_$t_id");
isset($_GET['gpf']) ? $gpf = "gpf-highlight" : $gpf = "";
?>

<tr class="form-field <?php echo $gpf;?>">
    <th scope="row" valign="top"><label for="term_meta[gpf_category]"><?php _e('Google Shopping Category', 'graphedia-base-theme'); ?></label></th>
    <td>
        <select name="term_meta[gpf_category]" class="gpf_category" style="width: 100%">

            <option></option>

            <?php foreach($google_categories_list as $cats){?>

                <option <?php if(isset($term_meta['gpf_category']) && $term_meta['gpf_category'] == $cats) echo "selected"?>><?php echo $cats;?></option>

            <?php } ?>

        </select>

        <script>
            jQuery(document).ready(function($){
                $( '.gpf_category' ).select2();

            });
        </script>
    </td>

    </tr>

<?php if(isset($term_meta['gpf_category'])){?>

<tr class="form-field">

    <th>
        <label>Category Feed Link</label>
    </th>

    <td>
        <input type="text" readonly value="<?php echo admin_url('admin-ajax.php?action=google_shopping_feed&cat='.$t_id);?>" />
    </td>


</tr>

<?php } ?>

