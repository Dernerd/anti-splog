<?php
/*
Plugin Name: Multisite Anti-Splog
Plugin URI: https://n3rds.work/docs/anti-splog-handbuch/
Description: Das ultimative Plugin und Service zum Stoppen und Beseitigen von Splogs und Spam-Registrierungen in WordPress Multisite und BuddyPress
Author: WMS N@W
Author URI: https://n3rds.work
Version: 2.3.2
Network: true
*/

/*
Copyright 2017-2022 WMS N@W (https://n3rds.work)
Author: DerN3rd

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require 'psource/psource-plugin-update/psource-plugin-updater.php';
use Psource\PluginUpdateChecker\v5\PucFactory;
$MyUpdateChecker = PucFactory::buildUpdateChecker(
	'https://n3rds.work//wp-update-server/?action=get_metadata&slug=anti-splog', 
	__FILE__, 
	'anti-splog' 
);

//check for activating
add_action( 'admin_head', 'ust_make_current' );

add_action( 'plugins_loaded', 'ust_localization' );
//wp-signup changes
add_action( 'plugins_loaded', 'ust_wpsignup_init' );
add_filter( 'the_content', 'ust_wpsignup_shortcode' );
add_filter( 'widget_text', 'ust_wpsignup_shortcode' );
add_filter( 'wp_signup_location', 'ust_wpsignup_filter' );
//keep table updated
add_action( 'make_spam_blog', 'ust_blog_spammed' );
add_action( 'make_ham_blog', 'ust_blog_unspammed' );
add_action( 'wpmu_new_blog', 'ust_blog_created', 10, 2 );
add_action( 'delete_blog', 'ust_blog_deleted', 10, 2 );
add_action( 'wpmu_delete_user', 'ust_user_deleted' );
add_action( 'wpmu_blog_updated', 'ust_blog_updated' );
//replace new blog email function
remove_action( 'wpmu_new_blog', 'newblog_notify_siteadmin', 10, 2 );
add_action( 'wpmu_new_blog', 'ust_newblog_notify_siteadmin', 10, 2 );
//various
add_action( 'init', 'ust_admin_url' );
add_action( 'admin_init', 'ust_admin_scripts_init' );
add_action( 'save_post', 'ust_check_post' );
add_action( 'network_admin_menu', 'ust_plug_pages' );
//add_action( 'network_admin_notices', 'ust_api_warning' );
add_action( 'network_admin_notices', 'ust_install_notice' );

add_action( 'signup_blogform', 'ust_signup_fields', 50 );
add_action( 'bp_before_registration_submit_buttons', 'ust_signup_fields_bp', 50 ); //buddypress support
add_filter( 'wpmu_validate_blog_signup', 'ust_signup_errorcheck' );
add_action( 'bp_signup_validate', 'ust_signup_errorcheck_bp' ); //buddypress support
add_filter( 'wpmu_validate_user_signup', 'ust_pre_signup_user_check' );
add_filter( 'wpmu_validate_blog_signup', 'ust_pre_signup_check' );
add_action( 'bp_signup_validate', 'ust_pre_signup_check_bp' ); //buddypress support
add_action( 'bp_before_account_details_fields', 'ust_pre_signup_check_bp_error_display' ); //adds multicheck error message hook
add_filter( 'add_signup_meta', 'ust_signup_meta' );
add_filter( 'bp_signup_usermeta', 'ust_signup_meta' ); //buddypress support
add_action( 'signup_header', 'ust_signup_css' );
//add_action( 'ust_check_api_cron', 'ust_check_api' ); //cron action
add_action( 'plugins_loaded', 'ust_show_widget' );
add_action( 'wp_ajax_ust_ajax', 'ust_do_ajax' ); //ajax
add_action( 'wp_ajax_ust_test_regex', 'ust_test_regex' ); //ajax

//handle toolbar menu
add_action( 'init', 'ust_toolbar_init' );
add_action( 'wp_ajax_ust_toggle_blog_flag', 'ust_toolbar_ajax' );

register_activation_hook( __FILE__, 'ust_activate_check' );

//------------------------------------------------------------------------//

//---Functions------------------------------------------------------------//

//------------------------------------------------------------------------//

function ust_activate_check() {
	global $wp_version;

	//force multisite
	if ( ! is_multisite() ) {
		die( __( 'Anti-Splog ist nur mit Multisite-Installationen kompatibel.', 'ust' ) );
	} else if ( version_compare( $wp_version, '4.2', '<' ) ) {
		die( __( 'Diese Version von Anti-Splog ist nur mit WordPress 4.2 und höher kompatibel.', 'ust' ) );
	}

}

function ust_admin_url() {
	global $ust_admin_url, $wp_version;

	//setup proper urls
	$ust_admin_url = network_admin_url( 'admin.php?page=ust' );
}

function ust_install_notice() {
	if ( ! is_super_admin() ) {
		return;
	}

	if ( ! file_exists( WP_CONTENT_DIR . '/blog-suspended.php' ) ) {
		?>
		<div class="error fade">
		<p><?php _e( 'Bitte verschiebe die Datei blog-suspended.php aus dem Anti-Splog-Plugin in das Verzeichnis /wp-content/.', 'ust' ); ?></p>
		</div><?php
	}
}

function ust_show_widget() {
	global $current_site, $blog_id;

	if ( $current_site->blog_id == $blog_id ) {
		add_action( 'widgets_init', function() {return register_widget("UST_Widget");} );
	}
}

function ust_localization() {
	// Load up the localization file if we're using WordPress in a different language
	// Place it in this plugin's "languages" folder and name it "ust-[locale].mo"
	load_plugin_textdomain( 'ust', false, '/anti-splog/languages' );
}


function ust_make_current() {

	global $wpdb, $ust_current_version;

	if ( get_site_option( "ust_version" ) == '' ) {
		add_site_option( 'ust_version', '2.3.2' );
	}

	if ( get_site_option( "ust_version" ) == $ust_current_version ) {
		// do nothing
	} else {
		//update to current version
		update_site_option( "ust_version", $ust_current_version );

		//setup default patterns
		$default_patterns = array(
			0 =>
				array(
					'regex'   => '/[a-z]+[0-9]{1,3}[a-z]+/',
					'desc'    => __( 'Vergleiche Domains mit Ziffern zwischen Wörtern wie "some45blog"', 'ust' ),
					'type'    => 'domain',
					'action'  => 'splog',
					'matched' => 0,
				),
			1 =>
				array(
					'regex'   => '/ugg|louboutin|pharma|warez|download|megaupload|porn|viagra/i',
					'desc'    => __( 'Blockiere beliebte Spam-Wörter', 'ust' ),
					'type'    => 'domain',
					'action'  => 'splog',
					'matched' => 0,
				),
			2 =>
				array(
					'regex'   => '/\b\d+\b/',
					'desc'    => __( 'Blockiere Domänen mit nur Nummern in der Domäne.', 'ust' ),
					'type'    => 'domain',
					'action'  => 'block',
					'matched' => 0,
				),
			3 =>
				array(
					'regex'   => '/\b(ugg|louboutin|pharma|warez|download|megaupload|porn|viagra)\b/i',
					'desc'    => __( 'Nur auf vollständige Wörter prüfen', 'ust' ),
					'type'    => 'title',
					'action'  => 'splog',
					'matched' => 0,
				),
			4 =>
				array(
					'regex'   => '/[a-z]+[0-9]{1,3}[a-z]+/',
					'desc'    => __( 'Vergleiche Benutzernamen mit Ziffern zwischen Wörtern wie "some45blog"', 'ust' ),
					'type'    => 'username',
					'action'  => 'splog',
					'matched' => 0,
				),
			5 =>
				array(
					'regex'   => '/\b(actices|afractalreality|autorambler|aquadivingaccessories|asiavirtualsolutions|bangkokhotelhub|bangkokremovals|bettereyesight|browndecorationlights|bigbucks|bigstring|blurelizer|bigman|bizml|brandisdream|care2|carmanial|ce6launch|ceramiccoffecups|cheapgreenteabags|chestpain|chiefdan|chinatravel|coolyarddecorations|cottonsleepingbags|compaltd|crossandgarlic|dasemana|denphijugico|dietingadvise|dizaer|dobunny|drypipe|dynamailbox|dynainbox|ecorreos|econgate|electrostaticdisinfectantsprayers|erpin|extravagandideas|fancycarnavalmasks|fastmail|feidnepra|freeinvestoradvice|freephotoretouch|freshbreadcrumbs|frienced|forexbinaryoption|gawab|gemmasmith|genericimages|goinglownow|goodiploms|hamstercage|homeimprovements|hidebox|italiancarairbags|intained|instambox|iservmail|japantravel|jooffy|junkcarsfloridamiami|kellergy|kiyoakari|kogobee|lampartist|leatherdocumentbags|leathermenshoes|linkbuilding|linkbuildingtools|loanme|lowendjunk|mailcase|m8sbeingm8s|makingdomes|mareinvestment|martinandgang|melverly|menterprise|midmico|mixwi|nestmoon|newpochta|newfishingaccessories|nicewoodenbaskets|nymega|onebyair|onstir|partcafe|pancingqueen|petsplit|plasticvouchercards|phdsearchandselection|psmscientific|portablespeaker|quelbroker|ragnortheblue|raytoy|realbloggroup|relucius|resistingmoney|roastedtastyfood|roofvent|rowrowleft|savedaday|scoldly|softtoiletpaper|softhandscream|sopharmsn|smilietoys|silkwomenshirts|silkbeachtowels|simichopra|sudeu|sfxmailbox|shavers|swmail|spicysallads|sydrinium|tastyarabicacoffee|travel|toddard|ualusa|uiscape|usamami|usgeek|universallightkeys|varsidesk|vaulker|vipitv|visagency|warboardplace|web20|wedfb|westrb|whatiscryptocurrency|woeishyang|wholesalehomefurniture|wikibacklinks|wirelax|wirelesschargers|vyaa5|yahoo|yandex|yoshisad|yourmail|zqbld|1000welectricscooter)\b/i',
					'desc'    => __( 'PSOURCE Killerliste SPAMMAILS', 'ust' ),
					'type'    => 'email',
					'action'  => 'block',
					'matched' => 0,
				),
		);
		update_site_option( 'ust_patterns', $default_patterns );

		ust_global_install();
	}
}

function ust_global_install() {

	global $wpdb, $ust_current_version;

	if ( get_site_option( "ust_installed" ) == '' ) {
		add_site_option( 'ust_installed', 'no' );
	}

	if ( get_site_option( "ust_installed" ) == "yes" ) {
		// do nothing
	} else {
		//create table
		$ust_table1 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->base_prefix . "ust` (
										`blog_id` bigint(20) unsigned NOT NULL,
										`last_user_id` bigint(20) NULL DEFAULT NULL,
										`last_ip` varchar(30),
										`last_user_agent` varchar(255),
										`spammed` DATETIME default '0000-00-00 00:00:00',
										`certainty` int(3) NOT NULL default '0',
										`ignore` int(1) NOT NULL default '0',
										PRIMARY KEY  (`blog_id`)
									) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
		$wpdb->query( $ust_table1 );

		//insert every blog_id
		$ust_query1 = "INSERT INTO `" . $wpdb->base_prefix . "ust` (`blog_id`) SELECT blog_id FROM `" . $wpdb->blogs . "` WHERE 1";
		$wpdb->query( $ust_query1 );

		//best guess estimate of spammed time by last updated
		$ust_query2 = "UPDATE " . $wpdb->base_prefix . "ust u, " . $wpdb->blogs . " b SET u.spammed = b.last_updated WHERE u.blog_id = b.blog_id AND b.spam = 1";
		$wpdb->query( $ust_query2 );

		//default options
		//$ust_settings['api_key']         = '';
		$ust_settings['block_certainty'] = '';
		$ust_settings['certainty']       = 80;
		$ust_settings['post_certainty']  = 90;
		$ust_settings['num_signups']     = '';
		$ust_settings['ip_blocking']     = 1;
		$ust_settings['strip']           = 0;
		$ust_settings['paged_blogs']     = 15;
		$ust_settings['paged_posts']     = 3;
		$ust_settings['hide_adminbar']   = 0;
		$ust_settings['keywords']        = array( 'ugg', 'pharma', 'erecti', viagra, porn, );
		$ust_settings['signup_protect']  = 'none';
		update_site_option( "ust_settings", $ust_settings );

		update_site_option( "ust_installed", "yes" );
	}
}

function ust_toolbar_init() {
	//check basic stuff
	if ( ! current_user_can( 'manage_sites' ) || is_network_admin() || is_main_site() || ! is_admin_bar_showing() ) {
		return;
	}

	//skip if turned off
	$ust_settings = get_site_option( "ust_settings" );
	if ( isset( $ust_settings['hide_adminbar'] ) && $ust_settings['hide_adminbar'] ) {
		return;
	}

	add_action( 'admin_bar_menu', 'ust_toolbar_menu', 999 );
	add_action( 'admin_footer', 'ust_toolbar_js' );
	add_action( 'wp_footer', 'ust_toolbar_js' );
	add_action( 'wp_print_scripts', 'ust_toolbar_enqueue_jquery' );
}

function ust_toolbar_menu() {
	global $wp_admin_bar, $blog_id, $wpdb;
	$data = get_blog_details( $blog_id );

	$wp_admin_bar->add_menu( array(
		'title'  => __( 'Splog', 'ust' ),
		'href'   => '',
		'parent' => false,
		'id'     => 'anti_splog',
	) );

	$spam_title = $data->spam ? __( 'Unsplog', 'ust' ) : __( 'Splog', 'ust' );
	$arch_title = $data->archived ? __( 'Freigeben', 'ust' ) : __( 'Archiv', 'ust' );
	$wp_admin_bar->add_menu( array(
		'title'  => $spam_title,
		'href'   => '#' . preg_replace( '/[^a-z]/', '', strtolower( $spam_title ) ),
		//'parent' => false,
		'parent' => 'anti_splog',
		'id'     => 'ust_options_spam',
		'meta'   => array(
			'onclick' => 'return ust_SendRequest("spam");'
		),
	) );
	$reg_ip = $wpdb->get_var( "SELECT IP FROM {$wpdb->registration_log} WHERE blog_id={$blog_id}" );
	if ( $reg_ip ) {
		$wp_admin_bar->add_menu( array(
			'title'  => sprintf( __( 'Splog IP: %s', 'ust' ), $reg_ip ),
			'href'   => network_admin_url( 'settings.php?page=ust&updated=1&id=' . $blog_id . '&spam_ip=' . $reg_ip ),
			'parent' => 'anti_splog',
			'id'     => 'ust_splog_by_ip',
			'meta'   => array(
				'onclick' => 'return ust_splog_request("registered");'
			),
		) );
	}
	$wp_admin_bar->add_menu( array(
		'title'  => $arch_title,
		'href'   => '#' . preg_replace( '/[^a-z]/', '', strtolower( $arch_title ) ),
		//'parent' => false,
		'parent' => 'anti_splog',
		'id'     => 'ust_options_archive',
		'meta'   => array(
			'onclick' => 'return ust_SendRequest("archive");'
		),
	) );
}

function ust_toolbar_enqueue_jquery() {
	wp_enqueue_script( 'jquery' );
}

function ust_toolbar_js() {
	global $blog_id;

	$ajax        = admin_url( 'admin-ajax.php' );
	$error_msg   = esc_js( __( 'Hoppla, da ist etwas schief gelaufen', 'ust' ) );
	$working_msg = "<img src='" . admin_url( 'images/loading.gif' ) . "' /> " . esc_js( __( 'Verarbeite...', 'ust' ) );
	$done_msg    = esc_js( __( 'Fertig! Neuladen...', 'ust' ) );

	echo <<<EOJs
<script type="text/javascript">
var _ust_ajax_url = "{$ajax}";
function ust_SendRequest (flag) {
	var $ = jQuery;
	var target = $("#wp-admin-bar-ust_options_" + flag + " a:first");
	var text = target.text();
	var flag = flag ? flag : "spam";

	target.html("{$working_msg}");

	$.post(_ust_ajax_url, {
		"action": "ust_toggle_blog_flag",
		"flag": flag,
		"blog_id": "{$blog_id}"
	}, function (data) {
		if (parseInt(data.status)) {
			target.html("{$done_msg}");
			window.location = window.location;
		} else {
			target.html(text);
			alert("{$error_msg}");
		}
	});

	return false;
}

function ust_splog_request (tgt) {
	var $ = jQuery;
	var target = $("#wp-admin-bar-ust_splog_by_ip a:first");
	if (!target.length) return false;
	
	var data = {
		"action": 'ust_ajax',
		"check_ip": target.attr("href")
	};

	$.post(_ust_ajax_url, data, function(response) {
		if (response.num) {
			var answer = confirm("Du bist dabei, " + 
				response.num + " Blogs als Spam zu markieren! Es gibt derzeit " + 
				response.numspam + " Blogs, die für diese IP bereits als Spam markiert sind (" +
				response.ip + ").\\n\\nBist Du sicher, dass Du dies tun möchtest?"
	);
			if (answer) {
					//create post data
					var data2 = {
					action: 'ust_ajax',
					url: data.check_ip
				};
		//send ajax
				$.post(_ust_ajax_url, data2, function () {
					window.location = window.location;
				});
			}
		}
	}, "json");
	return false;
}
</script>
EOJs;
}

function ust_toolbar_ajax() {
	if ( ! current_user_can( 'manage_sites' ) ) {
		die( 0 );
	} // Just for super admins

	$blog_id = (int) @$_POST['blog_id'];
	$flag    = @$_POST['flag'];
	$data    = get_blog_details( $blog_id );

	switch ( $flag ) {
		case "spam":
			$data->spam = $data->spam ? 0 : 1;
			break;
		case "archive":
			$data->archived = $data->archived ? 0 : 1;
			break;
	}
	$res = (int) update_blog_details( $blog_id, $data );
	header( 'Content-type: application/json' );
	echo json_encode( array(
		'status' => $res ? 1 : 0,
	) );
	exit();
}

function ust_wpsignup_init() {
	global $blog_id, $current_site;

	//if on main blog
	if ( is_main_site() ) {
		$ust_signup = get_site_option( 'ust_signup' );
		if ( ! $ust_signup['active'] ) {
			return;
		}

		add_filter( 'root_rewrite_rules', 'ust_wpsignup_rewrite' );
		add_filter( 'query_vars', 'ust_wpsignup_queryvars' );
		add_action( 'pre_get_posts', 'ust_wpsignup_page' );
		if ( ! defined( 'UST_OVERRIDE_SIGNUP_SLUG' ) ) {
			add_action( 'init', 'ust_wpsignup_flush_rewrite' );
			add_action( 'init', 'ust_wpsignup_change', 99 ); //run after the flush in case link has expired on already open page
		}
		add_action( 'init', 'ust_wpsignup_kill' );
	}
}

function ust_wpsignup_rewrite( $rules ) {
	$ust_signup = get_site_option( 'ust_signup' );

	$rules[ $ust_signup['slug'] . '/?$' ] = 'index.php?namespace=ust&newblog=$matches[1]';

	return $rules;
}

function ust_wpsignup_change() {
	$ust_signup = get_site_option( 'ust_signup' );
	//change url every 24 hours
	if ( $ust_signup['expire'] < time() ) {
		$ust_signup['expire'] = time() + 86400; //extend 24 hours
		$ust_signup['slug']   = 'signup-' . substr( md5( time() ), rand( 0, 30 ), 3 ); //create new random signup url
		update_site_option( 'ust_signup', $ust_signup );
		//clear cache if WP Super Cache is enabled
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
	}
}

function ust_wpsignup_flush_rewrite() {
	// This function clears the rewrite rules and forces them to be regenerated
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function ust_wpsignup_queryvars( $vars ) {
	// This function add the namespace (if it hasn't already been added) and the
	// eventperiod queryvars to the list that WordPress is looking for.
	// Note: Namespace provides a means to do a quick check to see if we should be doing anything
	if ( ! in_array( 'namespace', $vars ) ) {
		$vars[] = 'namespace';
	}
	$vars[] = 'newblog';

	return $vars;
}

function ust_wpsignup_page( $wp_query ) {

	if ( isset( $wp_query->query_vars['namespace'] ) && $wp_query->query_vars['namespace'] == 'ust' ) {

		// Set up the property query variables
		if ( isset( $wp_query->query_vars['newblog'] ) ) {
			$_GET['new'] = $wp_query->query_vars['newblog'];
		}

		//include the signup page
		$wp_query->is_home = false;
		$wp_query->is_page = 1;

		//allow for a custom signup page to override this by placing in wp-content dir
		if ( file_exists( WP_CONTENT_DIR . '/custom-wpsignup.php' ) ) {
			require_once( WP_CONTENT_DIR . '/custom-wpsignup.php' );
		} else {
			require_once( 'includes/ust-wp-signup.php' );
		}
		die();
	}
}

/* Kill the wp-signup.php if custom registration signup templates are present */
function ust_wpsignup_kill() {
	global $current_site;

	if ( false === strpos( $_SERVER['SCRIPT_NAME'], 'wp-signup.php' ) ) {
		return false;
	}

	/* could make it easy for sploggers to get current url from location header by setting the new variable
	  if (isset($_GET['new'])) {
		$ust_signup = get_site_option('ust_signup');
		header( "Location: http://" . $current_site->domain . $current_site->path . $ust_signup['slug'] . "/?new=" . $_GET['new'];
	  }
	  */

	header( "HTTP/1.0 404 Not Found" );
	die( __( 'Der Pfad der Anmeldeseite wurde geändert.', 'ust' ) );
}

