<?php
/*
Plugin Name: What's In Your Headphones?
Plugin URI: http://wilderdrums.com
Description: Allows user's of a website to share music that they're listening to. It adds a front end widget and a dashboard widget that displays the song/album/artist entered by each user.
Version: 1.0
Author: David Wilder
Author URI: http://wilderdrums.com
License: GPLv2
*/

// A function to determine which user name to display.
function dw_in_your_headphones_which_username( $user ) {
	// Get the name to show.
	if ( $user->first_name ) {
		return $user->first_name;
	} else {
		return $user->display_name;
	}
}

// A function to create a list of user and their music setting.
// This takes one parameter, $show_names, which determines whether user names will be shown in the list on the front end.
function dw_in_your_headphones_get_list( $show_names = false ) {
	
	// If this is the dashboard, set $show_names to true.
	if ( is_admin() ) {
		$show_names = true;
	}
		
	// Get the list of user.
	$users = get_users();
	
	// Create a buffer to store the list.
	$list = '';
	
	foreach ( $users as $user ) {
		
		// Get the name to show.
		$name = dw_in_your_headphones_which_username( $user );
		
		// Check if the user has meta for status.
		$in_your_headphones_music = get_user_meta( $user->ID, 'in_your_headphones_music', true );
		$in_your_headphones_artist = get_user_meta( $user->ID, 'in_your_headphones_artist', true );
		$in_your_headphones_link = get_user_meta( $user->ID, 'in_your_headphones_link', true );
		
		// If there's a song or artist, then create a list item.
		if ( $in_your_headphones_music || $in_your_headphones_artist ) {
			
			// Create a list item.
			$list .= '<li class="in_your_headphones_list_item">';
			
			// Add the user's name, if $show_names is true.
			$list .= $show_names ? '<em>' . esc_html( $name ) . ':</em> ' : '';
			
			// If there is a URL, then open the link.
			$list .= $in_your_headphones_link ? '<a href="' . esc_url( $in_your_headphones_link ) . '" title="' . esc_attr__( 'Check it out', 'whats-in-your-headphones' ) . '" target="_blank">' : '';

			// Determine whether to show the music, artist, or both.
			$list .= $in_your_headphones_music ? esc_html( $in_your_headphones_music ) : '';
			$list .= ( $in_your_headphones_music && $in_your_headphones_artist ) ? ' <i>' . esc_html_x( 'by', 'whats-in-your-headphones', 'song by artist' ) . '</i> ' : '';
			$list .= $in_your_headphones_artist ? esc_html( $in_your_headphones_artist ) : '';
			
			// If there is a URL, then close the link.
			$list .= $in_your_headphones_link ? '</a>' : '';
			
			// Close the list item.
			$list .= '</li>';
			
		}
		
	}
	
	return $list;
}

// A function to create and echo link to the user profile page to update the music setting.
function dw_in_your_headphones_update_link() {
	
	// Get the current user's name.
	$current_user = wp_get_current_user();
	
	// Get the name to show.
	$name = dw_in_your_headphones_which_username( $current_user );
	
	echo '
		<div class="in_your_headphones_update">
			<p>';
			
	printf( __( 'Hi %s.', 'whats-in-your-headphones' ), $name );
	
	echo ' <a href="' . admin_url( 'profile.php#in_your_headphones' ) . '" title="' . __( 'Update it in your user settings.', 'whats-in-your-headphones') . '">' . __( 'What are you listening to?', 'whats-in-your-headphones' ) . '</a></p>
		</div>
	';
	
}

// Call the dashboard widget when the dashboard is being set up.
add_action( 'wp_dashboard_setup', 'dw_in_your_headphones_dashboard_widget' );

