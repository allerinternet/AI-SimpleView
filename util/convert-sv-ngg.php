<?php
// Load MyBackup for database backup, if we have class.
$db_backup = FALSE;
if (file_exists('MyBackup.php')) {
	require_once('MyBackup.php');
	$db_backup = TRUE;
} else {
	printf("\n\n!!! Did not find MyBackup. Can not backup your database in script. Do it manually.\n");
	sleep(2);
}

// wp-bootstrap
define('SITE_NAME', 'cafe.se.allerinternet.net');	// wp-bootstrap
$_SERVER['HTTP_HOST'] = SITE_NAME;
require_once("/var/www/wpmu/wp-load.php");
//require_once("galimp.php");

// For NextGen Gallery
define("ORIG_DIR", "/var/www/wpmu/wp-content/blogs.dir/%d/files/ai-simpleview/%d/");
define("OWNER", "nginx");
define("USER_ID", 1);
define("BASEDIR", "/var/www/wpmu/");
require_once("/var/www/wpmu/wp-content/plugins/nextgen-gallery/admin/functions.php");

/**
* Get images from a slideshow, returns an array
* @return array $images with images-data
*/
function slideshow_images($id = NULL)
{
	global $wpdb;
	if ($id == NULL) return FALSE;
	$images = array();
	$count = 0;
	$sql = sprintf("SELECT id, file_name FROM %s WHERE slideshow_id='%d'", $wpdb->prefix."ai_simpleview_images", $id);
	$q = $wpdb->get_results($sql);
	if (count($q) > 0) {
		foreach ($q as $r) {
			$images[$count]['ID'] = $r->id;
			$images[$count]['file_name'] = $r->file_name;
			$count++;
		}
		return $images;
	} else {
		return FALSE;
	}
}

/**
* Get a slideshow and return it in an array
* @return array $slideshow with the slideshow
*/
function slideshow_get($id = NULL)
{
	global $wpdb;
	if ($id == NULL) return FALSE;
	$slideshow = array();
	$sql = sprintf("SELECT ID, slide_name, created_time FROM %s WHERE deleted='0' AND ID='%d' LIMIT 1", $wpdb->prefix."ai_simpleview_slideshow", $id);
	$q = $wpdb->get_results($sql);
	if (count($q) > 0) {
		$slideshow['slide_ID'] = $q[0]->ID;
		$slideshow['slide_name'] = $q[0]->slide_name;
		$slideshow['created_time'] = $q[0]->created_time;
		$slideshow['images'] = slideshow_images($id);
		return $slideshow;
	} else {
		return FALSE;
	}
}

/**
*	Looks for slideshows in database and return an array containing post_ID and slideshow_ID
* @return array $slideshows with post_id and slideshow_id
*/
function slideshow_find()
{
	global $wpdb;
	$slideshows = array();
	$count = 0;
	$sql = sprintf("SELECT ID, post_content FROM %s WHERE post_type='post' AND post_content LIKE '%%simpleview%%'", $wpdb->posts);
	$q = $wpdb->get_results($sql);
	if (count($q) > 0) {

	foreach ($q as $r) {
		preg_match('/simpleview\ id=(\d+)/', $r->post_content, $match);
		$slideshows[$count]['post_id'] = $r->ID;
		$slideshows[$count]['slideshow_id'] = $match[1];
		$count++;
	}
		printf("Found %s matching posts in database.\n", count($q));
	} else {
		printf("Could not find any posts in blog. Try another one.\n");
	}
	return $slideshows;
}

/**
* Copies files from one folder to another, DOES NOT remove any files!
*
* @param $orig is the original folder (copy FROM)
* @param $target is the new folder (copy TO)
* @return $dir_abs holding the new folder absolute dirname
*/
function gallery_copy_files($orig = NULL, $target = NULL)
{
	global $wpdb;
	// TODO: Kolla om vi har korrekta indata
	if (($orig == NULL) or ($target == NULL)) {
		return FALSE;
	}
	$target = clean_str($target);
	$dir_rel = "wp-content/blogs.dir/".$wpdb->blogid."/files/gallery/".$target."/";
	$dir_abs = BASEDIR.$dir_rel;
	$dir_thumb = $dir_abs."/thumbs/";

	if (!@mkdir($dir_abs, 0777)) {
		printf("Could not create gallery directory: %s\n", $dir_abs);
		die();
	}
	//chown($dir_abs, OWNER);

	if (!@mkdir($dir_thumb, 0777)) {
		printf("Could not create thumbs dir: %s\n", $dir_thumb);
		die();	
	}
	//chown($dir_thumb, OWNER);

	$images = nggAdmin::scandir($orig);
	foreach ($images as $i) {
		$file = $orig.$i;
		$dest = $dir_abs.$i;
		copy($file, $dest);
		printf("Copied %s\n\t==> to %s\n\n", $file, $dest);
	}
	return $dir_abs;
}