function ust_wpsignup_filter() {
	// filters redirect in wp-login.php
	return ust_wpsignup_url( false );
}

function ust_wpsignup_shortcode( $content ) {
	//replace shortcodes in content
	$content = str_replace( '[ust_wpsignup_url]', ust_wpsignup_url( false ), $content );

	$ust_signup = get_site_option( 'ust_signup' );

	$new_slug = defined( 'UST_OVERRIDE_SIGNUP_SLUG' ) ? UST_OVERRIDE_SIGNUP_SLUG : $ust_signup['slug'];

	//replace unchanged wp-signup.php calls too
	if ( $ust_signup['active'] ) {
		$content = str_replace( 'wp-signup.php', trailingslashit( $new_slug ), $content );
	}

	return $content;
}

function ust_blog_spammed( $blog_id ) {
	global $wpdb, $current_site;

	//prevent the spamming of supporters if free trial is not enabled
	$free_trial = get_site_option( "supporter_free_days" );
	if ( function_exists( 'is_supporter' ) && is_supporter( $blog_id ) && $free_trial === 0 ) {
		update_blog_status( $blog_id, "spam", '0' );

		return;
	}

	//spam blog's users if preference is set
	$ust_settings = get_site_option( "ust_settings" );
	if ( $ust_settings['spam_blog_users'] ) {
		$blogusers = get_users_of_blog( $blog_id );
		if ( $blogusers ) {
			foreach ( $blogusers as $bloguser ) {
				if ( ! is_super_admin( $bloguser->user_id ) ) {
					update_user_status( $bloguser->user_id, "spam", '1' );
				}
			}
		}
	}

	$wpdb->query( "UPDATE `" . $wpdb->base_prefix . "ust` SET spammed = '" . current_time( 'mysql', true ) . "' WHERE blog_id = '$blog_id' LIMIT 1" );

	//update spam stat
	$num = get_site_option( 'ust_spam_count' );
	if ( ! $num ) {
		$num = 0;
	}
	update_site_option( 'ust_spam_count', ( $num + 1 ) );

}

