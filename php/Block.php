<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types(  [ 'public' => true ] );
		/* We need to check if $post_types is not empty before we proceed to the next */
		if ( empty( $post_types ) ) {
        esc_html_e('No Posts Found.', 'xwp_site_counts');
        wp_die();
    	}
		$class_name = $attributes['className'];
		ob_start();

		?>
        <div class="<?php echo $class_name; ?>">
			<h2>Post Counts</h2>
			<ul>
			<?php
			foreach ( $post_types as $post_type_slug ) :
                $post_type_object = get_post_type_object( $post_type_slug  );
                $post_count = count(
                    get_posts(
						[
							'post_type' => $post_type_slug,
							'posts_per_page' => -1,
						]
					)
                );
				?>
				<li><?php esc_html_e('There are.', 'xwp_site_counts') . $post_count . ' ' .
					  $post_type_object->labels->name . '.'; ?></li>
			<?php endforeach;	?>

			</ul>

			<p><?php

			/* we need to check if we have the $_GET['post_id'] otherwise we can get the current post ID by using get_the_ID() function; */

			if (isset($_GET['post_id']) && $_GET['post_id'] != '') {

				$post_id =$_GET['post_id'];

			} else {
			$post_id = get_the_ID();
			}

			esc_html_e('The current post ID is: ', 'xwp_site_counts'). $post_id; 

			?></p>

			<?php

			/* by Default we don't have Tag and Categories inside pages section but it is something we can add so it is better to have a full fonction that consider pages'tag and categories as well for future use; */

			$args = array(
				'post_type' => ['post', 'page'],
				'post_status' => 'any', /* any will consider draft posts as well, so we need to clarify with the client on needs otherwise we have to change to publish so that only published post are considered*/ 
				'post__not_in' => array(get_the_ID()),
				'posts_per_page' => 5, 
				'order' => 'ASC',
				'tag'  => 'foo',
				'date_query' => array(
					array(
						'hour'      => 9,
						'compare'   => '>=',
					),
					array(
						'hour' => 17,
						'compare'=> '<=',
					),
				),
           
				  
			));

			/* we separate the tax from the main $agrs in case we would like to add more categories to the clause; */
 			$args['tax_query'][] = array('relation' => 'AND');
 			$args['tax_query'][] = array(
		      array(
		        'taxonomy' => 'category',
		        'field' => 'slug',
		        'terms' => 'baz'
		      )
		    );

			$query = new WP_Query($args);
			$count_total = $query->found_posts; 

			if ( $count_total != 0 ) :
				?>
				 <h2> <?php echo $count_total." ".esc_html_e('posts with the tag of foo and the category of baz.', 'xwp_site_counts') ?></h2>
                <ul>
                <?php

                /* since we already limit the result to 5 so no need for the array_slice; */
                if( $query->have_posts() ) {  
                		while( $query->have_posts() ) { 
                				$query->the_post(); global $post;?>
                	<li><?php echo get_the_title(); ?></li>

                	<?php
						}
					}
					else: 
					?>
					 <h2> <?php esc_html_e('No posts found with the tag of foo and the category of baz.', 'xwp_site_counts') ?></h2>

			<?		

			endif;
		 	?>
			</ul>
			  <?php wp_reset_postdata();
			    wp_reset_query();
			     ?>
		</div>
		<?php


		return ob_get_clean();
	}
}