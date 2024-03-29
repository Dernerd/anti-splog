<?php
global $wpdb, $current_user, $current_site, $ust_admin_url;

if ( ! current_user_can( 'manage_sites' ) ) {
	wp_die( __( 'Netter Versuch...', 'ust' ) );  //If accessed properly, this message doesn't appear.
}

//process any actions and messages
if ( isset( $_GET['spam_user'] ) ) {
	//spam a user and all blogs they are associated with
	//don't spam site admin
	$user_info = get_userdata( (int) $_GET['spam_user'] );
	if ( ! is_super_admin( $user_info->user_login ) ) {
		$blogs = get_blogs_of_user( (int) $_GET['spam_user'], true );
		foreach ( (array) $blogs as $key => $details ) {
			if ( $details->userblog_id == $current_site->blog_id ) {
				continue;
			} // main blog not a spam !
			update_blog_status( $details->userblog_id, "spam", '1' );
			set_time_limit( 60 );
		}
		update_user_status( (int) $_GET['spam_user'], "spam", '1' );
		$_GET['updatedmsg'] = sprintf( __( '%s Blog(s) für Benutzer gespammt!', 'ust' ), count( $blogs ) );
	}

} else if ( isset( $_GET['spam_ip'] ) ) {
	//spam all blogs created or modified with the IP address
	$spam_ip = addslashes( $_GET['spam_ip'] );
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
	$_GET['updatedmsg'] = sprintf( __( '%s Blog(s) für %s gespammt!', 'ust' ), count( $blogs ), $spam_ip );

} else if ( isset( $_GET['ignore_blog'] ) ) {
	//ignore a single blog so it doesn't show up on the possible spam list
	ust_blog_ignore( (int) $_GET['id'] );

} else if ( isset( $_GET['unignore_blog'] ) ) {
	//unignore a single blog so it can show up on the possible spam list
	ust_blog_unignore( (int) $_GET['id'] );

} else if ( isset( $_GET['spam_blog'] ) ) {
	//spam a single blog
	update_blog_status( (int) $_GET['id'], "spam", '1' );

} else if ( isset( $_GET['unspam_blog'] ) ) {

	update_blog_status( (int) $_GET['id'], "spam", '0' );
	ust_blog_ignore( (int) $_GET['id'], false );

} else if ( isset( $_GET['action'] ) && $_GET['action'] == 'all_notspam' ) {

	$_GET['updatedmsg'] = __( 'Als kein Spam markierte Blogs.', 'ust' );

} else if ( isset( $_GET['action'] ) && $_GET['action'] == 'allblogs' ) {

	foreach ( (array) $_POST['allblogs'] as $key => $val ) {
		if ( $val != '0' && $val != $current_site->blog_id ) {
			if ( isset( $_POST['allblog_ignore'] ) ) {
				$_GET['updatedmsg'] = __( 'Ausgewählte Blogs ignoriert.', 'ust' );
				ust_blog_ignore( $val );
				set_time_limit( 60 );
			} else if ( isset( $_POST['allblog_unignore'] ) ) {
				$_GET['updatedmsg'] = __( 'Ausgewählte Blogs nicht ignoriert.', 'ust' );
				ust_blog_unignore( $val );
				set_time_limit( 60 );
			} else if ( isset( $_POST['allblog_spam'] ) ) {
				$_GET['updatedmsg'] = __( 'Als Spam markierte Blogs.', 'ust' );
				update_blog_status( $val, "spam", '1' );
				set_time_limit( 60 );
			}
		}
	}

} else if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' ) {

	$_GET['updatedmsg'] = __( 'Blog gelöscht!', 'ust' );

}

if ( isset( $_GET['updated'] ) && $_GET['updatedmsg'] ) {
	?>
	<div id="message" class="updated fade"><p><?php echo urldecode( $_GET['updatedmsg'] ); ?></p></div><?php
}
?>

<div class="wrap">
	<h2><?php _e( 'Anti-Splog', 'ust' ) ?></h2>
	<h3 class="nav-tab-wrapper">
		<?php
		$tab = ( ! empty( $_GET['tab'] ) ) ? $_GET['tab'] : 'queue';

		$tabs    = array(
			'splogs'  => __( 'Letzte Splogs', 'ust' ),
			'ignored' => __( 'Ignorierte Blogs', 'ust' )
		);
		$tabhtml = array();

		// If someone wants to remove or add a tab
		$tabs = apply_filters( 'ust_tabs', $tabs );

		$class     = ( 'queue' == $tab ) ? ' nav-tab-active' : '';
		$tabhtml[] = '	<a href="' . $ust_admin_url . '" class="nav-tab' . $class . '">' . __( 'Verdächtige Blogs', 'ust' ) . '</a>';

		foreach ( $tabs as $stub => $title ) {
			$class     = ( $stub == $tab ) ? ' nav-tab-active' : '';
			$tabhtml[] = '	<a href="' . $ust_admin_url . '&amp;tab=' . $stub . '" class="nav-tab' . $class . '">' . $title . '</a>';
		}

		echo implode( "\n", $tabhtml );
		?>
	</h3>
	<div class="clear"></div>
