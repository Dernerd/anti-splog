<?php
if ( ! current_user_can( 'manage_network_options' ) ) {
	wp_die( 'Du hast keine Berechtigungen für diese Seite' );
}

$patterns = get_site_option( 'ust_patterns' );
$error    = false;

//delete checked patterns
if ( isset( $_POST['allpattern_delete'] ) ) {

	if ( is_array( $_POST['patterns_checks'] ) ) {
		//loop through and delete
		foreach ( $_POST['patterns_checks'] as $del_code ) {
			unset( $patterns[ $del_code ] );
		}

		update_site_option( 'ust_patterns', $patterns );
		//display message confirmation
		echo '<div class="updated fade"><p>' . __( 'Muster erfolgreich gelöscht.', 'ust' ) . '</p></div>';
	}
}

//save or add pattern
if ( isset( $_POST['submit_settings'] ) ) {

	$error = false;

	if ( false === @preg_match( stripslashes( $_POST['regex'] ), 'thisisjustateststring' ) ) {
		$error = __( 'Bitte gib einen gültigen regulären PCRE-Ausdruck mit Trennzeichen ein.', 'ust' );
	}

	if ( ! $error ) {
		if ( isset( $_POST['pattern_id'] ) && isset( $patterns[ intval( $_POST['pattern_id'] ) ] ) ) {
			$id                        = intval( $_POST['pattern_id'] );
			$patterns[ $id ]['regex']  = stripslashes( trim( $_POST['regex'] ) );
			$patterns[ $id ]['desc']   = stripslashes( wp_filter_nohtml_kses( trim( $_POST['desc'] ) ) );
			$patterns[ $id ]['type']   = $_POST['type'];
			$patterns[ $id ]['action'] = $_POST['action'];
		} else {
			$patterns[] = array( 'regex'   => stripslashes( trim( $_POST['regex'] ) ),
			                     'desc'    => stripslashes( wp_filter_nohtml_kses( trim( $_POST['desc'] ) ) ),
			                     'type'    => $_POST['type'],
			                     'action'  => $_POST['action'],
			                     'matched' => 0
			);
		}
		update_site_option( 'ust_patterns', $patterns );
		unset( $_POST );
		unset( $_GET );
		$new_pattern_code = '';
		echo '<div class="updated fade"><p>' . __( 'Muster erfolgreich gespeichert.', 'ust' ) . '</p></div>';
	} else {
		echo '<div class="error"><p>' . $error . '</p></div>';
	}
}

//if editing a pattern
if ( isset( $_GET['id'] ) ) {
	$new_pattern_code = (int) $_GET['id'];
}

$apage = isset( $_GET['apage'] ) ? intval( $_GET['apage'] ) : 1;
$num   = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 20;

$pattern_list = get_site_option( 'ust_patterns' );
$total        = ( is_array( $pattern_list ) ) ? count( $pattern_list ) : 0;

if ( $total ) {
	$pattern_list = array_slice( $pattern_list, intval( ( $apage - 1 ) * $num ), intval( $num ) );
}

$pattern_navigation = paginate_links( array(
	'base'    => add_query_arg( 'apage', '%#%' ),
	'format'  => '',
	'total'   => ceil( $total / $num ),
	'current' => $apage
) );
$page_link          = ( $apage > 1 ) ? '&amp;apage=' . $apage : '';
?>
<div class="wrap">
<h2><?php _e( 'Anti-Splog-Musterabgleich', 'ust' ) ?></h2>

<form method="post" action="">

<p><?php _e( "Pattern Matching ist eine erweiterte Funktion, mit der Sie leistungsstarke benutzerdefinierte Regeln erstellen können, um Spam-Bots zu verhindern. In fast allen Fällen, in denen Du eine Reihe von Splogs erhältst, die von einem Bot erstellt wurden, ist es möglich, Muster in den ausgewählten Domains, Seiten-Titeln, E-Mails oder Benutzernamen zu erkennen. Du kannst dann hier reguläre Ausdrücke schreiben und sofort testen, um zukünftige Anmeldungen, die diesen Mustern entsprechen, zu blockieren.", 'ust' ) ?></p>

<div class="tablenav">
	<?php if ( $pattern_navigation ) {
		echo "<div class='tablenav-pages'>$pattern_navigation</div>";
	} ?>

	<div class="alignleft">
		<input type="submit" value="<?php _e( 'Löschen', 'ust' ) ?>" name="allpattern_delete"
		       class="button-secondary delete"/>
		<br class="clear"/>
	</div>