function ust_blog_unspammed( $blog_id, $ignored = false ) {
	global $wpdb, $current_site;

	if ( ! $ignored ) {
		//update spam stat
		$num = get_site_option( 'ust_spam_count' );
		if ( ! $num || $num = 0 ) {
			$num = 0;
		} else {
			$num = $num - 1;
		}
		update_site_option( 'ust_spam_count', $num );

		//remove auto spammed status in case it is manually spammed again later
		update_blog_option( $blog_id, 'ust_auto_spammed', 0 );
		update_blog_option( $blog_id, 'ust_post_auto_spammed', 0 );
	}

	//unspam blog's users if preference is set
	$ust_settings = get_site_option( "ust_settings" );
	if ( $ust_settings['spam_blog_users'] ) {
		$blogusers = get_users_of_blog( $blog_id );
		if ( $blogusers ) {
			foreach ( $blogusers as $bloguser ) {
				update_user_status( $bloguser->user_id, "spam", '0' );
			}
		}
	}
}

function ust_blog_created( $blog_id, $user_id ) {
	global $wpdb, $current_site;
	$ust_signup_data = get_blog_option( $blog_id, 'ust_signup_data' );
	$user            = new WP_User( (int) $user_id );
	$ip              = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
	$blog            = $wpdb->get_row( "SELECT * FROM {$wpdb->blogs} WHERE blog_id = '$blog_id'", ARRAY_A );

	//collect signup info
	$api_data                          = $ust_signup_data;
	$api_data['activate_user_agent']   = $_SERVER['HTTP_USER_AGENT'];
	$api_data['activate_user_ip']      = $ip;
	$api_data['activate_user_referer'] = $_SERVER['HTTP_REFERER'];
	$api_data['user_login']            = $user->user_login;
	$api_data['user_email']            = $user->user_email;
	$api_data['user_registered']       = $user->user_registered;
	$api_data['blog_domain']           = is_subdomain_install() ? str_replace( '.' . $current_site->domain, '', $blog['domain'] ) : trim( $blog['path'], '/' );
	$api_data['blog_title']            = get_blog_option( $blog_id, 'blogname' );
	$api_data['blog_registered']       = $blog['registered'];

	//check patterns
	$matched_pattern = false;
	$ust_patterns    = get_site_option( "ust_patterns" );
	if ( is_array( $ust_patterns ) ) {
		foreach ( $ust_patterns as $key => $pattern ) {
			if ( $pattern['action'] == 'block' ) {
				continue;
			} //only check spamming rules here
			$error = false;
			if ( $pattern['type'] == 'domain' ) { //check domain
				if ( preg_match( $pattern['regex'], $api_data['blog_domain'] ) ) {
					$error = true;
				}
			} else if ( $pattern['type'] == 'title' ) { //check title
				if ( preg_match( $pattern['regex'], $api_data['blog_title'] ) ) {
					$error = true;
				}
			} else if ( $pattern['type'] == 'username' ) { //check username
				if ( preg_match( $pattern['regex'], $api_data['user_login'] ) ) {
					$error = true;
				}
			} else if ( $pattern['type'] == 'email' ) { //check username
				if ( preg_match( $pattern['regex'], $api_data['user_email'] ) ) {
					$error = true;
				}
			}

			if ( $error ) {
				$matched_pattern                 = true;
				$ust_patterns[ $key ]['matched'] = $pattern['matched'] + 1;
			}
		}
		update_site_option( "ust_patterns", $ust_patterns ); //save blocked counts
	}

	//don't test if a site admin or supporter or blog-user-creator plugin is creating the blog or spammed by pattern matching
	if ( is_super_admin() || strpos( $_SERVER['REQUEST_URI'], 'blog-user-creator' ) || $matched_pattern ) {
		$certainty = 0;
	} 

	//create new record in ust table
	$wpdb->query( $wpdb->prepare( "INSERT INTO `" . $wpdb->base_prefix . "ust` (blog_id, last_user_id, last_ip, last_user_agent, certainty) VALUES (%d, %d, %s, %s, %d)", $blog_id, $user->ID, $ip, $_SERVER['HTTP_USER_AGENT'], $certainty ) );

	//save data to blog for retrieval in case it's spammed later
	update_blog_option( $blog_id, 'ust_signup_data' );

	//spam blog if certainty is met
	$ust_settings = get_site_option( "ust_settings" );
	if ( $certainty >= $ust_settings['certainty'] || $matched_pattern ) {
		update_blog_option( $blog_id, 'ust_auto_spammed', 1 );
		update_blog_status( $blog_id, "spam", '1' );
	}
}