/**
* Create a gallery in NextGen Gallery
* @param $name specifies the Gallery name
* @param $folder specifies the local diskfolder to import to gallery
* @return WHAT
*/
function create_gallery($name = NULL, $folder = NULL)
{
	global $wpdb;
	if (($name == NULL) or ($folder == NULL)) return FALSE;
	if (!$wpdb->nggallery) {
		printf("\n\nError: NextGen Gallery is not activated in blog.\n");
		die();
	}
	if (!is_dir($folder)) {
		printf("ERROR: Not a folder: %s\n", $folder);
		return FALSE;
	}


	// get list of images in folder, using NextGen Gallery method
	$gallery_images = nggAdmin::scandir($folder);
	if (empty($gallery_images)) {
		printf("Error: No images found in folder %s\n", $folder);
		return FALSE;
	}
	// set gallery name, using NextGen filter
	$gallery_name = apply_filters('ngg_gallery_name', $name);	

	// TODO: gallery_folder finns inte, måste skapa en katalog för bilderna
	$gallery_folder = $folder;
	$result = $wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->nggallery(name, path, title, author) VALUES(%s, %s, %s, %s)", $gallery_name, $gallery_folder, $gallery_name, USER_ID));
	if (!$result) {
		printf("Error: Could not create gallery %s", $gallery_name);
		return FALSE;
	}
	$gallery_id = $wpdb->insert_id;

	foreach ($gallery_images as $gi) {
		$result = $wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->nggpictures(galleryid, filename, alttext) VALUES(%s, %s, %s)", $gallery_id, $gi,$gi) );
		if (!$result) {
			printf("Error: Could not add image %s in gallery %s.\n", $gi, $gallery_id);
		} else {
			nggAdmin::create_thumbnail($wpdb->insert_id);
			printf("Info: Added image %s with id %s.\n", $gi, $wpdb->insert_id);
		}
	}
	return $gallery_id;
}

/**
* Cleans a string from illegal chars.
* @param $str is the string to clean
* @param $allowed is the allowed chars (regexp style)
* @param $replace is what we shall replace illegal chars with
* @return string with cleaned string data
*/
function clean_str($str, $allowed = "0-9a-zA-Z", $replace = "-")
{
	$allowed = "/[^$allowed]+/";
	return preg_replace($allowed, $replace, $str);
}

/**
* Update a post with the new allergal shortcode and change ID
* corresponding to the newly created NextGen Gallery
* @param $str contain the post (text)
*
*/
function update_post_content($str, $id_new)
{
	preg_match('/simpleview\ id=(\d+)/', $str, $match);
	$find = $match[0];
	$replace = "allergal id=".$id_new;
	$str = mb_ereg_replace($find, $replace, $str);
	return $str;
}

// This is main, here we actually run code. //

// Start with backup of WP post table.
if ($db_backup) {
	$mb = new MyBackup( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
	$mb->database_connect();

	// TODO: More tables needed?
	$mb->tables_prepare($wpdb->posts);

	$mb->tables_print();
	$data = $mb->database_backup('/tmp/convert-'.time());
}

// Find slideshows in blog
$slideshows = slideshow_find();

if($slideshows) {
	foreach($slideshows as $s) {
		$id_post = $s['post_id'];
		$id_slideshow = $s['slideshow_id'];
		printf("post: %d - slideshow_id: %d\n", $id_post, $id_slideshow);

		// Get the original post content (later used for changing short code (simpleview->allergal)
		$post = get_post($id_post);

		// Get the meta data from original slideshow
		$slide_show = slideshow_get($id_slideshow);
		$name_slideshow = $slide_show['slide_name'];

		// Copy image files from old to new directory
		$orig_dir = sprintf(ORIG_DIR, $wpdb->blogid, $id_slideshow);
		$copy_files = gallery_copy_files($orig_dir, $name_slideshow);
		if ($copy_files != NULL) {
			printf("Copied image files to: %s", $copy_files);
		}

		// Create new gallery in NextGen Gallery
		$new_gallery_id = create_gallery($name_slideshow, $copy_files);

		$update = wp_update_post( array('ID' => $id_post, 'post_content' => update_post_content($post->post_content, $new_gallery_id)) );		
		if ($update == 0) {
			printf("ERROR: Could not update post with id: %d\n", $id_post);
		}
	}
}

