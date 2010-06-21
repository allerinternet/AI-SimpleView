<?php
error_reporting(E_ALL);
define('WP_USE_THEMES', false);
require_once( "../../../wp-load.php");
require_once( 'SimpleView.class.php' );
wp();
global $wpdb;

$SV = new SimpleView();

$id = ( isset($_GET['id']) ) ? (int) $_GET['id'] : 0;
$image = ( isset($_GET['i']) ) ? (int) $_GET['i'] : 0;


$sql = sprintf( "SELECT * FROM %s WHERE slideshow_id='%d' AND deleted='0' LIMIT %d,1", $wpdb->prefix.'ai_simpleview_images', $id, $image);
$result = $wpdb->get_row( $sql );
$updir = get_option('fileupload_url').'/ai-simpleview/'.$id.'/';
$img_src = $updir . $result->file_name;
if ( $image > 0 ) {
	$img_nav_left = "<a href='presenter.php?id=".$id."&amp;i=".($image-1)."'>Previous</a>";
} else {
	$img_nav_left = "";
}


if ( $image < $SV->count_images( $id )-1 ) {
	$img_nav_right = "<a href='presenter.php?id=".$id."&amp;i=".($image+1)."'>Next</a>";
} else {
	$img_nav_right = "";
}
?>
<html>
<head>
	<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'></script> 
	
	<style>
	
	#container {
		position: relative;
		width: 430px;
	}
		
		div#simpleview ul#nav {
			display: block;
			list-style: none;
			position: relative; top: 100px; z-index: 20;
		}
		div#simpleview ul#nav li#prev {
			float: left; margin: 0 0 0 -35px;
		}
		div#simpleview ul#nav li#next {
			float: right; margin: 0 5px 0 0;
		}
		div#simpleview ul#nav li a {
			display: block; width: 27px; height: 48px; text-indent: -9999px;
		}
		div#simpleview ul#nav li#prev a {
			background: url('images/nav_left.png');
		}
		div#simpleview ul#nav li#prev a:hover {
			background: url('images/on_nav_left.png');
		}
		div#simpleview ul#nav li#next a {
			background: url('images/nav_right.png');
		}
		div#simpleview ul#nav li#next a:hover {
			background: url('images/on_nav_right.png');
		}
	
		#header {
			background: url('images/slidetop.png') no-repeat;
			width: 430px;
			height: 30px;
			text-align: center;
			padding-top: 4px;
			color: #eee;
			font-family: verdana;
			font-size: 12px;
			font-weight: bold;
			z-index: 20;
			position: relative;
		}
		
		#slideimg {
			position: absolute;
			top: 24px;
		}
	</style>
</head>
<body style="margin: 0; padding: 0;">
	<div id="container">
		<div id="header">
			<span style="text-transform: uppercase;"><?php echo $result->description; ?></span> ( Bild <?php echo $image+1; ?>/<?php echo $SV->count_images( $id );?> )
		</div>
		<div id='simpleview'>
			<ul id="nav">
				<li id="prev"><?php echo $img_nav_left; ?></li>
				<li id="next"><?php echo $img_nav_right; ?></li>
			</ul>
			<div id="slideimg">
				<img src="<?php echo $img_src; ?>" onload="try{window.parent.setSize(window.self, this.height+60);} catch (err){}" style='width: 430px; border: 0' border='0' />
			</div>
		</div>
	</div>
</body>
</html>
