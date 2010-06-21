<?php


class SimpleView {
	
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
			$updir = get_upload_path().'ai-simpleview/'.$id.'/';
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

	/**
	* Returns the number of images in a slide show.
	*
	* @param string $id
	* @return void
	* @author Aller Internet, Jonas Björk
	*/
	function count_images( $id ) {
		global $wpdb;
		$sql = sprintf( "SELECT COUNT(*) AS cnt FROM %s WHERE slideshow_id='%d' AND deleted='0'", $wpdb->prefix.'ai_simpleview_images', $id);
		$row = $wpdb->get_row( $sql );
		return $row->cnt;
	}
	
	function get_slide_name( $id, $len = 15 ) {
		global $wpdb;
		$sql = sprintf("SELECT slide_name FROM %s WHERE ID=%d AND deleted='0' LIMIT 1", $wpdb->prefix.'ai_simpleview_slideshow', $id);
		$row = $wpdb->get_row($sql);
		if ( mb_strlen($row->slide_name) > $len ) {
			return mb_substr( $row->slide_name, 0, $len )."...";
		} else {
			return $row->slide_name;
		}
		
	}

	/**
	* Returns the upload path.
	*
	* @param void No need to send data here.
	* @return string The upload path with ending /
	* @author Aller Internet, Jonas Björk
	*/
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
	
}


?>
