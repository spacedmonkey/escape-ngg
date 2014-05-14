<?php

/**
 * Commands to help you escape NextGen Gallery
 * 
 */
class ENGG_Command extends WP_CLI_Command {

	/**
	 * Instance of Escape_NextGen_Gallery class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	private $class = null;
	
	public function __construct() {
		$this->class = Escape_NextGen_Gallery::init();
	}

	/**
	 * Count the number of posts with NextGen Gallery Shortcodes in this site.
	 *
	 * ## OPTIONS
	 * 
	 * [--shortcode=<shortcode>]
     * : Which shortcode to convert
	 *
	 * ## EXAMPLES
	 *
	 * wp escape-ngg count 
	 * 
	 */
	public function count($args, $assoc_args ) {
		$this->set_shortcode($assoc_args);
		WP_CLI::log( $this->class->count() );
	}

	/**
	 * Convert the NextGen Gallery Shortcodes in posts in this site into WordPress gallery shortcodes.
	 *
	 * ## OPTIONS
	 * 
	 * [--shortcode=<shortcode>]
     * : Which shortcode to convert
	 * 
	 * ## EXAMPLES
	 *
	 * wp escape-ngg convert
	 * 
	 */
	public function convert($args, $assoc_args ) {
		
		$this->set_shortcode($assoc_args);

		if(!in_array($this->class->shortcode, $this->class->allowed_shortcode)){
			WP_CLI::warning(sprintf( "Invalid shortcode: '%s'",$this->class->shortcode ));
			return;
		}

		$count = $this->class->count();
		WP_CLI::log( sprintf( 'Processing %d posts with %s shortcodes', $count, $this->class->shortcode ) );
		set_time_limit( 0 );

		$uploads = wp_upload_dir();
		$baseurl = $uploads['baseurl'];

		$post_ids = $this->class->get_post_ids();
		
		$progress = new \cli\progress\Bar( 'Progress',  $count );

		foreach ( $post_ids as $post_id ) {
			$progress->tick();
			$this->class->process_post( $post_id );
		}

		$progress->finish();
		

		foreach ( $this->class->infos as $info )
			WP_CLI::log( $info );

		foreach ( $this->class->warnings as $warning )
			WP_CLI::warning( $warning );


		$lines = array(
			(object) array( 'Converted' => 'posts converted', 'Count' => $this->class->posts_count ),
			(object) array( 'Converted' => 'images converted', 'Count' => $this->class->images_count ),
		);
		$fields = array( 'Converted', 'Count' );
		\WP_CLI\Utils\format_items( 'table', $lines, $fields );
	}

	/**
	 * Takes user input set the shortcode property to user input. 
	 *
	 *
	 */
	private function set_shortcode($assoc_args){
		// Default for shortcode
     	$defaults = array(
     		'shortcode'      => 'nggallery'
     	);
     	// Merge user args with defaults
     	$assoc_args = wp_parse_args( $assoc_args, $defaults );
		$this->class->shortcode = $assoc_args['shortcode'];
	}

}

WP_CLI::add_command( 'escape-ngg', 'ENGG_Command' );
