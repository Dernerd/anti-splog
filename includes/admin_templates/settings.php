<?php
if ( ! current_user_can( 'manage_network_options' ) ) {
	wp_die( 'Du hast keine Berechtigungen für diese Seite' );
}

global $current_site;
/*$domain       = $current_site->domain;
$register_url = "http://premium.wpmudev.org/wp-admin/profile.php?page=ustapi&amp;domain=$domain";

function ust_trim_array( $input ) {
	if ( ! is_array( $input ) ) {
		return trim( $input );
	}

	return array_map( 'ust_trim_array', $input );
}

//handle notice dismissal
if ( isset( $_GET['dismiss'] ) ) {
	update_site_option( 'ust_key_dismiss', strtotime( "+1 month" ) );
	?>
	<div class="updated fade"><p><?php _e( 'Notice dismissed.', 'ust' ); ?></p></div><?php
}

//process form
if ( isset( $_POST['ust_settings'] ) ) {

	//check the api key and connection
	$request["API_KEY"] = $_POST['ust']['api_key'];
	$api_response       = ust_http_post( 'api_check', $request );
	if ( $api_response && $api_response != 'Valid' ) {
		$_POST['ust']['api_key'] = '';
		echo '<div id="message" class="error"><p>' . __( sprintf( 'There was a problem with the API key you entered: "%s" <a href="%s" target="_blank">Fix it here&raquo;</a>', $api_response, $register_url ), 'ust' ) . '</p></div>';
	} else if ( ! $api_response ) {
		$_POST['ust']['api_key'] = '';
		echo '<div id="message" class="error"><p>' . __( 'There was a problem connecting to the API server. Please try again later.', 'ust' ) . '</p></div>';
	}
	$_POST['ust']['hide_adminbar'] = isset( $_POST['ust']['hide_adminbar'] ) ? 1 : 0; //handle checkbox
	if ( isset( $_POST['ust']['keywords'] ) && trim( $_POST['ust']['keywords'] ) ) {
		$_POST['ust']['keywords'] = explode( "\n", trim( $_POST['ust']['keywords'] ) );
	} else {
		$_POST['ust']['keywords'] = '';
	}
	update_site_option( "ust_settings", $_POST['ust'] );

	$ust_signup['active'] = isset( $_POST['ust_signup'] ) ? 1 : 0;
	$ust_signup['expire'] = time() + 86400; //extend 24 hours
	$ust_signup['slug']   = 'signup-' . substr( md5( time() ), rand( 0, 30 ), 3 ); //create new random signup url
	update_site_option( 'ust_signup', $ust_signup );

	update_site_option( "ust_recaptcha", ust_trim_array( $_POST['recaptcha'] ) );

	//process user questions
	$qa['questions'] = explode( "\n", trim( $_POST['ust_qa']['questions'] ) );
	$qa['answers']   = explode( "\n", trim( $_POST['ust_qa']['answers'] ) );
	$i               = 0;
	foreach ( $qa['questions'] as $question ) {
		if ( trim( $qa['answers'][ $i ] ) ) {
			$ust_qa[] = array( trim( $question ), trim( $qa['answers'][ $i ] ) );
		}
		$i ++;
	}
	update_site_option( "ust_qa", $ust_qa );

	do_action( 'ust_settings_process' );

	echo '<div id="message" class="updated fade"><p>' . __( 'Settings Saved!', 'ust' ) . '</p></div>';
}*/

$ust_settings  = get_site_option( "ust_settings" );
$ust_signup    = get_site_option( 'ust_signup' );
$ust_recaptcha = get_site_option( "ust_recaptcha" );
$ust_qa        = get_site_option( "ust_qa" );
if ( ! $ust_qa ) {
	$ust_qa = array(
		array( 'Was ist die Antwort auf „Zehn mal Zwei“ in Wortform?', 'Zwanzig' ),
		array( 'Wie lautet der Nachname des aktuellen US-Präsidenten?', 'Biden' )
	);
}

