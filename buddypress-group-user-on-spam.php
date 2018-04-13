<?php
/*
Plugin Name: buddypress-group-user-on-spam
Version: 1.0
Description: Buddypress addon to retreve the user groups when un-spam the user after spam.
Author: Subair T C
Author URI:
Plugin URI:
Text Domain: buddypress-group-user-on-spam
Domain Path: /languages
*/


/* Runs when plugin is activated */
register_activation_hook(__FILE__,'buddypress_group_user_on_spam_install'); 
function buddypress_group_user_on_spam_install() {
    global $wpdb;
	
	// CREATE groups table bk
	$table_name = $wpdb->prefix.'bp_groups_bk';
	$query  = "CREATE TABLE IF NOT EXISTS $table_name ( `id` BIGINT(20) NOT NULL AUTO_INCREMENT , `creator_id` BIGINT(20) NOT NULL , `name` VARCHAR(100) NOT NULL , `slug` VARCHAR(200) NOT NULL , `description` LONGTEXT NOT NULL , `status` VARCHAR(10) NOT NULL , `enable_forum` TINYINT(1) NOT NULL DEFAULT '1' , `date_created` DATETIME NOT NULL , `auto_join` BINARY(1) NOT NULL DEFAULT 0x0 , `max_members` INT(4) NOT NULL DEFAULT '20' , `parent_id` BIGINT(20) NOT NULL , PRIMARY KEY (`id`))";
	$wpdb->query($query);
	
	// create the group members table bk
	$table_name = $wpdb->prefix.'bp_groups_members_bk';
	$query  = "CREATE TABLE IF NOT EXISTS $table_name ( `id` BIGINT(20) NOT NULL AUTO_INCREMENT , `group_id` BIGINT(20) NOT NULL , `user_id` BIGINT(20) NOT NULL , `inviter_id` BIGINT(20) NOT NULL , `is_admin` TINYINT(1) NOT NULL DEFAULT '0' , `is_mod` TINYINT(1) NOT NULL DEFAULT '0' , `user_title` VARCHAR(100) NOT NULL , `date_modified` DATETIME NOT NULL , `comments` LONGTEXT NOT NULL , `is_confirmed` TINYINT(1) NOT NULL DEFAULT '0' , `is_banned` TINYINT(1) NOT NULL DEFAULT '0' , `invite_sent` TINYINT(1) NOT NULL DEFAULT '0' , PRIMARY KEY (`id`))";
	$wpdb->query($query);
	
}


add_action( 'make_spam_user', 'guos_make_spam_user', 10, 2 ); 
function guos_make_spam_user( $user_id ) {
	global $wpdb;
	$group_table 		= $wpdb->prefix.'bp_groups';
	$group_table_bk 	= $wpdb->prefix.'bp_groups_bk';
	$groups_members		= $wpdb->prefix.'bp_groups_members';
	$groups_members_bk 	= $wpdb->prefix.'bp_groups_members_bk';
	$groups = $wpdb->get_results( "SELECT * FROM {$group_table} WHERE creator_id = $user_id ",ARRAY_A );
	
	foreach ( $groups as $group ) {
		$wpdb->insert( $group_table_bk,$group );
		$group_id = $group['id'];
		$group_members = $wpdb->get_results("SELECT * FROM {$groups_members} WHERE group_id = $group_id ",ARRAY_A);
		foreach ( $group_members as $group_member ) {
			$wpdb->insert( $groups_members_bk,$group_member );
		}
	}
	
	$group_members = $wpdb->get_results("SELECT * FROM {$groups_members} WHERE user_id = $user_id ",ARRAY_A);
	foreach ( $group_members as $group_member ) {
		$wpdb->insert( $groups_members_bk,$group_member );
	}
	
}


add_action( 'make_ham_user', 'guos_make_ham_user', 10, 2 ); 
function guos_make_ham_user( $user_id ) {
	global $wpdb;
	$group_table 		= $wpdb->prefix.'bp_groups';
	$group_table_bk 	= $wpdb->prefix.'bp_groups_bk';
	$groups_members		= $wpdb->prefix.'bp_groups_members';
	$groups_members_bk 	= $wpdb->prefix.'bp_groups_members_bk';
	
	$groups = $wpdb->get_results( "SELECT * FROM {$group_table_bk} WHERE creator_id = $user_id ",ARRAY_A );

	foreach ( $groups as $group ) {
		$wpdb->insert( $group_table,$group );
		$group_id = $group['id'];
		$group_members = $wpdb->get_results("SELECT * FROM {$groups_members_bk} WHERE group_id = $group_id ",ARRAY_A);
		foreach ( $group_members as $group_member ) {
			$user_status = guos_is_user_spam( $group_member['user_id'] );
			if( ! $user_status  ) {
				
				$wpdb->insert( $groups_members,$group_member );
				$where = array(
					id	=>	$group_member['id']
				);
				$wpdb->delete( $groups_members_bk, $where );
			}	
		}
	}
	
	$group_members = $wpdb->get_results("SELECT * FROM {$groups_members_bk} WHERE user_id = $user_id ",ARRAY_A);
	foreach ( $group_members as $group_member ) {
		$wpdb->insert( $groups_members,$group_member );
	}
	
	$where = array(
		'creator_id' => $user_id
	);
	$wpdb->delete( $group_table_bk, $where );
	$where = array(
		'user_id' => $user_id
	);
	$wpdb->delete( $groups_members_bk, $where );
}


function guos_is_user_spam( $user_id = ''){
	if( !$user_id ) {
		return -1;
	}
	global $wpdb;
	
	$table = $wpdb->prefix.'users';
	$user_status  = $wpdb->get_row( $wpdb->prepare( "SELECT user_status FROM {$table} WHERE ID = %d", $user_id ) );
	return $user_status->user_status; 
}


function guos_make_spam_group( $group_id ) {
	global $wpdb;
	$group_table 		= $wpdb->prefix.'bp_groups';
	$group_table_bk 	= $wpdb->prefix.'bp_groups_bk';
	$groups_members		= $wpdb->prefix.'bp_groups_members';
	$groups_members_bk 	= $wpdb->prefix.'bp_groups_members_bk';
	$group = $wpdb->get_results( "SELECT * FROM {$group_table} WHERE id = $group_id ",ARRAY_A );

	$wpdb->insert( $group_table_bk,$group );
	$group_id = $group['id'];
	$group_members = $wpdb->get_results("SELECT * FROM {$groups_members} WHERE group_id = $group_id ",ARRAY_A);
	foreach ( $group_members as $group_member ) {
		$wpdb->insert( $groups_members_bk,$group_member );
	}
}
function guos_make_ham_group( $group_id ) {
	$group_table 		= $wpdb->prefix.'bp_groups';
	$group_table_bk 	= $wpdb->prefix.'bp_groups_bk';
	$groups_members		= $wpdb->prefix.'bp_groups_members';
	$groups_members_bk 	= $wpdb->prefix.'bp_groups_members_bk';
	$group = $wpdb->get_results( "SELECT * FROM {$group_table_bk} WHERE id = $group_id ",ARRAY_A );
	
	$wpdb->insert( $group_table,$group );
	$group_id = $group['id'];
	$group_members = $wpdb->get_results("SELECT * FROM {$groups_members_bk} WHERE group_id = $group_id ",ARRAY_A);
	foreach ( $group_members as $group_member ) {
		$wpdb->insert( $groups_members,$group_member );
	}
}