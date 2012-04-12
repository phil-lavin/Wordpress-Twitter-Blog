<?php
/*
Plugin Name: Twitter Blog
Plugin URI:  http://www.twitterblogplugin.com
Description: Twitter Blog will not only tweet your blog post, but it will also check hourly for replies to that tweet and turn it into a comment on that blog post. It also uses the <a href="http://bit.ly">bit.ly</a> API for URL shortening and adds link generated to your bit.ly account for tracking purposes. Tweets with a hashtag of #blog (customizable) will also be converted to a blog post.
Version: 0.8.4
Author: Chris Mielke
*/

// Copyright (c) 2009 Chris Mielke. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an add-on for WordPress
// http://wordpress.org/
//
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************


//For Twitter OAuth
@session_start();
if(!class_exists('TwitterOAuth'))
{
	require_once('twitteroauth/twitteroauth.php');
}
require_once('config.php');

class twitter_blog {
	
	var $twitter_username;
	var $twitter_password;
	
	var $bitly_username;
	var $bitly_api_key;
	
	var $tweet_comment_option;
	var $create_blog_post;
	var $create_blog_post_hashtag;
	var $tweet_prefix;
	var $tweet_postfix;
	
	public $tweet_post = true;
	
	function twitter_blog()
	{	
		//Gets Twitter username and password
		$this->twitter_username = get_option( 'tb_twitter_username' );
		$this->twitter_password = get_option( 'tb_twitter_password' );
		
		$this->twitter_oauth_token = get_option( 'tb_twitter_oauth_token' );
		$this->twitter_oauth_secret = get_option( 'tb_twitter_oauth_secret' );
		
		//Gets bit.ly username and API key
		$this->bitly_username = get_option( 'tb_bitly_username' );
		$this->bitly_api_key = get_option( 'tb_bitly_api_key' );
		
		//Twitter Blog Settings
		$this->tweet_comment_option = get_option( 'tb_tweet_comments' );
		$this->create_blog_post = get_option( 'tb_create_blog_post' );
		$this->create_blog_post_hashtag = get_option( 'tb_create_blog_post_hashtag' );
		$this->send_dm_confirmation = get_option( 'tb_send_dm_confirmation' );
		$this->tweet_prefix = get_option( 'tb_tweet_prefix' );
		$this->tweet_postfix = get_option( 'tb_tweet_postfix' );
		$this->auth_checked = get_option( 'tb_auth_checked' );
		
		//Inserts Twitter OAuth info into the DB
		if(isset($_REQUEST['oauth_token']) && isset($_REQUEST['oauth_verifier']))
		{
			//Create TwitteroAuth object with app key/secret and token key/secret from default phase
			$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
			
			//Request access tokens from twitter
			$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
			
			update_option( 'tb_twitter_oauth_token', $access_token['oauth_token']);
			$this->twitter_oauth_token = $access_token['oauth_token'];
			
			update_option( 'tb_twitter_oauth_secret', $access_token['oauth_token_secret']);
			$this->twitter_oauth_secret = $access_token['oauth_token_secret'];
			
			header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?page=twitter-blog-menu');
		}
		
		//Creates Twitter connection 
		$this->twitter_con = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $this->twitter_oauth_token, $this->twitter_oauth_secret);
	}
	
	function install_twitter_blog()
	{
		//Sets the default settings
		update_option( 'tb_tweet_comments', 'on' );
		update_option( 'tb_create_blog_post', 'on' );
		update_option( 'tb_create_blog_post_hashtag', 'blog' );
		update_option( 'tb_send_dm_confirmation', 'on' );
		update_option( 'tb_tweet_prefix', 'Blog Post:' );
		update_option( 'tb_tweet_postfix', '' );
		update_option( 'tb_auth_checked', 'false' );
	}
	
	function update_post_twitter_id($post_id, $twitter_status_id)
	{
		update_post_meta($post_id, '_twitter_status_id', $twitter_status_id);
			
		return true;

	}
	
	function update_comment_twitter_ids($post_id, $twitter_status_ids)
	{
		update_post_meta($post_id, '_twitter_comment_status_ids', $twitter_status_ids);
			
		return true;

	}

	function tweet_post($post_obj)
	{
		$post_id = $post_obj->ID;
		if(get_post_meta($post_id, '_tweet_sent', true) != '1' && $this->tweet_post )
		{
			$post = get_post($post_id);
			$post_link = get_permalink( $post_id );
			
			//Get bit.ly URL if API is set
			$bitly_options[CURLOPT_POSTFIELDS] = 'version=2.0.1&history=1&longUrl=' . $post_link . '&login=' . $this->bitly_username . '&apiKey=' . $this->bitly_api_key;
			$bitly_options[CURLOPT_RETURNTRANSFER] = true;
			
			$bitly_curl = curl_init( 'http://api.bit.ly/shorten' );
			curl_setopt_array($bitly_curl, $bitly_options);
			$bitly_response = curl_exec($bitly_curl);
			
			$bitly = json_decode($bitly_response);
			
			$post_link = urldecode($post_link);
			
			$bitly_link = $bitly->results->$post_link->shortUrl;
			
			curl_close($bitly_curl);
			
			//Checks the status length is longer than 140, and shortens as needed.
			$status = $this->tweet_prefix . ' ' . strip_tags($post->post_title) . ' ' . $bitly_link . ' ' . $this->tweet_postfix;
			$status = (strlen($status) <= 140) ? $status : $this->tweet_prefix . ' ' . substr(strip_tags($post->post_title), 0, (-(strlen($status) - 137)))  . '... ' . $bitly_link . ' ' . $this->twitter_postfix;
			
			//Updated status
			$twitter = $this->twitter_con->post( 'statuses/update',  array('status' => $status));

			$this->update_post_twitter_id($post_id, $twitter->id);
	
			update_post_meta($post_id, '_tweet_sent', '1' );
		}
		return $post_id;
	}
	
	function insert_twitter_comment($tweet, $post_id)
	{
		//Gets time for comment
		$time = current_time( 'mysql', $gmt = 0); 
		
		//Creates comment array
		$data = array(
			'comment_post_ID' => $post_id,
			'comment_author' => '@' . $tweet->user->screen_name,
			'comment_author_email' => '',
			'comment_author_url' => 'http://twitter.com/' . $tweet->user->screen_name . '/status/' . $tweet->id,
			'comment_content' => str_replace( '@' . $this->twitter_username, '', $tweet->text),
			'comment_type' => 'comment',
			'comment_parent' => 0,
			'user_ID' => 0,
			'comment_author_IP' => '',
			'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
			'comment_date' => $time,
			'comment_date_gmt' => $time,
			'comment_approved' => 1,
		);
		
		//Inserts the tweet as a comment
		$comment_ID = wp_new_comment($data);
		
		if ( get_option('comments_notify') && $data['comment_approved'] && $post->post_author != $commentdata['user_ID'] )
			wp_notify_postauthor($comment_ID, $data['comment_type']);
		
		
		//Sends a direct message to commenter if allowed
		if( $this->send_dm_confirmation == 'on' )
		{			
			//Send DM
			$twitter = $this->twitter_con->post( 'direct_messages/new',  array('text' => 'Your tweet has been added as a comment to @' . $this->twitter_username . '\'s blog - ' . get_permalink( $post_id ), 'screen_name' => $tweet->user->screen_name));
		}
	}
	
	function check_twitter_comments()
	{
		$tweets_added = 0;
		
		//Only search for tweets if turned on
		if($this->tweet_comment_option == 'on' )
		{
			//Searches Twitter for mentions of the user.
			$options[CURLOPT_USERPWD] = $this->twitter_username . ':' . $this->twitter_password;
			$options[CURLOPT_RETURNTRANSFER] = true;
			
			//Will reduce the amount of tweets that were grabbed to save on API calls
			$last_tweet_checked_id = get_option( 'tb_last_tweet_checked' );
			$last_tweet_checked_array = ( $last_tweet_checked_id ) ? array( 'since_id' => $last_tweet_checked_id) : array();
			
			//Gets all mentions of user since last check
			$json = $this->twitter_con->get( 'statuses/mentions',  $last_tweet_checked_array);
			
			if(isset($json->error))
			{
				echo('<p class="error">' . $json->error . '</p>');
				
				return false;
			}		 
			
			foreach($json as $tweet)
			{
				//Checks to make sure a reply ID is set
				if(empty($tweet->in_reply_to_status_id))
					continue;
				
				//Checks to see if the current Tweet ID is higher than the current stored. Used to save on API calls.
				if($tweet->id > $last_tweet_checked_id)
					update_option( 'tb_last_tweet_checked', $tweet->id);
								  
				$twitter_posts = get_posts( 'meta_key=_twitter_status_id&meta_value=' . $tweet->in_reply_to_status_id);
				if(count($twitter_posts) >= 1)
				{	
					//Should only be one result and gets that info
					foreach($twitter_posts as $temp_post_info)
					{
						$post_info = $temp_post_info;
					}
					
					//Check to see which Tweets have already been converted to comments
					$comment_ids = get_post_meta($post_info->ID, '_twitter_comment_status_ids', true);
					$comment_array = explode( ',', $comment_ids);
					if(!in_array($tweet->id, $comment_array) || count($comment_array) == 0)
					{
						$update_ids = (strlen($comment_ids) > 0) ? $comment_ids . ',' . $tweet->id : $tweet->id;
						
						$this->update_comment_twitter_ids($post_info->ID, $update_ids);
						
						//Inserts the Tweet as a comment
						$this->insert_twitter_comment($tweet, $post_info->ID);
						
						//Increments the number of tweets that have been added.
						$tweets_added++;
					}
				}
			}
		}
		
		return $tweets_added;
	}
	
	//Checks Twitter for any tweets that should be converted to blog posts via the hashtag
	function check_twitter_blog_posts()
	{
		//Only search for tweets if turned on
		if($this->create_blog_post == 'on' )
		{
			//Counts the number of posts that were created
			$posts_created = 0;
			
			//Checks to see if a "Twitter" category is already created
			$twitter_cat_id = get_cat_ID( 'Twitter' );
			if( $twitter_cat_id == 0)
				//Creates the Category
				$twitter_cat_id =  wp_create_category( 'Twitter' );
			
			//Searches Twitter for mentions of the user.
			$options[CURLOPT_USERPWD] = $this->twitter_username . ':' . $this->twitter_password;
			$options[CURLOPT_RETURNTRANSFER] = true;
			
			//Will reduce the amount of tweets that were grabbed to save on API calls
			$last_tweet_blog_post_checked_id = get_option( 'tb_last_tweet_blog_post_checked' );
			$last_tweet_checked_array = ( $last_tweet_blog_post_checked_id ) ? array( 'since_id' => $last_tweet_blog_post_checked_id) : array('count' => 200);
			
			$json = $this->twitter_con->get( 'statuses/user_timeline',  $last_tweet_checked_array );
			
			if(isset($json->error))
			{
				echo('<p class="error">' . $json->error . '</p>');
				
				return false;
			}		 
			
			foreach($json as $tweet)
			{
				//Checks to see if the current Tweet ID is higher than the current stored. Used to save on API calls.
				if($tweet->id > $last_tweet_blog_post_checked_id)
					update_option( 'tb_last_tweet_blog_post_checked', $tweet->id);
				
				//Skip the tweet if it doesn't have the hashtag
				if( !substr_count( $tweet->text, '#' . $this->create_blog_post_hashtag ) )
					continue;
				
				//Checks to see if the tweet has already been converted
				$checked_tweet_id_list = get_option( 'tb_created_blog_post_twitter_ids' );
				if( in_array( $tweet->id, explode( ',', $checked_tweet_id_list ) ) )
					continue;
				else
					update_option( 'tb_created_blog_post_twitter_ids', $checked_tweet_id_list . ',' . $tweet->id);
				
				//Strips out the hashtag out of the tweet
				$tweet_text = str_replace( '#' . $this->create_blog_post_hashtag, '', $tweet->text );
				
				//Posts the Tweet
				$my_post = array();
				$my_post['post_title'] = 'Twitter: ' . $tweet_text;
				$my_post['post_content'] = '<p><img src="' . $tweet->user->profile_image_url . '" class="alignleft" /><a href="http://twitter.com/' . $tweet->user->screen_name . '"><strong>' . $tweet->user->screen_name . '</strong></a> ' . $tweet_text . '</p><p>[<a href="http://twitter.com/' . $tweet->user->screen_name . '/status/' . $tweet->id . '">Source</a>]' ;
				$my_post['post_status'] = 'publish';
				$my_post['post_author'] = 1;
				$my_post['post_category'] = array($twitter_cat_id);
				
				//Do not tweet this post on publish
				$this->tweet_post = false;
				
				// Insert the post into the database
				$post_id = wp_insert_post( $my_post );
				
				//Reset it back to true
				$this->tweet_post = true;
				
				$this->update_post_twitter_id($post_id, $tweet->id);
				
				$posts_created++;

			}
		}
		
		return $posts_created;
	}
	
	//Checks to make sure the twitter credentials authenticate
	function verify_twitter_login()
	{
		//Verifies the user has logged in
		$json = $this->twitter_con->get('account/verify_credentials');
		
		//Checks if an error message was returned
		if(isset($json->error))
		{
			echo('<div class="error"><p><strong>Twitter Blog Plugin</strong>Twitter Authentication Error: ' . $json->error . ' <a href="options-general.php?page=twitter-blog-menu">Settings Page</a><br />
				 <em>Note: As of version 0.8, Twitter authentication is done with OAuth. You will need to re-enter your login information.</em></p></div>');
			return false;
		}
		else
		{
			return true;
		}
	}
	
	//Checks to make sure the bit.ly credentials authenticate
	function verify_bitly_login()
	{
		//Get bit.ly URL if API is set
		$bitly_options[CURLOPT_POSTFIELDS] = 'version=2.0.1&longUrl=http://www.google.com&login=' . $this->bitly_username . '&apiKey=' . $this->bitly_api_key;
		$bitly_options[CURLOPT_RETURNTRANSFER] = true;
		
		$bitly_curl = curl_init( 'http://api.bit.ly/shorten' );
		curl_setopt_array($bitly_curl, $bitly_options);
		$bitly_response = curl_exec($bitly_curl);
		
		$bitly = json_decode($bitly_response);
		
		//Checks if an error message was returned
		if(!empty($bitly->errorMessage))
		{
			echo('<div class="error"><p><strong>Twitter Blog Plugin</strong> Bit.ly Authentication Failed. Please try check your Username (case sensitive) and API Key. <a href="options-general.php?page=twitter-blog-menu">Settings Page</a></p></div>');
			return false;
		}
		else
		{
			return true;
		}
	}
	
	function schedule_twitter_cron() {
		wp_schedule_event(time(), 'hourly', 'hourly_twitter_comment_search' );
		wp_schedule_event(time(), 'hourly', 'hourly_twitter_blog_post_search' );
	}

	function unschedule_twitter_cron() {
		wp_clear_scheduled_hook( 'hourly_twitter_comment_search' );
		wp_clear_scheduled_hook( 'hourly_twitter_blog_post_search' );
	}
	
	//Runs at the top of every admin page.
	function admin_auth_check()
	{
		//If the Authentication check either failed or hasn't been done, run it
		if(($this->auth_checked == 'false' || !$this->auth_checked) && (substr_count( $_SERVER['REQUEST_URI'], 'options-general.php?page=twitter-blog-menu' ) == 0 ))
		{
			if($this->verify_twitter_login() && $this->verify_bitly_login())
			{
				$this->auth_checked = true;
				update_option( 'tb_auth_checked', 'true' );
			}
			else
			{
				$this->auth_checked = false;
				update_option( 'tb_auth_checked', 'false' );
			}
		}
		
		//Checks version of PHP
		list($ver_major, $ver_minor, $ver_release) = explode('.', phpversion());
		
		if($ver_major < 5)
		{
			echo('<div class="error"><p><strong>Twitter Blog Plugin error</strong>This blog is running on PHP 4 or lower. Please upgrade to a stable version of PHP 5 or higher.</p></div>');
		}
		
		return $this->auth_checked;
	}
	
	function twitter_blog_admin_menu_options() {
		echo( '<div class="wrap">
			<h2>Update Twitter Blog Settings</h2>' );
		echo('<pre>');
			//print_r($_SERVER);
		echo('</pre>');
			wp_nonce_field( 'update-options' );

			if(isset($_POST['submit']) && $_POST['submit'] == 'Update Options' )
			{
				//Updates Twitter Settings
				update_option( 'tb_twitter_username', $_POST['twitter_username']);
				$this->twitter_username = $_POST['twitter_username'];
				if(!empty($_POST['twitter_password']))
				{
					update_option( 'tb_twitter_password', $_POST['twitter_password'] );
				}
				
				//Updates Bit.ly settings
				update_option( 'tb_bitly_username', trim($_POST['bitly_username']));
				$this->bitly_username = trim($_POST['bitly_username']);
				
				update_option( 'tb_bitly_api_key', trim($_POST['bitly_api_key']));
				$this->bitly_api_key = trim($_POST['bitly_api_key']);
				
				//Updates Twitter Blog Settings
				update_option( 'tb_tweet_prefix', trim($_POST['tweet_prefix']));
				$this->tweet_prefix = trim($_POST['tweet_prefix']);
				
				update_option( 'tb_tweet_postfix', trim($_POST['tweet_postfix']));
				$this->tweet_postfix = trim($_POST['tweet_postfix']);
				
				update_option( 'tb_tweet_comments', $_POST['tweet_comments']);
				$this->tweet_comment_option = $_POST['tweet_comments'];
				
				update_option( 'tb_create_blog_post', $_POST['create_blog_post']);
				$this->create_blog_post = $_POST['create_blog_post'];
				
				update_option( 'tb_create_blog_post_hashtag', $_POST['create_blog_post_hashtag']);
				$this->create_blog_post_hashtag = $_POST['create_blog_post_hashtag'];
				
				update_option( 'tb_send_dm_confirmation', $_POST['send_dm']);
				$this->send_dm_confirmation = $_POST['send_dm'];
				
				echo( '<p class="updated">Settings Updated</p>' );
			}
			elseif(isset($_POST['submit']) && $_POST['submit'] == 'Create Comments' )
			{
				if($tweet_count = $this->check_twitter_comments())
					echo( '<p class="updated">' . $tweet_count . ' comment(s) created from Twitter replies.</p>' );
				else
					echo( '<p class="updated">No comments created from Twitter replies.</p>' );
			}
			elseif(isset($_POST['submit']) && $_POST['submit'] == 'Check for Twitter Blog Posts' )
			{
				if($post_count = $this->check_twitter_blog_posts())
					echo( '<p class="updated">' . $post_count . '  blog post(s) were created from your Tweets.</p>' );
				else
					echo( '<p class="updated">No blog posts were created from your tweets.</p>' );
			}
			
			//Tests Twitter Login & Bit.ly login
			$this->auth_checked = false;
			if($this->verify_twitter_login() && $this->verify_bitly_login())
			{
				$this->auth_checked = true;
				update_option( 'tb_auth_checked', 'true' );
			}
			else
			{
				$this->auth_checked = false;
				update_option( 'tb_auth_checked', 'false' );
			}
						
			//Checks if password is set for displaying
			$password_set = (!empty($this->twitter_password)) ? '' : '<span style="color: red;">Please set password</span>';
			
			//Checks the box for whether to Create comments from tweet replies.
			$tweet_comment_checkbox = ($this->tweet_comment_option == 'on' ) ? ' checked="checked"' : '';
			$create_blog_post_checkbox = ($this->create_blog_post == 'on' ) ? ' checked="checked"' : '';
			$send_dm_checkbox = ($this->send_dm_confirmation == 'on' ) ? ' checked="checked"' : '';
			
			$twitter = $this->twitter_con->get('account/verify_credentials');
			if(isset($twitter->error))
			{
				echo( '<a href="' . WP_PLUGIN_URL . '/twitter-blog/redirect.php?callback=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']) . '"><img src="' . WP_PLUGIN_URL . '/twitter-blog/images/lighter.png" alt="Sign in with Twitter"/></a>' );
			}
			else
			{
				//print_r($twitter);
				echo( '<p>
					<strong>Currently logged in as</strong><br />
					<img src="' . $twitter->profile_image_url . '" /> ' . $twitter->screen_name . '
				</p>
				<p>
					<strong>Login with a different twitter account</strong><br />
					<a href="' . WP_PLUGIN_URL . '/twitter-blog/redirect.php?callback=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']) . '"><img src="' . WP_PLUGIN_URL . '/twitter-blog/images/lighter.png" alt="Sign in with Twitter"/></a>
				</p>');	
			}
			
			echo( '<form name="twitter_blog_settings" method="post" action="' . str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . '">				
				<h3>bit.ly Settings</h3>
				<p>
					<label for="bitly_username"><strong>bit.ly Username</strong></label> <input type="text" size="40" name="bitly_username" value="' . $this->bitly_username . '" />
				</p>
				<p>
					<label for="bitly_api_key"><strong>bit.ly API Key</strong></label><br />
					<small>Register for an account at <a href="http://bit.ly">bit.ly</a> and find your API Key on the <a href="http://bit.ly/account/">Acounts</a> page.</small><br />
					<input type="text" size="40" name="bitly_api_key" value="' . $this->bitly_api_key . '" />
				</p>
				<h3>Twitter Blog Settings</h3>
				<p>
					<label for="twitter_prefix"><strong>Tweet Prefix</strong></label><br />
					<input type="text" size="40" name="tweet_prefix" value="' . $this->tweet_prefix . '" />
				</p>
				<p>
					<label for="twitter_postfix"><strong>Tweet Ending</strong></label><br />
					<input type="text" size="40" name="tweet_postfix" value="' . $this->tweet_postfix . '" />
				</p>
				<p>
					 <input type="checkbox" name="tweet_comments"' . $tweet_comment_checkbox  . ' /> <label for="tweet_comments"><strong>Create comments from Tweet replies.</strong></label>
				</p>
				<p>
					<input type="checkbox" name="create_blog_post"' . $create_blog_post_checkbox  . ' /> <label for="create_blog_post"><strong>Create blog post from Tweets.</strong></label>
				</p>
				<p>
					<input type="checkbox" name="send_dm"' . $send_dm_checkbox  . ' /> <label for="send_dm"><strong>Send Direct Message when a tweet is added as a comment.</strong></label>
				</p>
				<p>
					<label for="twitter_postfix"><strong>Hashtag to create blog posts from Tweets</strong></label><br />
					#<input type="text" size="40" name="create_blog_post_hashtag" value="' . $this->create_blog_post_hashtag . '" />
				</p>
				<p>
					<input type="submit" name="submit" value="Update Options" />
				</p>
				<h3>Manually Create Comments from Tweet Replies</h>
				<p>
					<input type="submit" name="submit" value="Create Comments" />
				</p>
				<h3>Manually Create Blog Posts from hashtaged tweets</h>
				<p>
					<input type="submit" name="submit" value="Check for Twitter Blog Posts" />
				</p>' );
		
			echo( '</form>
		</div>' );
	}
}

//Set up Twitter Blog menu

//Creates the hooks and actions.
if(class_exists( 'twitter_blog' ))
{
	$plugin_twitter_blog = new twitter_blog();
}

if(isset($plugin_twitter_blog))
{
	function twitter_blog_admin_menu() 
	{
		global $plugin_twitter_blog;
		add_options_page( 'Twitter Blog Settings', 'Twitter Blog', 8, 'twitter-blog-menu', array(&$plugin_twitter_blog, 'twitter_blog_admin_menu_options' ));
	}
	register_activation_hook( __FILE__,  array(&$plugin_twitter_blog, 'install_twitter_blog' ));
	register_activation_hook(__FILE__, array(&$plugin_twitter_blog, 'schedule_twitter_cron' ));
	register_deactivation_hook(__FILE__, array(&$plugin_twitter_blog, 'unschedule_twitter_cron' ));
	
	add_action( 'admin_menu',  'twitter_blog_admin_menu' );	
	add_action( 'hourly_twitter_comment_search', array(&$plugin_twitter_blog, 'check_twitter_comments' ));
	add_action( 'hourly_twitter_blog_post_search', array(&$plugin_twitter_blog, 'check_twitter_blog_posts' ));
	add_action( 'new_to_publish', array(&$plugin_twitter_blog, 'tweet_post' ));	
	add_action( 'draft_to_publish', array(&$plugin_twitter_blog, 'tweet_post' ));	
	add_action( 'pending_to_publish', array(&$plugin_twitter_blog, 'tweet_post' ));	
	add_action( 'future_to_publish', array(&$plugin_twitter_blog, 'tweet_post' ));	
	add_action( 'publish_post', array(&$plugin_twitter_blog, 'check_twitter_comments' ));
	add_action( 'admin_notices', array(&$plugin_twitter_blog, 'admin_auth_check' ));	
}

?>