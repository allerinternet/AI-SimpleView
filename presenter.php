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
	$img_nav_left = "<a href='presenter.php?id=".$id."&amp;i=".($image-1)."' onclick=''><img src='images/prev.png' alt='.' style='border: 0' /></a>";
} else {
	$img_nav_left = " ";
}


if ( $image < $SV->count_images( $id )-1 ) {
	$img_nav_right = "<a href='presenter.php?id=".$id."&amp;i=".($image+1)."' onclick=''><img src='images/next.png' alt='.' style='border: 0' /></a>";
} else {
	$img_nav_right = " ";
}
?>
<html>
<head>
<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'></script> 
<link rel="stylesheet" type="text/css" href="presenter.css" />

<script language="JavaScript1.3">
function ReloadAds () {
    var foo = window.parent.document.getElementById('iframe-ads');
    foo.contentWindow.location.reload(true);
    var bar = window.parent.document.getElementById('header-ads');
    bar.contentWindow.location.reload(true);
    var tile14 = window.parent.document.getElementById('ads-tile14');
    var tile15 = window.parent.document.getElementById('ads-tile15');
    tile14.contentWindow.location.reload(true);
    tile15.contentWindow.location.reload(true);
}

$.getDocHeight = function() {
	return Math.max(
		$(document).height(),
		$(window).height(),
		document.documentElement.clientHeight
	);
};

$('document').ready(function() {

	var slide = window.parent.document.getElementById('slideframe');
	var pic = 0;

	function AssignFrameHeight() {
		if ( $.browser.msie ) {
			pic = this.document.body.scrollHeight-10;
		} else {
			pic = this.document.body.offsetHeight;
		}
		slide.style.height = pic + 'px';
	}

	if ( $.browser.safari || $.browser.opera ) {
		$('#slideframe', window.parent.document).load(function() {
			setTimeout( AssignFrameHeight, 0 );
		});
		var iSource = this.document.src;
		this.document.src = '';
		this.document.src = iSource;
	} else {
		$('#slideframe', window.parent.document).load(function() { 
			AssignFrameHeight();
		});
	}


});

var D = window.parent.document.getElementById('slideframe');
D.style.height = 250 +'px';
</script>

</head>
<body onload="javascript: ReloadAds();" style="margin: 0; padding: 0;">
<div id="container">
		<div id="header">
			<table id="tblhead" border="0">
			<tr>
				<td id="navleft"><?php echo $img_nav_left; ?></td>
				<td id="navcenter"><?php echo $SV->get_slide_name($id); ?> ( <span style="text-transform: none">Bild</span> <?php echo $image+1; ?>/<?php echo $SV->count_images( $id );?> )</td>
			<td id="navright"><?php echo $img_nav_right; ?></td>
			</table>
		</div>
		<div id='simpleview'>
			<div id="slideimg">
		<?php
			if ( $image < $SV->count_images($id)-1 ) {
				echo "<a href='presenter.php?id=".$id."&amp;i=".($image+1)."' onclick=''>";
			}
		?><img src="<?php echo $img_src; ?>" onload="try{window.parent.setSize(window.self, this.height+60);} catch (err){}" style='z-index: 9999; width: 430px; border: 0' border='0' /><?php
			if ( $image < $SV->count_images($id)-1 ) {
				echo "</a>";
			}
		?></div>
		</div>
	<?php
		if ( strlen($result->description) ) {
			echo "<div id='slidedesc'>".$result->description."</div>\n";
		}
	?>
</div>


</body>
</html>
