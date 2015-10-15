<?php namespace SolutionWorks\SocialFeed;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class SocialFeed {

	public static function get_streams() {
		$streams = array();

		$facebook = json_decode(static::get_facebook_feed());
		$twitter = json_decode(static::get_twitter_feed());
		$instagram = json_decode(static::get_instagram_feed());

		foreach ( $facebook->data as $key => $item ) {
			$stream['username'] = $item->from->name;
			$stream['url']      = sprintf( 'https://www.facebook.com/%s/posts/%s', $item->from->id, $item->object_id );
			$stream['text']     = $item->message;
			$stream['type']     = 'facebook';
			if ( isset( $item->picture ) ) {
				$stream['thumbnail'] = $item->picture;
			}
			$created_at             = date( 'Y-m-d H:i:s.000001', strtotime( $item->created_time ) );
			$streams[ $created_at ] = $stream;
		}

		foreach ( $twitter as $key => $item ) {
			$stream['username'] = $item->user->name;
			$stream['url']      = sprintf( 'https://twitter.com/%s/status/%s', $item->user->screen_name, $item->id );
			$stream['text']     = $item->text;
			$stream['type']     = 'twitter';
			if ( isset( $item->media ) ) {
				$thumbnail           = array_shift( $item->media );
				$stream['thumbnail'] = $thumbnail->media_url_https;
			}
			$created_at             = date( 'Y-m-d H:i:s.000002', strtotime( $item->created_at ) );
			$streams[ $created_at ] = $stream;
		}

		foreach ( $instagram->data as $key => $item ) {
			$stream['username'] = $item->user->username;
			$stream['url']      = $item->link;
			$stream['text']     = $item->caption->text;
			$stream['type']     = 'instagram';
			if ( isset( $item->images ) ) {
				$stream['thumbnail'] = $item->images->thumbnail->url;
			}
			$created_at             = date( 'Y-m-d H:i:s.000003', $item->created_time );
			$streams[ $created_at ] = $stream;
		}

		krsort( $streams );

		foreach ( $streams as $created_at => $stream ) {
			$stream['date'] = $created_at;
			$new_streams[]  = $stream;
		}

		return $new_streams;
	}

	private static function getSslPage( $url, $curl_option = null, $option_value = null ) {
		$ch = curl_init();
		if ( isset( $curl_option ) && isset( $option_value ) ) {
			curl_setopt( $ch, $curl_option, $option_value );
		}
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_REFERER, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec( $ch );
		curl_close( $ch );

		return $result;
	}

	private static function get_facebook_feed() {
		//extract(sw_get_config('social.facebook'));
		$id           = sw_get_option( 'social.facebook.id' );
		$count        = sw_get_option( 'social.facebook.count' );
		$access_token = sw_get_option( 'social.facebook.access_token' );

		$access_token = is_array( $access_token ) ? $access_token[ array_rand( $access_token, 1 ) ] : $access_token;

		return static::getSslPage( "https://graph.facebook.com/{$id}/feed?limit={$count}&access_token={$access_token}" );
	}

	private static function get_twitter_feed() {
		$username            = sw_get_option( 'social.twitter.username' );
		$count               = sw_get_option( 'social.twitter.count' );
		$consumer_key        = sw_get_option( 'social.twitter.consumer_key' );
		$consumer_secret     = sw_get_option( 'social.twitter.consumer_secret' );
		$access_token        = sw_get_option( 'social.twitter.access_token' );
		$access_token_secret = sw_get_option( 'social.twitter.access_token_secret' );

		$access_token = is_array( $access_token ) ? $access_token[ array_rand( $access_token, 1 ) ] : $access_token;
		$timestamp    = time() - 1;

		$url          = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
		$base         = 'GET&' . rawurlencode( $url ) . '&' . rawurlencode( "count={$count}&oauth_consumer_key={$consumer_key}&oauth_nonce={$timestamp}&oauth_signature_method=HMAC-SHA1&oauth_timestamp={$timestamp}&oauth_token={$access_token}&oauth_version=1.0&screen_name={$username}" );
		$key          = rawurlencode( $consumer_secret ) . '&' . rawurlencode( $access_token_secret );
		$signature    = rawurlencode( base64_encode( hash_hmac( 'sha1', $base, $key, true ) ) );
		$oauth_header = "oauth_consumer_key=\"{$consumer_key}\", oauth_nonce=\"{$timestamp}\", oauth_signature=\"{$signature}\", oauth_signature_method=\"HMAC-SHA1\", oauth_timestamp=\"{$timestamp}\", oauth_token=\"{$access_token}\", oauth_version=\"1.0\", ";

		return static::getSslPage( $url . "?screen_name={$username}&count={$count}", CURLOPT_HTTPHEADER, array(
			"Authorization: Oauth {$oauth_header}",
			'Expect:'
		) );
	}

	private static function get_instagram_feed() {
		$id           = sw_get_option( 'social.instagram.id' );
		$count        = sw_get_option( 'social.instagram.count' );
		$access_token = sw_get_option( 'social.instagram.access_token' );

		$access_token = is_array( $access_token ) ? $access_token[ array_rand( $access_token, 1 ) ] : $access_token;

		return static::getSslPage( 'https://api.instagram.com/v1/users/' . $id . '/media/recent/?count=' . $count . '&access_token=' . $access_token );
	}
}

$action = sw_get_config( 'social.action' );
$nopriv = sw_get_config( 'social.nopriv', true );

add_action( 'wp_ajax_' . $action, __NAMESPACE__ . '\\social_feed' );

if ( $nopriv ) {
	add_action( 'wp_ajax_nopriv_' . $action, __NAMESPACE__ . '\\social_feed' );
}

function social_feed() {
	/*
		$streams = static::get_streams();

		$per_page = 4;
		$page = 2;

		$offset = $per_page * ($page - 1);

		for ($i = $offset; $i < count($streams); $i++) {
			if ($i >= $offset + $per_page) {
				break;
			}
			$out[] = $streams[$i];
		}
	*/
	$response['success'] = true;
	$response['data']    = SocialFeed::get_streams();
	header( 'Content-type: application/json' );
	echo json_encode( $response );
	exit;
}
