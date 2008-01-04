<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        upgrade-1.4.0_01.php
 * Began:       Sun Nov  4 2007
 * Date:        $Date$
 * -----------------------------------------------------------------------
 * @author      $Author$
 * @copyright   2002-2007 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     upgrade
 * @version     $Rev$
 */

if ( !defined('EQDKP_INC') )
{
    header('HTTP/1.0 404 Not Found');
    exit;
}

// FIXME: This needs to change as we decide our beta-testing plan
$VERSION = '1.4.0 B1';

if ( class_exists('Upgrade') && Upgrade::should_run($VERSION) )
{
    global $db, $eqdkp;
    
    // Make sure the files that were deleted for this version are, in fact, missing
    Upgrade::assert_deleted(array(
        '/ChangeLog',
        // '/COPYING',
        '/INSTALL',
        // '/LICENSE',
        '/README',
        '/install.php',
        '/upgrade.php',
        '/admin/mm/mm_addmember.php.NEW.gz',
        '/admin/soap/',
        '/admin/config.php',
        '/admin/fix_negative.php',
        '/admin/lua.php',
        '/admin/lua_config.php',
        '/admin/DAoC.php',
        '/admin/Everquest.php',
        '/admin/Everquest2-german.sql',
        '/admin/Everquest2.php',
        '/admin/WoW-english.php',
        '/admin/WoW-german.php',
        '/admin/WoW.php',
        '/dbal/',
        '/games/Everquest.php',
        '/games/WoW-german.php',
        '/games/WoW.php',
        '/includes/lib/',
        '/includes/file_upload.php',
        '/includes/nusoap.php',
        '/images/arrow.gif',
        '/images/error.gif',
        '/images/glyphs/',
        '/images/statbox.jpg',
        '/images/view.gif',
        '/templates/default/admin/config.html',
        '/templates/default/admin/lua.html',
        '/templates/default/admin/menu.html',
    ));
    
    Upgrade::prepare_uniquekey('auth_users',     array('user_id', 'auth_id'));
    Upgrade::prepare_uniquekey('raid_attendees', array('raid_id', 'member_name'));
    
    // Determine what the currently installed game is
    $sql = "SELECT * 
            FROM __config
            WHERE `config_name` = 'default_game'";
    $result = $db->query($sql);
    $game_name = $db->fetch_record($result);
    
    Upgrade::execute(array(
        // Change auth_users to use a UNIQUE index
        "ALTER TABLE __auth_users DROP INDEX `user_id`",
        "ALTER TABLE __auth_users DROP INDEX `auth_id`",
        "ALTER TABLE __auth_users ADD UNIQUE `user_auth` ( `user_id` , `auth_id` )",

        // Change raid_attendees to use a UNIQUE index
        "ALTER TABLE __raid_attendees DROP INDEX `raid_id`",
        "ALTER TABLE __raid_attendees DROP INDEX `member_name`",
        "ALTER TABLE __raid_attendees ADD UNIQUE `raid_member` ( `raid_id` , `member_name` )",

        // Update the size of all of our float values to larger doubles, since the 1.3 upgrade failed at this
        "ALTER TABLE __adjustments CHANGE `adjustment_value` `adjustment_value` DOUBLE( 11, 2 ) NOT NULL DEFAULT '0.00",
        "ALTER TABLE __events CHANGE `event_value` `event_value` DOUBLE( 11, 2 ) NOT NULL DEFAULT '0.00",
        "ALTER TABLE __items CHANGE `item_value` `item_value` DOUBLE( 11, 2 ) NOT NULL DEFAULT '0.00",
        "ALTER TABLE __members CHANGE `member_earned` `member_earned` DOUBLE( 11, 2 ) NOT NULL DEFAULT '0.00",
        "ALTER TABLE __members CHANGE `member_spent` `member_spent` DOUBLE( 11, 2 ) NOT NULL DEFAULT '0.00",
        "ALTER TABLE __members CHANGE `member_adjustment` `member_adjustment` DOUBLE( 11, 2 ) NOT NULL DEFAULT '0.00",
        "ALTER TABLE __raids CHANGE `raid_value` `raid_value` DOUBLE( 11, 2 ) NOT NULL DEFAULT '0.00'",
        
        // Update the default game values
        "INSERT INTO __config (`config_name`, `config_value`) VALUES ('current_game_name', '" . $game_name . "')",
        "UPDATE __config SET `config_name` = 'current_game' WHERE `config_name` = 'default_game' LIMIT 1",
        
        // New session and user management
        "DELETE FROM __config WHERE (config_name IN ('session_cleanup','cookie_domain','cookie_path')", // Unused config values
        "ALTER TABLE __users CHANGE `username` `user_name` VARCHAR( 30 ) NOT NULL", // username to user_name
        "ALTER TABLE __sessions CHANGE `session_user_id` `user_id` SMALLINT( 5 ) NOT NULL DEFAULT '-1'", // session_user_id to user_id
        "ALTER TABLE __users CHANGE `user_password` `user_password` VARCHAR( 40 ) NOT NULL", // Increase user_password length to 40, for SHA1 hashes
        "ALTER TABLE __users CHANGE `user_newpassword` `user_newpassword` VARCHAR( 40 ) NULL DEFAULT NULL",
        "ALTER TABLE __users ADD `user_salt` VARCHAR( 40 ) NOT NULL AFTER `user_password`",
        "ALTER TABLE __sessions DROP INDEX `session_current`",
        "ALTER TABLE __sessions DROP `session_last_visit`",
    ));
    
    // Generate an installation-specific unique salt value
    $eqdkp->config_set('auth_salt', generate_salt());
    
    // Finalize
    Upgrade::set_version($VERSION);
    Upgrade::progress($VERSION);
}