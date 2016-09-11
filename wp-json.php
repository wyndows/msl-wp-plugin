<?php

/*
  Plugin Name: WP_Json
  Description: A simple plugin for getting Json data from Mindshare Labs API and displaying the data in an organized manner.  Use 'Settings' to choose the category and number of posts to view.  Use 'Display' to view them.
  Version: 1.0
  Author: Marlan Ball
  License: GPLv2+
*/


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


	// displays the output from the Mindshare API using settings from wpj-settings() and the 'display' shortcode.
	public static function wpj_display() {

		// checking if user has submitted the settings page and if not sends user to settings page.  prevents an error if user didn't go there first.
		if ( !isset( $_SESSION['number_of_posts'] ) ) {
			WP_Json::wpj_settings();
			die;
		} else {
			$numPosts = $_SESSION['number_of_posts'];
		}

		//displays the category of posts at the top of the display page from shortcode 'display'
		echo '<p style="color: blue; font-size: 24px; text-transform: uppercase">' . do_shortcode( '[display]' ) . '</p>';

		// iterating through the number of posts desired to be displayed
		for ( $n = 1; $n <= $numPosts; $n ++ ) {

			// checking if the amount of desired posts is greater than the actual available and stopping the display if it is
			if ( $n > count( $_SESSION['title'] ) ) {
				die;
			}

			// setting the date value to just year/month/day
			$date = substr( ( $_SESSION['date'][ $n - 1 ] ), 0, 10 );

			// displaying the values that were added to the session array in the shortcode 'display', with some css styling
			echo '<img src=https://gallery.mailchimp.com/bf1d0f5bebb27fa15f1c84adb/images/78e77fb1-064b-47bf-96ea-43b234357c2c.png height=50 width=50>';
			echo '<p style="color: orangered; font-size: 18px; width: 66%;">' . $_SESSION['title'][ $n - 1 ] . '</p>';
			echo '<p style="color: black; font-size: 13px;">' . "by: " . $_SESSION['author'][ $n - 1 ] . "&nbsp;&nbsp;&nbsp;&nbsp;" . $date . '</p>';
			echo '<div style="color:black; font-size:13px; width: 66%;">' . $_SESSION['content'][ $n - 1 ] . '-------------------------------------------------------------' . '</div>';
			echo '<br><br><br><br><br>';
		}
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

		echo "<br><p><span style=\"font-size: 15px; color: blue\">WP Json Settings Page</span></p><p>After making your choices, please click <span style=\"font-size: 15px;font-weight: bold\">Submit</span>, then wait until the settings page resets to '1' and 'News' respectively (1-2 seconds).  Click on <span style=\"font-size: 15px;font-weight: bold\">Display</span> under <span style=\"font-size: 15px;font-weight: bold\">WP Json</span> plugin name to view chosen posts.</p>

<!-- The form for setting user choices -->
<form  action='' method='post'>
    <table width='450px'>
        <tr>
            <td valign='top'>
                <label for='number of posts'>Number of Posts </label>
            </td>
            <td valign='top'>
                <input  type='number' name='number_of_posts' value='1' min='1' max='$posts' size='1'>
            </td>
        </tr>

        <tr>
            <td valign='top'>
                <label for='post category'>Post Category </label>
            </td>
            <td valign='top'>
				<select name='post_category'>
					<option value='News'>News</option>
					<option value='Code'>Code</option>
					<option value='Downloads'>Downloads</option>
				</select>
            </td>
        </tr>

		<tr>
            <td valign='top'>
            	<input  type='submit' value='Submit'>            	
            </td>
        </tr>
    </table>
	</form>";

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
	public static function what_display() {

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

}

// wordpress shortcode method for a class
add_shortcode( 'display', array( 'WP_Json', 'what_display' ) );

// instantiate a new WP_Json object so the pages can be loaded
new WP_Json();


?>