<?php
/**
 * Copyright (c) 2018.
 */

defined( 'ABSPATH' ) or die( "Cannot access pages directly." );

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class FeedTable extends WP_List_Table {


    function __construct() {
        global $status, $page;

        //Set parent defaults
        parent::__construct( array(
            'singular' => 'Feed',     //singular name of the listed records
            'plural'   => 'Feeds',    //plural name of the listed records
            'ajax'     => false        //does this table support ajax?
        ) );

    }


    function column_default( $item, $column_name ) {



        switch ( $column_name ) {


            case 'category_name':

                return $this->column_title( $item );

                break;


            case 'google_category':


                return $item['google_category'];

                break;


            case 'product_count':


                return $item['product_count'];

                break;


            case 'feed_link':


                return "<input type='text' readonly value='".$item['feed_link']."' />";

                break;


            case 'feed_view':


                return "<a target='_blank' href='".$item['feed_link']."'>View</a>";


                break;

            case 'feed_edit':


                return "<a target='_blank' href='".$item['feed_edit']."'>Edit</a>";


                break;

            default:


        }
    }


    function get_columns() {
        $columns = array(
            'category_name' => 'WooCommerce Category',
            'google_category' => 'Google Category',
            'product_count'     => 'Products',
            'feed_link'     => 'Feed Link',
            'feed_view'     => 'View',
            'feed_edit'     => 'Edit',


        );

        return $columns;
    }


    function get_sortable_columns() {
        $sortable_columns = array(
            'product_count'        => array( 'product_count', true ),
            'category_name'        => array('category_name', false)




        );

        return $sortable_columns;
    }


    function column_title( $item ) {


        $title = $item['category_name'];

        //Build row actions
        $actions = array(
        );

        //Return the title contents
        return sprintf( '%1$s %2$s',
            /*$1%s*/
            $title,
            /*$3%s*/
            $this->row_actions( $actions )
        );
    }




    function prepare_items( $s = "" ) {



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
                    'feed_link' => admin_url('admin-ajax.php?action=google_shopping_feed&amp;cat='.$term->term_id),
                    'feed_edit' => admin_url('term.php?taxonomy=product_cat&amp;tag_ID='.$term->term_id.'&amp;post_type=product&amp;gpf=highlight')

                );

            }

        }



        $data = $feeds;

        usort( $data, array( &$this, 'sort_data' ) );


        $per_page = $this->get_items_per_page( 'files_per_page', 48 );


        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();



        $this->_column_headers = array( $columns, $hidden, $sortable );


        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently
         * looking at. We'll need this later, so you should always include it in
         * your own package classes.
         */
        $current_page = $this->get_pagenum();

        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = count( $data );


        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );


        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;


        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil( $total_items / $per_page )   //WE have to calculate the total number of pages
        ) );
    }



    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'category_name';
        $order = 'desc';
        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }
        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }
        $result = strcmp( $a[$orderby], $b[$orderby] );
        if($order === 'asc')
        {
            return $result;
        }
        return -$result;
    }


}
