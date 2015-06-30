<?php

/**
 * courses name can be changed from here
 * @return string
 */
function bp_learndash_profile_courses_name()
{
    return __( 'Courses', 'buddypress-learndash' );
}

/**
 * courses slug can be changed from here
 * @return string
 */
function bp_learndash_profile_courses_slug()
{
    return __( 'courses', 'buddypress-learndash' );
}

/**
 * My courses name can be changed from here
 * @return string
 */
function bp_learndash_profile_my_courses_name()
{
    return __( 'My Courses', 'buddypress-learndash' );
}

/**
 * My courses slug can be changed from here
 * @return string
 */
function bp_learndash_profile_my_courses_slug()
{
    return __( 'my-courses', 'buddypress-learndash' );
}

function bp_learndash_profile_create_courses_name() {
    return __( 'Create a Course', 'buddypress-learndash' );
}

function bp_learndash_profile_create_courses_slug() {
    return __( 'create-courses', 'buddypress-learndash' );
}

function bp_learndash_get_nav_link($slug, $parent_slug=''){
    $displayed_user_id = bp_displayed_user_id();
    $user_domain = ( ! empty( $displayed_user_id ) ) ? bp_displayed_user_domain() : bp_loggedin_user_domain();
    if(!empty($parent_slug)){
        $nav_link = trailingslashit( $user_domain . $parent_slug .'/'. $slug );
    }else{
        $nav_link = trailingslashit( $user_domain . $slug );
    }
    return $nav_link;
}

function bp_learndash_adminbar_nav_link($slug, $parent_slug=''){
    $user_domain = bp_loggedin_user_domain();
    if(!empty($parent_slug)){
        $nav_link = trailingslashit( $user_domain . $parent_slug .'/'. $slug );
    }else{
        $nav_link = trailingslashit( $user_domain . $slug );
    }
    return $nav_link;
}

function bp_learndash_get_all_users(){
    global $wpdb;
    $user_ids = array();
    $user_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
    return $user_ids;
}

function bp_learndash_sql_member_type_id($type_name){
    global $wpdb;
    $type_id = $wpdb->get_col("SELECT t.term_id FROM {$wpdb->prefix}terms t INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id WHERE t.slug = '".$type_name."' AND  tt.taxonomy = 'bp_member_type' ");
    return !isset($type_id[0]) ? '' : $type_id[0];
}

function bp_learndash_sql_members_by_type($type_id){
    global $wpdb;
    $student_ids = $wpdb->get_col("SELECT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->prefix}term_relationships r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = ".$type_id);
    return $student_ids;
}

function bp_learndash_sql_members_count_by_type($type_id){
    global $wpdb;
    $student_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} u INNER JOIN {$wpdb->prefix}term_relationships r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = ".$type_id);
    return $student_count;
}

function bp_learndash_members_count_by_type($type_name){
    $type_id = bp_learndash_sql_member_type_id($type_name);
    $student_ids = bp_learndash_sql_members_by_type($type_id);
    $members_count = count($student_ids);
    return $members_count;
}

function bp_learndash_get_user_profile($user_id=''){
    if(isset($user_id))
        $user_id = $user_id;
    else
    {
        $current_user = wp_get_current_user();

        if(empty($current_user->ID))
            return;

        $user_id = $current_user->ID;
    }
    $user_courses = ld_get_mycourses($user_id);
    if(empty($current_user))
        $current_user = get_user_by("id", $user_id);
    $usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );
    $quiz_attempts_meta = empty($usermeta) ?  false : $usermeta;
    $quiz_attempts  = array();
    if(!empty($quiz_attempts_meta))
        foreach($quiz_attempts_meta as $quiz_attempt) {
            $c = learndash_certificate_details($quiz_attempt['quiz'], $user_id);
            $quiz_attempt['post'] = get_post( $quiz_attempt['quiz'] );
            $quiz_attempt["percentage"]  = !empty($quiz_attempt["percentage"])? $quiz_attempt["percentage"]:(!empty($quiz_attempt["count"])? $quiz_attempt["score"]*100/$quiz_attempt["count"]:0  );

            if($user_id == get_current_user_id() && !empty($c["certificateLink"]) && ((isset($quiz_attempt['percentage']) && $quiz_attempt['percentage'] >= $c["certificate_threshold"] * 100)))
                $quiz_attempt['certificate'] = $c;
            $quiz_attempts[learndash_get_course_id($quiz_attempt['quiz'])][] = $quiz_attempt;
        }
    return $user_courses;
}