function ust_check_post( $tmp_post_ID ) {
	global $wpdb, $current_site, $blog_id;

	if ( ! $blog_id ) {
		$blog_id = $wpdb->blogid;
	}

	$tmp_post = get_post( $tmp_post_ID );

	//$api_data = get_option( 'ust_signup_data' );

	//only check the first valid post for blogs that were created after plugin installed
	if ( get_option( 'ust_first_post' ) || $tmp_post->post_status != 'publish' || ! in_array( $tmp_post->post_type, array(
				'post',
				'page'
			) ) || $tmp_post->post_content == ''
	) {
		return;
	}

	//collect info
	if ( ! $api_data ) {
		$blog                         = $wpdb->get_row( "SELECT * FROM {$wpdb->blogs} WHERE blog_id = '$blog_id'", ARRAY_A );
		$api_data['activate_user_ip'] = $wpdb->get_var( "SELECT `IP` FROM {$wpdb->registration_log} WHERE blog_id = '$blog_id'" );
		$api_data['user_email']       = $wpdb->get_var( "SELECT `email` FROM {$wpdb->registration_log} WHERE blog_id = '$blog_id'" );
		$api_data['blog_registered']  = $blog['registered'];
		$api_data['blog_domain']      = is_subdomain_install() ? str_replace( '.' . $current_site->domain, '', $blog['domain'] ) : trim( $blog['path'], '/' );
		$api_data['blog_title']       = get_blog_option( $blog_id, 'blogname' );
	}
	$last                        = $wpdb->get_row( "SELECT * FROM {$wpdb->base_prefix}ust WHERE blog_id = '$blog_id'" );
	$api_data['last_user_id']    = $last->last_user_id;
	$api_data['last_ip']         = $last->last_ip;
	$api_data['last_user_agent'] = $last->last_user_agent;

	//add post title/content
	$api_data['post_content'] = $tmp_post->post_title . "\n" . $tmp_post->post_content . "\n";

	//add tags to the content to scan
	$tags = wp_get_object_terms( $tmp_post_ID, 'post_tag' );
	if ( ! empty( $tags ) ) {
		if ( ! is_wp_error( $tags ) ) {
			foreach ( $tags as $term ) {
				$api_data['post_content'] .= ' ' . $term->name;
			}
		}
	}

	//update certainty in table if greater
	$last_certainty = $wpdb->get_var( "SELECT certainty FROM {$wpdb->base_prefix}ust WHERE blog_id = '$blog_id'" );
	if ( $certainty > $last_certainty && $certainty > 60 ) {
		$wpdb->query( "UPDATE `" . $wpdb->base_prefix . "ust` SET `certainty` = $certainty WHERE blog_id = '$blog_id' LIMIT 1" );
	}

	//save action so we don't check this blog again
	if ( $result >= 0 ) {
		update_option( 'ust_first_post', 1 );
	}

	//spam blog if certainty is met
	$ust_settings = get_site_option( "ust_settings" );
	if ( $certainty >= $ust_settings['post_certainty'] ) {
		update_blog_option( $blog_id, 'ust_post_auto_spammed', 1 );
		update_blog_status( $blog_id, "spam", '1' );
	}
}

function ust_blog_ignore( $blog_id, $report = true ) {
	global $wpdb;
	$wpdb->query( "UPDATE `" . $wpdb->base_prefix . "ust` SET `ignore` = '1' WHERE blog_id = '$blog_id' LIMIT 1" );

	//send info to API for learning
	/*if ( $report ) {
		ust_blog_unspammed( $blog_id, true );
	}*/
}

function ust_blog_unignore( $blog_id ) {
	global $wpdb;
	$wpdb->query( "UPDATE `" . $wpdb->base_prefix . "ust` SET `ignore` = '0' WHERE blog_id = '$blog_id' LIMIT 1" );
}

function ust_blog_deleted( $blog_id, $drop ) {
	global $wpdb;

	if ( $drop ) {
		$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "ust` WHERE blog_id = '$blog_id' LIMIT 1" );
	}
}

function ust_user_deleted( $user_id ) {
	global $wpdb;
	$wpdb->query( "UPDATE `" . $wpdb->base_prefix . "ust` SET last_user_id = NULL WHERE last_user_id = '$user_id'" );
}

function ust_blog_updated( $blog_id ) {
	global $wpdb, $current_user;
	if ( ! empty( $current_user->ID ) ) {
		$wpdb->query( "UPDATE `" . $wpdb->base_prefix . "ust` SET last_user_id = '" . $current_user->ID . "', last_ip = '" . $_SERVER['REMOTE_ADDR'] . "', last_user_agent = '" . esc_sql( $_SERVER['HTTP_USER_AGENT'] ) . "' WHERE blog_id = '$blog_id' LIMIT 1" );
	}
}

if ( function_exists( '\add_security_page' ) ) {
	add_security_page(
		'ust', __( 'Anti-Splog-Statistiken', 'ust' ), __( 'Statistiken', 'ust' ), 'manage_sites', 'ust-stats', 'ust_admin_stats' );
		add_action( 'admin_print_scripts-' . $page, 'ust_admin_script_flot' );
		add_action( 'load-' . $page, 'ust_admin_help' );
} else {
	function ust_plug_pages() {
		global $ust_admin_url, $wp_version;
	
		$page = add_menu_page( __( 'Anti-Splog', 'ust' ), __( 'Anti-Splog', 'ust' ), 'manage_sites', 'ust', 'ust_admin_moderate', 'dashicons-shield' );
		$page = add_submenu_page( 'ust', __( 'Seiten-Moderation', 'ust' ), __( 'Moderation', 'ust' ), 'manage_sites', 'ust', 'ust_admin_moderate' );
	
		/* Using registered $page handle to hook script load */
		add_action( 'admin_print_scripts-' . $page, 'ust_admin_script' );
		add_action( 'admin_print_styles-' . $page, 'ust_admin_style' );
		add_action( 'load-' . $page, 'ust_admin_help' );
	
		$page = add_submenu_page( 'ust', __( 'Anti-Splog-Statistiken', 'ust' ), __( 'Statistiken', 'ust' ), 'manage_sites', 'ust-stats', 'ust_admin_stats' );
		add_action( 'admin_print_scripts-' . $page, 'ust_admin_script_flot' );
		add_action( 'load-' . $page, 'ust_admin_help' );
	
		$page = add_submenu_page( 'ust', __( 'Anti-Splog-Musterabgleich', 'ust' ), __( 'Musterabgleich', 'ust' ), 'manage_network_options', 'ust-patterns', 'ust_admin_patterns' );
		add_action( 'admin_print_scripts-' . $page, 'ust_admin_script' );
		add_action( 'load-' . $page, 'ust_admin_help' );
	
		$page = add_submenu_page( 'ust', __( 'Anti-Splog-Einstellungen', 'ust' ), __( 'Einstellungen', 'ust' ), 'manage_network_options', 'ust-settings', 'ust_admin_settings' );
		add_action( 'load-' . $page, 'ust_admin_help' );
	}
}


function ust_do_ajax() {
	global $wpdb, $current_site;

	//make sure we have permission!
	if ( ! current_user_can( 'manage_sites' ) ) {
		die();
	}

	if ( isset( $_POST['url'] ) ) {
		$query = parse_url( $_POST['url'] );
		parse_str( $query['query'], $_GET );
	}

	//process any actions and messages
	if ( isset( $_GET['spam_user'] ) ) {
		//spam a user and all blogs they are associated with

		//don't spam site admin
		$user_info = get_userdata( (int) $_GET['spam_user'] );
		if ( ! is_super_admin( $user_info->ID ) ) {
			$blogs = get_blogs_of_user( (int) $_GET['spam_user'], true );
			foreach ( (array) $blogs as $key => $details ) {
				if ( $details->userblog_id == $current_site->blog_id ) {
					continue;
				} // main blog not a spam !
				update_blog_status( $details->userblog_id, "spam", '1' );
				set_time_limit( 60 );
			}
			update_user_status( (int) $_GET['spam_user'], "spam", '1' );
		}

	} else if ( isset( $_POST['check_ip'] ) ) {
		//count all blogs created or modified with the IP address
		$ip_query = parse_url( $_POST['check_ip'] );
		parse_str( $ip_query['query'], $ip_data );
		$spam_ip = esc_sql( $ip_data['spam_ip'] );

		$query  = "SELECT COUNT(b.blog_id)
        				FROM {$wpdb->blogs} b, {$wpdb->registration_log} r, {$wpdb->base_prefix}ust u
        				WHERE b.site_id = '{$wpdb->siteid}'
        				AND b.blog_id = r.blog_id
        				AND b.blog_id = u.blog_id
        				AND b.spam = 0
        				AND (r.IP = '$spam_ip' OR u.last_ip = '$spam_ip')";
		$query2 = "SELECT COUNT(b.blog_id)
        				FROM {$wpdb->blogs} b, {$wpdb->registration_log} r, {$wpdb->base_prefix}ust u
        				WHERE b.site_id = '{$wpdb->siteid}'
        				AND b.blog_id = r.blog_id
        				AND b.blog_id = u.blog_id
        				AND b.spam = 1
        				AND (r.IP = '$spam_ip' OR u.last_ip = '$spam_ip')";
		//return json response
		echo '{"num":"' . $wpdb->get_var( $query ) . '", "numspam":"' . $wpdb->get_var( $query2 ) . '", "bid":"' . $ip_data['id'] . '", "ip":"' . $ip_data['spam_ip'] . '"}';

	} else if ( isset( $_GET['spam_ip'] ) ) {
		//spam all blogs created or modified with the IP address
		$spam_ip = esc_sql( $_GET['spam_ip'] );
		$query   = "SELECT b.blog_id
        				FROM {$wpdb->blogs} b, {$wpdb->registration_log} r, {$wpdb->base_prefix}ust u
        				WHERE b.site_id = '{$wpdb->siteid}'
        				AND b.blog_id = r.blog_id
        				AND b.blog_id = u.blog_id
        				AND b.spam = 0
        				AND (r.IP = '$spam_ip' OR u.last_ip = '$spam_ip')";
		$blogs   = $wpdb->get_results( $query, ARRAY_A );
		foreach ( (array) $blogs as $blog ) {
			if ( $blog['blog_id'] == $current_site->blog_id ) {
				continue;
			} // main blog not a spam !
			update_blog_status( $blog['blog_id'], "spam", '1' );
			set_time_limit( 60 );
		}

	} else if ( isset( $_GET['ignore_blog'] ) ) {
		//ignore a single blog so it doesn't show up on the possible spam list
		ust_blog_ignore( (int) $_GET['id'] );
		echo $_GET['id'];

	} else if ( isset( $_GET['unignore_blog'] ) ) {
		//unignore a single blog so it can show up on the possible spam list
		ust_blog_unignore( (int) $_GET['id'] );
		echo $_GET['id'];

	} else if ( isset( $_GET['spam_blog'] ) ) {
		//spam a single blog
		update_blog_status( (int) $_GET['id'], "spam", '1' );
		echo $_GET['id'];

	} else if ( isset( $_GET['unspam_blog'] ) ) {

		update_blog_status( (int) $_GET['id'], "spam", '0' );
		ust_blog_ignore( (int) $_GET['id'], false );
		echo $_GET['id'];

	} else if ( isset( $_POST['allblogs'] ) ) {
		parse_str( $_POST['allblogs'], $blog_list );

		foreach ( (array) $blog_list['allblogs'] as $key => $val ) {
			if ( $val != '0' && $val != $current_site->blog_id ) {
				if ( isset( $_POST['allblog_ignore'] ) ) {
					ust_blog_ignore( $val );
					set_time_limit( 60 );
				} else if ( isset( $_POST['allblog_unignore'] ) ) {
					ust_blog_unignore( $val );
					set_time_limit( 60 );
				} else if ( isset( $_POST['allblog_spam'] ) ) {
					update_blog_status( $val, "spam", '1' );
					set_time_limit( 60 );
				} else if ( isset( $_POST['allblog_notspam'] ) ) {
					update_blog_status( $val, "spam", '0' );
					ust_blog_ignore( $val, false );
					set_time_limit( 60 );
				}
			}
		}
		_e( "Ausgewählte Blogs verarbeitet", 'ust' );
	}

	die();
}

