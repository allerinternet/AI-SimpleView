<?php
/*
Plugin Name: AI Simple View
Plugin URI: http://www.allerinternet.se/
Description: AI Simple View is a simple gallery for editorial sites.
Version: 0.1
Author: Jonas Björk <jonas.bjork@aller.se>
Author URI: http://www.jonasbjork.net/

Bug in WPMU 2.8.2 ->
add:
require_once( '/var/www/wpmu/wp-includes/pluggable.php' );
in file /var/www/wpmu/wp-includes/capabilities.php
 
*/

// TODO: Vote for single images in slideshow
/*
	TODO Database cons check
*/

define( 'AI_SIMPLEVIEW_URL', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/' );
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); // For _install
require_once( 'SimpleView.class.php' );

$SV = new SimpleView();

function aisimpleview_install() {
	global $wpdb;
	
	// TODO: Check if MySQL-server has support for InnoDB engine.
	
	$table_name = $wpdb->prefix . 'ai_simpleview_slideshow';
	
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
		
		$sql = "CREATE TABLE " . $table_name . " (
		  `ID` int(11) NOT NULL AUTO_INCREMENT,
		  `user_id` int(11) DEFAULT NULL,
		  `slide_name` varchar(255) DEFAULT NULL,
		  `slide_desc` text,
		  `created_time` timestamp NULL DEFAULT NULL,
		  `updated_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
		  `deleted` tinyint(1) DEFAULT NULL,
		  PRIMARY KEY (`ID`)
		) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";
		
		dbDelta( $sql );
		
	}
	
	$table_name = $wpdb->prefix . 'ai_simpleview_images';
	
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
		
		$sql = "CREATE TABLE " . $table_name . " (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `slideshow_id` int(11) unsigned DEFAULT NULL,
		  `file_name` varchar(200) DEFAULT NULL,
		  `file_size` int(11) DEFAULT NULL,
		  `created_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
		  `checksum` char(40) DEFAULT NULL,
		  `deleted` int(11) DEFAULT NULL,
		  `description` text,
		  `file_type` varchar(200) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";
		
		dbDelta( $sql );
		
	}

}
register_activation_hook( __FILE__, 'aisimpleview_install' );

function aisimpleview_init() {
	
}
add_action( 'init', 'aisimpleview_init' );

function aisimpleview_admin_init() {
	
}
add_action( 'admin_init', 'aisimpleview_admin_init' );


function aisimpleview_shortcode( $atts ) {
	global $wpdb;
	
	extract(shortcode_atts(array(
		'id' => '',
		'size' => '',
	), $atts));
	$content .= "<iframe id='" . $id . "' scrolling='no' src='" . trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) ) . "/presenter.php?id=";
	$content .= $id . "' frameborder='1' style='width: 430px;height:345px; overflow: hidden; margin-top: 15px; margin-bottom: 15px'></iframe>";
	
	//	<iframe scrolling='no' src='/public/templates/v3bildspel.aspx?id=674779&fid=674730&level=0&h=415&w=435&first=true' frameborder='0' style='overflow:hidden;'></iframe>
	return $content;
}

// [simpleview id=NUM]
add_shortcode('simpleview', 'aisimpleview_shortcode');

function aisimpleview_js() 
{
	$plugin_url = trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) );
	wp_enqueue_script('simpleviewer', $plugin_url.'/simpleview.js');
}
add_action( 'wp_print_scripts', 'aisimpleview_js' );

function aisimpleview_menu() {
	if ( function_exists('add_menu_page') ) {
		//add_options_page( 'Simple View', 'Simple View', 'administrator', '', 'aisimpleview_controller' );
		add_menu_page( 'Simple View', 'Simple View', 'manage_options', 'simpleview', 'aisimpleview_page_start' );
		add_submenu_page( 'simpleview', 'Simple View', 'Slideshows', 'manage_options', 'simpleview-slideshows', 'aisimpleview_page_slideshow');
		add_submenu_page( 'simpleview', 'Simple View', 'Settings', 'manage_options', 'simpleview-settings',  'aisimpleview_page_settings' );
	}
}
add_action( 'admin_menu', 'aisimpleview_menu' );