/**
 * Get Course members
 * @param type $course_id
 * @return array
 */
function bp_learndash_get_course_members( $course_id ) {
	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	
	if ( !empty( $meta['sfwd-courses_course_access_list'] ) ) 
		$course_access_list = explode( ',', $meta['sfwd-courses_course_access_list'] );
	else 
		$course_access_list = array();
	
	return $course_access_list;
}

/**
 * Add members to groups
 * @param type $course_id
 * @param type $group_id
 */
function bp_learndash_add_members_group( $course_id, $group_id ) {

	$course_students = bp_learndash_get_course_members( $course_id );
	
	if ( empty( $course_students ) ) {
		return;
	}
	if ( is_array( $course_students ) ) {
		foreach ( $course_students as $course_students_id ) {
			groups_join_group( $group_id, $course_students_id );
		}
	} else {
		groups_join_group( $group_id, $course_students );
	}
}

/**
 * Removes members from group
 * @param type $course_id
 * @param type $group_id
 */
function bp_learndash_remove_members_group( $course_id, $group_id ) {

	$course_students = bp_learndash_get_course_members( $course_id );

	if ( empty( $course_students ) ) {
		return;
	}
	if ( is_array( $course_students ) ) {
		foreach ( $course_students as $course_students_id ) {
			groups_remove_member( $course_students_id, $group_id );
		}
	} else {
		groups_remove_member( $course_students, $group_id );
	}
}

/**
 * Add course teacher as group admin
 * @param type $course_id
 * @param type $group_id
 */
 function bp_learndash_course_teacher_group_admin( $course_id, $group_id ) {

	$teacher = get_post_field( 'post_author', $course_id );
	groups_join_group( $group_id, $teacher );
	$member = new BP_Groups_Member( $teacher, $group_id );
	$member->promote( 'admin' );

 }

/**
 * Inserts a new forum and attachs it to the group
 * @param type $group_id
 */
function bp_learndash_attach_forum( $group_id ) {

	if ( class_exists('bbPress') && bp_is_group_forums_active() ) {

		$group = groups_get_group( array( 'group_id' => $group_id ) );
		if ( $group->enable_forum == '0' ) {
			$forum_id = bbp_insert_forum( array( 'post_title' => $group->name ) );
			bbp_add_forum_id_to_group( $group_id, $forum_id );
			bbp_add_group_id_to_forum( $forum_id, $group_id );
			bp_learndash_enable_disable_group_forum( '1', $group_id );
		}

	}
}

/**
 * Group forum enable/disable
 * @param type $enable
 * @param type $group_id
 */
function bp_learndash_enable_disable_group_forum( $enable, $group_id ) {
	$group = groups_get_group( array( 'group_id' => $group_id ) );
	$group->enable_forum = $enable;
	$group->save();
}

/**
* alter group status
* @param type $group_id
*/
function bp_learndash_alter_group_status( $group_id ) {
   $group = groups_get_group( array( 'group_id' => $group_id ) );

   if ( 'public' == $group->status ) {
	   $group->status = 'private';

   } elseif ( 'hidden' == $group->status ) {
	   $group->status = 'hidden';
   }
   $group->save();

}

/**
 * Update group avatar with course avatar
 * @global type $bp
 * @param type $course_id
 * @param type $group_id
 */
