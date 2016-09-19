<?php

/*
  Plugin Name: WP_Json
  Description: A simple plugin for getting Json data from Mindshare Labs API and displaying the data in an organized manner.  Use 'Settings' to choose the category and number of posts to view.  Use 'Display' to view them.
  Version: 1.0
  Author: Marlan Ball
  License: GPLv2+
*/

// to utilize the style sheet on the admin page
function wpj_load_plugin_css() {
	$plugin_url = plugin_dir_url( __FILE__ );
	wp_enqueue_style( 'style1', $plugin_url . 'css/style.css' );
}
add_action( 'admin_enqueue_scripts', 'wpj_load_plugin_css' );


class WP_Json {

	// constructor for WP_Json class
	function __construct() {

		add_action( 'admin_menu', array( $this, 'wpj_add_menu' ) );
		register_deactivation_hook( __FILE__, array( $this, 'wpj_uninstall' ) );

	}

		// actions performed at the loading of admin menu
		public static function wpj_add_menu() {

		// wordpress hook for adding WP Json to the menu page
		add_menu_page( 'WPJson', 'WP Json', 'manage_options', 'wpj-dashboard' );

		// wordpress hooks for adding a submenu to WP Json
		add_submenu_page( 'wpj-dashboard', 'WPJ' . ' Display', ' Display', 'manage_options', 'wpj-dashboard', array(
			__CLASS__,
			'wpj_display'
		) );

		add_submenu_page( 'wpj-dashboard', 'WPJ' . ' Settings', '<b style="color:#f9845b">Settings</b>', 'manage_options', 'wpj-set', array(
			__CLASS__,
			'wpj_settings'
		) );

		// starting session if not already started so data can be used by different pages
		if ( ! session_id() ) {
			session_start();

		}
	}

	/**
	* Actions performed at the loading of menu pages
	*/

	// displays the output from the Mindshare API using settings from wpj-settings() and the 'display_the_data' shortcode.
	public static function wpj_display() {

		echo (do_shortcode( '[display_the_data]' ));

	}

	/*
	 * Form for inputting the 2 settings, # of posts and what category of post
	 */
	public static function wpj_settings() {

		// retrieving the json data from the mindshare api and converting it to an array
		$response = wp_remote_get( 'https://mind.sh/are/wp-json/posts' );
		if ( is_array( $response ) ) {
			$body      = $response['body']; // use the content
			$jsonArray = json_decode( $body, true ); // decode the json response into an array
			$posts     = count( $jsonArray ); // this sets the maximum value to choose in the settings form
		}

		// the form for entering in settings used in the shortcode 'display' as well as the Display page
		include( 'views/display-form.php' );

		// making sure the POST went through and then moving the values to the SESSION cookie on the server so it can be used by other pages
		if ( isset ( $_POST['post_category'] ) ) {
			$_SESSION['post_category']   = $_POST['post_category'];
			$_SESSION['number_of_posts'] = $_POST['number_of_posts'];
		}
	}

	/*
	* Actions performed on de-activation of plugin
	*/
	function wpj_uninstall() {

		// destroy the session if we are deactivating the plugin
		if (isset($_COOKIE['PHPSESSID'])) {
			setcookie('PHPSESSID', "", time()-3600, "/");
			$_SESSION = array();
			session_destroy();
		}
	}

	/*
	 * Setting up the shortcode, this gets the data from the API and fills $_SESSION with results so they can be utilized in the wpj-display function
	 */
	public static function create_display() {

		// checking if the settings page has been submitted, if so setting category and number, and in case it hasn't a default setting
		if ( isset( $_SESSION['post_category'] ) && ( $_SESSION['number_of_posts'] ) ) {
			$postCategory = $_SESSION['post_category'];
			$numOfPosts   = $_SESSION['number_of_posts'];
		} else {
			$postCategory = 'News';
			$numOfPosts   = 1;
		}

		// retrieving the json data from the mindshare api and converting it to an array
		$response = wp_remote_get( 'https://mind.sh/are/wp-json/posts' );
		if ( is_array( $response ) ) {
			$body      = $response['body']; // use the content
			$jsonArray = json_decode( $body, true ); // decode the json response into an array
			$posts     = count( $jsonArray ); // calculating the number of posts available

			// iterate through the number of posts available at the api site
			for ( $p = 0; $p < $posts; $p ++ ) {

				// iterate through the number of posts from the settings page set by the user
				for ( $n = 1; $n <= $numOfPosts; $n ++ ) {
					$category      = ( $jsonArray[ $p ]['terms']['category'] );
					$categoryCount = count( $category );  // calculating how many different categories are in each post

					// iterating through the categories available in the post
					for ( $i = 0; $i < $categoryCount; $i ++ ) {
						$categoryName = ( $jsonArray[ $p ]['terms']['category'][ $i ]['name'] );
						// checking if the category name from the post matches from the settings page
						if ( $categoryName == $postCategory ) {
							// if category name does match then fill the various arrays with data from the individual api post
							$title[ $p ]   = ( $jsonArray[ $p ]['title'] . "<br>" );
							$content[ $p ] = ( $jsonArray[ $p ]['content'] . "<br>" );
							$author[ $p ]  = ( $jsonArray[ $p ]['author']['name'] );
							$datepub[ $p ] = ( $jsonArray[ $p ]['date'] );
						}
					}
				}
			}
		}

		// set the session variables to the arrays created
		$_SESSION['title']   = $title;
		$_SESSION['content'] = $content;
		$_SESSION['author']  = $author;
		$_SESSION['date']    = $datepub;

		// return the category that the session arrays are now filled with
		return $postCategory;
	}

	public static function display_the_data() {
		// starting the session so we can use the data from the settings
		if ( ! session_id() ) {
			session_start();
		}

		// utilizing the style.css file for styling the output
		$plugin_url = plugin_dir_url( __FILE__ );
		wp_enqueue_style( 'style1', $plugin_url . 'css/style.css' );


		// checking if user has submitted the settings page and if not sends user to settings page.  prevents an error if user didn't go there first.
		if ( ! isset( $_SESSION['number_of_posts'] ) ) {
			WP_Json::wpj_settings();
			die;
		} else {
			$numPosts = $_SESSION['number_of_posts'];
		}

		//displays the category of posts at the top of the display page from shortcode 'display'
		include ('views/display-category.php');

		// iterating through the number of posts desired to be displayed
		for ( $n = 1; $n <= $numPosts; $n ++ ) {

			// checking if the amount of desired posts is greater than the actual available and stopping the display if it is
			if ( $n > count( $_SESSION['title'] ) ) {
				die;
			}

			// setting the date value to just year/month/day
			$date = substr( ( $_SESSION['date'][ $n - 1 ] ), 0, 10 );

			include ('views/display-posts.php');
		}


	}

}

// wordpress shortcode method for a class
add_shortcode( 'create_display', array( 'WP_Json', 'create_display' ) );
add_shortcode( 'display_the_data', array( 'WP_Json', 'display_the_data' ) );

// instantiate a new WP_Json object so the pages can be loaded
new WP_Json();


?>