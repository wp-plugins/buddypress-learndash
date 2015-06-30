<?php

function bp_learndash_courses_page(){
    add_action( 'bp_template_title', 'bp_learndash_courses_page_title' );
    add_action( 'bp_template_content', 'bp_learndash_courses_page_content' );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

function bp_learndash_courses_page_title(){
    _e( 'Registered Courses', 'buddypress-learndash' );
}

function bp_learndash_courses_page_content(){
    do_action('template_notices');
    bp_learndash_load_template( 'courses' );
}