if ( is_array( $ust_qa ) && count( $ust_qa ) ) {
	foreach ( $ust_qa as $pair ) {
		$questions[] = $pair[0];
		$answers[]   = $pair[1];
	}
}

//create salt if not set
if ( ! get_site_option( "ust_salt" ) ) {
	update_site_option( "ust_salt", substr( md5( time() ), rand( 0, 15 ), 10 ) );
}

if ( ! $ust_settings['api_key'] ) {
	$style = ' style="background-color:#FF7C7C;"';
} else {
	$style = ' style="background-color:#ADFFAA;"';
}

?>
<div class="wrap">
<h2><?php _e( 'Anti-Splog Einstellungen', 'ust' ) ?></h2>

<div id="poststuff" class="metabox-holder">
<form method="post" action="">
<input type="hidden" name="ust_settings" value="1"/>

<div class="postbox">
	<h3 class='hndle'><span><?php _e( 'Allgemeine Einstellungen', 'ust' ) ?></span> - <span
			class="description"><?php _e( 'Diese Schutzmaßnahmen helfen dabei Spam-Blogs und Registrierungen zu minimieren.', 'ust' ) ?></span></h3>

	<div class="inside">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'Blog-Anmeldungen pro Tag begrenzen', 'ust' ) ?></th>
				<td><select name="ust[num_signups]">
						<?php
						for ( $counter = 1; $counter <= 250; $counter += 1 ) {
							echo '<option value="' . $counter . '"' . ( $ust_settings['num_signups'] == $counter ? ' selected="selected"' : '' ) . '>' . $counter . '</option>' . "\n";
						}
						echo '<option value=""' . ( $ust_settings['num_signups'] == '' ? ' selected="selected"' : '' ) . '>' . __( 'Unbegrenzt', 'ust' ) . '</option>' . "\n";
						?>
					</select>
					<br/><em><?php _e( 'Splog-Bots und -Benutzer registrieren oft in kurzer Zeit eine große Anzahl von Blogs. Diese Einstellung begrenzt die Anzahl der Blog-Anmeldungen pro 24 Stunden pro IP, was die Splogs, mit denen Du umgehen musst, drastisch reduzieren kann, wenn sie an anderen Filtern (menschlichen Sploggern) vorbeikommen. Denke daran, dass eine IP nicht unbedingt an einen einzelnen Benutzer gebunden ist. Zum Beispiel können sich Mitarbeiter hinter einer Firmen-Firewall eine einzelne IP teilen.', 'ust' ); ?></em>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Splogger-IPs auf die schwarze Liste setzen', 'ust' ) ?></th>
				<td><select name="ust[ip_blocking]">
						<?php
						echo '<option value="0"' . ( $ust_settings['ip_blocking'] == '' ? ' selected="selected"' : '' ) . '>' . __( 'Nie blockieren', 'ust' ) . '</option>' . "\n";
						for ( $counter = 1; $counter <= 250; $counter += 1 ) {
							echo '<option value="' . $counter . '"' . ( $ust_settings['ip_blocking'] == $counter ? ' selected="selected"' : '' ) . '>' . $counter . '</option>' . "\n";
						}
						?>
					</select>
					<br/><em><?php _e( 'Diese Einstellung blockiert Anmeldungen von IPs, die mit Blog-Anmeldungen verknüpft sind, die Du als Spam markiert hast. Eine strikte Einstellung von "1" ist normalerweise in Ordnung, es sei denn, Du möchtest die Prüfung bei falscher Spam-Markierung abschwächen. Denke daran, dass eine IP nicht unbedingt an einen einzelnen Benutzer gebunden ist. Zum Beispiel können sich Mitarbeiter hinter einer Firmen-Firewall eine einzelne IP teilen.', 'ust' ); ?></em>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'wp-signup.php umbenennen', 'ust' ) ?>
					<br/><em>
						<small><?php _e( '(Nicht Buddypress-kompatibel)', 'ust' ) ?></small>
					</em>
				</th>
				<td>
					<label for="ust_signup"><input type="checkbox" name="ust_signup"
					                               id="ust_signup"<?php echo ( $ust_signup['active'] ) ? ' checked="checked"' : ''; ?> /> <?php _e( 'Move wp-signup.php', 'ust' ) ?>
					</label>
					<br/><?php _e( 'Aktuelle Anmelde-URL:', 'ust' ) ?> <strong><a target="_blank"
					                                                            href="<?php ust_wpsignup_url(); ?>"><?php ust_wpsignup_url(); ?></a></strong>
					<br/><em><?php _e( "Wenn Du diese Option aktivierst, wird das Formular wp-signup.php deaktiviert und die Anmelde-URL automatisch alle 24 Stunden geändert. Es sieht etwa so aus wie <strong>http://$domain/signup-XXX/</strong>. Um dies zu verwenden, musst Du möglicherweise einige geringfügige Änderungen an den Vorlagendateien Deines Hauptthemes vornehmen. Ersetze alle hartcodierten Links zu wp-signup.php durch diese Funktion: <strong>&lt;?php ust_wpsignup_url(); ?&gt;</strong> Im Inhalt eines Beitrags oder einer Seite kannst Du den Shortcode <strong>[ust_wpsignup_url]</strong> einfügen, normalerweise in die href eines Links.", 'ust' ); ?></em>
				</td>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Blog-Benutzer Spam/Unspam', 'ust' ) ?></th>
				<td>
					<select name="ust[spam_blog_users]">
						<?php
						echo '<option value="1"' . ( isset( $ust_settings['spam_blog_users']) && $ust_settings['spam_blog_users'] == 1 ? ' selected="selected"' : '' ) . '>' . __( 'Ja', 'ust' ) . '</option>' . "\n";
						echo '<option value="0"' . ( ! isset( $ust_settings['spam_blog_users'] ) || $ust_settings['spam_blog_users'] != 1 ? ' selected="selected"' : '' ) . '>' . __( 'Nein', 'ust' ) . '</option>' . "\n";
						?>
					</select><br/><em><?php _e( "Aktiviere diese Option, um alle Benutzer eines Blogs zu spammen/zu entspammen, wenn das Blog gespammt/gespamt wurde. Kein Spam an Super Admins.", 'ust' ); ?></em>
				</td>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Schaltfläche in der Admin-Leiste ausblenden', 'ust' ); ?></th>
				<td><label><input type="checkbox" name="ust[hide_adminbar]"
				                  value="1"<?php checked( $ust_settings['hide_adminbar'] ); ?> />
						<?php _e( 'Entferne die Menüschaltfläche Anti-Splog-Aktionen aus der Admin-Leiste', 'ust' ); ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Warteschlangen-Anzeigeeinstellungen', 'ust' ) ?></th>
				<td>
					<?php _e( 'Bilder aus Post-Vorschauen entfernen:', 'ust' ) ?>
					<select name="ust[strip]">
						<?php
						echo '<option value="1"' . ( $ust_settings['strip'] == 1 ? ' selected="selected"' : '' ) . '>' . __( 'Ja', 'ust' ) . '</option>' . "\n";
						echo '<option value="0"' . ( $ust_settings['strip'] == 0 ? ' selected="selected"' : '' ) . '>' . __( 'Nein', 'ust' ) . '</option>' . "\n";
						?>
					</select><br/>
					<?php _e( 'Blogs pro Seite:', 'ust' ) ?>
					<select name="ust[paged_blogs]">
						<?php
						for ( $counter = 5; $counter <= 100; $counter += 5 ) {
							echo '<option value="' . $counter . '"' . ( $ust_settings['paged_blogs'] == $counter ? ' selected="selected"' : '' ) . '>' . $counter . '</option>' . "\n";
						}
						?>
					</select><br/>
					<?php _e( 'Post-Vorschauen pro Blog:', 'ust' ) ?>
					<select name="ust[paged_posts]">
						<?php
						for ( $counter = 1; $counter <= 20; $counter += 1 ) {
							echo '<option value="' . $counter . '"' . ( $ust_settings['paged_posts'] == $counter ? ' selected="selected"' : '' ) . '>' . $counter . '</option>' . "\n";
						}
						?>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Spam-Keyword-Suche', 'ust' ) ?></th>
				<td>
					<em><?php _e( 'Gib ein Wort oder einen Satz pro Zeile ein. Bei Schlüsselwörtern wird die Groß-/Kleinschreibung nicht beachtet und sie können mit jedem Teil eines Wortes übereinstimmen. Beispiel: "Ugg" würde "s<strong>ugg</strong>estion" entsprechen.', 'ust' ); ?></em><br/>
					<?php if ( ! class_exists( 'postindexermodel' ) ) { ?>
						<p class="error"><?php _e( 'Du musst das <a target="_blank" href="https://n3rds.work/piestingtal_source/multisite-beitragsindex-plugin/">Multisite Beitragsindex</a>-Plugin installieren, um die Schlüsselwortkennzeichnung zu aktivieren.', 'ust' ); ?></p>
						<textarea name="ust[keywords]" style="width:200px" rows="4"
						          disabled="disabled"><?php echo stripslashes( implode( "\n", (array) $ust_settings['keywords'] ) ); ?></textarea>
					<?php } else { ?>
						<textarea name="ust[keywords]" style="width:200px"
						          rows="4"><?php echo stripslashes( implode( "\n", (array) $ust_settings['keywords'] ) ); ?></textarea>
					<?php } ?>
					<br/><strong><em><?php _e( 'Diese Funktion wurde entwickelt, um in Verbindung mit unserem Multisite Beitragsindex-Plugin zu arbeiten, um Dir zu helfen, alte und inaktive Splogs zu finden. Blogs, die diese Keywords in Posts enthalten, werden vorübergehend markiert und der Warteschlange für potenzielle Splogs hinzugefügt. Schlüsselwörter sollten hier nur vorübergehend hinzugefügt werden, während Du nach Splogs suchst. VORSICHT: Gib nicht mehr als ein paar (2-4) Schlüsselwörter gleichzeitig ein, da dies die Seite "Verdächtige Blogs" je nach Anzahl der Beiträge auf der gesamten Webseite und der Servergeschwindigkeit verlangsamen oder eine Zeitüberschreitung verursachen kann.', 'ust' ); ?></em></strong>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Zusätzlicher Anmeldeschutz', 'ust' ) ?></th>
				<td>
					<select name="ust[signup_protect]" id="ust_signup_protect">
						<option value="none" <?php selected( $ust_settings['signup_protect'], 'none' ); ?>><?php _e( 'Keine', 'ust' ) ?></option>
                        <option value="recaptcha" <?php selected( $ust_settings['signup_protect'], 'recaptcha' ); ?>><?php _e( 'reCAPTCHA - Erweitertes Captcha', 'ust' ) ?></option>
						<option value="questions" <?php selected( $ust_settings['signup_protect'], 'questions' ); ?>><?php _e( 'Vom Administrator definierte Fragen', 'ust' ) ?></option>
					</select>
					<br/><em><?php _e( 'Diese Optionen wurden entwickelt, um automatisierte Spam-Bot-Anmeldungen zu verhindern, haben also eine begrenzte Wirkung beim Stoppen menschlicher Splogger. Sei bei der Verwendung dieser Optionen vorsichtig, da es wichtig ist, ein Gleichgewicht zwischen dem Stoppen von Bots und dem Vermeiden von Belästigungen Deiner Benutzer zu finden.', 'ust' ); ?></em>
				</td>
				</td>
			</tr>

			<?php do_action( 'ust_settings' ); ?>
		</table>
	</div>