function ust_signup_errorcheck( $content ) {
	//skip check if BP
	global $bp;
	if ( isset( $bp->signup->step ) ) {
		return $content;
	}

	$ust_settings = get_site_option( "ust_settings" );

	if ( $ust_settings['signup_protect'] == 'recaptcha' ) {

		//check reCAPTCHA
		$recaptcha = get_site_option( 'ust_recaptcha' );

		$resp = ust_recaptcha_check_answer( $recaptcha['privkey'], $_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"] );

		if ( ! $resp ) {
			$content['errors']->add( 'recaptcha', __( "Das reCAPTCHA wurde nicht richtig eingegeben. Bitte versuche es erneut.", 'ust' ) );
		}

	} else if ( $ust_settings['signup_protect'] == 'questions' ) {

		$ust_qa = get_site_option( "ust_qa" );
		if ( is_array( $ust_qa ) && count( $ust_qa ) ) {
			//check the encrypted answer field
			$salt         = get_site_option( "ust_salt" );
			$datesalt     = strtotime( date( 'Y-m-d H:00:00' ) );
			$valid_fields = false;
			foreach ( $ust_qa as $qkey => $answer ) {
				$field_name = 'qa_' . md5( $qkey . $salt . $datesalt );
				if ( isset( $_POST[ $field_name ] ) ) {
					if ( strtolower( trim( $_POST[ $field_name ] ) ) != strtolower( stripslashes( $answer[1] ) ) ) {
						$content['errors']->add( 'qa', __( "Falsche Antwort. Bitte versuche es erneut.", 'ust' ) );
					}
					$valid_fields = true;
				}
			}
			//if no fields are valid try again for previous hour
			if ( ! $valid_fields ) {
				$datesalt = strtotime( '-1 hour', $datesalt );
				foreach ( $ust_qa as $qkey => $answer ) {
					$field_name = 'qa_' . md5( $qkey . $salt . $datesalt );
					if ( isset( $_POST[ $field_name ] ) ) {
						if ( strtolower( trim( $_POST[ $field_name ] ) ) != strtolower( stripslashes( $answer[1] ) ) ) {
							$content['errors']->add( 'qa', __( "Falsche Antwort. Bitte versuche es erneut.", 'ust' ) );
						}
					}
				}
			}
		}

	}

	return $content;
}

function ust_signup_errorcheck_bp() {
	global $bp;
	$ust_settings = get_site_option( "ust_settings" );

	if ( $ust_settings['signup_protect'] == 'recaptcha' ) {

		//check reCAPTCHA
		$recaptcha = get_site_option( 'ust_recaptcha' );

		$resp = ust_recaptcha_check_answer( $recaptcha['privkey'], $_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"] );

		if ( ! $resp ) {
			$bp->signup->errors['recaptcha'] = __( "Das reCAPTCHA wurde nicht richtig eingegeben. Bitte versuche es erneut.", 'ust' );
		}

	} else if ( $ust_settings['signup_protect'] == 'questions' ) {

		$ust_qa = get_site_option( "ust_qa" );
		if ( is_array( $ust_qa ) && count( $ust_qa ) ) {
			//check the encrypted answer field
			$salt         = get_site_option( "ust_salt" );
			$datesalt     = strtotime( date( 'Y-m-d H:00:00' ) );
			$valid_fields = false;
			foreach ( $ust_qa as $qkey => $answer ) {
				$field_name = 'qa_' . md5( $qkey . $salt . $datesalt );
				if ( isset( $_POST[ $field_name ] ) ) {
					if ( strtolower( trim( $_POST[ $field_name ] ) ) != strtolower( stripslashes( $answer[1] ) ) ) {
						$bp->signup->errors['qa'] = __( "Falsche Antwort. Bitte versuche es erneut.", 'ust' );
					}
					$valid_fields = true;
				}
			}
			//if no fields are valid try again for previous hour
			if ( ! $valid_fields ) {
				$datesalt = strtotime( '-1 hour', $datesalt );
				foreach ( $ust_qa as $qkey => $answer ) {
					$field_name = 'qa_' . md5( $qkey . $salt . $datesalt );
					if ( isset( $_POST[ $field_name ] ) ) {
						if ( strtolower( trim( $_POST[ $field_name ] ) ) != strtolower( stripslashes( $answer[1] ) ) ) {
							$bp->signup->errors['qa'] = __( "Falsche Antwort. Bitte versuche es erneut.", 'ust' );
						}
					}
				}
			}
		}

	}
}

function ust_pre_signup_user_check( $content ) {

	//check patterns
	$ust_patterns = get_site_option( "ust_patterns" );
	if ( is_array( $ust_patterns ) ) {
		foreach ( $ust_patterns as $key => $pattern ) {
			if ( $pattern['action'] != 'block' ) {
				continue;
			} //only check blocking rules here
			$error = false;
			if ( $pattern['type'] == 'username' ) { //check username
				if ( preg_match( $pattern['regex'], trim( $content['user_name'] ) ) ) {
					$content['errors']->add( 'user_name', __( "Wenn Du kein Spammer bist, versuche es bitte erneut mit einem anderen Benutzernamen.", 'ust' ) );
					$error = true;
				}
			} else if ( $pattern['type'] == 'email' ) { //check username
				if ( preg_match( $pattern['regex'], trim( $content['user_email'] ) ) ) {
					$content['errors']->add( 'user_email', __( "Wir denken, Du könntest ein Spambot sein. Wenn Du kein Spammer bist, kontaktiere uns bitte oder verwende eine andere E-Mail-Adresse.", 'ust' ) );
					$error = true;
				}
			}

			if ( $error ) {
				$ust_patterns[ $key ]['matched'] = $pattern['matched'] + 1;
			}
		}
		update_site_option( "ust_patterns", $ust_patterns ); //save blocked counts
	}

	return $content;
}


//check for multiple signups from the same IP in 24 hours or patterns
function ust_pre_signup_check( $content ) {
	global $wpdb;
	$ust_settings = get_site_option( "ust_settings" );

	if ( $ust_settings['num_signups'] ) {
		$date = date( 'Y-m-d H:i:s', strtotime( '-1 day', time() ) );
		$ips  = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->registration_log} WHERE IP = '{$_SERVER['REMOTE_ADDR']}' AND date_registered >= '$date'" );
		if ( $ips > $ust_settings['num_signups'] ) {
			$content['errors']->add( 'blogname', __( "Eine begrenzte Anzahl von Anmeldungen kann in kurzer Zeit über Deine Internetverbindung durchgeführt werden. Wenn Du kein Spammer bist, versuche es bitte in 24 Stunden erneut.", 'ust' ) );
		}
	}

	//check signup history for this IP
	if ( $ust_settings['ip_blocking'] ) {
		$ip_splogs = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->registration_log} l LEFT JOIN {$wpdb->blogs} b ON l.blog_id = b.blog_id WHERE l.IP = '{$_SERVER['REMOTE_ADDR']}' AND b.spam = 1" );
		if ( $ip_splogs >= $ust_settings['ip_blocking'] ) {
			$content['errors']->add( 'blogname', __( "Unsere automatisierten Systeme halten Dich für einen Spambot. Wenn Du kein Spammer bist, kontaktiere uns bitte und gib uns diese IP: ", 'ust' ) . $_SERVER['REMOTE_ADDR'] );
		}
	}

	//check patterns
	$ust_patterns = get_site_option( "ust_patterns" );
	if ( is_array( $ust_patterns ) ) {
		foreach ( $ust_patterns as $key => $pattern ) {
			if ( $pattern['action'] != 'block' ) {
				continue;
			} //only check blocking rules here
			$error = false;
			if ( $pattern['type'] == 'domain' ) { //check domain
				if ( preg_match( $pattern['regex'], trim( $content['blogname'] ) ) ) {
					$content['errors']->add( 'blogname', __( "Wenn Du kein Spammer bist, versuche es bitte erneut mit einer anderen Domain.", 'ust' ) );
					$error = true;
				}
			} else if ( $pattern['type'] == 'title' ) { //check title
				if ( preg_match( $pattern['regex'], trim( $content['blog_title'] ) ) ) {
					$content['errors']->add( 'blog_title', __( "Wenn Du kein Spammer bist, versuche es bitte erneut mit einem anderen Titel.", 'ust' ) );
					$error = true;
				}
			} else if ( $pattern['type'] == 'username' && isset( $_POST["user_name"] ) ) { //check username
				if ( preg_match( $pattern['regex'], trim( $_POST["user_name"] ) ) ) {
					$content['errors']->add( 'blogname', __( "Wenn Du kein Spammer bist, versuche es bitte erneut mit einem anderen Benutzernamen.", 'ust' ) );
					$error = true;
				}
			} else if ( $pattern['type'] == 'email' && isset( $_POST["user_email"] ) ) { //check username
				if ( preg_match( $pattern['regex'], trim( $_POST["user_email"] ) ) ) {
					$content['errors']->add( 'blogname', __( "Wir denken, Du könntest ein Spambot sein. Wenn Du kein Spammer bist, kontaktiere uns bitte oder verwende eine andere E-Mail-Adresse.", 'ust' ) );
					$error = true;
				}
			}

			if ( $error ) {
				$ust_patterns[ $key ]['matched'] = $pattern['matched'] + 1;
			}
		}
		update_site_option( "ust_patterns", $ust_patterns ); //save blocked counts
	}

	return $content;
}