</div>

<br class="clear"/>

<?php
// define the columns to display, the syntax is 'internal name' => 'display name'
$posts_columns = array(
	'regex'   => __( 'Regulären Ausdruck', 'ust' ),
	'type'    => __( 'Prüfen', 'ust' ),
	'action'  => __( 'Aktionen', 'ust' ),
	'matched' => __( 'Abgestimmt', 'ust' ),
	'edit'    => __( 'Bearbeiten', 'ust' )
);
?>

<table width="100%" cellpadding="3" cellspacing="3" class="widefat">
	<thead>
	<tr>
		<th scope="col" class="check-column"><input type="checkbox"/></th>
		<?php foreach ( $posts_columns as $column_id => $column_display_name ) {
			$col_url = $column_display_name;
			?>
			<th scope="col"><?php echo $col_url ?></th>
		<?php } ?>
	</tr>
	</thead>
	<tbody id="the-list">
	<?php
	if ( is_array( $pattern_list ) && count( $pattern_list ) ) {
		$bgcolor = isset( $class ) ? $class : '';
		foreach ( $pattern_list as $pattern_code => $pattern ) {
			$class = ( isset( $class ) && 'alternate' == $class ) ? '' : 'alternate';

			echo '<tr class="' . $class . ' blog-row">
							<th scope="row" class="check-column">
							<input type="checkbox" name="patterns_checks[]"" value="' . $pattern_code . '" />
							</th>';

			foreach ( $posts_columns as $column_name => $column_display_name ) {
				switch ( $column_name ) {
					case 'regex':
						?>
						<th scope="row">
							<?php echo esc_attr( $pattern['regex'] ); ?>
							<br/>
							<small><?php echo esc_html( $pattern['desc'] ); ?></small>
						</th>
						<?php
						break;

					case 'type':
						?>
						<th scope="row">
							<?php
							if ( $pattern['type'] == 'title' ) {
								_e( 'Seitentitel', 'ust' );
							} else if ( $pattern['type'] == 'username' ) {
								_e( 'Nutzername', 'ust' );
							} else if ( $pattern['type'] == 'email' ) {
								_e( 'Email', 'ust' );
							} else {
								_e( 'Seiten-Domain', 'ust' );
							}
							?>
						</th>
						<?php
						break;

					case 'action':
						?>
						<th scope="row">
							<?php echo ( isset( $pattern['action'] ) && $pattern['action'] == 'block' ) ? __( 'Anmeldung blockieren', 'ust' ) : __( 'Als Splog markieren', 'ust' ); ?>
						</th>
						<?php
						break;

					case 'matched':
						?>
						<th scope="row">
							<?php echo isset( $pattern['matched'] ) ? number_format_i18n( intval( $pattern['matched'] ) ) : 0; ?>
						</th>
						<?php
						break;

					case 'edit':
						?>
						<th scope="row">
							<a href="admin.php?page=ust-patterns<?php echo $page_link; ?>&amp;id=<?php echo $pattern_code; ?>#add_pattern"><?php _e( 'Bearbeiten', 'ust' ) ?>&raquo;</a>
						</th>
						<?php
						break;

				}
			}
			?>
			</tr>
		<?php
		}
	} else {
		?>
		<tr style='background-color: <?php echo $bgcolor; ?>'>
			<td colspan="9"><?php _e( 'Noch keine Muster.', 'ust' ) ?></td>
		</tr>
	<?php
	} // end if patterns
	?>

	</tbody>
	<tfoot>
	<tr>
		<th scope="col" class="check-column"><input type="checkbox"/></th>
		<?php foreach ( $posts_columns as $column_id => $column_display_name ) {
			$col_url = $column_display_name;
			?>
			<th scope="col"><?php echo $col_url ?></th>
		<?php } ?>
	</tr>
	</tfoot>
</table>

<div class="tablenav">
	<?php if ( $pattern_navigation ) {
		echo "<div class='tablenav-pages'>$pattern_navigation</div>";
	} ?>
</div>

