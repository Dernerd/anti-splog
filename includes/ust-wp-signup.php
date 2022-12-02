<?php
/*
Anti-Splog Signup page
*/

global $active_signup, $current_site;
$ust_signup = get_site_option('ust_signup');

add_action( 'wp_head', 'signuppageheaders' ) ;

if ( is_array( get_site_option( 'illegal_names' )) && isset( $_GET[ 'new' ] ) && in_array( $_GET[ 'new' ], get_site_option( 'illegal_names' ) ) == true ) {
	wp_redirect( network_home_url() );
	die();
}

function do_signup_header() {
	do_action("signup_header");
}
add_action( 'wp_head', 'do_signup_header' );

function signuppageheaders() {
	echo "<meta name='robots' content='noindex,nofollow' />\n";
}

if ( !is_multisite() ) {
	wp_redirect( site_url('wp-login.php?action=register') );
	die();
}

if ( !is_main_site() ) {
	wp_redirect( ust_wpsignup_url(false) );
	die();
}

// Fix for page title
$wp_query->is_404 = false;

function wpmu_signup_stylesheet() {
	?>
	<style type="text/css">
		.mu_register { width: 90%; margin:0 auto; }
		.mu_register form { margin-top: 2em; }
		.mu_register .error { font-weight:700; padding:10px; color:#333333; background:#FFEBE8; border:1px solid #CC0000; }
		.mu_register input[type="submit"],
			.mu_register #blog_title,
			.mu_register #user_email,
			.mu_register #blogname,
			.mu_register #user_name { width:100%; font-size: 24px; margin:5px 0; }
		.mu_register .prefix_address,
			.mu_register .suffix_address {font-size: 18px;display:inline; }
		.mu_register label { font-weight:700; font-size:15px; display:block; margin:10px 0; }
		.mu_register label.checkbox { display:inline; }
		.mu_register .mu_alert { font-weight:700; padding:10px; color:#333333; background:#ffffe0; border:1px solid #e6db55; }
	</style>
	<?php
}

add_action( 'wp_head', 'wpmu_signup_stylesheet' );
get_header();

do_action( 'before_signup_form' );
?>
<div id="content" class="widecolumn">
<div class="mu_register">
<?php
function show_blog_form($blogname = '', $blog_title = '', $errors = '') {
	global $current_site;
	// Blog name
	if ( !is_subdomain_install() )
		echo '<label for="blogname">' . __('Webseitenname:') . '</label>';
	else
		echo '<label for="blogname">' . __('Webseite-Domain:') . '</label>';

	if ( $errmsg = $errors->get_error_message('blogname') ) { ?>
		<p class="error"><?php echo $errmsg ?></p>
	<?php }

	if ( !is_subdomain_install() )
		echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span><input name="blogname" type="text" id="blogname" value="'. esc_attr($blogname) .'" maxlength="60" /><br />';
	else
		echo '<input name="blogname" type="text" id="blogname" value="'.esc_attr($blogname).'" maxlength="60" /><span class="suffix_address">.' . ( $site_domain = preg_replace( '|^www\.|', '', $current_site->domain ) ) . '</span><br />';

	if ( !is_user_logged_in() ) {
		if ( !is_subdomain_install() )
			$site = $current_site->domain . $current_site->path . __( 'sitename' );
		else
			$site = __( 'domain' ) . '.' . $site_domain . $current_site->path;
		echo '<p>(<strong>' . sprintf( __('Deine Adresse wird %s sein.'), $site ) . '</strong>) ' . __( 'Muss mindestens 4 Zeichen lang sein, nur Buchstaben und Zahlen. Es kann nicht geändert werden, also wähle sorgfältig!' ) . '</p>';
	}

	// Blog Title
	?>
	<label for="blog_title"><?php _e('Webseitentitel:') ?></label>
	<?php if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>
		<p class="error"><?php echo $errmsg ?></p>
	<?php }
	echo '<input name="blog_title" type="text" id="blog_title" value="'.esc_attr($blog_title).'" />';
	?>

	<div id="privacy">
        <p class="privacy-intro">
            <label for="blog_public_on"><?php _e('Privatsphäre:') ?></label>
            <?php _e('Zulassen, dass meine Webseite in Suchmaschinen wie Google, Bing und in öffentlichen Einträgen in diesem Netzwerk erscheint.'); ?>
            <br style="clear:both" />
            <label class="checkbox" for="blog_public_on">
                <input type="radio" id="blog_public_on" name="blog_public" value="1" <?php if ( !isset( $_POST['blog_public'] ) || $_POST['blog_public'] == '1' ) { ?>checked="checked"<?php } ?> />
                <strong><?php _e( 'Ja' ); ?></strong>
            </label>
            <label class="checkbox" for="blog_public_off">
                <input type="radio" id="blog_public_off" name="blog_public" value="0" <?php if ( isset( $_POST['blog_public'] ) && $_POST['blog_public'] == '0' ) { ?>checked="checked"<?php } ?> />
                <strong><?php _e( 'Nein' ); ?></strong>
            </label>
        </p>
	</div>

	<?php
	do_action('signup_blogform', $errors);
}

function validate_blog_form() {
	$user = '';
	if ( is_user_logged_in() )
		$user = wp_get_current_user();

	return wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title'], $user);
}

function show_user_form($user_name = '', $user_email = '', $errors = '') {
	// User name
	echo '<label for="user_name">' . __('Nutzername:') . '</label>';
	if ( $errmsg = $errors->get_error_message('user_name') ) {
		echo '<p class="error">'.$errmsg.'</p>';
	}
	echo '<input name="user_name" type="text" id="user_name" value="'. esc_attr($user_name) .'" maxlength="60" /><br />';
	_e( '(Muss mindestens 4 Zeichen lang sein, nur Buchstaben und Zahlen.)' );
	?>

	<label for="user_email"><?php _e( 'Email&nbsp;Addresse:' ) ?></label>
	<?php if ( $errmsg = $errors->get_error_message('user_email') ) { ?>
		<p class="error"><?php echo $errmsg ?></p>
	<?php } ?>
	<input name="user_email" type="text" id="user_email" value="<?php  echo esc_attr($user_email) ?>" maxlength="200" /><br /><?php _e('Wir senden Deine Registrierungs-E-Mail an diese Adresse. (Überprüfe Deine E-Mail-Adresse, bevor Du fortfährst.)') ?>
	<?php
	if ( $errmsg = $errors->get_error_message('generic') ) {
		echo '<p class="error">' . $errmsg . '</p>';
	}
	do_action( 'signup_extra_fields', $errors );
}

function validate_user_form() {
	return wpmu_validate_user_signup($_POST['user_name'], $_POST['user_email']);
}

function signup_another_blog($blogname = '', $blog_title = '', $errors = '') {
	global $current_site;
	$current_user = wp_get_current_user();

	if ( ! is_wp_error($errors) ) {
		$errors = new WP_Error();
	}

	// allow definition of default variables
	$filtered_results = apply_filters('signup_another_blog_init', array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
	$blogname = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors = $filtered_results['errors'];

	echo '<h2>' . sprintf( __( 'Hole Dir innerhalb von Sekunden eine <em>weitere</em> %s-Webseite' ), $current_site->site_name ) . '</h2>';

	if ( $errors->get_error_code() ) {
		echo '<p>' . __( 'Es ist ein Problem aufgetreten, bitte korrigiere das unten stehende Formular und versuche es erneut.' ) . '</p>';
	}
	?>
	<p><?php printf( __( 'Willkommen zurück, %s. Durch Ausfüllen des nachstehenden Formulars kannst Du <strong>Deinem Konto eine weitere Webseite hinzufügen</strong>. Die Anzahl der Webseiten, die Du haben kannst, ist unbegrenzt. Erstelle also nach Herzenslust, aber schreibe verantwortungsbewusst!' ), $current_user->display_name ) ?></p>

	<?php
	$blogs = get_blogs_of_user($current_user->ID);
	if ( !empty($blogs) ) { ?>

			<p><?php _e( 'Webseiten, bei denen Du bereits Mitglied bist:' ) ?></p>
			<ul>
				<?php foreach ( $blogs as $blog ) {
					$home_url = get_home_url( $blog->userblog_id );
					echo '<li><a href="' . esc_url( $home_url ) . '">' . $home_url . '</a></li>';
				} ?>
			</ul>
	<?php } ?>

	<p><?php _e( 'Wenn Du keine großartige Webseiten-Domain verwenden möchtest, überlasse sie einem neuen Benutzer. Jetzt ran an die Sache!' ) ?></p>
	<form id="setupform" method="post" action="<?php ust_wpsignup_url(); ?>">
		<input type="hidden" name="stage" value="gimmeanotherblog" />
		<?php do_action( "signup_hidden_fields" ); ?>
		<?php show_blog_form($blogname, $blog_title, $errors); ?>
		<p class="submit"><input type="submit" name="submit" class="submit" value="<?php esc_attr_e( 'Webseite erstellen' ) ?>" /></p>
	</form>
	<?php
}

function validate_another_blog_signup() {
	global $wpdb, $blogname, $blog_title, $errors, $domain, $path;
	$current_user = wp_get_current_user();
	if ( !is_user_logged_in() )
		die();

	$result = validate_blog_form();
	extract($result);

	if ( $errors->get_error_code() ) {
		signup_another_blog($blogname, $blog_title, $errors);
		return false;
	}

	$public = (int) $_POST['blog_public'];
	$meta = apply_filters( 'signup_create_blog_meta', array( 'lang_id' => 1, 'public' => $public ) ); // deprecated
	$meta = apply_filters( 'add_signup_meta', $meta );

	wpmu_create_blog( $domain, $path, $blog_title, $current_user->id, $meta, $wpdb->siteid );
	confirm_another_blog_signup($domain, $path, $blog_title, $current_user->user_login, $current_user->user_email, $meta);
	return true;
}

function confirm_another_blog_signup($domain, $path, $blog_title, $user_name, $user_email = '', $meta = '') {
	?>
	<h2><?php printf( __( 'Die Webseite %s gehört Dir.' ), "<a href='http://{$domain}{$path}'>{$blog_title}</a>" ) ?></h2>
	<p>
		<?php printf( __( '<a href="http://%1$s">http://%2$s</a> ist Deine neue Webseite.  <a href="%3$s">Melde Dich an</a> als &#8220;%4$s&#8221; mit Deinem bestehenden Passwort.' ), $domain.$path, $domain.$path, "http://" . $domain.$path . "wp-login.php", $user_name ) ?>
	</p>
	<?php
	do_action( 'signup_finished' );
}

function signup_user($user_name = '', $user_email = '', $errors = '') {
	global $current_site, $active_signup;

	if ( !is_wp_error($errors) )
		$errors = new WP_Error();
	if ( isset( $_POST[ 'signup_for' ] ) )
		$signup[ esc_html( $_POST[ 'signup_for' ] ) ] = 'checked="checked"';
	else
		$signup[ 'blog' ] = 'checked="checked"';

	//TODO - This doesn't seem to do anything do we really need it?
	$signup['user'] = isset( $signup['user'] ) ? $signup['user'] : '';

	// allow definition of default variables
	$filtered_results = apply_filters('signup_user_init', array('user_name' => $user_name, 'user_email' => $user_email, 'errors' => $errors ));
	$user_name = $filtered_results['user_name'];
	$user_email = $filtered_results['user_email'];
	$errors = $filtered_results['errors'];

	?>

	<h2><?php printf( __( 'Hole Dir in Sekundenschnelle Dein eigenes %s-Konto' ), $current_site->site_name ) ?></h2>
	<form id="setupform" method="post" action="<?php ust_wpsignup_url(); ?>">
		<input type="hidden" name="stage" value="validate-user-signup" />
		<?php do_action( "signup_hidden_fields" ); ?>
		<?php show_user_form($user_name, $user_email, $errors); ?>

		<p>
		<?php if ( $active_signup == 'blog' ) { ?>
			<input id="signupblog" type="hidden" name="signup_for" value="blog" />
		<?php } elseif ( $active_signup == 'user' ) { ?>
			<input id="signupblog" type="hidden" name="signup_for" value="user" />
		<?php } else { ?>
			<input id="signupblog" type="radio" name="signup_for" value="blog" <?php echo $signup['blog'] ?> />
			<label class="checkbox" for="signupblog"><?php _e('Gib mir eine Webseite!') ?></label>
			<br />
			<input id="signupuser" type="radio" name="signup_for" value="user" <?php echo $signup['user'] ?> />
			<label class="checkbox" for="signupuser"><?php _e('Nur einen Benutzernamen, bitte.') ?></label>
		<?php } ?>
		</p>

		<p class="submit"><input type="submit" name="submit" class="submit" value="<?php esc_attr_e('Weiter') ?>" /></p>
	</form>
	<?php
}

function validate_user_signup() {
	$result = validate_user_form();
	extract($result);

	if ( $errors->get_error_code() ) {
		signup_user($user_name, $user_email, $errors);
		return false;
	}

	if ( 'blog' == $_POST['signup_for'] ) {
		signup_blog($user_name, $user_email);
		return false;
	}

	wpmu_signup_user($user_name, $user_email, apply_filters( "add_signup_meta", array() ) );

	confirm_user_signup($user_name, $user_email);
	return true;
}

function confirm_user_signup($user_name, $user_email) {
	?>
	<h2><?php printf( __( '%s ist Dein neuer Benutzername' ), $user_name) ?></h2>
	<p><?php _e( 'Aber bevor Du Deinen neuen Benutzernamen verwenden kannst, <strong>musst Du ihn aktivieren</strong>.' ) ?></p>
	<p><?php printf(__( 'Überprüfe Deinen Posteingang unter <strong>%1$s</strong> und klicke auf den angegebenen Link.' ),  $user_email) ?></p>
	<p><?php _e( 'Wenn Du Deinen Benutzernamen nicht innerhalb von zwei Tagen aktivierst, musst Du Dich erneut anmelden.' ); ?></p>
	<?php
	do_action( 'signup_finished' );
}

function signup_blog($user_name = '', $user_email = '', $blogname = '', $blog_title = '', $errors = '') {
	if ( !is_wp_error($errors) )
		$errors = new WP_Error();

	// allow definition of default variables
	$filtered_results = apply_filters('signup_blog_init', array('user_name' => $user_name, 'user_email' => $user_email, 'blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
	$user_name = $filtered_results['user_name'];
	$user_email = $filtered_results['user_email'];
	$blogname = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors = $filtered_results['errors'];

	if ( empty($blogname) )
		$blogname = $user_name;
	?>
	<form id="setupform" method="post" action="<?php ust_wpsignup_url(); ?>">
		<input type="hidden" name="stage" value="validate-blog-signup" />
		<input type="hidden" name="user_name" value="<?php echo esc_attr($user_name) ?>" />
		<input type="hidden" name="user_email" value="<?php echo esc_attr($user_email) ?>" />
		<?php do_action( "signup_hidden_fields" ); ?>
		<?php show_blog_form($blogname, $blog_title, $errors); ?>
		<p class="submit"><input type="submit" name="submit" class="submit" value="<?php esc_attr_e('Anmelden') ?>" /></p>
	</form>
	<?php
}

function validate_blog_signup() {
	// Re-validate user info.
	$result = wpmu_validate_user_signup($_POST['user_name'], $_POST['user_email']);
	extract($result);

	if ( $errors->get_error_code() ) {
		signup_user($user_name, $user_email, $errors);
		return false;
	}

	$result = wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title']);
	extract($result);

	if ( $errors->get_error_code() ) {
		signup_blog($user_name, $user_email, $blogname, $blog_title, $errors);
		return false;
	}

	$public = (int) $_POST['blog_public'];
	$meta = array ('lang_id' => 1, 'public' => $public);
	$meta = apply_filters( "add_signup_meta", $meta );

	wpmu_signup_blog($domain, $path, $blog_title, $user_name, $user_email, $meta);
	confirm_blog_signup($domain, $path, $blog_title, $user_name, $user_email, $meta);
	return true;
}

function confirm_blog_signup($domain, $path, $blog_title, $user_name = '', $user_email = '', $meta) {
	?>
	<h2><?php printf( __( 'Herzlichen Glückwünsch! Deine neue Webseite, %s, ist fast fertig.' ), "<a href='http://{$domain}{$path}'>{$blog_title}</a>" ) ?></h2>

	<p><?php _e( 'Aber bevor Du Deine Webseite verwenden kannst, <strong>musst Du sie aktivieren</strong>.' ) ?></p>
	<p><?php printf( __( 'Überprüfe Deinen Posteingang unter <strong>%s</strong> und klicke auf den angegebenen Link.' ),  $user_email) ?></p>
	<p><?php _e( 'Wenn Du Deine Webseite nicht innerhalb von zwei Tagen aktivierst, musst Du Dich erneut anmelden.' ); ?></p>
	<h2><?php _e( 'Wartest Du immer noch auf Deine E-Mail?' ); ?></h2>
	<p>
		<?php _e( 'Wenn Du Deine E-Mail noch nicht erhalten hast, kannst Du Folgendes tun:' ) ?>
		<ul id="noemail-tips">
			<li><p><strong><?php _e( 'Warte ein wenig länger. Manchmal kann die Zustellung von E-Mails durch Prozesse außerhalb unserer Kontrolle verzögert werden.' ) ?></strong></p></li>
			<li><p><?php _e( 'Überprüfe den Junk- oder Spam-Ordner Deines E-Mail-Clients. Manchmal landen versehentlich E-Mails dort.' ) ?></p></li>
			<li><?php printf( __( 'Hast Du Deine E-Mail richtig eingegeben? Du hast %s eingegeben, wenn es falsch ist, erhältst Du Deine E-Mail nicht.' ), $user_email ) ?></li>
		</ul>
	</p>
	<?php
	do_action( 'signup_finished' );
}

// Main
$active_signup = get_site_option( 'registration' );
if ( !$active_signup )
	$active_signup = 'all';

$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"

// Make the signup type translatable.
$i18n_signup['all'] = _x('all', 'Aktiver Anmeldetyp für mehrere Webseiten');
$i18n_signup['none'] = _x('none', 'Aktiver Anmeldetyp für mehrere Webseiten');
$i18n_signup['blog'] = _x('blog', 'Aktiver Anmeldetyp für mehrere Webseiten');
$i18n_signup['user'] = _x('user', 'Aktiver Anmeldetyp für mehrere Webseiten');

if ( is_super_admin() )
	echo '<div class="mu_alert">' . sprintf( __( 'Hallo Webseiten-Administrator! Du erlaubst derzeit &#8220;%s&#8221; Anmeldungen. Um die Registrierung zu ändern oder zu deaktivieren, gehe zu Deiner <a href="%s">Optionsseite</a>.' ), $i18n_signup[$active_signup], esc_url( network_admin_url( 'settings.php' ) ) ) . '</div>';

$newblogname = isset($_GET['new']) ? strtolower(preg_replace('/^-|-$|[^-a-zA-Z0-9]/', '', $_GET['new'])) : null;

$current_user = wp_get_current_user();
if ( $active_signup == "none" ) {
	_e( 'Die Registrierung wurde deaktiviert.' );
} elseif ( $active_signup == 'blog' && !is_user_logged_in() ) {
	if ( is_ssl() )
		$proto = 'https://';
	else
		$proto = 'http://';
	$login_url = site_url( 'wp-login.php?redirect_to=' . urlencode( ust_wpsignup_url(false) ));
	echo sprintf( __( 'Du musst Dich zuerst <a href="%s">anmelden</a> und kannst dann eine neue Webseite erstellen.' ), $login_url );
} else {
	$stage = isset( $_POST['stage'] ) ?  $_POST['stage'] : 'default';
	switch ( $stage ) {
		case 'validate-user-signup' :
			if ( $active_signup == 'all' || $_POST[ 'signup_for' ] == 'blog' && $active_signup == 'blog' || $_POST[ 'signup_for' ] == 'user' && $active_signup == 'user' )
				validate_user_signup();
			else
				_e( 'Die Benutzerregistrierung wurde deaktiviert.' );
		break;
		case 'validate-blog-signup':
			if ( $active_signup == 'all' || $active_signup == 'blog' )
				validate_blog_signup();
			else
				_e( 'Die Websiten-Registrierung wurde deaktiviert.' );
			break;
		case 'gimmeanotherblog':
			validate_another_blog_signup();
			break;
		case 'default':
		default :
			$user_email = isset( $_POST[ 'user_email' ] ) ? $_POST[ 'user_email' ] : '';
			do_action( "preprocess_signup_form" ); // populate the form from invites, elsewhere?
			if ( is_user_logged_in() && ( $active_signup == 'all' || $active_signup == 'blog' ) )
				signup_another_blog($newblogname);
			elseif ( is_user_logged_in() == false && ( $active_signup == 'all' || $active_signup == 'user' ) )
				signup_user( $newblogname, $user_email );
			elseif ( is_user_logged_in() == false && ( $active_signup == 'blog' ) )
				_e( 'Leider sind zur Zeit keine Neuanmeldungen möglich.' );
			else
				_e( 'Du bist bereits angemeldet. Eine erneute Registrierung ist nicht erforderlich!' );

			if ( $newblogname ) {
				$newblog = get_blogaddress_by_name( $newblogname );

				if ( $active_signup == 'blog' || $active_signup == 'all' )
					printf( __( '<p><em>Die Webseite, nach der Du gesucht hast, <strong>%s</strong>, existiert nicht, aber Du kannst sie jetzt erstellen!</em></p>' ), $newblog );
				else
					printf( __( '<p><em>Die Webseite, nach der Du gesucht hast, <strong>%s</strong>, existiert nicht.</em></p>' ), $newblog );
			}
			break;
	}
}
?>
</div>
</div>
<?php do_action( 'after_signup_form' ); ?>

<?php get_footer(); ?>