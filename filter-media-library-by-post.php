<?php

/**
 * Plugin Name:     Filter Media Library by Post
 * Plugin URI:      http://qstudio.us/
 * Description:     Filter the lits of items in the WordPress Media Library by the post they are attached to
 * Version:         0.0.1
 * Author:          Q Studio
 * Author URI:      http://qstudio.us
 * License:         GPL2
 * Class:           Filter_Media_Library_By_Post
 * Text Domain:     filter-media-library-by-post
 */

defined( 'ABSPATH' ) OR exit;

if ( ! class_exists( 'Filter_Media_Library_By_Post' ) ) {
    
    // instatiate plugin via WP plugins_loaded - init was too late for CPT ##
    add_action( 'plugins_loaded', array ( 'Filter_Media_Library_By_Post', 'get_instance' ), 0 );
    
    // click to stick ##
    define( "Q_STICKY_POST_TYPE", serialize ( array ( 'property' ) ) );
    define( "Q_STICKY_TITLE", __( "Feature", 'q-client' ) );
    define( "GOOGLE_MAPS_V3_API_KEY", '' );
    
    class Filter_Media_Library_By_Post {
        
        // version ##
        const version = '0.0.1';
        
        // for translation ##
        static $text_domain = 'filter-media-library-by-post';
        static $post_types = array( 'property' );
        
        // Refers to a single instance of this class. ##
        private static $instance = null;
        
        
        /**
         * Creates or returns an instance of this class.
         *
         * @return  Foo     A single instance of this class.
         */
        public static function get_instance() {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        }
        
        /**
         * Instatiate Class
         * 
         * @since       0.2
         * @return      void
         */
        private function __construct() 
        {
         
            // filter the query ##
            add_filter( 'parse_query', array( __CLASS__, 'parse_query' ) );

            // build the <select> dropdown ##
            add_action( 'restrict_manage_posts', array( __CLASS__, 'restrict_manage_posts' ) );
            
        }
        
        
        /**
         * Set query variables in $wp_query
         * 
         * @since       0.1
         */
        public static function parse_query( $wp_query ) 
        {

            global $pagenow;  

            if ( 'upload.php' === $pagenow ) {

                if ( is_admin() && isset( $_GET['post_id'] ) && $_GET['post_id'] != '' ) {

                    $original_query = $wp_query;

                    $wp_query->set('post_parent', $_GET['post_id']);

                    $wp_query = $original_query;

                    wp_reset_postdata();

                }

            }

        }


        /**
         * build the <select> dropdown
         * 
         * @since       0.1
         * @link        http://wpquestions.com/question/show/id/8268
         */
        public static function restrict_manage_posts()
        {

            global $pagenow, $wp_query;

            if ( 'upload.php' == $pagenow ) {

                // get current ID ##
                $current= isset( $_GET['post_id'] ) ? $_GET['post_id'] : '';
                
                #$queried_object = get_queried_object();
                #pr( get_query_var() );

                // prepare $args ##
                $args = array (
                    'post_type'         => self::$post_types,
                    'posts_per_page'    => -1,
                    'order'             => 'DESC',
                    'orderby'           => 'title',
                    'post_status'       => 'publish'
                );

                // don't mess with this query - please ## 
                #wp_reset_query();
                #wp_reset_postdata();

                $media_query = new WP_Query( $args );
                #pr( $wp_query );
                #pr( $media_query->query );
                #pr( $media_query->found_posts );

?>
                <select name="post_id">
                    <option value=""><?php _e ( 'Uploaded To', self::$text_domain ); ?></option>
<?php

                    while ( $media_query->have_posts() ) {

                        $media_query->the_post();

                        // highligh current selection ##
                        $selected = ( $current == $media_query->post->ID ) ? $selected = ' selected="selected"' : '' ;

                        // count ##
                        $count = count( get_children( array( 'post_parent' => $media_query->post->ID ) ) );

                        if ( $count > 0 ) {

?>
                    <option value="<?php echo $media_query->post->ID; ?>"<?php echo $selected; ?>>
                        <?php echo $media_query->post->post_title; ?> ( <?php echo $count; ?> )
                    </option>
<?php

                        }

                    }

        ?>
                </select>
<?php

                wp_reset_postdata();

            }

        }
        
        
    }
    
}
