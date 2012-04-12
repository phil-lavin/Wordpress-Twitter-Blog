=== Plugin Name ===
Contributors: wildcuddler
Tags: twitter, comments, bitly, posting, URL Shortening, tracking, stats
Requires at least: 2.7
Tested up to: 3.0
Stable tag: 0.8.4


Tweets your post, then captures tweets replying to that tweet and converts them to comments.

== Description ==

<strong>Updated to use Twitter OAuth. You'll have to re-login to Twitter. Basic Authentication is scheduled to end in June breaking all versions of this plugin before version 0.8</strong>

Twitter Blog will not only tweet your blog post, but it will also check hourly for replies to that tweet and turn it into a comment on that blog post. It also uses the <a href="http://bit.ly">bit.ly</a> API for URL shortening and adds link generated to your bit.ly account for tracking purposes. Tweets with a hashtag of #blog (customizable) will also be converted to a blog post.

Check out a <a href="http://www.twitterblogplugin.com">working example of Twitter Blog in use</a>.

If you find any bugs or have a suggestion: chris@twitterblogplugin.com

= Current Features =
* Tweets about your blog post on posting
* Creates a bit.ly link when your blog post is tweeted and adds it to your bit.ly link history
* Checks for replies to the tweet hourly and converts them to comments
* Direct Message is sent to the user notifying them their tweet has been added to your blog post
* Comment will be moderated and administrator will be notified according to system settings.
* Customizable Tweet format
* Converts tweets with #blog hashtag to a blog post. (Hashtag is customizable)
* Allows for Start and End parts of tweet to be customized.

= Future Enhancements =
* Use the Twitter user's image for the comment image
* Add Twitter hashtags to tweets for individual posts

== Installation ==
1. Upload `twitter_blog` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings -> Twitter Blog, and set your Twitter username and password as well as your bit.ly username and API key.

== Frequently Asked Questions ==

= Do I need a bit.ly account =

Yes, currently you do.

= I've got a suggestion or problem, what should I do? =

Contact twitter_blog@milkdogdesign.com

== Changelog ==

= 0.8.4 (06/25/2010) =
* Fixed: Blog posts weren't tweeted because of change in WP code.

= 0.8.3 (06/03/2010) =
* Fixed: Wouldn't install if another plugin using the same OAuth class was already installed.

= 0.8.2 (06/02/2010) =
* Fixed: Blog post tweets are displaying bad characters.
* Will tweet any post that hasn't already been posted regardless of status. (Thanks David)

= 0.8.1 (05/27/2010) =
* Fixed: Callback script didn't work with all hosts.

= 0.8 (05/26/2010) =
* Converted from basic Twitter Auth to a more secure Twitter OAuth
* Option to send DMs to users that replied to a tweeted post.
* Fixed: Strip HTML tags out of post titles when tweeted.

= 0.7.5 (11/10/2009) =
* Replies to blog posts created from hashtagged tweets are converted to blog comments.

= 0.7.4 (08/28/2009) =
* Fixed: Bit.ly API Key is copied into the Bit.ly username field

= 0.7.3 (08/24/2009) =
* Fixed: Warning not displayed on Settings page on first visit.
* Fixed: Bit.ly login fails if there is white space on either the login or API

= 0.7.2 (08/24/2009) =
* Fixed: Errors displayed on settings page for 1 refresh after they were corrected.

= 0.7.1 (08/24/2009) =
* Displays warning on every admin page if Twitter or Bit.ly login fail.

= 0.7 (08/22/2009) =
* Converts tweets with #blog hashtag to a blog post. (Hashtag is customizable)
* Allows for Start and End parts of tweet to be customized.

= 0.6 (08/04/2009) =
* Will truncate blog post titles that will push the tweet over the 140 characters allowed.
* Checks Twitter and bit.ly authentication when credentials are saved.
* Fixed (This time for real): Tweets that weren't in reply to any blog posts were being converted to blog comments.

= 0.5.1 (08/04/2009) =
* Fixed: Tweets that weren't in reply to any blog posts were being converted to blog comments.

= 0.5 (08/04/2009) =
* Changed Twitter reply search to reduce the amount of Twitter API calls from many down to one.

= 0.4 (08/04/2009) =
* Comments added will now be approved or wait for approval based on your system settings.
* Reduced the amount of Twitter API calls used each time comments are searched for.
* Email notifications for comments will be sent according to system settings.
* Manually creating comments will now display Twitter API errors and the number of comments created when successful.

= 0.3.1 (08/03/2009) =
* Fixed: API call for Twitter reply searching failed.

= 0.3 (08/03/2009) =
* Improved search for Twitter replies that need to be converted to blog comments.

= 0.2.2 (07/31/2009) =
* Fixed: Bit.ly links aren't Tweeted when the URLs contained some certain characters.

= 0.2.1 (07/31/2009) =
* Fixed: Post Titles with `&` and other characters didn't post correctly.

= 0.2 (07/30/2009) =
* Added option to not create comments from Twitter replies
* Added button to manually check for Tweets to be converted to comments. 
* Fixed: Post is tweeted again if you re-publish the post.

= 0.1.1 (07/25/2009) =
* Fixed: Links aren't tweeted properly

= 0.1.0 (07/25/2009) =
* Alpha release

