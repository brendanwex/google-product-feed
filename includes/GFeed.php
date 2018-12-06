<?php
/**
 * Created by PhpStorm.
 * User: BrendanDoyle
 * Date: 28/11/2018
 * Time: 09:44
 */
defined('ABSPATH') or die("Cannot access pages directly.");

class GFeed
{



    public $brand_tax;

    public function __construct()
    {

        $this->brand_tax = "product_brand";

        add_action('admin_menu', array($this, 'gpf_settings_page'));

        add_action("wp_ajax_google_shopping_feed", array($this, 'endpoint_xml_feed'));
        add_action("wp_ajax_nopriv_google_shopping_feed", array($this, 'endpoint_xml_feed'));
        add_action('product_cat_edit_form_fields', array($this, 'gpf_category_meta_edit'), 10, 2);
        add_action('product_cat_add_form_fields', array($this, 'gpf_category_meta'), 10, 2);

        add_action('edited_product_cat', array($this, 'gpf_category_meta_save'), 10, 2);
        add_action('create_product_cat', array($this, 'gpf_category_meta_save'), 10, 2);
        add_filter('manage_product_cat_custom_column', array($this, 'gpf_category_col_content'), 10, 3);
        add_filter('manage_edit-product_cat_columns', array($this, 'gpf_category_col'));


        add_action('admin_enqueue_scripts', array($this, 'gpf_plugin_assets'), 99);

    }




    public function gpf_plugin_assets(){

        wp_enqueue_style('gpf-admin', GPF_URL.'assets/css/gpf-admin.css', false, '1.0.0');


    }


    public function gpf_settings_page(){

        add_submenu_page('woocommerce', 'Google Shopping', 'Google Shopping', 'manage_categories', 'gpf-feeds', array($this, 'gpf_settings_page_content'));


    }

    public function gpf_settings_page_content(){

        include(GPF_BASE."/views/feeds.php");



    }


    public function get_all_feeds(){


        $feeds = array();

        $terms = get_terms(array('taxonomy' => 'product_cat'));

        foreach($terms as $term){

            $term_meta = get_option("taxonomy_$term->term_id");

            if (isset($term_meta['gpf_category']) && !empty($term_meta['gpf_category'])) {


                $feeds[] = array(

                  'category' => $term->term_id,
                  'category_name' => $term->name,
                  'google_category' =>  $term_meta['gpf_category'],
                  'product_count' => $term->count,
                  'feed_link' => admin_url('admin-ajax.php?action=google_shopping_feed&cat='.$term->term_id)
                );

            }

        }


        return $feeds;




    }
    public function clean_output_for_google($input, $limit=5000){



        $strip = wp_strip_all_tags( $input, true );

        if(strlen($strip) > $limit){

            $output = substr($strip, 0, $limit);

        }else{

            $output = $strip;
        }


        return $output;


    }

    public function gpf_category_col($columns){


        $columns['gpf_feed'] = __('Google Feed');


        return $columns;

    }
    function gpf_category_col_content($content, $column_name, $term_id)
    {
        if ('gpf_feed' == $column_name) {
            $t_id = $term_id;
            $term_meta = get_option("taxonomy_$t_id");


            if (isset($term_meta['gpf_category']) && !empty($term_meta['gpf_category'])) {
                $content = "";
                $content .= $term_meta['gpf_category']."<br />";
                $content .= "<a href='".admin_url('admin-ajax.php?action=google_shopping_feed&cat='.$t_id)."' target='_blank'>View Feed</a>";

            }
        }



        return $content;
    }

    public function gpf_category_meta($term){


        $google_categories = file_get_contents(GPF_BASE."/assets/google/taxonomy.en-GB.txt");

        $google_categories_list = explode("\n", $google_categories);

        unset($google_categories_list[0]); //remove first line comments

        include(GPF_BASE."/views/parts/product-cat-meta.php");


    }

    public function gpf_category_meta_edit($term){


        $google_categories = file_get_contents(GPF_BASE."/assets/google/taxonomy.en-GB.txt");

        $google_categories_list = explode("\n", $google_categories);

        unset($google_categories_list[0]); //remove first line comments



        include(GPF_BASE."/views/parts/product-cat-meta-edit.php");


    }