function bp_learndash_update_group_avatar( $course_id, $group_id ) {

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	$group_avatar = bp_get_group_has_avatar( $group_id );
	if ( ! empty( $group_avatar ) ) {
		return;
	}

	$attached_media_id = get_post_thumbnail_id( $course_id, $group_id );
	
	if ( empty($attached_media_id) ) {
		return;
	}
	
	$attachment_src = wp_get_attachment_image_src( $attached_media_id, 'full' );

	$wp_upload_dir = wp_upload_dir();
	$tfile = uniqid() . '.jpg';
	file_put_contents( $wp_upload_dir[ "basedir" ] . "/" . $tfile, file_get_contents( $attachment_src[0] ) );

	$temp_file = download_url( $wp_upload_dir[ "baseurl" ] . "/" . $tfile, 5 );

	if ( ! is_wp_error( $temp_file ) ) {

		// array based on $_FILE as seen in PHP file uploads
		$file = array(
			'name' => basename( $tfile ), // ex: wp-header-logo.png
			'type' => 'image/png',
			'tmp_name' => $temp_file,
			'error' => 0,
			'size' => filesize( $temp_file ),
		);

		$_FILES[ "file" ] = $file;
	}

	global $bp;
	if ( ! isset( $bp->groups->current_group ) || ! isset( $bp->groups->current_group->id ) ) {
		//required for groups_avatar_upload_dir function
		$bp->groups->current_group = new stdClass();
		$bp->groups->current_group->id = $group_id;
	}

	if ( ! isset( $bp->avatar_admin ) )
		$bp->avatar_admin = new stdClass ();

	$original_action = $_POST[ 'action' ];
	$_POST[ 'action' ] = 'bp_avatar_upload';
	// Pass the file to the avatar upload handler
	if ( bp_core_avatar_handle_upload( $_FILES, 'groups_avatar_upload_dir' ) ) {
		//avatar upload was successful
		//do cropping
		list($width, $height, $type, $attr) = getimagesize( $bp->avatar_admin->image->url );
		$args = array(
			'object' => 'group',
			'avatar_dir' => 'group-avatars',
			'item_id' => $bp->groups->current_group->id,
			'original_file' => bp_get_avatar_to_crop_src(),
			'crop_x' => 0,
			'crop_y' => 0,
			'crop_h' => $height,
			'crop_w' => $width
		);

		bp_core_avatar_handle_crop( $args );
	}
	$_POST[ 'action' ] = $original_action;
}

/**
 * Record an activity item
 */
function bp_learndash_record_activity( $args = '' ) {
    global $bp;

    if ( !function_exists( 'bp_activity_add' ) ) return false;

    $defaults = array(
        'id' => false,
        'user_id' => $bp->loggedin_user->id,
        'action' => '',
        'content' => '',
        'primary_link' => '',
        'component' => $bp->profile->id,
        'type' => false,
        'item_id' => false,
        'secondary_item_id' => false,
        'recorded_time' => gmdate( "Y-m-d H:i:s" ),
        'hide_sitewide' => false
    );

    $r = wp_parse_args( $args, $defaults );
    extract( $r );

    $activity_id = bp_activity_add( array(
        'id' => $id,
        'user_id' => $user_id,
        'action' => $action,
        'content' => $content,
        'primary_link' => $primary_link,
        'component' => $component,
        'type' => $type,
        'item_id' => $item_id,
        'secondary_item_id' => $secondary_item_id,
        'recorded_time' => $recorded_time,
        'hide_sitewide' => $hide_sitewide
    ) );
	
	bp_activity_add_meta( $activity_id, 'bp_learndash_group_activity_markup', 'true' );
	
	return $activity_id;
}

	/**
	* Learndash activity filter
	* @param type $has_activities
	* @param type $activities
	* @return type array
	*/
	function bp_learndash_activity_filter( $has_activities, $activities ) {

	   if ( bp_current_component() != 'activity' ) {
		   return $has_activities;
	   }
	   $remove_from_stream = false;

	   foreach ( $activities->activities as $key => $activity ) {

		   if ( $activity->component == 'groups' ) {
			   $act_visibility = bp_activity_get_meta( $activity->id, 'bp_learndash_group_activity_markup',true );
			   if ( !empty( $act_visibility ) ) {
				   $remove_from_stream = true;
			   }
		   }

		   if ( $remove_from_stream && isset( $activities->activity_count ) ) {
			   $activities->activity_count = $activities->activity_count - 1;
			   unset( $activities->activities[ $key ] );
			   $remove_from_stream = false;
		   }
	   }

	   $activities_new = array_values( $activities->activities );
	   $activities->activities = $activities_new;

	   return $has_activities;
	}

	add_action( 'bp_has_activities', 'bp_learndash_activity_filter', 110, 2 );