function dw_in_your_headphones_dashboard_widget() {
	
	// Call the function to create the widget.
	wp_add_dashboard_widget( 'dashboard_in_your_headphones', 'What\'s in your headphones?', 'dw_in_your_headphones_widget_display' );
	
	// Force this widget to the top of the admin page.
	// see http://codex.wordpress.org/Dashboard_Widgets_API
	
	// Globalize the metaboxes array.
	global $wp_meta_boxes;
	
	// Get the regular dashboard widgets array.
	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
	
	// Backup and delete the new widget from the end of the array.
	$wiyh_widget_backup = array( 'dashboard_in_your_headphones' => $normal_dashboard['dashboard_in_your_headphones'] );
	unset( $normal_dashboard['dashboard_in_your_headphones'] );
	
	// Merge the two arrays together so the new widget is at the beginning.
	$sorted_dashboard = array_merge( $wiyh_widget_backup, $normal_dashboard );
	
	// Save the sorted array back into the original metaboxes.
	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	
}

// 
function dw_in_your_headphones_widget_display() {

	// Get the list of users and their in your headphones setting.
	$list = dw_in_your_headphones_get_list();
	
	if ( $list ) {

		// Open an unordered list.
		echo '<ul class="in_your_headphones_list">';
		
		// Display the list items.
		echo $list;
	
		// Close the unordered list.
		echo '</ul>';
		
	}
	
	// Display a link asking the user to update their status.
	dw_in_your_headphones_update_link();
	
}


// Create a field for adding the text on the users edit page.
add_action( 'show_user_profile', 'dw_in_your_headphones_form' );
add_action( 'edit_user_profile', 'dw_in_your_headphones_form' );

// Create the function to display the user status form.
function dw_in_your_headphones_form( $user ) {
	
	// Get the meta data.
	$in_your_headphones_music = get_user_meta( $user->ID, 'in_your_headphones_music', true );
	$in_your_headphones_artist = get_user_meta( $user->ID, 'in_your_headphones_artist', true );
	$in_your_headphones_link = get_user_meta( $user->ID, 'in_your_headphones_link', true );
	
	// Display the form.
	?>
	
	<h3 id="in_your_headphones"><?php esc_html_e( 'What\'s in your Headphones?', 'whats-in-your-headphones' ); ?></h3>
	
	<table class="form-table">
		
		<tr>
			<th><label for="in_your_headphones_music"><?php esc_html_e( 'Music', 'whats-in-your-headphones' ); ?></label></th>
			
			<td>
				<input type="text" name="in_your_headphones_music" id="in_your_headphones_music" class="widefat" value="<?php echo esc_attr( $in_your_headphones_music ); ?>" />
				<br />
				<span class="description"><?php esc_html_e( 'What is the name of the song, album, genre or whatever it is your listening to?', 'whats-in-your-headphones' ); ?></span>
			</td>
		</tr>
		
		<tr>
			<th><label for="in_your_headphones_music"><?php esc_html_e( 'Artist', 'whats-in-your-headphones' ); ?></label></th>
			
			<td>
				<input type="text" name="in_your_headphones_artist" id="in_your_headphones_artist" class="widefat" value="<?php echo esc_attr( $in_your_headphones_artist ); ?>" />
				<br />
				<span class="description"><?php esc_html_e( 'Who is the performer, label, radio station, or whoever it is that put this great music together?', 'whats-in-your-headphones' ); ?></span>
			</td>
		</tr>
		
		<tr>
			<th><label for="in_your_headphones_link"><?php esc_html_e( 'Share a Link', 'whats-in-your-headphones' ); ?></label></th>
			
			<td>
				<input type="text" name="in_your_headphones_link" id="in_your_headphones_link" class="widefat" value="<?php echo esc_attr( $in_your_headphones_link ); ?>" />
				<br />
				<span class="description"><?php esc_html_e( 'Paste a link to where other people can check it out, like from YouTube for instance.', 'whats-in-your-headphones' ); ?></span>
			</td>
		</tr>
		
	</table>
	
	<?php
	
}

// Add custom styling for this widget.
add_action( 'admin_head', 'dw_in_your_headphones_load_styles' );