<?php
switch ( $tab ) {
	//---------------------------------------------------//
	case "queue":

		?><h3><?php _e( 'Verdächtige Blogs', 'ust' ) ?></h3><?php

		$ust_settings = get_site_option( "ust_settings" );
		
		_e( '<p>Dies ist die Moderationswarteschlange für verdächtige Blogs. Wenn Du sicher bist, dass es sich bei einem Blog um Spam handelt, markiere ihn als Spam. Wenn es sich definitiv um einen gültigen Blog handelt, solltest Du ihn "ignorieren". Blogs lasse am besten so lange drin, bis Du sicher bist, ob es sich um Spam handelt oder nicht, da das System aus beiden Aktionen lernt.</p>', 'ust' );

		$apage     = isset( $_GET['apage'] ) ? intval( $_GET['apage'] ) : 1;
		$num       = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : $ust_settings['paged_blogs'];
		$page_link = ( $apage > 1 ) ? '&amp;apage=' . $apage : '';
		//get sort
		if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'lastupdated' ) {
			$order_by = 'b.last_updated DESC';
		} else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'registered' ) {
			$order_by = 'b.registered DESC';
		} else {
			$order_by = 'u.certainty DESC, b.last_updated DESC';
		}

		$blogname_columns = is_subdomain_install() ? __( 'Domain' ) : __( 'Pfad' );

		if ( is_array( $ust_settings['keywords'] ) && count( $ust_settings['keywords'] ) ) {
			foreach ( $ust_settings['keywords'] as $word ) {
				$keywords[] = "`post_content` LIKE '%" . addslashes( trim( $word ) ) . "%'";
			}

			//$keyword_string = implode($keywords, ' OR ' );
			$keyword_string = implode(' OR ', $keywords );
		}

		//if the Post Indexer plugin is installed and keywords are set
		if ( class_exists( 'postindexermodel' ) && $keyword_string ) {

			$query = "SELECT *
								FROM {$wpdb->blogs} b
									JOIN {$wpdb->registration_log} r ON b.blog_id = r.blog_id
									JOIN {$wpdb->base_prefix}ust u ON b.blog_id = u.blog_id
									LEFT JOIN (SELECT `BLOG_ID` as bid, COUNT( `ID` ) AS total FROM `{$wpdb->base_prefix}network_posts` WHERE $keyword_string GROUP BY BLOG_ID) as s ON b.blog_id = s.bid
								WHERE b.site_id = '{$wpdb->siteid}'
									AND b.spam = '0' AND b.deleted = '0' AND b.archived = '0'
									AND u.`ignore` = '0' AND b.blog_id != '{$current_site->blog_id}'
									AND (u.certainty > 0 OR s.total > 0)
								ORDER BY s.total DESC, u.certainty DESC, b.last_updated DESC";

			$total = $wpdb->get_var( "SELECT COUNT(b.blog_id)
																FROM {$wpdb->blogs} b
																	JOIN {$wpdb->registration_log} r ON b.blog_id = r.blog_id
																	JOIN {$wpdb->base_prefix}ust u ON b.blog_id = u.blog_id
																	LEFT JOIN (SELECT `BLOG_ID`, COUNT( `ID` ) AS total FROM `{$wpdb->base_prefix}network_posts` WHERE $keyword_string GROUP BY BLOG_ID) as s ON b.blog_id = s.BLOG_ID
																WHERE b.site_id = '{$wpdb->siteid}'
																	AND b.spam = '0' AND b.deleted = '0' AND b.archived = '0'
																	AND u.`ignore` = '0' AND b.blog_id != '{$current_site->blog_id}'
																	AND (u.certainty > 0 OR s.total > 0)" );

			$posts_columns = array(
				'id'          => __( 'ID', 'ust' ),
				'blogname'    => $blogname_columns,
				'ips'         => __( 'IPs', 'ust' ),
				'users'       => __( 'Blog-Benutzer', 'ust' ),
				'keywords'    => __( 'Schlüsselwörter', 'ust' ),
				'certainty'   => __( 'Splog Gewissheit', 'ust' ),
				'lastupdated' => __( 'Letzte Aktualisierung' ),
				'registered'  => __( 'Regisitriert' ),
				'posts'       => __( 'Kürzliche Posts', 'ust' )
			);

		} else { //no post indexer

			$query = "SELECT *
								FROM {$wpdb->blogs} b
									JOIN {$wpdb->registration_log} r ON b.blog_id = r.blog_id
									JOIN {$wpdb->base_prefix}ust u ON b.blog_id = u.blog_id
								WHERE b.site_id = '{$wpdb->siteid}'
									AND b.spam = '0' AND b.deleted = '0' AND b.archived = '0'
									AND u.ignore = '0' AND b.blog_id != '{$current_site->blog_id}'
									AND u.certainty > 0
								ORDER BY $order_by";

			$total = $wpdb->get_var( "SELECT COUNT(b.blog_id)
																FROM {$wpdb->blogs} b
																	JOIN {$wpdb->registration_log} r ON b.blog_id = r.blog_id
																	JOIN {$wpdb->base_prefix}ust u ON b.blog_id = u.blog_id
																WHERE b.site_id = '{$wpdb->siteid}'
																	AND b.spam = '0' AND b.deleted = '0' AND b.archived = '0'
																	AND u.ignore = '0' AND b.blog_id != '{$current_site->blog_id}'
																	AND u.certainty > 0" );

			$posts_columns = array(
				'id'          => __( 'ID', 'ust' ),
				'blogname'    => $blogname_columns,
				'ips'         => __( 'IPs', 'ust' ),
				'users'       => __( 'Blog-Benutzer', 'ust' ),
				'certainty'   => __( 'Splog Gewissheit', 'ust' ),
				'lastupdated' => '<a href="' . $ust_admin_url . $page_link . '&orderby=lastupdated">' . __( 'Letzte Aktualisierung' ) . '</a>',
				'registered'  => '<a href="' . $ust_admin_url . $page_link . '&orderby=registered">' . __( 'Registriert' ) . '</a>',
				'posts'       => __( 'Kürzliche Posts', 'ust' )
			);
		}

		$query .= " LIMIT " . intval( ( $apage - 1 ) * $num ) . ", " . intval( $num );

		$blog_list = $wpdb->get_results( $query, ARRAY_A );

		$blog_navigation = paginate_links( array(
			'base'    => add_query_arg( 'apage', '%#%' ),
			'format'  => '',
			'total'   => ceil( $total / $num ),
			'current' => $apage
		) );
		if ( isset( $_GET['orderby'] ) ) {
			$page_link = $page_link . '&orderby=' . urlencode( $_GET['orderby'] );
		}
		?>

		<form id="form-blog-list" action="<?php echo $ust_admin_url . $page_link; ?>&amp;action=allblogs&amp;updated=1"
		      method="post">

		<div class="tablenav">
			<?php if ( $blog_navigation ) {
				echo "<div class='tablenav-pages'>$blog_navigation</div>";
			} ?>

			<div class="alignleft">
				<input type="submit" value="<?php _e( 'Ignorieren', 'ust' ) ?>" name="allblog_ignore"
				       class="button-secondary allblog_ignore"/>
				<input type="submit" value="<?php _e( 'Als Spam markieren' ) ?>" name="allblog_spam"
				       class="button-secondary allblog_spam"/>
				<br class="clear"/>
			</div>
		</div>

		<br class="clear"/>

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
			if ( $blog_list ) {
				$bgcolor    = $class = '';
				$preview_id = 0;
				foreach ( $blog_list as $blog ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate';

					echo '<tr class="' . $class . ' blog-row" id="bid-' . $blog['blog_id'] . '">';

					$blogname = is_subdomain_install() ? str_replace( '.' . $current_site->domain, '', $blog['domain'] ) : $blog['path'];
					foreach ( $posts_columns as $column_name => $column_display_name ) {
						switch ( $column_name ) {
							case 'id':
								?>
								<th scope="row" class="check-column">
									<input type='checkbox' id='blog_<?php echo $blog['blog_id'] ?>' name='allblogs[]'
									       value='<?php echo $blog['blog_id'] ?>'/>
								</th>
								<th scope="row">
									<?php echo $blog['blog_id']; ?>
								</th>
								<?php
								break;

							case 'blogname':
								?>
								<td valign="top">
									<a title="<?php _e( 'Vorschau', 'ust' ); ?>"
									   href="http://<?php echo $blog['domain'] . $blog['path']; ?>?KeepThis=true&TB_iframe=true&height=450&width=900"
									   class="thickbox"><?php echo $blogname; ?></a>
									<br/>

									<div class="row-actions">
										<?php echo '<a class="delete ust_ignore" href="' . $ust_admin_url . $page_link . '&amp;ignore_blog=1&amp;id=' . $blog['blog_id'] . '&amp;updated=1&amp;updatedmsg=' . urlencode( __( 'Blog ignoriert!', 'ust' ) ) . '">' . __( 'Ignorieren', 'ust' ) . '</a>'; ?>
										|
										<?php echo '<a class="delete ust_spam" href="' . $ust_admin_url . $page_link . '&amp;spam_blog=1&amp;id=' . $blog['blog_id'] . '&amp;updated=1&amp;updatedmsg=' . urlencode( __( 'Blog als Spam markiert!', 'ust' ) ) . '">' . __( 'Spam' ) . '</a>'; ?>
									</div>
								</td>
								<?php
								break;

							case 'ips':
								$result     = $wpdb->get_row( "SELECT user_login, spam FROM " . $wpdb->base_prefix . "users WHERE ID = '" . $blog['last_user_id'] . "'" );
								$user_login = $result->user_login;
								$user_spam  = $result->spam;
								?>
								<td valign="top">
									Registered: <a title="<?php _e( 'Suche nach IP', 'ust' ) ?>"
									               href="sites.php?action=blogs&amp;s=<?php echo $blog['IP'] ?>&blog_ip=1"
									               class="edit"><?php echo $blog['IP']; ?></a>
									<small class="row-actions"><a class="ust_spamip"
									                              title="<?php _e( 'Spam alle Blogs, die an diese IP gebunden sind', 'ust' ) ?>"
									                              href="<?php echo $ust_admin_url . $page_link; ?>&updated=1&id=<?php echo $blog['blog_id']; ?>&spam_ip=<?php echo $blog['IP']; ?>"><?php _e( 'Spam', 'ust' ) ?></a>
									</small>
									<br/>
									<?php if ( $blog['last_user_id'] ) : ?>
										<?php $spm_class = ( $user_spam ) ? ' style="color:red;"' : ''; ?>
										Last User: <a<?php echo $spm_class ?>
											title="<?php _e( 'Suche nach Benutzerblogs', 'ust' ) ?>"
											href="users.php?s=<?php echo $user_login; ?>"
											class="edit"><?php echo $user_login; ?></a>
										<?php if ( $user_spam == 0 ) : ?>
											<small class="row-actions"><a class="ust_spamuser"
											                              title="<?php _e( 'Spam alle Blogs, die mit diesem Benutzer verknüpft sind', 'ust' ) ?>"
											                              href="<?php echo $ust_admin_url . $page_link; ?>&updated=1&spam_user=<?php echo $blog['last_user_id']; ?>"><?php _e( 'Spam', 'ust' ) ?></a>
											</small><?php endif; ?>
										<br/>
									<?php endif; ?>
									<?php if ( $blog['last_ip'] ) : ?>
										Last IP: <a title="<?php _e( 'Suche nach IP', 'ust' ) ?>"
										            href="sites.php?action=blogs&amp;s=<?php echo $blog['last_ip']; ?>&blog_ip=1"
										            class="edit"><?php echo $blog['last_ip']; ?></a>
										<small class="row-actions"><a class="ust_spamip"
										                              title="<?php _e( 'Spam alle Blogs, die an diese IP gebunden sind', 'ust' ) ?>"
										                              href="<?php echo $ust_admin_url . $page_link; ?>&updated=1&id=<?php echo $blog['blog_id']; ?>&spam_ip=<?php echo $blog['last_ip']; ?>"><?php _e( 'Spam', 'ust' ) ?></a>
										</small>
									<?php endif; ?>
								</td>
								<?php
								break;

							case 'users':
								?>
								<td valign="top">
									<?php
									$blog_prefix = $wpdb->get_blog_prefix( $blog['blog_id'] );
									$blogusers   = $wpdb->get_results( "SELECT user_id, user_id AS ID, user_login, display_name, user_email, spam, meta_value FROM $wpdb->users, $wpdb->usermeta WHERE {$wpdb->users}.ID = {$wpdb->usermeta}.user_id AND meta_key = '{$blog_prefix}capabilities' ORDER BY {$wpdb->usermeta}.user_id" );
									if ( is_array( $blogusers ) ) {
										$blogusers_warning = '';
										if ( count( $blogusers ) > 5 ) {
											$blogusers         = array_slice( $blogusers, 0, 5 );
											$blogusers_warning = __( 'Es werden nur die ersten 5 Benutzer angezeigt.' ) . ' <a href="http://' . $blog['domain'] . $blog['path'] . 'wp-admin/users.php">' . __( 'Mehr' ) . '</a>';
										}
										foreach ( $blogusers as $key => $val ) {
											$spm_class = ( $val->spam ) ? ' style="color:red;"' : '';
											echo '<a' . $spm_class . ' title="Edit User: ' . $val->display_name . ' (' . $val->user_email . ')" href="user-edit.php?user_id=' . $val->user_id . '">' . $val->user_login . '</a> ';
											echo '<small class="row-actions"><a title="' . __( 'Alle Blogs des Benutzers', 'ust' ) . '" href="users.php?s=' . $val->user_login . '">' . __( 'Blogs', 'ust' ) . '</a>';
											if ( $val->spam == 0 ) {
												echo ' | <a class="ust_spamuser" title="' . __( 'Spam alle Blogs, die mit diesem Benutzer verknüpft sind', 'ust' ) . '" href="' . $ust_admin_url . $page_link . '&updated=1&spam_user=' . $val->user_id . '">' . __( 'Spam', 'ust' ) . '</a></small>';
											}
											echo '<br />';
										}
										if ( $blogusers_warning != '' ) {
											echo '<strong>' . $blogusers_warning . '</strong><br />';
										}
									}
									?>
								</td>
								<?php
								break;

							case 'certainty':
								?>
								<td valign="top">
									<?php echo $blog['certainty']; ?>%
								</td>
								<?php
								break;

							case 'keywords':  //only called when post indexer is installed
								?>
								<td valign="top">
									<?php echo ( $blog['total'] ) ? $blog['total'] : 0; ?>
								</td>
								<?php
								break;

							case 'lastupdated':
								?>
								<td valign="top">
									<?php echo ( $blog['last_updated'] == '0000-00-00 00:00:00' ) ? __( "Niemals" ) : mysql2date( __( 'Y-m-d \<\b\r \/\> g:i:s a' ), $blog['last_updated'] ); ?>
								</td>
								<?php
								break;

							case 'registered':
								?>
								<td valign="top">
									<?php echo mysql2date( __( 'Y-m-d \<\b\r \/\> g:i:s a' ), $blog['registered'] ); ?>
								</td>
								<?php
								break;

							case 'posts':
								$query = "SELECT ID, post_title, post_excerpt, post_content, post_author, post_date FROM `{$wpdb->base_prefix}{$blog['blog_id']}_posts` WHERE post_status = 'publish' AND post_type = 'post' AND ID != '1' ORDER BY post_date DESC LIMIT {$ust_settings['paged_posts']}";
								$posts = $wpdb->get_results( $query, ARRAY_A );
								?>
								<td valign="top">
									<?php
									if ( is_array( $posts ) && count( $posts ) ) {
										foreach ( $posts as $post ) {
											$post_preview[ $preview_id ] = $post['post_content'];
											$link                        = '#TB_inline?height=440&width=600&inlineId=post_preview_' . $preview_id;
											if ( empty( $post['post_title'] ) ) {
												$title = __( 'Kein Titel', 'ust' );
											} else {
												$title = htmlentities( $post['post_title'] );
											}
											echo '<a title="' . mysql2date( __( 'Y-m-d g:i:sa - ', 'ust' ), $post['post_date'] ) . $title . '" href="' . $link . '" class="thickbox">' . ust_trim_title( $title ) . '</a><br />';
											$preview_id ++;
										}
									} else {
										_e( 'Keine Beiträge', 'ust' );
									}
									?>
								</td>
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
				<tr>
					<td colspan="8"><?php _e( 'Keine Blogs gefunden.' ) ?></td>
				</tr>
			<?php
			} // end if ($blogs)
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
			<?php if ( $blog_navigation ) {
				echo "<div class='tablenav-pages'>$blog_navigation</div>";
			} ?>

			<div class="alignleft">
				<input type="submit" value="<?php _e( 'Ignorieren', 'ust' ) ?>" name="allblog_ignore"
				       class="button-secondary allblog_ignore"/>
				<input type="submit" value="<?php _e( 'Als Spam markieren' ) ?>" name="allblog_spam"
				       class="button-secondary allblog_spam"/>
				<br class="clear"/>
			</div>
		</div>

		</form>
		<?php
		//print hidden post previews
		if ( isset( $post_preview ) && is_array( $post_preview ) && count( $post_preview ) ) {
			echo '<div id="post_previews" style="display:none;">';
			foreach ( $post_preview as $id => $content ) {
				if ( $ust_settings['strip'] ) {
					$content = strip_tags( $content, '<a><strong><em><ul><ol><li>' );
				}
				echo '<div id="post_preview_' . $id . '">' . wpautop( strip_shortcodes( $content ) ) . "</div>\n";
			}
			echo '</div>';
		}

		break;


	//---------------------------------------------------//
	case "splogs":

		?><h3><?php _e( 'Aktuelle Splogs', 'ust' ) ?></h3><?php

		_e( '<p>Dies sind alle Blogs, die als Spam markiert wurden, in der Reihenfolge, in der sie gespammt wurden. Du kannst diese Splogs oder ihre letzten Posts sofort in der Vorschau anzeigen und sie entspammen, wenn ein Fehler aufgetreten ist.</p>', 'ust' );

		$ust_settings = get_site_option( 'ust_settings' );
		$apage        = isset( $_GET['apage'] ) ? intval( $_GET['apage'] ) : 1;
		$num          = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : $ust_settings['paged_blogs'];
		$review       = isset( $_GET['bid'] ) ? "AND b.blog_id = '" . (int) $_GET['bid'] . "'" : '';

		$query = "SELECT *
							FROM {$wpdb->blogs} b
							JOIN {$wpdb->registration_log} r ON b.blog_id = r.blog_id
							JOIN {$wpdb->base_prefix}ust u ON b.blog_id = u.blog_id
							WHERE b.site_id = '{$wpdb->siteid}'
							AND b.spam = 1 $review
							ORDER BY u.spammed DESC";

		$total = $wpdb->get_var( "SELECT COUNT(b.blog_id)
															FROM {$wpdb->blogs} b
															JOIN {$wpdb->registration_log} r ON b.blog_id = r.blog_id
															JOIN {$wpdb->base_prefix}ust u ON b.blog_id = u.blog_id
															WHERE b.site_id = '{$wpdb->siteid}'
															AND b.spam = 1 $review" );

		$query .= " LIMIT " . intval( ( $apage - 1 ) * $num ) . ", " . intval( $num );

		$blog_list = $wpdb->get_results( $query, ARRAY_A );

		$blog_navigation = paginate_links( array(
			'base'    => add_query_arg( 'apage', '%#%' ),
			'format'  => '',
			'total'   => ceil( $total / $num ),
			'current' => $apage
		) );
		$page_link       = ( $apage > 1 ) ? '&amp;apage=' . $apage : '';
		?>

		<form id="form-blog-list" action="sites.php?action=allblogs" method="post">

		<div class="tablenav">
			<?php if ( $blog_navigation ) {
				echo "<div class='tablenav-pages'>$blog_navigation</div>";
			} ?>

			<div class="alignleft">
				<select name="action">
					<option selected="selected" value="-1"><?php _e( 'Massenaktionen' ) ?></option>
					<option value="delete"><?php _e( 'Löschen' ) ?></option>
					<option value="notspam"><?php _e( 'Kein Spam' ) ?></option>
				</select>
				<input type="submit" value="Apply" class="button-secondary action" id="doaction" name="doaction">
				<?php wp_nonce_field( 'bulk-sites' ); ?>
				<br class="clear"/>
			</div>
		</div>

		<br class="clear"/>


		<?php
		// define the columns to display, the syntax is 'internal name' => 'display name'
		$blogname_columns = is_subdomain_install() ? __( 'Domain' ) : __( 'Pfad' );
		$posts_columns    = array(
			'id'         => __( 'ID' ),
			'blogname'   => $blogname_columns,
			'ips'        => __( 'IPs', 'ust' ),
			'users'      => __( 'Blog-Benutzer', 'ust' ),
			'certainty'  => __( 'Splog Gewissheit', 'ust' ),
			'method'     => __( 'Methode' ),
			'spammed'    => __( 'Spammed', 'ust' ),
			'registered' => __( 'Registriert' ),
			'posts'      => __( 'Letzte Beiträge', 'ust' )
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
			if ( $blog_list ) {
				$bgcolor    = $class = '';
				$preview_id = 0;
				foreach ( $blog_list as $blog ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate';

					echo '<tr class="' . $class . ' blog-row" id="bid-' . $blog['blog_id'] . '">';

					$blogname = is_subdomain_install() ? str_replace( '.' . $current_site->domain, '', $blog['domain'] ) : $blog['path'];
					foreach ( $posts_columns as $column_name => $column_display_name ) {
						switch ( $column_name ) {
							case 'id':
								?>
								<th scope="row" class="check-column">
									<input type='checkbox' id='blog_<?php echo $blog['blog_id'] ?>' name='allblogs[]'
									       value='<?php echo $blog['blog_id'] ?>'/>
								</th>
								<th scope="row">
									<?php echo $blog['blog_id'] ?>
								</th>
								<?php
								break;

							case 'blogname':
								?>
								<td valign="top">
									<a title="<?php _e( 'Vorschau', 'ust' ); ?>"
									   href="http://<?php echo $blog['domain'] . $blog['path']; ?>?KeepThis=true&TB_iframe=true&height=450&width=900"
									   class="thickbox"><?php echo $blogname; ?></a>
									<br/>
									<?php
									$controlActions   = array();
									$controlActions[] = '<a class="delete ust_unspam" href="' . $ust_admin_url . '&amp;tab=splogs' . $page_link . '&amp;unspam_blog=1&amp;id=' . $blog['blog_id'] . '&amp;updated=1&amp;updatedmsg=' . urlencode( __( 'Blog als kein Spam markiert!', 'ust' ) ) . '">' . __( 'Kein Spam' ) . '</a>';
									$controlActions[] = '<a class="delete" href="' . wp_nonce_url( 'sites.php?action=confirm&amp;action2=deleteblog&amp;id=' . $blog['blog_id'] . '&amp;msg=' . urlencode( sprintf( __( "Du bist im Begriff, den Blog %s zu löschen" ), $blogname ) ) . '&amp;updatedmsg=' . urlencode( __( 'Blog gelöscht!', 'ust' ) ), 'confirm' ) . '">' . __( "Löschen" ) . '</a>';
									?>

									<?php if ( count( $controlActions ) ) : ?>
										<div class="row-actions">
											<?php echo implode( ' | ', $controlActions ); ?>
										</div>
									<?php endif; ?>
								</td>
								<?php
								break;

							case 'ips':
								$result     = $wpdb->get_row( "SELECT user_login, spam FROM " . $wpdb->base_prefix . "users WHERE ID = '" . $blog['last_user_id'] . "'" );
								$user_login = $result->user_login;
								$user_spam  = $result->spam;
								?>
								<td valign="top">
									Registered: <a title="<?php _e( 'Suche nach IP', 'ust' ) ?>"
									               href="sites.php?action=blogs&amp;s=<?php echo $blog['IP'] ?>&blog_ip=1"
									               class="edit"><?php echo $blog['IP']; ?></a>
									<small class="row-actions"><a class="ust_spamip"
									                              title="<?php _e( 'Spam alle Blogs, die an diese IP gebunden sind', 'ust' ) ?>"
									                              href="<?php echo $ust_admin_url . $page_link; ?>&updated=1&id=<?php echo $blog['blog_id']; ?>&spam_ip=<?php echo $blog['IP']; ?>"><?php _e( 'Spam', 'ust' ) ?></a>
									</small>
									<br/>
									<?php if ( $blog['last_user_id'] ) : ?>
										<?php $spm_class = ( $user_spam ) ? ' style="color:red;"' : ''; ?>
										Last User: <a<?php echo $spm_class ?>
											title="<?php _e( 'Suche nach Benutzerblogs', 'ust' ) ?>"
											href="users.php?s=<?php echo $user_login; ?>"
											class="edit"><?php echo $user_login; ?></a>
										<?php if ( $user_spam == 0 ) : ?>
											<small class="row-actions"><a class="ust_spamuser"
											                              title="<?php _e( 'Spam alle Blogs, die mit diesem Benutzer verknüpft sind', 'ust' ) ?>"
											                              href="<?php echo $ust_admin_url . $page_link; ?>&updated=1&spam_user=<?php echo $blog['last_user_id']; ?>"><?php _e( 'Spam', 'ust' ) ?></a>
											</small><?php endif; ?>
										<br/>
									<?php endif; ?>
									<?php if ( $blog['last_ip'] ) : ?>
										Last IP: <a title="<?php _e( 'Suche nach IP', 'ust' ) ?>"
										            href="sites.php?action=blogs&amp;s=<?php echo $blog['last_ip']; ?>&blog_ip=1"
										            class="edit"><?php echo $blog['last_ip']; ?></a>
										<small class="row-actions"><a class="ust_spamip"
										                              title="<?php _e( 'Spam alle Blogs, die an diese IP gebunden sind', 'ust' ) ?>"
										                              href="<?php echo $ust_admin_url . $page_link; ?>&updated=1&id=<?php echo $blog['blog_id']; ?>&spam_ip=<?php echo $blog['last_ip']; ?>"><?php _e( 'Spam', 'ust' ) ?></a>
										</small>
									<?php endif; ?>
								</td>
								<?php
								break;

							case 'users':
								?>
								<td valign="top">
									<?php
									$blog_prefix = $wpdb->get_blog_prefix( $blog['blog_id'] );
									$blogusers   = $wpdb->get_results( "SELECT user_id, user_id AS ID, user_login, display_name, user_email, spam, meta_value FROM $wpdb->users, $wpdb->usermeta WHERE {$wpdb->users}.ID = {$wpdb->usermeta}.user_id AND meta_key = '{$blog_prefix}capabilities' ORDER BY {$wpdb->usermeta}.user_id" );
									if ( is_array( $blogusers ) ) {
										$blogusers_warning = '';
										if ( count( $blogusers ) > 5 ) {
											$blogusers         = array_slice( $blogusers, 0, 5 );
											$blogusers_warning = __( 'Es werden nur die ersten 5 Benutzer angezeigt.' ) . ' <a href="http://' . $blog['domain'] . $blog['path'] . 'wp-admin/users.php">' . __( 'More' ) . '</a>';
										}
										foreach ( $blogusers as $key => $val ) {
											$spm_class = ( $val->spam ) ? ' style="color:red;"' : '';
											echo '<a' . $spm_class . ' title="Edit User: ' . $val->display_name . ' (' . $val->user_email . ')" href="user-edit.php?user_id=' . $val->user_id . '">' . $val->user_login . '</a> ';
											echo '<small class="row-actions"><a title="' . __( 'Alle Blogs des Benutzers', 'ust' ) . '" href="users.php?s=' . $val->user_login . '">' . __( 'Blogs', 'ust' ) . '</a>';
											if ( $val->spam == 0 ) {
												echo ' | <a class="ust_spamuser" title="' . __( 'Spam alle Blogs, die mit diesem Benutzer verknüpft sind', 'ust' ) . '" href="' . $ust_admin_url . $page_link . '&updated=1&spam_user=' . $val->user_id . '">' . __( 'Spam', 'ust' ) . '</a></small>';
											}
											echo '<br />';
										}
										if ( $blogusers_warning != '' ) {
											echo '<strong>' . $blogusers_warning . '</strong><br />';
										}
									}
									?>
								</td>
								<?php
								break;

							case 'certainty':
								?>
								<td valign="top">
									<?php echo $blog['certainty']; ?>%
								</td>
								<?php
								break;

							case 'method':
								?>
								<td valign="top">
									<?php
									if ( get_blog_option( $blog['blog_id'], 'ust_auto_spammed' ) ) {
										_e( 'Auto: Signup', 'ust' );
									} else if ( get_blog_option( $blog['blog_id'], 'ust_post_auto_spammed' ) ) {
										_e( 'Auto: Post', 'ust' );
									} else {
										_e( 'Manuell', 'ust' );
									}
									?>
								</td>
								<?php
								break;

							case 'spammed':
								?>
								<td valign="top">
									<?php echo ( $blog['spammed'] == '0000-00-00 00:00:00' ) ? __( "Niemals" ) : mysql2date( __( 'Y-m-d \<\b\r \/\> g:i:s a' ), $blog['spammed'] ); ?>
								</td>
								<?php
								break;

							case 'registered':
								?>
								<td valign="top">
									<?php echo mysql2date( __( 'Y-m-d \<\b\r \/\> g:i:s a' ), $blog['registered'] ); ?>
								</td>
								<?php
								break;

							case 'posts':
								$query = "SELECT ID, post_title, post_excerpt, post_content, post_author, post_date FROM `{$wpdb->base_prefix}{$blog['blog_id']}_posts` WHERE post_status = 'publish' AND post_type = 'post' AND ID != '1' ORDER BY post_date DESC LIMIT {$ust_settings['paged_posts']}";
								$posts = $wpdb->get_results( $query, ARRAY_A );
								?>
								<td valign="top">
									<?php
									if ( is_array( $posts ) && count( $posts ) ) {
										foreach ( $posts as $post ) {
											$post_preview[ $preview_id ] = $post['post_content'];
											$link                        = '#TB_inline?height=440&width=600&inlineId=post_preview_' . $preview_id;
											if ( empty( $post['post_title'] ) ) {
												$title = __( 'Kein Titel', 'ust' );
											} else {
												$title = htmlentities( $post['post_title'] );
											}
											echo '<a title="' . mysql2date( __( 'Y-m-d g:i:sa - ', 'ust' ), $post['post_date'] ) . $title . '" href="' . $link . '" class="thickbox">' . ust_trim_title( $title ) . '</a><br />';
											$preview_id ++;
										}
									} else {
										_e( 'Keine Beiträge', 'ust' );
									}
									?>
								</td>
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
				<tr style='background-color: <?php echo isset($bgcolor); ?>'>
					<td colspan="8"><?php _e( 'Keine Blogs gefunden.' ) ?></td>
				</tr>
			<?php
			} // end if ($blogs)
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
			<?php if ( $blog_navigation ) {
				echo "<div class='tablenav-pages'>$blog_navigation</div>";
			} ?>

			<div class="alignleft">
				<select name="action2">
					<option selected="selected" value="-1"><?php _e( 'Massenaktionen' ) ?></option>
					<option value="delete"><?php _e( 'Löschen' ) ?></option>
					<option value="notspam"><?php _e( 'Kein Spam' ) ?></option>
				</select>
				<input type="submit" value="Apply" class="button-secondary action" id="doaction2" name="doaction2">
				<br class="clear"/>
			</div>
		</div>

		</form>
		<?php
		//print hidden post previews
		if ( isset( $post_preview ) && is_array( $post_preview ) && count( $post_preview ) ) {
			echo '<div id="post_previews" style="display:none;">';
			foreach ( $post_preview as $id => $content ) {
				if ( $ust_settings['strip'] ) {
					$content = strip_tags( $content, '<a><strong><em><ul><ol><li>' );
				}
				echo '<div id="post_preview_' . $id . '">' . wpautop( strip_shortcodes( $content ) ) . "</div>\n";
			}
			echo '</div>';
		}

		break;


	//---------------------------------------------------//
	case "ignored":

		?><h3><?php _e( 'Ignorierte Blogs', 'ust' ) ?></h3><?php

		_e( '<p>Dies sind verdächtige Blogs, die Sie für gültig befunden haben. Wenn Du einen Fehler gemacht hast, kannst Du sie zurück in die Warteschlange für verdächtige Blogs schicken oder sie spammen.</p>', 'ust' );

		$ust_settings = get_site_option( 'ust_settings' );
		$apage        = isset( $_GET['apage'] ) ? intval( $_GET['apage'] ) : 1;
		$num          = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : $ust_settings['paged_blogs'];

		$query = "SELECT *
							FROM {$wpdb->blogs} b, {$wpdb->registration_log} r, {$wpdb->base_prefix}ust u
							WHERE b.site_id = '{$wpdb->siteid}'
							AND b.blog_id = r.blog_id
							AND b.blog_id = u.blog_id
							AND b.spam = 0 AND u.`ignore` = 1
							ORDER BY u.spammed DESC";

		$total = $wpdb->get_var( "SELECT COUNT(b.blog_id)
															FROM {$wpdb->blogs} b, {$wpdb->registration_log} r, {$wpdb->base_prefix}ust u
															WHERE b.site_id = '{$wpdb->siteid}'
															AND b.blog_id = r.blog_id
															AND b.blog_id = u.blog_id
															AND b.spam = 0 AND u.`ignore` = 1" );

		$query .= " LIMIT " . intval( ( $apage - 1 ) * $num ) . ", " . intval( $num );

		$blog_list = $wpdb->get_results( $query, ARRAY_A );

		$blog_navigation = paginate_links( array(
			'base'    => add_query_arg( 'apage', '%#%' ),
			'format'  => '',
			'total'   => ceil( $total / $num ),
			'current' => $apage
		) );
		$page_link       = ( $apage > 1 ) ? '&amp;apage=' . $apage : '';
		?>

		<form id="form-blog-list"
		      action="<?php echo $ust_admin_url; ?>&amp;tab=ignored<?php echo $page_link; ?>&amp;action=allblogs&amp;updated=1"
		      method="post">

		<div class="tablenav">
			<?php if ( $blog_navigation ) {
				echo "<div class='tablenav-pages'>$blog_navigation</div>";
			} ?>

			<div class="alignleft">
				<input type="submit" value="<?php _e( 'Nicht ignorieren', 'ust' ) ?>" name="allblog_unignore"
				       class="button-secondary allblog_unignore"/>
				<input type="submit" value="<?php _e( 'Als Spam markieren' ) ?>" name="allblog_spam"
				       class="button-secondary allblog_spam"/>
				<br class="clear"/>
			</div>
		</div>

		<br class="clear"/>

		<?php
		// define the columns to display, the syntax is 'internal name' => 'display name'
		$blogname_columns = is_subdomain_install() ? __( 'Domain' ) : __( 'Pfad' );
		$posts_columns    = array(
			'id'          => __( 'ID' ),
			'blogname'    => $blogname_columns,
			'ips'         => __( 'IPs', 'ust' ),
			'users'       => __( 'Blog-Benutzer', 'ust' ),
			'certainty'   => __( 'Splog Gewissheit', 'ust' ),
			'lastupdated' => __( 'Letzte Aktualisierung' ),
			'registered'  => __( 'Registriert' ),
			'posts'       => __( 'Kürzliche Posts', 'ust' )
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
			if ( $blog_list ) {
				$bgcolor    = $class = '';
				$preview_id = 0;
				foreach ( $blog_list as $blog ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate';

					echo '<tr class="' . $class . ' blog-row" id="bid-' . $blog['blog_id'] . '">';

					$blogname = is_subdomain_install() ? str_replace( '.' . $current_site->domain, '', $blog['domain'] ) : $blog['path'];
					foreach ( $posts_columns as $column_name => $column_display_name ) {
						switch ( $column_name ) {
							case 'id':
								?>
								<th scope="row" class="check-column">
									<input type='checkbox' id='blog_<?php echo $blog['blog_id'] ?>' name='allblogs[]'
									       value='<?php echo $blog['blog_id'] ?>'/>
								</th>
								<th scope="row">
									<?php echo $blog['blog_id']; ?>
								</th>
								<?php
								break;

							case 'blogname':
								?>
								<td valign="top">
									<a title="<?php _e( 'Vorschau', 'ust' ); ?>"
									   href="http://<?php echo $blog['domain'] . $blog['path']; ?>?KeepThis=true&TB_iframe=true&height=450&width=900"
									   class="thickbox"><?php echo $blogname; ?></a>
									<br/>

									<div class="row-actions">
										<?php echo '<a class="delete ust_unignore" href="' . $ust_admin_url . '&amp;tab=ignored' . $page_link . '&amp;unignore_blog=1&amp;id=' . $blog['blog_id'] . '&amp;updated=1&amp;updatedmsg=' . urlencode( __( 'Blog Nicht-Ignoriert!', 'ust' ) ) . '">' . __( 'Nicht ignorieren', 'ust' ) . '</a>'; ?>
										|
										<?php echo '<a class="delete ust_spam" href="' . $ust_admin_url . '&amp;tab=ignored' . $page_link . '&amp;spam_blog=1&amp;id=' . $blog['blog_id'] . '&amp;updated=1&amp;updatedmsg=' . urlencode( __( 'Blog als Spam markiert!', 'ust' ) ) . '">' . __( 'Spam' ) . '</a>'; ?>
									</div>
								</td>
								<?php
								break;

							case 'ips':
								$result     = $wpdb->get_row( "SELECT user_login, spam FROM " . $wpdb->base_prefix . "users WHERE ID = '" . $blog['last_user_id'] . "'" );
								$user_login = $result->user_login;
								$user_spam  = $result->spam;
								?>
								<td valign="top">
									Registered: <a title="<?php _e( 'Suche nach IP', 'ust' ) ?>"
									               href="sites.php?action=blogs&amp;s=<?php echo $blog['IP'] ?>&blog_ip=1"
									               class="edit"><?php echo $blog['IP']; ?></a>
									<small class="row-actions"><a class="ust_spamip"
									                              title="<?php _e( 'Spam alle Blogs, die an diese IP gebunden sind', 'ust' ) ?>"
									                              href="<?php echo $ust_admin_url . $page_link; ?>&updated=1&id=<?php echo $blog['blog_id']; ?>&spam_ip=<?php echo $blog['IP']; ?>"><?php _e( 'Spam', 'ust' ) ?></a>
									</small>
									<br/>
									<?php if ( $blog['last_user_id'] ) : ?>
										<?php $spm_class = ( $user_spam ) ? ' style="color:red;"' : ''; ?>
										Last User: <a<?php echo $spm_class ?>
											title="<?php _e( 'Suche nach Benutzerblogs', 'ust' ) ?>"
											href="users.php?s=<?php echo $user_login; ?>"
											class="edit"><?php echo $user_login; ?></a>
										<?php if ( $user_spam == 0 ) : ?>
											<small class="row-actions"><a class="ust_spamuser"
											                              title="<?php _e( 'Spam alle Blogs, die mit diesem Benutzer verknüpft sind', 'ust' ) ?>"
											                              href="<?php echo $ust_admin_url . $page_link; ?>&updated=1&spam_user=<?php echo $blog['last_user_id']; ?>"><?php _e( 'Spam', 'ust' ) ?></a>
											</small><?php endif; ?>
										<br/>
									<?php endif; ?>
									<?php if ( $blog['last_ip'] ) : ?>
										Last IP: <a title="<?php _e( 'Suche nach IP', 'ust' ) ?>"
										            href="sites.php?action=blogs&amp;s=<?php echo $blog['last_ip']; ?>&blog_ip=1"
										            class="edit"><?php echo $blog['last_ip']; ?></a>
										<small class="row-actions"><a class="ust_spamip"
										                              title="<?php _e( 'Spam alle Blogs, die an diese IP gebunden sind', 'ust' ) ?>"
										                              href="<?php echo $ust_admin_url . $page_link; ?>&updated=1&id=<?php echo $blog['blog_id']; ?>&spam_ip=<?php echo $blog['last_ip']; ?>"><?php _e( 'Spam', 'ust' ) ?></a>
										</small>
									<?php endif; ?>
								</td>
								<?php
								break;

							case 'users':
								?>
								<td valign="top">
									<?php
									$blog_prefix = $wpdb->get_blog_prefix( $blog['blog_id'] );
									$blogusers   = $wpdb->get_results( "SELECT user_id, user_id AS ID, user_login, display_name, user_email, spam, meta_value FROM $wpdb->users, $wpdb->usermeta WHERE {$wpdb->users}.ID = {$wpdb->usermeta}.user_id AND meta_key = '{$blog_prefix}capabilities' ORDER BY {$wpdb->usermeta}.user_id" );
									if ( is_array( $blogusers ) ) {
										$blogusers_warning = '';
										if ( count( $blogusers ) > 5 ) {
											$blogusers         = array_slice( $blogusers, 0, 5 );
											$blogusers_warning = __( 'Es werden nur die ersten 5 Benutzer angezeigt.' ) . ' <a href="http://' . $blog['domain'] . $blog['path'] . 'wp-admin/users.php">' . __( 'More' ) . '</a>';
										}
										foreach ( $blogusers as $key => $val ) {
											$spm_class = ( $val->spam ) ? ' style="color:red;"' : '';
											echo '<a' . $spm_class . ' title="Edit User: ' . $val->display_name . ' (' . $val->user_email . ')" href="user-edit.php?user_id=' . $val->user_id . '">' . $val->user_login . '</a> ';
											echo '<small class="row-actions"><a title="' . __( 'Alle Blogs des Benutzers', 'ust' ) . '" href="users.php?s=' . $val->user_login . '">' . __( 'Blogs', 'ust' ) . '</a>';
											if ( $val->spam == 0 ) {
												echo ' | <a class="ust_spamuser" title="' . __( 'Spam alle Blogs, die mit diesem Benutzer verknüpft sind', 'ust' ) . '" href="' . $ust_admin_url . $page_link . '&updated=1&spam_user=' . $val->user_id . '">' . __( 'Spam', 'ust' ) . '</a></small>';
											}
											echo '<br />';
										}
										if ( $blogusers_warning != '' ) {
											echo '<strong>' . $blogusers_warning . '</strong><br />';
										}
									}
									?>
								</td>
								<?php
								break;

							case 'certainty':
								?>
								<td valign="top">
									<?php echo $blog['certainty']; ?>%
								</td>
								<?php
								break;

							case 'lastupdated':
								?>
								<td valign="top">
									<?php echo ( $blog['last_updated'] == '0000-00-00 00:00:00' ) ? __( "Niemals" ) : mysql2date( __( 'Y-m-d \<\b\r \/\> g:i:s a' ), $blog['last_updated'] ); ?>
								</td>
								<?php
								break;

							case 'registered':
								?>
								<td valign="top">
									<?php echo mysql2date( __( 'Y-m-d \<\b\r \/\> g:i:s a' ), $blog['registered'] ); ?>
								</td>
								<?php
								break;

							case 'posts':
								$query = "SELECT ID, post_title, post_excerpt, post_content, post_author, post_date FROM `{$wpdb->base_prefix}{$blog['blog_id']}_posts` WHERE post_status = 'publish' AND post_type = 'post' AND ID != '1' ORDER BY post_date DESC LIMIT {$ust_settings['paged_posts']}";
								$posts = $wpdb->get_results( $query, ARRAY_A );
								?>
								<td valign="top">
									<?php
									if ( is_array( $posts ) && count( $posts ) ) {
										foreach ( $posts as $post ) {
											$post_preview[ $preview_id ] = $post['post_content'];
											$link                        = '#TB_inline?height=440&width=600&inlineId=post_preview_' . $preview_id;
											if ( empty( $post['post_title'] ) ) {
												$title = __( 'Kein Titel', 'ust' );
											} else {
												$title = htmlentities( $post['post_title'] );
											}
											echo '<a title="' . mysql2date( __( 'Y-m-d g:i:sa - ', 'ust' ), $post['post_date'] ) . $title . '" href="' . $link . '" class="thickbox">' . ust_trim_title( $title ) . '</a><br />';
											$preview_id ++;
										}
									} else {
										_e( 'Keine Beiträge', 'ust' );
									}
									?>
								</td>
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
				<tr style='background-color: <?php echo isset($bgcolor); ?>'>
					<td colspan="8"><?php _e( 'Keine Blogs gefunden.' ) ?></td>
				</tr>
			<?php
			} // end if ($blogs)
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
			<?php if ( $blog_navigation ) {
				echo "<div class='tablenav-pages'>$blog_navigation</div>";
			} ?>

			<div class="alignleft">
				<input type="submit" value="<?php _e( 'Nicht ignorieren', 'ust' ) ?>" name="allblog_unignore"
				       class="button-secondary allblog_unignore"/>
				<input type="submit" value="<?php _e( 'Als Spam markieren' ) ?>" name="allblog_spam"
				       class="button-secondary allblog_spam"/>
				<br class="clear"/>
			</div>
		</div>

		</form>
		<?php
		//print hidden post previews
		if ( isset( $post_preview ) && is_array( $post_preview ) && count( $post_preview ) ) {
			echo '<div id="post_previews" style="display:none;">';
			foreach ( $post_preview as $id => $content ) {
				if ( $ust_settings['strip'] ) {
					$content = strip_tags( $content, '<a><strong><em><ul><ol><li>' );
				}
				echo '<div id="post_preview_' . $id . '">' . wpautop( strip_shortcodes( $content ) ) . "</div>\n";
			}
			echo '</div>';
		}

		break;

} //end switch

//hook to extend admin screen. Check $_GET['tab'] for new tab
do_action( 'ust_add_screen' );

echo '</div>';
?>