//check for multiple signups from the same IP in 24 hours buddypress
function ust_pre_signup_check_bp() {
	global $wpdb, $bp;
	$ust_settings = get_site_option( "ust_settings" );

	if ( $ust_settings['num_signups'] ) {
		$date = date( 'Y-m-d H:i:s', strtotime( '-1 day', time() ) );
		$ips  = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->registration_log} WHERE IP = '{$_SERVER['REMOTE_ADDR']}' AND date_registered >= '$date'" );
		if ( $ips > $ust_settings['num_signups'] ) {
			$bp->signup->errors['multicheck'] = __( "Eine begrenzte Anzahl von Anmeldungen kann in kurzer Zeit über Deine Internetverbindung durchgeführt werden. Wenn Du kein Spammer bist, versuche es bitte in 24 Stunden erneut.", 'ust' );
		}
	}

	//check signup history for this IP
	if ( $ust_settings['ip_blocking'] ) {
		$ip_splogs = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->registration_log} l LEFT JOIN {$wpdb->blogs} b ON l.blog_id = b.blog_id WHERE l.IP = '{$_SERVER['REMOTE_ADDR']}' AND b.spam = 1" );
		if ( $ip_splogs >= $ust_settings['ip_blocking'] ) {
			$bp->signup->errors['multicheck'] = __( "Unsere automatisierten Systeme halten Dich für einen Spambot. Wenn Du kein Spammer bist, kontaktiere uns bitte und gib uns diese IP: ", 'ust' ) . $_SERVER['REMOTE_ADDR'];
		}
	}

	//only check for blog signups
	if ( ! ( isset( $_POST['signup_with_blog'] ) && $_POST['signup_with_blog'] ) ) {
		return;
	}

	//check patterns
	$ust_patterns = get_site_option( "ust_patterns" );
	if ( is_array( $ust_patterns ) ) {
		foreach ( $ust_patterns as $key => $pattern ) {
			if ( $pattern['action'] != 'block' ) {
				continue;
			} //only check blocking rules here
			$error = false;
			if ( $pattern['type'] == 'domain' && isset( $_POST["signup_blog_url"] ) ) { //check domain
				if ( preg_match( $pattern['regex'], trim( $_POST["signup_blog_url"] ) ) ) {
					$bp->signup->errors['blogname'] = __( "Wenn Du kein Spammer bist, versuche es bitte erneut mit einer anderen Domain.", 'ust' );
					$error                          = true;
				}
			} else if ( $pattern['type'] == 'title' && isset( $_POST["signup_blog_title"] ) ) { //check title
				if ( preg_match( $pattern['regex'], trim( $_POST["signup_blog_title"] ) ) ) {
					$bp->signup->errors['blog_title'] = __( "Wenn Du kein Spammer bist, versuche es bitte erneut mit einem anderen Titel.", 'ust' );
					$error                            = true;
				}
			} else if ( $pattern['type'] == 'username' && isset( $_POST["signup_username"] ) ) { //check username
				if ( preg_match( $pattern['regex'], trim( $_POST["signup_username"] ) ) ) {
					$bp->signup->errors['signup_username'] = __( "Wenn Du kein Spammer bist, versuche es bitte erneut mit einem anderen Benutzernamen.", 'ust' );
					$error                                 = true;
				}
			} else if ( $pattern['type'] == 'email' && isset( $_POST["signup_email"] ) ) { //check username
				if ( preg_match( $pattern['regex'], trim( $_POST["signup_email"] ) ) ) {
					$bp->signup->errors['signup_email'] = __( "Wir denken, Du könntest ein Spambot sein. Wenn Du kein Spammer bist, kontaktiere uns bitte oder verwende eine andere E-Mail-Adresse.", 'ust' );
					$error                              = true;
				}
			}

			if ( $error ) {
				$ust_patterns[ $key ]['matched'] = $pattern['matched'] + 1;
			}
		}
		update_site_option( "ust_patterns", $ust_patterns ); //save blocked counts
	}
}

function ust_pre_signup_check_bp_error_display() {
	?>
	<div class="register-section" id="antisplog-multicheck">
		<?php do_action( 'bp_multicheck_errors' ) ?>
	</div>
<?php
}

function ust_signup_meta( $meta ) {

	$ust_signup_data['signup_user_agent']   = $_SERVER['HTTP_USER_AGENT'];
	$ust_signup_data['signup_user_ip']      = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
	$ust_signup_data['signup_user_referer'] = $_SERVER['HTTP_REFERER'];

	$meta['ust_signup_data'] = $ust_signup_data;

	return $meta;
}