    function gpf_category_meta_save($term_id)
    {
        if (isset($_POST['term_meta'])) {

            $t_id = $term_id;
            $term_meta = get_option("taxonomy_$t_id");
            $cat_keys = array_keys($_POST['term_meta']);
            foreach ($cat_keys as $key) {
                if (isset ($_POST['term_meta'][$key])) {
                    $term_meta[$key] = $_POST['term_meta'][$key];
                }
            }
            // Save the option array.
            update_option("taxonomy_$t_id", $term_meta);
        }


    }



    public function get_brand($product_id){


        $brand = get_the_terms($product_id, $this->brand_tax);

        if($brand && ! is_wp_error( $brand )){
            return $brand[0]->name;
        }else{
            return "No Brand";
        }


    }


    public function get_google_category($product_id){

        $terms = get_the_terms( $product_id,  "product_cat" );

        if($terms & !is_wp_error($terms)){

            $term = $terms[0]->term_id;

            $term_meta = get_option("taxonomy_$term");

            if(isset($term_meta['gpf_category'])){
                return $term_meta['gpf_category'];
            }

        }

    }

    public function get_woo_category($product_id){

        $terms = get_the_terms( $product_id,  "product_cat" );

        if($terms & !is_wp_error($terms)){


            $array_terms = array();

            foreach($terms as $term){

                $array_terms[] = $term->name;
            }


            return implode(" > ", $array_terms);

        }

    }

    public function get_grouped_price($product){

        $grouped_price = 0;
        foreach ($product->get_children() as $child_id ) {
            $child = wc_get_product( $child_id );
            $grouped_price =+ $child->get_price();
        }

        $price = wc_price($grouped_price);

        return $price;

    }


