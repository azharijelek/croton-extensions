<?php
/**
 * new WordPress Widget format
 * Wordpress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class zl_twitter_widget extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 **/
	function zl_twitter_widget() {
		$widget_ops = array( 'classname' => 'zl_twitter_widget', 'description' => 'Display Twitter Feed' );
		$this->__construct( 'zl_twitter_widget', '&raquo; zl Twitter', $widget_ops );
	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @param array  An array of standard parameters for widgets in this theme
	 * @param array  An array of settings for this widget instance
	 * @return void Echoes it's output
	 **/
	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		$title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		$username = empty($instance['username']) ? ' ' : apply_filters('widget_title', $instance['username']);
		$fetch = empty($instance['fetch']) ? ' ' : apply_filters('widget_title', $instance['fetch']);
		$key = empty($instance['key']) ? ' ' : apply_filters('widget_title', $instance['key']);
		$keysecret = empty($instance['keysecret']) ? ' ' : apply_filters('widget_title', $instance['keysecret']);
		$token = empty($instance['token']) ? ' ' : apply_filters('widget_title', $instance['token']);
		$tokensecret = empty($instance['tokensecret']) ? ' ' : apply_filters('widget_title', $instance['tokensecret']);
		$show_user_ava = empty($instance['show_user_ava']) ? '' : apply_filters('widget_title', $instance['show_user_ava']);

		
		echo $before_widget;
		if($title){
		echo $before_title;
		echo $title; // Can set this with a widget option, or omit altogether
		echo $after_title;
		}

	//
	// Widget display logic goes here
	//
/*oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo*/
/* Here We Go, BUild the Gate to prevent headache to find out which the Output*/
/*oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo*/

	$settings = array(
	    'oauth_access_token' => $token,
	    'oauth_access_token_secret' => $tokensecret,
	    'consumer_key' => $key,
	    'consumer_secret' => $keysecret
	);
	$url = 'https://api.twitter.com/1.1/users/lookup.json';
	$getfield = '?screen_name='.$username;
	$requestMethod = 'GET';

	$twitter = new TwitterAPIExchange($settings);
	$response = $twitter->setGetfield($getfield)
	    ->buildOauth($url, $requestMethod)
	    ->performRequest();
	$userdata = json_decode($response);
	$user = $userdata[0];
	
	/*echo '<pre>';
	var_dump(json_decode($response));
	echo '</pre>';*/


	$status_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
	$status_getfield = '?screen_name='.$username.'&count='.$fetch;
	$status_requestMethod = 'GET';

	$status_twitter = new TwitterAPIExchange($settings);
	$status_response = $status_twitter->setGetfield($status_getfield)
	    ->buildOauth($status_url, $status_requestMethod)
	    ->performRequest();
	if($status_response){
		$tweets = json_decode($status_response);
		/*echo '<pre>';
		print_r($tweets);
		echo '</pre>';*/
		 ?>

		<?php if( $show_user_ava == 1 ){ ?>
		<div class="croton_twit_userinfo">
			<div class="left">
				<?php if($user->profile_image_url): ?>
				<a href="https://twitter.com/'.$username.'" >
					<img src="<?php echo $user->profile_image_url;?>" alt="<?php echo $username; ?>"/>
				</a>
				<?php endif; ?>
			</div>
			<div class="left">
				<?php if($user->name) echo '<div class="zl_twt_un"><a href="https://twitter.com/'.$username.'" >'.$user->name.'</a></div>'; ?>
				<a href="https://twitter.com/<?php echo $username; ?>" >@<?php echo $username; ?></a>
			</div>
			<div class="clear"></div>
		</div>
		<?php } // endif; $show_user_ava ?>
		
		<?php 
		if($tweets){
			$addclass = '';
			if($show_user_ava != 1){
				$addclass = 'twit_noinfo';
			}
			echo '<script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>';
			echo '<div class="zl_twitter_slide '. $addclass .'">';
			foreach($tweets as $tweet){
				echo '<div class="zltwitloop">'."\n";
				// Echo Tweet
				if($tweet->text){
					$the_tweet = $tweet->text;

					// i. User_mentions must link to the mentioned user's profile.
					if($tweet->entities->user_mentions){
						foreach($tweet->entities->user_mentions as $key => $user_mention){
							$the_tweet = preg_replace(
								'/@'.$user_mention->screen_name.'/i',
								'<a href="http://www.twitter.com/'.$user_mention->screen_name.'" target="_blank">@'.$user_mention->screen_name.'</a>',
								$the_tweet);
						}
					}

					// ii. Hashtags must link to a twitter.com search with the hashtag as the query.
					if($tweet->entities->hashtags){
						foreach($tweet->entities->hashtags as $key => $hashtag){
							$the_tweet = preg_replace(
								'/#'.$hashtag->text.'/i',
								'<a href="https://twitter.com/search?q=%23'.$hashtag->text.'&src=hash" target="_blank">#'.$hashtag->text.'</a>',
								$the_tweet);
						}
					}

					// iii. Links in Tweet text must be displayed using the display_url
					//      field in the URL entities API response, and link to the original t.co url field.
					if(is_array($tweet->entities->urls)){
						foreach($tweet->entities->urls as $key => $link){
							$the_tweet = preg_replace(
								'`'.$link->url.'`',
								'<a href="'.$link->url.'" target="_blank">'.$link->url.'</a>',
								$the_tweet);
						}
					}

					echo '<div class="twitcontent">'.$the_tweet.'</div>';


					// 3. Tweet Actions
					//    Reply, Retweet, and Favorite action icons must always be visible for the user to interact with the Tweet. These actions must be implemented using Web Intents or with the authenticated Twitter API.
					//    No other social or 3rd party actions similar to Follow, Reply, Retweet and Favorite may be attached to a Tweet.
					// get the sprite or images from twitter's developers resource and update your stylesheet
					echo '
					<div class="twitter_intents">
						<p><a class="reply" href="https://twitter.com/intent/tweet?in_reply_to='.$tweet->id_str.'"><i class="fa fa-mail-reply"></i></a></p>
						<p><a class="retweet" href="https://twitter.com/intent/retweet?tweet_id='.$tweet->id_str.'"><i class="fa fa-retweet"></i></a></p>
						<p><a class="favorite" href="https://twitter.com/intent/favorite?tweet_id='.$tweet->id_str.'"><i class="fa fa-star"></i></a></p>
					</div>';


					// 4. Tweet Timestamp
					//    The Tweet timestamp must always be visible and include the time and date. e.g., “3:00 PM - 31 May 12”.
					// 5. Tweet Permalink
					//    The Tweet timestamp must always be linked to the Tweet permalink.
					echo '
					<p class="timestamp">
						<a href="https://twitter.com/'.$username.'/status/'.$tweet->id_str.'" target="_blank">
							'.date('h:i A M d',strtotime($tweet->created_at. '- 8 hours')).'
						</a>
					</p>';// -8 GMT for Pacific Standard Time;
				}
				
				echo '</div>';
			}
			echo '</div>';
		}
	

	} else {
		echo '<i class="fa fa-exclamation-triangle"></i> Failed to Receive Tweets. Make sure your username, Key, Key Secret, Token, and Token Secret is right. Otherwise, the error maybe has caused by twitter\'s server issue.';
	}
	

	

	 ?>


	


<?php

echo $after_widget;
}
/*oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo*/
/* </END of: Here We Go, BUild the Gate to prevent headache to find out which the Output*>
/*oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo*/
	/**
	 * Deals with the settings when they are saved by the admin. Here is
	 * where any validation should be dealt with.
	 *
	 * @param array  An array of new settings as submitted by the admin
	 * @param array  An array of the previous settings
	 * @return array The validated and (if necessary) amended settings
	 **/
	function update( $new_instance, $old_instance ) {

		// update logic goes here
		$updated_instance = $new_instance;

		$instance['title'] = $new_instance['title'];
		$instance['username'] = $new_instance['username'];
		$instance['fetch'] = $new_instance['fetch'];
		$instance['key'] = $new_instance['key'];
		$instance['keysecret'] = $new_instance['keysecret'];
		$instance['token'] = $new_instance['token'];
		$instance['tokensecret'] = $new_instance['tokensecret'];
		$instance['show_user_ava'] = $new_instance['show_user_ava'];

		return $updated_instance;
	}

	/**
	 * Displays the form for this widget on the Widgets page of the WP Admin area.
	 *
	 * @param array  An array of the current settings for this widget
	 * @return void Echoes it's output
	 **/
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, 
			array( 
				'title' => null,
				'username' => null,
				'fetch' => null,
				'token' => null,
				'key' => null,
				'keysecret' => null,
				'token' => null,
				'tokensecret' => null,
				'show_user_ava' => null
			));
		$title = $instance['title'];
		$username = $instance['username'];
		$fetch = $instance['fetch'];
		$key = $instance['key'];
		$keysecret = $instance['keysecret'];
		$token = $instance['token'];
		$tokensecret = $instance['tokensecret'];
		$show_user_ava = $instance['show_user_ava'];
		// display field names here using:
		// $this->get_field_id( 'option_name' ) - the CSS ID
		// $this->get_field_name( 'option_name' ) - the HTML name
		// $instance['option_name'] - the option value
		?>
		<p>
			<input id="<?php echo $this->get_field_id('show_user_ava'); ?>" name="<?php echo $this->get_field_name('show_user_ava'); ?>" type="checkbox" value="1" <?php checked( '1', $show_user_ava ); ?> />
			<label for="<?php echo $this->get_field_id('show_user_ava'); ?>"><?php _e('Show User\'s Info?', 'zatolab'); ?></label>
		</p>
		<h4>Twitter API Settings</h4>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">
			Title
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('username'); ?>">
			Username
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>" value="<?php echo esc_attr($username); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('fetch'); ?>">
			Items to Fetch
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('fetch'); ?>" name="<?php echo $this->get_field_name('fetch'); ?>" value="<?php echo esc_attr($fetch); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('key'); ?>">
			Key
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('key'); ?>" name="<?php echo $this->get_field_name('key'); ?>" value="<?php echo esc_attr($key); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('keysecret'); ?>">
			Key Secret
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('keysecret'); ?>" name="<?php echo $this->get_field_name('keysecret'); ?>" value="<?php echo esc_attr($keysecret); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('token'); ?>">
			Token
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('token'); ?>" name="<?php echo $this->get_field_name('token'); ?>" value="<?php echo esc_attr($token); ?>" />
			</label>
		</p>
		 <p>
			<label for="<?php echo $this->get_field_id('tokensecret'); ?>">
			Token Secret
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('tokensecret'); ?>" name="<?php echo $this->get_field_name('tokensecret'); ?>" value="<?php echo esc_attr($tokensecret); ?>" />
			</label>
		</p>
		<?php
	}
}

add_action( 'widgets_init', create_function( '', "register_widget( 'zl_twitter_widget' );" ) );