<div id="poststuff" class="metabox-holder">

	<div class="postbox">
		<h3 class='hndle'><span>
	<?php
	if ( isset( $_GET['id'] ) || $error ) {
		_e( 'Muster bearbeiten', 'ust' );
	} else {
		_e( 'Muster hinzufügen', 'ust' );
	}
	?></span></h3>

		<div class="inside">
			<?php
			//setup defaults
			if ( isset( $_GET['id'] ) && isset( $patterns[ intval( $_GET['id'] ) ] ) ) {
				$id     = intval( $_GET['id'] );
				$regex  = $patterns[ $id ]['regex'];
				$desc   = $patterns[ $id ]['desc'];
				$type   = $patterns[ $id ]['type'];
				$action = $patterns[ $id ]['action'];
				echo '<input type="hidden" name="pattern_id" value="' . $id . '" />';
			} else {
				$regex  = isset( $_POST['regex'] ) ? stripslashes( $_POST['regex'] ) : '';
				$desc   = isset( $_POST['desc'] ) ? stripslashes( $_POST['desc'] ) : '';
				$type   = isset( $_POST['type'] ) ? stripslashes( $_POST['type'] ) : 'domain';
				$action = isset( $_POST['action'] ) ? stripslashes( $_POST['action'] ) : 'splog';
			}
			?>
			<table id="add_pattern">
				<thead>
				<tr>
					<th>
						<?php _e( 'Regulären Ausdruck', 'ust' ) ?>
					</th>
					<th>
						<?php _e( 'Beschreibung', 'ust' ) ?>
					</th>
					<th>
						<?php _e( 'Prüfen', 'ust' ) ?>
					</th>
					<th>
						<?php _e( 'Aktionen', 'ust' ) ?>
					</th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td>
						<input value="<?php echo esc_attr( $regex ); ?>" name="regex" id="regex" type="text" size="50"/>
					</td>
					<td>
						<input value="<?php echo esc_attr( $desc ); ?>" name="desc" type="text" size="75"/>
					</td>
					<td>
						<select name="type" id="type">
							<option
								value="domain"<?php selected( $type, 'domain' ) ?>><?php _e( 'Seiten-Domain', 'ust' ); ?></option>
							<option
								value="title"<?php selected( $type, 'title' ) ?>><?php _e( 'Seitentitel', 'ust' ); ?></option>
							<option
								value="username"<?php selected( $type, 'username' ) ?>><?php _e( 'Nutzername', 'ust' ); ?></option>
							<option
								value="email"<?php selected( $type, 'email' ) ?>><?php _e( 'Email', 'ust' ); ?></option>
						</select>
					</td>
					<td>
						<select name="action">
							<option
								value="splog"<?php selected( $action, 'splog' ) ?>><?php _e( 'Als Splog markieren', 'ust' ); ?></option>
							<option
								value="block"<?php selected( $action, 'block' ) ?>><?php _e( 'Anmeldung blockieren', 'ust' ); ?></option>
						</select>
					</td>
					<td>
						<input type="button" id="ust-test-regex" class="button-secondary"
						       value="<?php _e( 'Test &raquo;', 'ust' ) ?>"/>
					</td>
				</tr>
				</tbody>
			</table>
			<span
				class="description"><?php _e( 'Reguläre Ausdrücke müssen in einem <a href="http://php.net/manual/en/book.pcre.php" target="_blank">gültigen PCRE-Format mit Trennzeichen</a> vorliegen. Regex kann sehr kompliziert sein, daher wird empfohlen, ein Online-Tool wie <a href="http://gskinner.com/RegExr/" target="_blank">RegExr</a> zu verwenden, um sie zu erstellen und zu testen . Es wird in den meisten Fällen auch empfohlen, die Aktion "Als Splog markieren" zu verwenden, anstatt zu blockieren. Dadurch wird es für Spammer viel schwieriger zu lernen, wie sie Deine Musterregeln umgehen können.', 'ust' ) ?></span>

			<p class="submit">
				<input type="submit" name="submit_settings" class="button-primary"
				       value="<?php _e( 'Muster speichern', 'ust' ) ?>"/>
			</p>
		</div>
	</div>

	<div class="postbox" id="test-results" style="display:none;">
		<h3 class='hndle'><span><?php _e( 'Testergebnisse:', 'ust' ); ?></span> <span class="description"></span></h3>

		<div class="inside">
			<div id="results"></div>
			<p style="text-align:center;"><img
					src="<?php echo admin_url( 'images/loading.gif' ); ?>"/> <?php _e( 'Wird geladen...', 'ust' ); ?></p>
		</div>
	</div>

</div>
</form>
</div>