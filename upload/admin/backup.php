<?php
/******************************
 * EQdkp
 * Copyright 2002-2003
 * Licensed under the GNU GPL.  See COPYING for full terms.
 * ------------------
 * manage_members.php
 * Began: Fri March 2 2007
 * 
 * $Id$
 * 
 ******************************/

define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../';
include_once($eqdkp_root_path . 'common.php');

class Backup extends EQdkp_Admin
{
    function backup()
    {
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID;
        
        parent::eqdkp_admin();
        
        $this->assoc_buttons(array(
            'form' => array(
                'name'    => '',
                'process' => 'display_menu',
                'check'   => 'a_backup'
            ),
            'backup' => array(
                'name'    => 'backup',
                'process' => 'do_backup',
                'check'   => 'a_backup'
            )
        ));
    }
    
    function error_check()
    {
        return $this->fv->is_error();
    }
    
    // ---------------------------------------------------------
    // Display menu
    // ---------------------------------------------------------
    function display_menu()
    {
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID;
        $tpl->assign_vars(array(
            'F_BACKUP'            => 'backup.php' . $SID,
            'L_BACKUP_TITLE'      => $user->lang['backup_title'],
            'L_CREATE_TABLE'      => $user->lang['create_table'],
            'L_SKIP_NONESSENTIAL' => $user->lang['skip_nonessential'],
            'L_GZIP_CONTENT'      => $user->lang['gzip_content'],
            'L_BACKUP_DATABASE'   => $user->lang['backup_database'],
            'L_YES'               => $user->lang['yes'],
            'L_NO'                => $user->lang['no']
        ));
        
        $eqdkp->set_vars(array(
            'page_title'    => page_title($user->lang['backup']),
            'template_file' => 'admin/backup.html',
            'display'       => true
        ));
    }
    
    // ---------------------------------------------------------
    // Main Backup Script
    // ---------------------------------------------------------
    function do_backup()
    {
        global $db, $eqdkp, $user, $tpl, $pm, $dbhost;
        global $SID;
        
        $tables = array(
            '__adjustments',
            '__auth_options',
            '__auth_users',
            '__config',
            '__events',
            '__items',
            '__logs',
            '__members',
            '__member_ranks',
            '__member_user',
            '__news',
            '__plugins',
            '__raids',
            '__raid_attendees',
            '__sessions',
            '__styles',
            '__style_config',
            '__users',
            // Game-specific tables
            '__classes',
            '__races',
            '__factions',
        );
          
        $do_gzip = false;
        
        if(phpversion() >= "4.0")
        {
            if( extension_loaded("zlib") && $_POST['gzip'] == 'Y')
            {
                $do_gzip = true;
            }
        }
          
        if( $do_gzip )
        {
            @ob_start();
            @ob_implicit_flush(0);
            header("Content-Type: text/x-delimtext; name=\"eqdkpbackup.sql.gz\"");
            header("Content-disposition: attachment; filename=eqdkpbackup.sql.gz");
        }
        else
        {
            header("Content-Type: text/x-delimtext; name=\"eqdkpbackup.sql\"");
            header("Content-disposition: attachment; filename=eqdkpbackup.sql");
        }
          
        //Lets echo out our header
        echo "-- EQDKP SQL Dump " . "\n";
		echo "-- version " . EQDKP_VERSION . "\n";
        echo "-- http://www.eqdkp.com" . "\n";
        echo "-- \n";
        echo "-- Host: " . $dbhost . "\n";
        echo "-- Generation Time: " . date('M d, Y \a\t g:iA') . "\n";
        echo "-- \n";
        echo "-- --------------------------------------------------------" . "\n";
        echo "\n";
        
        foreach ( $tables as $table )
        {
            $tablename        = $this->_generate_table_name($table);
            $table_sql_string = $this->_create_table_sql_string($tablename);
            $data_sql_string  = $this->_create_data_sql_string($tablename);
        
            // NOTE: Error checking for table or data sql strings here?
        
            if ( $_POST['create_table'] == 'Y' )
            {
                echo "\n" . "-- \n";
                echo "-- Table structure for table `{$tablename}`" . "\n";
                echo "-- \n\n";
                echo $table_sql_string . "\n";
            }

            if ( $table != '__sessions' ) 
            {
                echo "\n" . "-- \n";
                echo "-- Dumping data for table `{$tablename}`" . "\n";
                echo "-- \n\n";
                echo (($data_sql_string) ? $data_sql_string : "-- No data available.") . "\n";
            }
        
        }
        unset($tablename, $table_sql_string, $data_sql_string);
        
        @header("Pragma: no-cache");
          
        // FIXME: Gzip not functioning correctly. Consider revising.
        if( $do_gzip )
        {
            $size     = ob_get_length();
            $crc      = crc32(ob_get_contents());
            $contents = gzcompress(ob_get_contents());
            ob_end_clean();
            
            $gzfile  = '\x1f\x8b\x08\x00\x00\x00\x00\x00';
            $gzfile .= substr($contents, 0, strlen($contents) - 4);
            $gzfile .= $this->_gzip_four_chars($crc);
            $gzfile .= $this->_gzip_four_chars($size);
            
            echo $gzfile;
        }
    }
    