function dw_in_your_headphones_load_styles() {
	
	// Only load styles on the dashboard.
	echo '
	<style type="text/css">
		.in_your_headphones_list {
			margin-bottom: 8px;
			padding-bottom: 6px;
			border-bottom: 1px solid #eee;
		}
		.in_your_headphones_list_item {
			padding-left: 24px;
			line-height: 1.4em;
			background: url(' . plugins_url( 'images/icon_headphones.png', __FILE__ ) . ') no-repeat 0 50%;
		}
		.in_your_headphones_update > p {
			padding: 0;
			margin: 0;
			line-height: 1em
		}
	</style>';
	
}

// Make sure updates to the profile are saved.
add_action( 'personal_options_update', 'dw_in_your_headphones_update' );
add_action( 'edit_user_profile_update', 'dw_in_your_headphones_update' );

// Create a function to save the status update.
function dw_in_your_headphones_update( $user_id ) {
	
	// Check if the current user has permission to edit the user.
	if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
	}
	
	// Validate the entry. This can be text, punctuation and numbers.
	$in_your_headphones_music = sanitize_text_field( $_POST['in_your_headphones_music'] );
	$in_your_headphones_artist = sanitize_text_field( $_POST['in_your_headphones_artist'] );
	$in_your_headphones_link = esc_url_raw( $_POST['in_your_headphones_link'] );
	
	// Update the user's meta data.
	update_user_meta( $user_id, 'in_your_headphones_music', $in_your_headphones_music );
	update_user_meta( $user_id, 'in_your_headphones_artist', $in_your_headphones_artist );
	update_user_meta( $user_id, 'in_your_headphones_link', $in_your_headphones_link );
	
}


// Create a widget for the front end.
add_action( 'widgets_init', 'dw_in_your_headphones_register_widget' );

function dw_in_your_headphones_register_widget() {
	register_widget( 'dw_in_your_headphones_widget' );
}

// Create the widget class
class dw_in_your_headphones_widget extends WP_Widget {
	
	// Constructor
	function dw_in_your_headphones_widget() {
		$widget_options = array(
			'classname' => 'dw-in-your-headphones-widget',
			'description' => 'Display the list of users and the music they are listening to.'
		);
		
		// Pass the options to WP_Widget. Build the widget.
		$this->WP_Widget( 'dw_in_your_headphones_widget', 'What\'s In Your Headphones', $widget_options );
	}
	
	// Build the widget settings form.
	function form( $instance ) {
		
		$defaults = array( 'title' => 'I\'m Listening to...' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$show_names = $instance['show_names'];
		
		// Exit PHP and create the form.
		?>
		
		<p><?php esc_html_e( 'Title', 'whats-in-your-headphones' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		<p><input type="checkbox" name="<?php echo $this->get_field_name( 'show_names' ); ?>" <?php checked( $show_names, 'on' ); ?> /> <?php esc_html_e( 'Show users\' names in the widget.', 'whats-in-your-headphones' ); ?></p>
		
		<?php
		
	}
	
	// Create a method to save the settings.
	function update( $new_instance, $old_instance ) {
		
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['show_names'] = strip_tags( $new_instance['show_names'] );
		
		return $instance;
		
	}
	
	// Create a method to display the widget on the front end.
	function widget( $args, $instance ) {
		
		extract( $args );
		
		echo $before_widget;
		
		$title = apply_filters( 'widget_title', $instance['title'] );
		$show_names = empty( $instance['show_names']) ? false : true;
		
		if ( !empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}
		
		// Get the list of users and their in your headphones setting.
		$list = dw_in_your_headphones_get_list( $show_names );
	
		// If there is a list of users, output it.
		if ( $list ) {

			// Open an unordered list.
			echo '<ul class="in_your_headphones_list">';
		
			// Display the list items.
			echo $list;
	
			// Close the unordered list.
			echo '</ul>';
		
		} else {
			
			// There's no list of users, so just output a placeholder.
			echo '<p class="in_your_headphones_no_music">' . esc_html_x( 'The sound of silence', 'placeholder text', 'whats-in-your-headphones' ) . '</p>';
			
		}
		
		if ( is_user_logged_in() ) {
			
			// Display a link asking the user to update their status.
			echo '<br />';
			dw_in_your_headphones_update_link();
			
		}
		
		echo $after_widget;
		
	}
	
}