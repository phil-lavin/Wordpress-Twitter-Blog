Changelog
=========

0.8.4.1
-------

First Phil's mod release. Changes as follows:

### Fixes ###

* URL encode the params passed to the bitly REST API. One of them is a URL... duh
* Changed all references of twitter IDs to their _str brothers. This avoids floating point numbers being converted to standard form when stringified
* Set the comment time to be the tweet time, not the current time. This seems to make more sense
* Forcefully set the IP and user agent after the comment has been added, else it'll be that of the user who triggers the auto fetcher job
* Forcefully mark comments as not approved, post insert, to avoid them being marked as spam by anti-spam plugins
* Pass count param (200) to statuses/mentions else it'll get the last 20 tweets from the mentions
* When updating tb_last_tweet_checked, remember what we updated it to else it'll be an invalid value when multiple tweets are being processed
* When updating tb_last_tweet_blog_post_checked, remember what we updated it to else it'll be an invalid value when multiple tweets are being processed
* Pass count param (200) to statuses/user_timeline when we're using a since_id rather than just when we're not
* Implement dirty remove_wp_magic_quotes() function as Wordpress core seems to auto escape request data regardless of magic_quotes setting. Ew.
* Moved tb_last_tweet_checked check above the continue to avoid unecessary excess work
* Reinstated twitter_username as the code uses this to strip @you from the beginning of the tweet

### New Features ###

* Added ability to prefix comments with an arbitrary string
* Added Analytics (utm source, medium and campaign) tracking inc admin interface config settings
* Changed to bitly API v3 as it's easier to use
* Mark comments as tb_is_tweet with meta data. This can be used to distinguish twitter comments from regular comments in the template
* Use your own app for better security

### Tidying Up ###

* Renamed to Twitter Blog - Phil's Mod to avoid original plugin updates affecting it
* Changed version to 0.8.4.1 to reflect the base. This will likely be dropped in future
* Removed slack white space from twitter-blog.php
* Removed redundant/invalid params in the $data array passed to wp_new_comment()
* Removed a bunch of debug code
* Tidied up implementation of params passed into api calls
* Added one WS after comment //. It annoyed me :(
* Removed legacy (I presume) curl $options array
* Removed redundant >= 1 on count()
* Fixed retarded way of getting 1 item from array
* Removed redundant connect.php
* Removed redundant references to the twitter password

0.8.4
-----

This is the original Chris Mielke release. Sadly, it's shit. See orig-readme.txt for the original changelog.
