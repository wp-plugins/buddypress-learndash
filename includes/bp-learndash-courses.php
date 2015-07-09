<?php

function bp_learndash_courses_page(){
    add_action( 'bp_template_title', 'bp_learndash_courses_expand' );
    add_action( 'bp_template_title', 'bp_learndash_courses_page_title' );
    add_action( 'bp_template_content', 'bp_learndash_courses_page_content' );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

function bp_learndash_courses_expand(){
    ?>
    <div class="expand_collapse"><a href="#" onClick="return flip_expand_all('#course_list');"><?php _e('Expand All', 'learndash'); ?></a> <span class="sep"><?php _e('/', 'boss-learndash'); ?></span> <a href="#" onClick="return flip_collapse_all('#course_list');"><?php _e('Collapse All','learndash'); ?></a></div>
    <?php
}

function bp_learndash_courses_page_title(){
    $title = __( 'Registered Courses', 'buddypress-learndash' );
	echo apply_filters( 'bp_learndash_courses_page_title',$title );
}

function bp_learndash_courses_page_content(){
    do_action('template_notices');
    bp_learndash_load_template( 'courses' );
}