//replace new blog admin notification email
function ust_newblog_notify_siteadmin( $blog_id, $deprecated = '' ) {
	global $current_site, $ust_admin_url;
	if ( get_site_option( 'registrationnotification' ) != 'yes' ) {
		return false;
	}

	$email = get_site_option( 'admin_email' );
	if ( is_email( $email ) == false ) {
		return false;
	}

	switch_to_blog( $blog_id );
	$blogname = get_option( 'blogname' );
	$siteurl  = get_option( 'siteurl' );
	restore_current_blog();
	$spam_url         = esc_url( "$ust_admin_url&spam_blog=1&id=$blog_id&updated=1&updatedmsg=Blog+marked+as+spam%21" );
	$ust_url          = esc_url( $ust_admin_url );
	$options_site_url = esc_url( network_admin_url( "admin.php" ) );

	$msg = sprintf( __( "Neuer Blog: %1s
URL: %2s
Remote IP: %3s

Spam diesen Blog: %4s
Vermutete Splog-Warteschlange anzeigen: %5s

Deaktiviere diese Benachrichtigungen: %6s", 'ust' ), $blogname, $siteurl, $_SERVER['REMOTE_ADDR'], $spam_url, $ust_url, $options_site_url );
	$msg = apply_filters( 'newblog_notify_siteadmin', $msg );

	wp_mail( $email, sprintf( __( "Neue Blog-Registrierung: %s" ), $siteurl ), $msg );

	return true;
}

function ust_trim_title( $title ) {
	$title = strip_tags( $title );

	if ( strlen( $title ) > 20 ) {
		return substr( $title, 0, 17 ) . '...';
	} else {
		return $title;
	}
}

//------------------------------------------------------------------------//

//---Output Functions-----------------------------------------------------//

//------------------------------------------------------------------------//


function ust_wpsignup_url( $echo = true ) {
	global $current_site;
	$ust_signup   = get_site_option( 'ust_signup' );
	$original_url = network_home_url( 'wp-signup.php' );
	$new_slug     = defined( 'UST_OVERRIDE_SIGNUP_SLUG' ) ? UST_OVERRIDE_SIGNUP_SLUG : $ust_signup['slug'];
	$new_url      = network_home_url( trailingslashit( $new_slug ) );

	//if ( ! $ust_signup['active'] ) {
	// Bool Fix?
	/*if ( ! $ust_signup['active']??='ust_signup') {*/
	if ( ! $ust_signup['active'] = 'ust_signup') {
		if ( $echo ) {
			echo $original_url;
		} else {
			return $original_url;
		}
	} else {
		if ( $echo ) {
			echo $new_url;
		} else {
			return $new_url;
		}
	}
}

function ust_signup_fields( $errors ) {
	$ust_settings = get_site_option( "ust_settings" );

	if ( $ust_settings['signup_protect'] == 'recaptcha' ) {

		$recaptcha = get_site_option( 'ust_recaptcha' );

		echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
		echo '<label>' . __( 'Menschliche Verifikation:', 'ust' ) . '</label>';
		if ( $errmsg = $errors->get_error_message( 'recaptcha' ) ) {
			echo '<p class="error">' . $errmsg . '</p>';
		}
		echo '<div class="g-recaptcha" data-sitekey="' . esc_attr( $recaptcha['pubkey'] ) . '" data-theme="' . esc_attr( $recaptcha['theme'] ) . '"></div>';
		echo '<br />';

	} else if ( $ust_settings['signup_protect'] == 'asirra' ) {

		echo '<p><label>' . __( 'Menschliche Verifikation:', 'ust' ) . '</label></p>';
		if ( $errmsg = $errors->get_error_message( 'asirra' ) ) {
			echo '<p class="error">' . $errmsg . '</p>';
		} else {
			echo '<div id="asirraError"></div>';
		}
		echo '<script type="text/javascript" src="http://challenge.asirra.com/js/AsirraClientSide.js"></script>';
		echo '<script type="text/javascript">
          asirraState.SetEnlargedPosition("right");
          asirraState.SetCellsPerRow(4);
          formElt = document.getElementById("setupform");
          formElt.onsubmit = function() { return MySubmitForm(); };
          
          var passThroughFormSubmit = false;
          function MySubmitForm() {
            if (passThroughFormSubmit) {
              return true;
            }
            Asirra_CheckIfHuman(HumanCheckComplete);

            return false;
          }
          function HumanCheckComplete(isHuman) {
            if (!isHuman) {
              asirraError = document.getElementById("asirraError");
              asirraError.innerHTML = \'<div class="error">' . __( 'Bitte versuche erneut, die Katzen richtig zu identifizieren.', 'ust' ) . '</div>\';
              return false;
            } else {
              passThroughFormSubmit = true;
              formElt.submit.click();
              return true;
            }
          }
          </script>';

	} else if ( $ust_settings['signup_protect'] == 'questions' ) {

		$ust_qa = get_site_option( "ust_qa" );
		if ( is_array( $ust_qa ) && count( $ust_qa ) ) {
			$qkey = rand( 0, count( $ust_qa ) - 1 );

			//encrypt the answer field name to make it harder for sploggers to guess. Changes every hour & different for every site.
			$salt       = get_site_option( "ust_salt" );
			$datesalt   = strtotime( date( 'Y-m-d H:00:00' ) );
			$field_name = 'qa_' . md5( $qkey . $salt . $datesalt );

			echo '<p><label>' . __( 'Menschliche Verifikation:', 'ust' ) . '</label>';
			if ( $errmsg = $errors->get_error_message( 'qa' ) ) {
				echo '<p class="error">' . $errmsg . '</p>';
			}
			echo stripslashes( $ust_qa[ $qkey ][0] );
			echo '<br /><input type="text" id="qa" name="' . $field_name . '" value="' . htmlentities( $_POST[ $field_name ] ) . '" />';
			echo '<br /><small>' . __( 'HINWEIS: Bei den Antworten wird die Groß-/Kleinschreibung nicht beachtet.', 'ust' ) . '</small>';
			echo '</p>&nbsp;<br />';
		}

	}
}

function ust_signup_fields_bp() {
	$ust_settings = get_site_option( "ust_settings" );

	if ( $ust_settings['signup_protect'] == 'recaptcha' ) {

		$recaptcha = get_site_option( 'ust_recaptcha' );
		echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';

		echo '<div class="register-section" id="blog-details-section">';
		echo '<label>' . __( 'Menschliche Verifikation:', 'ust' ) . '</label>';
		do_action( 'bp_recaptcha_errors' );
		echo '<div class="g-recaptcha" data-sitekey="' . esc_attr( $recaptcha['pubkey'] ) . '" data-theme="' . esc_attr( $recaptcha['theme'] ) . '"></div>';
		echo '</div>';

	} else if ( $ust_settings['signup_protect'] == 'asirra' ) {

		echo '<div class="register-section" id="blog-details-section">';
		echo '<label>' . __( 'Menschliche Verifikation:', 'ust' ) . '</label>';
		do_action( 'bp_asirra_errors' );
		echo '<div id="asirraError"></div>';
		echo '<script type="text/javascript" src="http://challenge.asirra.com/js/AsirraClientSide.js"></script>';
		echo '<script type="text/javascript">
          asirraState.SetEnlargedPosition("right");
          asirraState.SetCellsPerRow(4);
          formElt = document.getElementById("signup_form");
          formElt.onsubmit = function() { return MySubmitForm(); };
          
          var passThroughFormSubmit = false;
          function MySubmitForm() {
            if (passThroughFormSubmit) {
              return true;
            }
            Asirra_CheckIfHuman(HumanCheckComplete);

            return false;
          }
          function HumanCheckComplete(isHuman) {
            if (!isHuman) {
              asirraError = document.getElementById("asirraError");
              asirraError.innerHTML = \'<div class="error">' . __( 'Bitte versuche erneut, die Katzen richtig zu identifizieren.', 'ust' ) . '</div>\';
              return false;
            } else {
              passThroughFormSubmit = true;
              formElt.submit();
              return true;
            }
            
          }
          </script>';
		echo '</div>';
		echo '<input type="hidden" name="signup_submit" value="1" />';

	} else if ( $ust_settings['signup_protect'] == 'questions' ) {

		$ust_qa = get_site_option( "ust_qa" );
		if ( is_array( $ust_qa ) && count( $ust_qa ) ) {
			$qkey = rand( 0, count( $ust_qa ) - 1 );

			//encrypt the answer field name to make it harder for sploggers to guess. Changes every hour & different for every site.
			$salt       = get_site_option( "ust_salt" );
			$datesalt   = strtotime( date( 'Y-m-d H:00:00' ) );
			$field_name = 'qa_' . md5( $qkey . $salt . $datesalt );
			$field_value = isset($_POST[ $field_name ]) ? esc_attr( $_POST[ $field_name ] ) : '';

			echo '<div class="register-section" id="antisplog">';
			echo '<label>' . __( 'Menschliche Verifikation:', 'ust' ) . '</label>';
			do_action( 'bp_qa_errors' );
			echo stripslashes( $ust_qa[ $qkey ][0] );
			echo '<br /><input type="text" id="qa" name="' . $field_name . '" value="' . $field_value . '" />';
			echo '<br /><small>' . __( 'HINWEIS: Bei den Antworten wird die Groß-/Kleinschreibung nicht beachtet.', 'ust' ) . '</small>';
			echo '</div>';
		}

	}

}

//Add CSS to signup
function ust_signup_css() {
	?>
	<style type="text/css">
		input#qa {
			font-size: 24px;
			width: 50%;
			padding: 3px;
			margin-left: 20px;
		}

		#reCAPTCHA {
			position: relative;
			margin-left: 10px;
		}

		#AsirraDiv {
			position: relative;
			margin-left: 10px;
		}

		small {
			font-weight: normal;
			margin-left: 20px;
		}
	</style>
<?php
}

function ust_admin_scripts_init() {
	global $ust_current_version;

	/* Register our scripts. */
	wp_register_script( 'anti-splog', WP_PLUGIN_URL . '/anti-splog/includes/js/anti-splog.js', array( 'jquery' ), $ust_current_version );
}

function ust_admin_script_flot() {
	global $ust_current_version;
	wp_enqueue_script( 'flot', plugins_url( '/anti-splog/includes/js/jquery.flot.min.js' ), array( 'jquery' ), $ust_current_version );
	wp_enqueue_script( 'flot-excanvas', plugins_url( '/anti-splog/includes/js/excanvas.pack.min.js' ), array(
			'jquery',
			'flot'
		), $ust_current_version );
	wp_enqueue_script( 'flot-stack', plugins_url( '/anti-splog/includes/js/jquery.flot.stack.min.js' ), array(
			'jquery',
			'flot'
		), $ust_current_version );
}

function ust_admin_script() {
	wp_enqueue_script( 'thickbox' );
	wp_enqueue_script( 'anti-splog' );
}

function ust_admin_style() {
	wp_enqueue_style( 'thickbox' );
}