</div>

<div class="postbox">
	<h3 class='hndle'><span><?php _e( 'reCAPTCHA-Optionen', 'ust' ) ?></span></h3>

	<div class="inside">
		<p><?php _e( 'reCAPTCHA fordert jemanden heraus, zu beweisen, dass es sich um einen Menschen handelt. Dies bestätigt, dass es sich nicht um Spambots handelt. So erhältst Du weniger Spam! Weitere Informationen findest Du auf der <a href="https://www.google.com/recaptcha/intro/">reCAPTCHA-Webseite</a>.', 'ust' ) ?></p>

		<p><?php _e( '<strong>HINWEIS</strong>: Auch wenn Du reCAPTCHA im Anmeldeformular nicht verwendest, solltest Du trotzdem einen API-Schlüssel einrichten, um Spam aus den Splog-Überprüfungsformularen zu verhindern.', 'ust' ) ?></p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'reCaptcha V2 Schlüssel', 'ust' ) ?>*</th>
				<td>
					<?php _e( 'reCAPTCHA benötigt für jede Domain einen API-Schlüssel, bestehend aus einem „Site“- und einem „Secret“-Schlüssel. Du kannst Dich für einen <a href="https://www.google.com/recaptcha/admin" target="_blank">kostenlosen reCAPTCHA-Schlüssel</a> anmelden.', 'ust' ) ?>
					<br/>

					<p class="re-keys">
						<!-- reCAPTCHA public key -->
						<label class="which-key"
						       for="recaptcha_pubkey"><?php _e( 'Webseiten-Schlüssel:&nbsp;&nbsp;', 'ust' ) ?></label>
						<input name="recaptcha[pubkey]" id="recaptcha_pubkey" size="40"
						       value="<?php echo stripslashes( $ust_recaptcha['pubkey'] ); ?>"/>
						<br/>
						<!-- reCAPTCHA private key -->
						<label class="which-key" for="recaptcha_privkey"><?php _e( 'Geheimer Schlüssel:', 'ust' ) ?></label>
						<input name="recaptcha[privkey]" id="recaptcha_privkey" size="40"
						       value="<?php echo stripslashes( $ust_recaptcha['privkey'] ); ?>"/>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Theme:', 'ust' ) ?></th>
				<td>
					<!-- The theme selection -->
					<div class="theme-select">
						<select name="recaptcha[theme]" id="recaptcha_theme">
							<option value="light" <?php if ( $ust_recaptcha['theme'] == 'light' ) {
								echo 'selected="selected"';
							} ?>>Hell
							</option>
							<option value="dark" <?php if ( $ust_recaptcha['theme'] == 'dark' ) {
								echo 'selected="selected"';
							} ?>>Dunkel
							</option>
						</select>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div>