// OLD, will be  removed
function aisimpleview_controller() {
	
	$action = $_GET['action'];
		
	switch ( $action ) {
		case 'setup':
			aisimpleview_setup_slideshow();
			break;
		default:
			aisimpleview_config_page();
	}
	
}

function aisimpleview_page_slideshow() {
	global $wpdb;
	$action = null;
	$id = null;
	
	if ( isset($_GET['view']) ) {
		// visa en slideshow
		$action = 'view';
		$id = (int)$_GET['view'];
		
		if ( isset($_POST['submit_files']) ) {

			for ( $i = 0; $i < count ($_FILES['file']) ; $i++ ) {
				$name     = $_FILES['file']['name'][$i];
				$type     = $_FILES['file']['type'][$i];
				$tmp_name = $_FILES['file']['tmp_name'][$i];
				$size     = $_FILES['file']['size'][$i];
				$error    = $_FILES['file']['error'][$i];
				$desc     = $_POST['desc'][$i];
				$updir = get_upload_path().'ai-simpleview/'.$id.'/';
				if ( !file_exists( $updir ) ) {
					if ( !mkdir( $updir, 0777, true ) ) {
						// TODO: How can I insert $var into __() ?
						$message[] = __('Could not create directory!');
					}
				}

				if ( $type == "image/jpeg" && $error == 0 ) {
					// TODO: Does the file already exist?
					$checksum = sha1($tmp_name);
					if ( file_exists( $updir . $name ) ) {
						_e("File already exists.");
					} else {
						if ( move_uploaded_file( $tmp_name, $updir . $name ) ) {
							$result = $wpdb->insert( $wpdb->prefix.'ai_simpleview_images', array( 'slideshow_id' => $id, 'file_name' => $name, 'file_size' => $size, 'file_type' => $type, 'checksum' => $checksum, 'deleted' => 0, 'description' => $desc ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s') );
							// echo $wpdb->last_query;
							if ( $result == 1 ) {
								_e('File successfully uploaded.');	
							} else {
								_e('Error while inserting image in database.');
							}
						}
					}
				}
			}
			
		}
		print_r($error);
		
	} else if ( isset($_GET['delete_id']) ) {
		// ta bort en slideshow
		$action = 'delete';
		$id = (int) $_GET['delete'];
		
	} else if ( isset($_GET['dimg']) ) {
		// ta bort en bild
		$action = 'view';
		$id = (int) $_GET['id'];
		$img = (int) $_GET['dimg'];
		aisv_delete_image( $img );
		
		
		 
	}

	if ( isset($_POST['submit_create']) ) {
		$name = $_POST['create_name'];
		$desc = $_POST['create_desc'];

		global $user_ID;
		$time = date("Y-m-d H:i:s", time());
		$wpdb->insert( $wpdb->prefix.'ai_simpleview_slideshow',
			array( 'user_id' => $user_ID, 'slide_name' => $name, 'slide_desc' => $desc, 'created_time' => $time,
				'deleted' => 0),
			array( '%d', '%s', '%s', '%s', '%d')
		);
		if ( $wpdb->insert_id > 0 ) {
			echo "Slideshow created";
		} else {
			echo "Could not create slideshow.";
		}


	}
	
?>
	<div class="wrap">		
		<h2>Simple View - Slideshows</h2>
		<?php echo aisv_create_tabs(); ?>
		
<?php
	if ( $action == 'view' ) {
		aisv_view_slideshow($id);
		aisv_upload_form($id);
	} else if ( $action == 'delete' ) {
	
	} else {
		aisv_list_slideshows();
	}
?>		
	</div>
<?php
}

function aisv_view_slideshow( $id ) {
	?>
	<h3><?php echo aisv_get_slidesshow_name( $id ); ?></h3>
	
	<p>To include this slideshow in a post, just add: <tt style='background-color: yellow'>[simpleview id=<?php echo $id; ?>]</tt> in post content.</p>
	<table class='widefat'>
		<thead>
			<tr>
				<th scope="col"><?php _e('Image', 'simpleview'); ?></th>
				<th scope="col"><?php _e('Description', 'simpleview'); ?></th>
				<th scope="col"><?php _e('Action'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope="col"><?php _e('Image', 'simpleview'); ?></th>
				<th scope="col"><?php _e('Description', 'simpleview'); ?></th>
				<th scope="col"><?php _e('Action'); ?></th>
			</tr>
		</tfoot>	
	<?php
		$images = slideshow_get_images_preview( $id );
		//var_dump($images);
		if ( empty( $images ) ) {
			echo "<p>" . __('There is no images.') . "</p>";
		} else {
			$count = 0;
			foreach ( $images as $i ) {
	//			var_dump($i);
				echo "<tr>";
				echo "<td><img src='" . $i['url'] . "' title='" . $i['description'] . "' width='80' /></td>";
				echo "<td>".$i['description']."</td>";
				echo "<td>";
				echo "<form method='GET' action=''>";
				echo "<input type='hidden' name='page' value='simpleview-slideshows' />";
				echo "<input type='hidden' name='id' value='".$id."' />";
				echo "<input type='hidden' name='dimg' value='".$i['id']."' />";
				echo "<input type='submit' value='"._('Delete')."'/>";
				echo "</form>";
				echo "</td>";
				echo "</tr>";
			}
		}
	?>
	</table>
<?php
	
	
}

function aisv_list_slideshows() {
	?>

	<form method="post" id="form_create_slideshow">
	<table class='widefat'>
	<thead>
		<tr><th scope="col" colspan="3">Create slideshow</th></tr>
	</thead>
	<tr>
	<td>Name:</td><td><input type="text" name="create_name" /></td>
	<td><input type="submit" class="button-primary" name="submit_create" value="Create" /></td>
	</tr>
	<tr>
	<td>Description:</td>
	<td colspan="2"><textarea name="create_desc" cols="40"></textarea></td>
	</tr>
	</table>
	</form>

	<br /><br />
	<table class='widefat'>
		<thead>
			<tr>
				<th scope="col"><?php _e('Slideshow name', 'simpleview'); ?></th>
				<th scope="col"><?php _e('Delete'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope="col"><?php _e('Slideshow name', 'simpleview'); ?></th>
				<th scope="col"><?php _e('Delete'); ?></th>
			</tr>
		</tfoot>
	<?php
		$shows = slideshow_list();
		if ( count( $shows ) > 0 ) {
			echo "<tbody>";
			foreach ( $shows as $s ) {
				echo '<tr>';
				echo '<td><a href="admin.php?page=simpleview-slideshows&amp;view=' . $s->ID . '">' . $s->slide_name . '</a><br />'.$s->slide_desc.'</td>';
				echo '<td><a href="admin.php?page=simpleview-slideshows&amp;delete_id=' . $s->ID . '">' . __('Delete'). '</a></td>';
				echo '</tr>';
				//echo '<tr><td cols="2">'..'</td></tr>';
			}
			echo "</tbody>";
		} else {
			echo '<p>' . __( 'There is no slideshows.' ) . '</p>';
		}

	?>
	</table>
<?php
}

function aisimpleview_page_start() {
	
?>
<div class="wrap">
	<h2>Simple View</h2>
	<?php echo aisv_create_tabs(); ?>
	<p><strong>Slideshow dir:</strong> <?php echo get_upload_path().'ai-simpleview/'; ?></p>
	
</div>
<?php
}

function aisimpleview_page_settings() {
	global $wpdb, $blog_id;
	$updated = false;
	$hidden_field_name = 'simpleview-submitted';
	$slide_width = get_option ( 'sv_slide_width', 430 );

	if ( isset($_POST[ $hidden_field_name ]) && ($_POST[ $hidden_field_name ] == 'tada') ) {
		$slide_width = $_POST['slide_width'];
		if ( is_numeric( $slide_width ) ) {
			update_option( 'sv_slide_width', $slide_width );
			echo "<div class='updated'><p><strong>".__('settings saved', 'simpleview')."</strong></p></div>";
		}
	}

?>
<div class="wrap">
	<h2>Simple View - <?php _e('Settings', 'simpleview'); ?></h2>
	<p>Here you can tweak your slides.</p>
	<p><strong>Upload_path:</strong> <?php echo get_option('upload_path');?></p>
	<p><strong>Fileupload_url:</strong> <?php echo get_option('fileupload_url');?></p>
	<p>Change the values <a href="/wp-admin/wpmu-blogs.php?action=editblog&amp;id=<?php echo $blog_id; ?>">here</a> .</p>
	<p><strong>Slideshow dir:</strong> <?php echo get_upload_path().'ai-simpleview/'; ?></p>
	<form name="form1" method="post" action="">
	<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="tada">

	<p><?php _e("Slideshow width (pixels):", 'simpleview' ); ?> 
	<input type="text" name="slide_width" value="<?php echo $slide_width; ?>" size="10">
	</p><hr />

	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
	</p>

	</form>

</div>
<?php 
}



function aisimpleview_setup_slideshow() {
	global $wpdb;

	$message = array();
	$fs_err = false;
		
	if ( !isset( $_GET['slide'] ) ) {
		$message[] = __('Slide show not found!') ;
	}
	
	$slide_id = (int) $_GET['slide'];
	
	if ( !slideshow_is_deleted( $slide_id ) ) {
		$message[] = __('Slide show not found!') ;
	}
	
	if ( isset($_POST['submit_files']) && empty( $message ) ) {

		for ( $i = 0; $i < count ($_FILES['file']) ; $i++ ) {
			$name     = $_FILES['file']['name'][$i];
			$type     = $_FILES['file']['type'][$i];
			$tmp_name = $_FILES['file']['tmp_name'][$i];
			$size     = $_FILES['file']['size'][$i];
			$error    = $_FILES['file']['error'][$i];
			$desc     = $_POST['desc'][$i];

//			$updir = get_option( 'upload_path' ) . '/ai-simpleview/' . $slide_id . '/' ;
			$updir = get_upload_path().'ai-simpleview/'.$slide_id.'/';
			// Check if directory exists, else create it.
			if ( !file_exists( $updir ) ) {
				if ( !mkdir( $updir, 0777, true ) ) {
					// TODO: How can I insert $var into __() ?
					$message[] = __('Could not create directory!');
					$fs_error = true;
				}
			}

			if ( $type == "image/jpeg" && $error == 0 ) {
				// TODO: Does the file already exist?
				$checksum = sha1($tmp_name);
				if ( file_exists( $updir . $name ) ) {
					$message[] = __("File already exists.");
				} else {
					if ( move_uploaded_file( $tmp_name, $updir . $name ) ) {
						$result = $wpdb->insert( $wpdb->prefix.'ai_simpleview_images', array( 'slideshow_id' => $slide_id, 'file_name' => $name, 'file_size' => $size, 'file_type' => $type, 'checksum' => $checksum, 'deleted' => 0, 'description' => $desc ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s') );
						// echo $wpdb->last_query;
						if ( $result == 1 ) {
							$message[] = __('File successfully uploaded.');	
						} else {
							$message[] = __('Error while inserting image in database.');
						}
					}
				}

			} else {
				// TODO: Something went wrong.
//				$error[] = sprintf( "Something went wrong while uploading file %s .", $name );
			}
			
		}
	}
?>
<h1>Simple View</h1>
<?php foreach ( $message as $m ): ?>
	<li><?php echo $m; ?></li>
<?php endforeach; ?>

?>

<h2>Upload new images</h2>
<form action="" method="post" enctype="multipart/form-data">



<?php
}

function aisimpleview_config_page() {
	global $wpdb, $user_ID;
	$error = null;
	
	// TODO: Check if a slideshow with that name already exists.

	if ( isset( $_GET['delete_id'] ) ) {
		$update = slideshow_delete( $_GET['delete_id'] );
		if ( $update ) {
			$message = '<p>'. sprintf( 'Slide show %s deleted.', $_GET['delete_id'] ) .'</p>';
		} else {
			$message = '<p>' . __( 'Could not delete slide show.' ) . '</p>';
		}
	}
	
	if ( $_POST['form_submit'] ) {
		$s_name = $_POST['slideshow_name'];
		$s_desc = $_POST['slideshow_desc'];
		//var_dump( $_POST );
		
		if ( strlen( $s_name ) < 3 || strlen( $s_desc ) > 20 ) {
			$error[] = __('Slideshow name must be at least 3 and max 20 chars.');
		}
		
		if ( !preg_match( '/^[a-z0-9åäö.-\s]+$/i', $s_name ) ) {
			$error[] = __('Slideshow name contains illegal chars.');
		}
		
		if ( strlen( $s_desc ) > 0 ) {
			if( !preg_match( '/^[a-z0-9åäö.-\s]+$/i', $s_desc ) ) {
				$error[] = __('Slideshow description contains illegal chars.');
			}			
		}
		
		if ( empty( $error ) ) {
			$time = date("Y-m-d H:i:s", time());
			$wpdb->insert( $wpdb->prefix.'ai_simpleview_slideshow',
				array( 'user_id' => $user_ID, 'slide_name' => $s_name, 'slide_desc' => $s_desc, 'created_time' => $time, 'deleted' => 0),
				array( '%d', '%s', '%s', '%s', '%d')
			);
			if ( $wpdb->insert_id > 0 ) {
				$message = __('Slideshow created!');
				unset( $_POST );
			} else {
				$error = __('Could not create slidwshow.');
			}
		}
	
	}
	
?>
<h1>Simple View</h1>	

<h2><?php _e('Create a new slideshow'); ?>.</h2>


<form method="post" action="options-general.php?page=" name="form_create">
	<label for="slideshow_name"><?php _e('Slideshow name'); ?>: </label>
	<input type="text" name="slideshow_name" id="slideshow_name" value="<?php echo stripslashes($_POST['slideshow_name']); ?>"/>
	<br />
	<label for="slideshow_desc"><?php _e('Description'); ?>:</label>
	<textarea id="slideshow_desc" name="slideshow_desc"><?php echo stripslashes($_POST['slideshow_desc']); ?></textarea>
	<br />
	<input type="submit" name="form_submit" value="<?php _e('Create'); ?>">
</form>

<h2><?php _e('Slide shows'); ?></h2>


<?php
	
	upload_images(array(), 1);
}


function aisv_upload_form( $id ) {
	
	?>
	<h2>Upload new images</h2>
	<form action="" method="post" enctype="multipart/form-data">
	<input type="hidden" name="page" value="simpleview-slideshow" />
	<input type="hidden" name="id" value="<?php echo $id; ?>" />
	<label for="file1"><?php _e('Filename'); ?>:</label>
	<input type="file" name="file[]" id="file1" />
	<input type="submit" class="button-primary" name="submit_files" value="<?php _e('Submit'); ?>" />
	<br />
	<label for="desc1"><?php _e('Image text'); ?></label>
	<textarea name="desc[]" id="desc1"></textarea>

<?php
}


function aisv_create_tabs() {
	$str = "<a href='?page=simpleview-slideshows'>List slideshows</a>";
	
	return $str;
}

function aisv_get_slidesshow_name( $id ) {
	global $wpdb, $user_ID;
	$sql = sprintf("SELECT slide_name FROM %s WHERE ID=%s AND user_id=%s AND deleted='0' LIMIT 1", $wpdb->prefix.'ai_simpleview_slideshow', $id, $user_ID);
	$result = $wpdb->get_row( $sql );
	if ( count($result) > 0 ) {
		return $result->slide_name;
	} else {
		return "No name slideshow";
	}
	
}

function aisv_delete_image( $id ) {
		global $wpdb, $user_ID;
		// TODO: Kontrollera om user_ID äger bildspelet!
		$up = $wpdb->update( $wpdb->prefix.'ai_simpleview_images', array( 'deleted' => 1 ), array( 'ID' => $id ), array( '%d' ), array( '%d') );
		return $up;
}

function slideshow_list() {
	global $wpdb, $user_ID;

	$sql = sprintf("SELECT ID, slide_name, slide_desc FROM %s WHERE user_id='%s' AND deleted='0' ORDER BY created_time DESC", $wpdb->prefix.'ai_simpleview_slideshow', $user_ID);
	$result = $wpdb->get_results( $sql, OBJECT);
	return $result;
}

function slideshow_delete( $id ) {
	global $wpdb, $user_ID;
	// TODO: Delete all assoc images.
	$up = $wpdb->update( $wpdb->prefix.'ai_simpleview_slideshow', array( 'deleted' => 1 ), array( 'ID' => $id, 'user_id' => $user_ID ), array( '%d' ), array( '%d', '%d' ) );

	return $up;

}
function slideshow_count_images( $id ) {
	global $wpdb, $user_ID;
	$sql = sprintf( "SELECT COUNT(*) AS cnt FROM %s WHERE slideshow_id='%d' AND deleted='0'", $wpdb->prefix.'ai_simpleview_images', $id, $user_ID );
	$row = $wpdb->get_row( $sql );
	return $row->cnt;
}

function slideshow_has_images( $id ) {
	global $wpdb, $user_ID;
	$sql = sprintf( "SELECT * FROM %s WHERE slideshow_id='%d' AND deleted='0' LIMIT 1", $wpdb->prefix.'ai_simpleview_images', $id, $user_ID );
	$row = $wpdb->get_row( $sql );
	if ( count( $row ) > 0 ) {
		return true;
	}
	return false;
}

function slideshow_is_deleted( $id ) {
	global $wpdb, $user_ID;
	$sql = sprintf( "SELECT * FROM %s WHERE ID='%d' AND user_id='%s' AND deleted='0' LIMIT 1", $wpdb->prefix.'ai_simpleview_slideshow', $id, $user_ID );
	$row = $wpdb->get_row( $sql );
	if ( count( $row ) > 0 ) {
		return true;
	}
	return false;
}

function upload_images( $images, $slideshow ) {
	global $wpdb, $user_ID;

	// TODO: Check quota

	$show = (int) $slideshow;
	if ( $slideshow == 0 ) return false;				// NO SLIDESHOW CHOSEN
	if ( !is_array( $images ) ) return false;		// NOT A $_FILES array

	return true;
}

function slideshow_get_images_preview( $id ) {
	global $wpdb;
	if ( slideshow_has_images( $id ) ) {
		$images = array();
		$sql = sprintf( "SELECT * FROM %s WHERE slideshow_id='%d' AND deleted='0'", $wpdb->prefix . 'ai_simpleview_images', $id );
		$result = $wpdb->get_results( $sql );
		//$updir = get_option( 'upload_path' ) . '/ai-simpleview/' . $id . '/' ;
		//$updir = get_upload_path().'ai-simpleview/'.$id.'/';
		$updir = get_option('fileupload_url').'/ai-simpleview/'.$id.'/';
		$count = 0;
		foreach ( $result as $r ) {
			$images[$count]['id'] = $r->id;
			$images[$count]['url'] = $updir . $r->file_name;
			$images[$count]['description'] = $r->description;
			$count++;
		}
		return $images;
	} else {
		return false;
	}

}

function get_upload_path() {

	$up = get_option('upload_path');
	if ( substr($up, 0, 1) != '/')  {
		$up = ABSPATH.$up;
	}
	if ( substr($up, -1) != '/' ) {
		$up = $up.'/';
	}
	return $up;
}

function aisv_get_preview($content, $blog, $w = 125, $h = 96 ) {
	global $wpdb;

	preg_match_all('/\[simpleview\ id=([0-9]{1,20})\]/', $content, $match);
	if ( !empty( $match[1][0] ) ) {
		$id = $match[1][0];
		$updir = get_option('fileupload_url').'/ai-simpleview/'.$id.'/';
		$updir = str_replace( get_bloginfo('url'), '', $updir ); 
		$table = $wpdb->prefix."ai_simpleview_images";
		$r = $wpdb->get_row( "SELECT * FROM $table WHERE slideshow_id='$id' AND deleted='0' LIMIT 1" );
		$image = $updir.urlencode($r->file_name);
		return "<img src='/images/timthumb".$image."&w=".$w."&h=".$h."&b=".$blog."&a=b' alt='slideshow' border='0' />";
	} else {
		return false;
	}
	

}