function ust_admin_help() {
	global $current_site;

	$current_screen = get_current_screen();

	//check for WP 3.3+
	if ( ! method_exists( get_current_screen(), 'add_help_tab' ) ) {
		return;
	}

	$current_screen->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __( 'Überblick' ),
		'content' => __( "<h3>Das Plugin funktioniert in 3 Phasen:</h3>
          <ol>
          <li><b>Anmeldungsverhinderung</b> - Diese Maßnahmen dienen hauptsächlich dazu, Bots zu stoppen. Benutzerfreundliche Fehlermeldungen werden den Benutzern angezeigt, wenn diese die Anmeldung verhindern. Sie sind alle optional und beinhalten:</li>
            <ul style=\"margin-left:20px;\">
              <li><b>Begrenzung der Anzahl der Anmeldungen pro IP pro 24 Stunden</b> (Dies kann auch menschliche Spammer verlangsamen, wenn die Webseiten-Kundschaft dies unterstützt. Wahrscheinlich jedoch keine Edublogs, da es sich an Schulen richtet, die möglicherweise eine große Anzahl von Blogs aus einer IP erstellen müssen)</li>
              <li><b>Ändern des Speicherorts der Anmeldeseite alle 24 Stunden</b> - Dies ist eine der effektivsten und dennoch benutzerfreundlichen Methoden, um Bots zu stoppen. </li>
              <li><b>Menschlicher Test</b> - Beantworten von benutzerdefinierten Fragen, Auswählen der Katzenbilder, Recaptcha oder Are You A Human PlayThrough.</li>
				<li><b>Musterabgleich</b> - Überprüfung von Seiten-Domains, Titeln, E-Mails oder Benutzernamen anhand Deines definierten Satzes von regulären Ausdrücken.</li>
            </ul>
          <li><b>Die Moderations-Warteschlange</b> - Für bestehende Blogs oder Blogs, die andere Filter umgehen, bietet die Warteschlange eine kontinuierliche Möglichkeit, Blogs und Spam zu überwachen oder sie einfacher als gültig zu kennzeichnen (ignorieren), da sie mit neuen Beiträgen aktualisiert werden. Auch wenn ein Benutzer versucht, einen Spam-Blog zu besuchen, wird jetzt eine benutzerfreundliche Nachricht und ein Formular angezeigt, um den Administrator zur Überprüfung zu kontaktieren, wenn er dies für gültig hält. Die E-Mail enthält Links, um einfach den Spam zu entfernen oder die letzten Beiträge aufzurufen. Die gesamte Warteschlange ist AJAX-basiert, sodass Du Blogs mit unglaublicher Geschwindigkeit moderieren kannst.</li>
            <ul style=\"margin-left:20px;\">
              <li><b>Verdächtige Blogs</b> - Diese Liste enthält alle Blogs, die das Plugin für Splogs hält. Es zieht Blogs ein, die mindestens 1 Keyword in den letzten Posts aus der von Dir definierten Keyword-Liste enthalten. Die Liste versucht, die am häufigsten verdächtigten Blogs an die Spitze zu bringen, sortiert nach Anzahl der Keyword-Übereinstimmungen und schließlich nach der letzten Aktualisierung. Die Liste enthält eine Reihe von Verbesserungen für die Moderation, einschließlich der letzten Benutzer-ID, der letzten Benutzer-IP, Links zum Suchen oder Spam nach Benutzern und ihren Blogs oder Blogs, die an eine IP-Adresse gebunden sind (seien Sie vorsichtig mit dieser!), Möglichkeit zum Ignorieren (Ablehnen) ) gültige Blogs aus der Warteschlange und eine Liste der letzten Beiträge und eine sofortige Vorschau ihres Inhalts, ohne die Seite zu verlassen (die zeitsparendste Funktion von allen!)</li>
              <li><b>Letzte Splogs</b> - Dies ist einfach eine Liste aller Blogs, die jemals auf der Webseite gespamt wurden, in der Reihenfolge der Spam-Zeit. Die Idee hier ist, dass Du, wenn Du einen Fehler machst, hierher zurückkehren kannst, um ihn rückgängig zu machen. Auch wenn sich ein Benutzer beschwert, dass ein gültiger Blog gespammt wurde, kannst Du ihn schnell hier aufrufen und eine Vorschau der neuesten Beiträge zur Bestätigung anzeigen (normalerweise kannst Du den Blog-Inhalt überhaupt nicht sehen).</li>
              <li><b>Ignorierte Blogs</b> - Wenn ein gültiger Blog in der Liste der Verdächtigen auftaucht, markiere ihn einfach als ignoriert, um ihn dort zu entfernen. Es wird dann in der ignorierten Liste angezeigt, nur für den Fall, dass Du es rückgängig machen musst.</li>
            </ul>
          </ol>", 'ust' ) 
	) );

	//$domain       = $current_site->domain;
	$spamlist_url = "https://n3rds.work/docs/spamliste-fuer-mail-domains/";

	get_current_screen()->set_help_sidebar(
		'<p><strong>' . __( 'Für mehr Informationen:' ) . '</strong></p>' .
		'<p><a href="https://n3rds.work/docs/anti-splog-handbuch/" target="_blank">' . __( 'Handbuch', 'ust' ) . '</a></p>' .
		'<p><a href="' . $spamlist_url . '" target="_blank">' . __( 'Spamlisten', 'ust' ) . '</a></p>'
	);
}

function ust_test_regex() {
	global $wpdb;

	$response = '';

	if ( false === @preg_match( stripslashes( $_POST['regex'] ), 'thisisjustateststring' ) ) {
		die( json_encode( array(
				'status' => 0,
				'data'   => __( 'Bitte gib einen gültigen regulären PCRE-Ausdruck mit Trennzeichen ein.', 'ust' )
			) ) );
	}

	if ( $_POST['type'] == 'domain' ) {

		$domains = $wpdb->get_col( "SELECT SUBSTRING_INDEX(domain, '.', 1) as domain FROM $wpdb->blogs ORDER BY registered DESC LIMIT 10000" );
		$result  = preg_grep( stripslashes( $_POST['regex'] ), $domains );
		if ( count( $result ) ) {
			$response = '<ul>';
			$i        = 1;
			foreach ( $result as $value ) {
				if ( $i >= 50 ) {
					$response .= '<li><em>' . sprintf( __( '%s Ergebnisse werden nicht angezeigt...', 'ust' ), number_format_i18n( count( $result ) - $i ) ) . '</em></li>';
					break;
				}
				$response .= '<li>' . $value . '</li>';
				$i ++;
			}
			$response .= '</ul>';
		} else {
			$response = __( 'Deine Testsuche ergab keine Ergebnisse von den letzten 10.000 registrierten Domains.', 'ust' );
		}
		die( json_encode( array( 'status' => 1, 'data' => $response ) ) );

	} else if ( $_POST['type'] == 'username' ) {

		$users  = $wpdb->get_col( "SELECT user_login FROM $wpdb->users ORDER BY user_registered DESC LIMIT 10000" );
		$result = preg_grep( stripslashes( $_POST['regex'] ), $users );
		if ( count( $result ) ) {
			$response = '<ul>';
			$i        = 1;
			foreach ( $result as $value ) {
				if ( $i >= 50 ) {
					$response .= '<li><em>' . sprintf( __( '%s Ergebnisse werden nicht angezeigt...', 'ust' ), number_format_i18n( count( $result ) - $i ) ) . '</em></li>';
					break;
				}
				$response .= '<li>' . $value . '</li>';
				$i ++;
			}
			$response .= '</ul>';
		} else {
			$response = __( 'Deine Testsuche ergab keine Ergebnisse von den letzten 10.000 registrierten Benutzern.', 'ust' );
		}
		die( json_encode( array( 'status' => 1, 'data' => $response ) ) );

	} else if ( $_POST['type'] == 'email' ) {

		$users  = $wpdb->get_col( "SELECT user_email FROM $wpdb->users ORDER BY user_registered DESC LIMIT 10000" );
		$result = preg_grep( stripslashes( $_POST['regex'] ), $users );
		if ( count( $result ) ) {
			$response = '<ul>';
			$i        = 1;
			foreach ( $result as $value ) {
				if ( $i >= 50 ) {
					$response .= '<li><em>' . sprintf( __( '%s Ergebnisse werden nicht angezeigt...', 'ust' ), number_format_i18n( count( $result ) - $i ) ) . '</em></li>';
					break;
				}
				$response .= '<li>' . $value . '</li>';
				$i ++;
			}
			$response .= '</ul>';
		} else {
			$response = __( 'Deine Testsuche ergab keine Ergebnisse von den letzten 10.000 registrierten Benutzern.', 'ust' );
		}
		die( json_encode( array( 'status' => 1, 'data' => $response ) ) );

	} else {
		die( json_encode( array(
				'status' => 0,
				'data'   => __( 'Entschuldigung, Du darfst keine Live-Tests für Webseiten-Titel durchführen.', 'ust' )
			) ) );
	}
}

function ust_recaptcha_check_answer( $secret_key, $ip, $response ) {

	$body = array( 'secret' => $secret_key,
	               'remoteip' => $ip,
	               'response' => $response );

	$result = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify',
		array( 'body' => $body )
	);

	if ( is_wp_error( $result ) ) {
		return false;
	} else {
		$result = json_decode( wp_remote_retrieve_body( $result ) );
		if ( isset( $result->success ) ) {
			return (bool)$result->success;
		}
	}
	return false;
}

//------------------------------------------------------------------------//

//---Page Output Functions------------------------------------------------//

//------------------------------------------------------------------------//

function ust_admin_moderate() {
	require_once( dirname( __FILE__ ) . '/includes/admin_templates/moderate.php' );
}

function ust_admin_stats() {
	require_once( dirname( __FILE__ ) . '/includes/admin_templates/stats.php' );
}

function ust_admin_patterns() {
	require_once( dirname( __FILE__ ) . '/includes/admin_templates/patterns.php' );
}

function ust_admin_settings() {
	require_once( dirname( __FILE__ ) . '/includes/admin_templates/settings.php' );
}

class UST_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname'   => 'ust_widget',
			'description' => __( 'Zeigt die Anzahl der Seiten-Blogs und Splogs an, die vom Anti-Splog erfasst wurden.', 'ust' )
		);
		parent::__construct( 'ust_widget', __( 'Splog-Statistiken', 'ust' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		global $wpdb, $current_user, $bp;

		extract( $args );
		$date_format = __( 'm/d/Y g:ia', 'ust' );

		echo $before_widget;
		$title = $instance['title'];
		if ( ! empty( $title ) ) {
			echo $before_title . apply_filters( 'widget_title', $title ) . $after_title;
		};
		?>
		<ul>
			<li><?php _e( 'Blogs: ', 'ust' );
				echo get_blog_count(); ?></li>
			<li><?php _e( 'Gefangene Splogs: ', 'ust' );
				echo number_format_i18n( (int)get_site_option( 'ust_spam_count' ) ); ?></li>
		</ul>

		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => __( 'Splog-Statistiken', 'ust' ) ) );
		$title    = strip_tags( $instance['title'] );
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Titel:', 'ust' ) ?> <input
					class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
					name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
					value="<?php echo esc_attr( $title ); ?>"/></label></p>
	<?php
	}
}