<div class="postbox">
	<h3 class='hndle'><span><?php _e( 'Definierte Fragenoptionen', 'ust' ) ?></span></h3>

	<div class="inside">
		<p><?php _e( 'Zeigt eine zufällige Frage aus der Liste an und der Benutzer muss die richtige Antwort eingeben. Am besten erstelle einen großen Fragenpool mit Ein-Wort-Antworten. Bei den Antworten wird die Groß-/Kleinschreibung nicht beachtet.', 'ust' ) ?></p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'Fragen und Antworten', 'ust' ) ?></th>
				<td>
					<table>
						<tr>
							<td style="width:75%">
								<?php _e( 'Fragen (eine pro Zeile)', 'ust' ) ?>
								<textarea name="ust_qa[questions]" style="width:100%"
								          rows="10"><?php echo stripslashes( implode( "\n", $questions ) ); ?></textarea>
							</td>
							<td style="width:25%">
								<?php _e( 'Antworten (eine pro Zeile)', 'ust' ) ?>
								<textarea name="ust_qa[answers]" style="width:100%"
								          rows="10"><?php echo stripslashes( implode( "\n", $answers ) ); ?></textarea>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
</div>

<p class="submit">
	<input type="submit" name="Submit" value="<?php _e( 'Änderungen speichern', 'ust' ) ?>" class="button-primary"/>
</p>
</form>
</div>