    public function get_product_variations($parent_id){


        $args = array(
            'post_parent' => $parent_id,
            'post_status' => 'publish',
            'post_type' => 'product_variation',
            'posts_per_page' => -1,

        );

        $query = new WP_Query($args);

        $result = array();
        if($query->have_posts()){
            while ($query->have_posts()) {
                $query->next_post();
                $result[] = $query->post;
            }
            wp_reset_postdata();
        }
        wp_reset_query();


        return $result;


    }
    public function generate_feed(){



        isset($_GET['cat']) ? $cat = $_GET['cat'] : $cat = "";

        $feed_products = array();


        $args = array(

            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1

        );

        if(!empty($cat)){


            $args['tax_query'][] = array(

                'taxonomy' => 'product_cat',
                'terms' => $cat,
                'field' => 'term_id',
                'include_children' => false,
                'operator' => 'IN'

            );

        }


        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                $variable = false;

                $variations = $this->get_product_variations(get_the_ID());

                if($variations){
                    $variable = true;
                }


                if ( $variable){


                    foreach($variations as $variation){

                        $feed_products[] = $this->generate_feed_body_variable($variation->ID);


                    }


                }else{

                    $feed_products[] = $this->generate_feed_body_simple(get_the_ID());

                }



            }
        }

        wp_reset_postdata();


        return $feed_products;


    }

    public function generate_feed_body_simple($product_id){


        $product = new WC_Product($product_id);


        $gf_product = array();

        if(has_post_thumbnail()){
            $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
        }else{
            $image = "";
        }
        $attachment_ids = $product->get_gallery_attachment_ids();


        if($product->is_in_stock()){
            $stock = "in stock";
        }else{
            $stock = "out of stock";
        }

        //feed attributes
        $gf_product['g:id'] = get_the_ID();
        $gf_product['g:sku'] = $product->get_sku();
        $gf_product['title'] = $this->clean_output_for_google(get_the_title(), 150);
        $gf_product['description'] = $this->clean_output_for_google(get_the_content(), 5000);
        $gf_product['link'] = get_the_permalink();
        $gf_product['g:image_link'] = $image;

        //Extra Images
        if($attachment_ids) {
            foreach ($attachment_ids as $attachment_id) {
                $gf_product['g:additional_image_link']  = wp_get_attachment_url($attachment_id);
            }
        }

        $gf_product['g:availability'] = $stock;
        $gf_product['g:price'] = $product->get_regular_price()." ".get_option('woocommerce_currency');
        $gf_product['g:brand'] = $this->get_brand(get_the_ID());

        if($this->get_google_category($product->ID)){

            $gf_product['g:google_product_category'] = $this->get_google_category($product->ID);

        }

        if($this->get_woo_category($product->ID)){

            $gf_product['g:product_type'] = $this->get_woo_category($product->ID);

        }


        if (($gf_product['g:gtin'] == "") && ($gf_product['g:mpn'] == "")) { $gf_product['g:identifier_exists'] = "no"; };
        $gf_product['g:condition'] = "NEW"; //must be NEW or USED

        if ($product->is_on_sale()) {
            $gf_product['g:sale_price'] = $product->get_sale_price();
        }

        return $gf_product;

    }


    public function generate_feed_body_variable($variation_id){


        $product = new WC_Product_Variation($variation_id);


        $gf_product = array();

        if(has_post_thumbnail()){
            $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
        }else{
            $image = "";
        }
        $attachment_ids = $product->get_gallery_attachment_ids();


        if($product->is_in_stock()){
            $stock = "in stock";
        }else{
            $stock = "out of stock";
        }

        //feed attributes
        $gf_product['g:id'] = $variation_id;
        $gf_product['g:sku'] = $product->get_sku();
         $gf_product['title'] = $this->clean_output_for_google(get_the_title($variation_id), 150);
        $gf_product['description'] = $this->clean_output_for_google(get_the_content($variation_id), 5000);
        $gf_product['link'] = get_the_permalink();
        $gf_product['g:image_link'] = $image;
        $gf_product['g:item_group_id'] = get_the_ID();

        //Extra Images
        if($attachment_ids) {
            foreach ($attachment_ids as $attachment_id) {
                $gf_product['g:additional_image_link']  = wp_get_attachment_url($attachment_id);
            }
        }

        $gf_product['g:availability'] = $stock;
        $gf_product['g:price'] = $product->get_regular_price()." ".get_option('woocommerce_currency');
        $gf_product['g:brand'] = $this->get_brand(get_the_ID());

        if($this->get_google_category($product->ID)){

            $gf_product['g:google_product_category'] = $this->get_google_category($product->ID);

        }

        if($this->get_woo_category($product->ID)){

            $gf_product['g:product_type'] = $this->get_woo_category($product->ID);

        }


        if (($gf_product['g:gtin'] == "") && ($gf_product['g:mpn'] == "")) { $gf_product['g:identifier_exists'] = "no"; };
        $gf_product['g:condition'] = "NEW"; //must be NEW or USED

        if ($product->is_on_sale()) {
            $gf_product['g:sale_price'] = $product->get_sale_price();
        }

        return $gf_product;

    }




    public function endpoint_xml_feed(){



        $shop_name = get_bloginfo("name");
        $shop_link =  site_url();

        $feed_products = $this->generate_feed();





        $doc = new DOMDocument('1.0', 'UTF-8');

        $xmlRoot = $doc->createElement("rss");
        $xmlRoot = $doc->appendChild($xmlRoot);
        $xmlRoot->setAttribute('version', '2.0');
        $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:g', "http://base.google.com/ns/1.0");

        $channelNode = $xmlRoot->appendChild($doc->createElement('channel'));
        $channelNode->appendChild($doc->createElement('title', $shop_name));
        $channelNode->appendChild($doc->createElement('link', $shop_link));

        foreach ($feed_products as $product) {
            $itemNode = $channelNode->appendChild($doc->createElement('item'));
            foreach($product as $key=>$value) {
                if ($value != "") {
                    if (is_array($product[$key])) {
                        $subItemNode = $itemNode->appendChild($doc->createElement($key));
                        foreach($product[$key] as $key2=>$value2){
                            $subItemNode->appendChild($doc->createElement($key2))->appendChild($doc->createTextNode($value2));
                        }
                    } else {
                        $itemNode->appendChild($doc->createElement($key))->appendChild($doc->createTextNode($value));
                    }

                } else {

                    $itemNode->appendChild($doc->createElement($key));
                }

            }
        }


        $doc->formatOutput = true;
        echo $doc->saveXML();


        wp_die();

    }


}
$google_shopping_feed = new GFeed();