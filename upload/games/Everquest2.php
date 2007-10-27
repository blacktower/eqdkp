<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        Everquest2.php
 * Began:       Fri May 13 2005
 * Date:        $Date$
 * -----------------------------------------------------------------------
 * @author      $Author$
 * @copyright   2002-2007 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev$
 */

if ( !defined('EQDKP_INC') )
{
    header('HTTP/1.0 404 Not Found');
    exit;
}

class Manage_Game extends EQdkp_Admin
{
    function do_it()
    {
        global $db, $eqdkp, $user;
        global $SID, $dbname;

        parent::eqdkp_admin(); 

        $queries = array(
            "UPDATE __members SET member_level = 50 WHERE member_level > 50;",
            "ALTER TABLE __members MODIFY member_level tinyint(2) NOT NULL default '50';",

            "TRUNCATE TABLE __classes;",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (0, 'Unknown', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (1, 'Fighter', 'Heavy',1,9);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (2, 'Scout', 'Medium',1,9);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (3, 'Mage', 'VeryLight',1,9);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (4, 'Priest', 'Heavy',1,9);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (5, 'Warrior', 'Heavy',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (6, 'Crusader', 'Heavy',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (7, 'Brawler', 'Light',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (8, 'Bruiser', 'Light',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (9, 'Monk', 'Light',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (10, 'Berserker', 'Heavy',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (11, 'Guardian', 'Heavy',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (12, 'Paladin', 'Heavy',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (13, 'Shadowknight', 'Heavy',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (14, 'Enchanter', 'VeryLight',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (15, 'Sorcerer', 'VeryLight',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (16, 'Summoner', 'VeryLight',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (17, 'Illusionist', 'VeryLight',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (18, 'Coercer', 'VeryLight',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (19, 'Wizard', 'VeryLight',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (20, 'Warlock', 'VeryLight',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (21, 'Necromancer', 'VeryLight',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (22, 'Conjuror', 'VeryLight',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (23, 'Cleric', 'Heavy',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (24, 'Druid', 'Light',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (25, 'Shaman', 'Medium',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (26, 'Templar', 'Heavy',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (27, 'Inquisitor', 'Heavy',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (28, 'Warden', 'Light',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (29, 'Fury', 'Light',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (30, 'Defiler', 'Medium',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (31, 'Mystic', 'Medium',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (32, 'Rogue', 'Medium',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (33, 'Bard', 'Medium',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (34, 'Predator', 'Medium',10,19);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (35, 'Swashbuckler', 'Medium',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (36, 'Brigand', 'Medium',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (37, 'Dirge', 'Medium',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (38, 'Troubador', 'Medium',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (39, 'Assassin', 'Medium',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (40, 'Ranger', 'Medium',20,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (41, 'Craftsmen', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (42, 'Scholar', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (43, 'Outfitter', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (44, 'Provisioner', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (45, 'Woodworker', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (46, 'Carpenter', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (47, 'Armorer', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (48, 'Weaponsmith', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (49, 'Tailor', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (50, 'Jeweler', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (51, 'Sage', 'Heavy',1,99);",
            "INSERT INTO __classes (class_id, class_name, class_armor_type, class_min_level, class_max_level) VALUES (52, 'Alchemist', 'Heavy',1,99);",

            "TRUNCATE TABLE __races;",
            "INSERT INTO __races (race_id, race_name) VALUES (0, 'Unknown');",
            "INSERT INTO __races (race_id, race_name) VALUES (1, 'Gnome');",
            "INSERT INTO __races (race_id, race_name) VALUES (2, 'Human');",
            "INSERT INTO __races (race_id, race_name) VALUES (3, 'Barbarian');",
            "INSERT INTO __races (race_id, race_name) VALUES (4, 'Dwarf');",
            "INSERT INTO __races (race_id, race_name) VALUES (5, 'High Elf');",
            "INSERT INTO __races (race_id, race_name) VALUES (6, 'Dark Elf');",
            "INSERT INTO __races (race_id, race_name) VALUES (7, 'Wood Elf');",
            "INSERT INTO __races (race_id, race_name) VALUES (8, 'Half Elf');",
            "INSERT INTO __races (race_id, race_name) VALUES (9, 'Kerra');",
            "INSERT INTO __races (race_id, race_name) VALUES (10, 'Troll');",
            "INSERT INTO __races (race_id, race_name) VALUES (11, 'Ogre');",
            "INSERT INTO __races (race_id, race_name) VALUES (12, 'Frog');",
            "INSERT INTO __races (race_id, race_name) VALUES (13, 'Erudite');",
            "INSERT INTO __races (race_id, race_name) VALUES (14, 'Iksar');",
            "INSERT INTO __races (race_id, race_name) VALUES (15, 'Ratonga');",
            "INSERT INTO __races (race_id, race_name) VALUES (16, 'Halfling');",

            "TRUNCATE TABLE __factions;",
            "INSERT INTO __factions (faction_id, faction_name) VALUES (1, 'Good');",
            "INSERT INTO __factions (faction_id, faction_name) VALUES (2, 'Evil');",
            "INSERT INTO __factions (faction_id, faction_name) VALUES (3, 'Neutral');",
            
            "UPDATE __config SET config_value = 'Everquest2' WHERE config_name = 'default_game';",
        );

        foreach ( $queries as $sql )
        {
            $db->query($sql);
        }

        redirect("admin/config.php");
    }
}

$manage = new Manage_Game;
$manage->do_it();