    function _create_table_sql_string($tablename)
    {
        global $db;
        // Start the SQL string for this table
        // TODO: Revise the 'DROP TABLE' business. It's a little too risqu� for the average Joe Blo.
        // EQDKP_CHANGE: We always drop tables by default. You may not like this.
        // This is what we need for our app, so don't expect this to work for everything.
        $sql_string  = "DROP TABLE IF EXISTS {$tablename};" . "\n";
        $sql_string .= "CREATE TABLE {$tablename}";
          
        // Get the field info and output to a string in the correct MySQL syntax
        $field_string = "";
        $result = $db->query("DESCRIBE {$tablename}");
        while ($field_info = $db->fetch_record($result))
        {
            $field_name = $field_info[0];
            $field_type = $field_info[1];
            $field_not_null = ($field_info[2] == "YES") ? "" : " NOT NULL";
            $field_default = ($field_info[4] == NULL) ? "" : sprintf(" default '%s'", $field_info[4]);
            $field_auto_increment = ($field_info[5] == NULL) ? "" : sprintf(" %s", $field_info[5]);
            
            $field_string = sprintf("%s,\n  `%s` %s%s%s%s", $field_string, $field_name, $field_type, $field_not_null, $field_auto_increment, $field_default);
        }

        // Get the index info and output to a string in the correct MySQL syntax
        $index_string = "";
        $result = $db->query("SHOW INDEX FROM {$tablename}");
        while ($index_info = $db->fetch_record($result))
        {
            $index_name = $index_info[2];
            $index_unique = $index_info[1];
            $index_field_name = $index_info[4];
            $index_type = $index_info[10];
            
            if ($index_name == "PRIMARY") 
            {
                $index_name = "PRIMARY KEY";
            }
            
            if ($index_unique == "1" && $index_type != "FULLTEXT") 
            {
                $index_name = sprintf("KEY %s", $index_name);
            }
            
            if ($index_unique == "0" && $index_name != "PRIMARY KEY") 
            {
                $index_name = sprintf("UNIQUE KEY %s", $index_name);
            }
            
            if ($index_type == "FULLTEXT") 
            {
                $index_name = sprintf("FULLTEXT KEY %s", $index_name);
            }
            
            $index_string = sprintf("%s,\n  %s (`%s`)", $index_string, $index_name, $index_field_name);
        }

        // Get the table type and output it to a string in the correct MySQL syntax
        $result = $db->query("SHOW TABLE STATUS");
        while ($status_info = $db->fetch_record($result))
        {
            for ($i = 0; $i < count($status_info); $i++)
            {
                // add a semicolon to the end of the line so this tools output will be usable
                if ($status_info[0] == $tablename) $table_type = sprintf("TYPE=%s ;", $status_info[1]);
            }
        }
      
        // Remove the first 2 characters (", ") from the field string
        $field_string = "\n" . substr($field_string, 2);
        
        // Append the index string to the field string
        $field_string = sprintf("%s%s", $field_string, $index_string);
        
        // Put the field string in parantheses
        $field_string = sprintf("(%s\n)", $field_string);
        
        // Finalise the MySQL create table string
        $sql_string = sprintf("%s %s %s", $sql_string, $field_string, $table_type);
        
        return $sql_string;
    }
      
    function _create_data_sql_string($tablename)
    {
        global $db;
        
        // Initialise the field and sql strings
        $field_string = "";
        $sql_string = "";
          
        // Get field names from MySQL and output to a string in the correct MySQL syntax
        $result = $db->query("SELECT * FROM $tablename");
          
        for ($i = 0; $i < @mysql_num_fields($result); $i++) 
        {
            $meta = @mysql_fetch_field($result, $i);
            
            $field_string = sprintf("%s, %s", $field_string, $meta->name);
        }
      
        // Remove the first 2 characters (", ") from the field string
        $field_string = substr($field_string, 2);
        
        // Put the field string in parantheses
        $field_string = sprintf("(%s)", $field_string);
        
        // Get table data from MySQL and output to a string in the correct MySQL syntax
        while ($row = $db->fetch_record($result)) 
        {
            // Initialise the data string
            $data_string = "";
            
            // Loop through the records and append data to the string after escaping
            for ($i = 0; $i < mysql_num_fields($result); $i++) 
            {
                $data_string = sprintf("%s, '%s'", $data_string, mysql_escape_string($row[$i]));
            }
            
            // Remove the first 2 characters (", ") from the data string
            $data_string = substr($data_string, 2);
            
            // Put the data string in parantheses and prepend "VALUES "
            $data_string = sprintf("VALUES (%s)", $data_string);
            
            // Finalise the MySQL insert into string for this record
            // add a semicolon to the end of the line so this tools output will be usable
            $sql_string = sprintf("%sINSERT INTO %s %s %s ;\n", $sql_string, $tablename, $field_string, $data_string);
        }
          
        return $sql_string;
    }
      
    function _gzip_four_chars($val)
    {
        $return = '';
        for ($i = 0; $i < 4; $i ++)
        {
            $return .= chr($val % 256);
            $val     = floor($val / 256);
        }
        
        return $return;
    } 
    
    function _generate_table_name($val)
    {
        global $table_prefix;
        
        $val = preg_replace('#__([^\s]+)#', $table_prefix . '\1', $val);
        return $val;
    }
}

$backup = new Backup;
$backup->process();