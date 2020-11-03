<?php

/**
 * Plugin Name:     Filter Media Library by Post
 * Plugin URI:      http://qstudio.us/
 * Description:     Filter the lits of items in the WordPress Media Library by the post they are attached to
 * Version:         0.0.2
 * Author:          Q Studio
 * Author URI:      http://qstudio.us
 * License:         GPL2
 * Class:           Filter_Media_Library_By_Post
 * Text Domain:     filter-media-library-by-post
 */

defined( 'ABSPATH' ) OR exit;

if ( ! class_exists( 'Filter_Media_Library_By_Post' ) ) {
    
    // instatiate plugin via WP plugins_loaded - init was too late for CPT ##
    \add_action( 'plugins_loaded', array ( 'Filter_Media_Library_By_Post', 'get_instance' ), 0 );
    
    class Filter_Media_Library_By_Post {
        
        // version ##
        const version = '0.0.2';
        
        // post types ##
		static $post_types = [ 'page', 'post' ];

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
        private function __construct(){
		 
            // filter the query ##
            \add_filter( 'parse_query', array( __CLASS__, 'parse_query' ), 1 );

            // build the <select> dropdown ##
			\add_action( 'restrict_manage_posts', array( __CLASS__, 'restrict_manage_posts' ), 1 );
			
		}
		

		public static function log( $log ){

			// local testing only ##
			if( ! class_exists( '\q\core\helper' ) ){ return false; }

			\q\core\helper::log( $log );

		}
		
		

		public static function get_post_types(){

			// filter posts types to include ##
			$post_types = \apply_filters( 'q/filter_media_library_by_post/post_type', self::$post_types );

			// escape for SQL - done here due to bug of adding string of integer values ##
			$post_types = array_map(function($v) {
				return "'" . esc_sql($v) . "'";
			}, $post_types);

			// we need to return a csv string ##
			$return  = implode( ',', $post_types );

			// self::log( $return );

			// kick it back ##
			return $return;

		}


		public static function get_attachments(){

			global $wpdb;
			
			$array = $wpdb->get_results( 
				"
					select *
					FROM {$wpdb->posts}
					WHERE post_type = 'attachment'
					ORDER BY post_date
				"
			);

			// self::log( $array );
			
			return $array;

		}


		public static function get_parents(){

			// get attachments ##
			$attachment_query = self::get_attachments();

			// save parents ##
			$parents = [];

			// loop over to get unique parents ##
			// @todo - this could be moved to a single SQL query ##
			foreach( $attachment_query as $post ) {

				// add parent to parents ##
				$parents[] = $post->post_parent;

			}

			// drop duplicates ##
			$parents = array_unique( $parents );

			// escape for SQL - done here due to bug of adding string of integer values ##
			$parents = array_map(function($v) {
				return esc_sql($v);
			}, $parents);

			// we need to return a csv string ##
			$return  = implode( ',', $parents );

			// self::log( $return );

			// kick it back ##
			return $return;

		}



		public static function get_posts_with_attachments(){

			global $wpdb;
			
			$array = $wpdb->get_results( 
				"
					select *
					FROM {$wpdb->posts}
					WHERE ID IN (".self::get_parents().")
					&& post_type IN (".self::get_post_types().")
					ORDER BY post_date
				"
			);

			// self::log( $wpdb->last_query );
			
			return $array;

		}



		public static function get_attachment_count( $post_parent = null ){

			global $wpdb;
			
			$count = $wpdb->get_var( $wpdb->prepare(
				"
					select COUNT(*) 
					FROM {$wpdb->posts}
					WHERE post_parent = '%d'
					&& post_type = 'attachment'
				",
				$post_parent
				)
			);

			// self::log( $wpdb->last_query );
			
			return $count;

		}


        
        /**
         * Set query variables in $wp_query
         * 
         * @since       0.1
         */
        public static function parse_query( $wp_query ){

			// get the global $pagenow value ##
            global $pagenow;  

			// check we're on the upload view ##
            if (
				\is_admin() 
				&& 'upload.php' === $pagenow
				&& isset( $_GET['q_filter_parent'] ) 
				&& $_GET['q_filter_parent'] != '' 
			) {

					// sanitize ##
					$q_filter_parent = \sanitize_key( $_GET['q_filter_parent'] );

					// set a post parent ##
                    $wp_query->set( 'post_parent', $q_filter_parent );

                }

        }


        /**
         * build the <select> dropdown
         * 
         * @since       0.1
         * @link        http://wpquestions.com/question/show/id/8268
         */
        public static function restrict_manage_posts(){

			// bring in some globals ##
            global $pagenow, $wp_query;

			// upload check ##
            if ( 'upload.php' == $pagenow ) {

                // get current attachment parent ID -- if set ##
                $current = isset( $_GET['q_filter_parent'] ) ? \sanitize_key( $_GET['q_filter_parent'] ) : 0 ;

				// get posts with attachments ##
				$posts_with_attachments = self::get_posts_with_attachments();

?>
                <select name="q_filter_parent">
                    <option value=""><?php _e ( 'Uploaded To', 'filter-media-library-by-post' ); ?></option>
<?php

					foreach( $posts_with_attachments as $post_parent ) {

                        // highligh current selection ##
						$selected = ( $current == $post_parent->ID ) ? $selected = ' selected="selected"' : '' ;
						
						// get attachment count ##
						$count = self::get_attachment_count( $post_parent->ID ) ;
						// self::log( 'attachment count for post: '.$post_parent->ID.' --> '.$count );

?>
                    <option value="<?php echo $post_parent->ID; ?>"<?php echo $selected; ?>>
                        [ <?php echo $post_parent->post_type; ?> ] <?php echo $post_parent->post_title; ?> ( <?php echo $count; ?> )
                    </option>
<?php

                    }

        ?>
                </select>
<?php


            }

        }
        
        
    }
    
}
