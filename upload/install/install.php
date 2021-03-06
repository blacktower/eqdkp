<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        install.php
 * Began:       Wed Aug 1 2007
 * Date:        $Date$
 * -----------------------------------------------------------------------
 * @author      $Author$
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev$
 */

if ( !defined('IN_INSTALL') )
{
    exit;
}

class installer 
{

    var $submenu_ary = array('INTRO', 'REQUIREMENTS', 'DATABASE', 'ADMINISTRATOR', 'CONFIG_FILE', 'GAME_SETTINGS', 'CREATE_TABLE', 'FINAL');
    var $install_url = '';

    function installer($url)
    {
        $this->install_url = $url;
    }

    function main($mode, $sub)
    {
        // NOTE: If the sub isn't a valid installation step, throw them to the start page.
        $sub = (!in_array(strtoupper($sub), $this->submenu_ary)) ? 'intro' : $sub;
    
        switch($sub)
        {
            case 'intro':
                $this->introduction($mode, $sub);
                break;
            
            case 'requirements':
                $this->requirements($mode, $sub);
                break;
            
            case 'database':
                $this->obtain_database_settings($mode, $sub);
                break;
            
            case 'administrator':
                $this->obtain_administrator_info($mode, $sub);
                break;
            
            case 'config_file':
                $this->create_config_file($mode, $sub);
                break;
            
            case 'game_settings':
                $this->obtain_game_info($mode, $sub);
            break;
            
            case 'create_table':
            case 'create_tables':
                $this->create_database_tables($mode, $sub);
                break;
            
            case 'final':
                $this->finish_install($mode, $sub);
                break;
        }
    }

    ## ########################################################################
    ## Installation methods
    ## ########################################################################
    
    /**
     * Introductory Step
     */
    function introduction($mode, $sub)
    {
        global $eqdkp_root_path, $lang;

        $tpl = new Template_Wrap('install_install.html');
        
        $tpl->assign_vars(array(
            'TITLE'               => $lang['INSTALL_INTRO'],
            'BODY'                => $lang['INSTALL_INTRO_BODY'],
            
            'L_SUBMIT'            => $lang['NEXT_STEP'],

            'U_ACTION'            => $this->install_url . "?mode=$mode&amp;sub=requirements",
        ));

        $tpl->generate_navigation($mode, $this->submenu_ary, $sub);
    
        $tpl->page_header();
        $tpl->page_tail();
    }
    
    /**
     * Display and Check EQdkp Requiremenets Step
     */
    function requirements($mode, $sub)
    {
        global $eqdkp_root_path, $lang, $DEFAULTS;
    
        define('DEBUG', 0);

        $tpl = new Template_Wrap('install_install.html');
    
        $tpl->assign_vars(array(
            'TITLE'               => $lang['REQUIREMENTS_TITLE'],
            'BODY'                => $lang['REQUIREMENTS_EXPLAIN'],
            
            'S_CHECKS'            => true,
        ));

        $passed = array('php' => false, 'config' => false, 'db' => false,);

        // Check EQdkp Information
        $tpl->assign_block_vars('checks', array(
            'S_LEGEND'            => true,
            'LEGEND'              => $lang['EQDKP_INFO'],
            'LEGEND_EXPLAIN'      => $lang['EQDKP_INFO_EXPLAIN'],
        ));

        // Current EQdkp version
        $tpl->assign_block_vars('checks', array(
            'TITLE'               => $lang['EQDKP_VER_CURRENT'],
            'RESULT'              => $DEFAULTS['version'],

            'S_EXPLAIN'           => false,
            'S_LEGEND'            => false,
        ));

#        get_latest_eqdkp_version();
#        $tpl->assign_block_vars('checks', array(
#            'TITLE'           => $lang['EQDKP_VER_LATEST'],
#            'RESULT'          => $result,
#
#            'S_EXPLAIN'       => false,
#            'S_LEGEND'        => false,
#        ));

        // Test for basic PHP settings
        $php_version_reqd = '4.2.0';
        
        $tpl->assign_block_vars('checks', array(
            'S_LEGEND'            => true,
            'LEGEND'              => $lang['PHP_SETTINGS'],
            'LEGEND_EXPLAIN'      => sprintf($lang['PHP_SETTINGS_EXPLAIN'], $php_version_reqd),
        ));

        // Check if the PHP version on the server is the minimum required to run EQdkp
        if ( phpversion() < $php_version_reqd )
        {
            $result = '<strong style="color:red">' . $lang['NO'] . ' [' . phpversion() . ']' . '</strong>';
        }
        else
        {
            $passed['php'] = true;

            // We also give feedback on whether we're running in safe mode
            $result = '<strong style="color:green">' . $lang['YES'] . ' [' . phpversion();
            if (@ini_get('safe_mode') || strtolower(@ini_get('safe_mode')) == 'on')
            {
                $result .= ', ' . $lang['PHP_SAFE_MODE'];
            }
            $result .= ']' . '</strong>';
        }

        $tpl->assign_block_vars('checks', array(
            'TITLE'               => sprintf($lang['PHP_VERSION_REQD'], $php_version_reqd),
            'RESULT'              => $result,

            'S_EXPLAIN'           => false,
            'S_LEGEND'            => false,
        ));

        // Check for register_globals being enabled
        if (@ini_get('register_globals') == '1' || strtolower(@ini_get('register_globals')) == 'on')
        {
            $result = '<strong style="color:red">' . $lang['NO'] . '</strong>';
        }
        else
        {
            $result = '<strong style="color:green">' . $lang['YES'] . '</strong>';
        }

        $tpl->assign_block_vars('checks', array(
            'TITLE'               => $lang['PHP_REGISTER_GLOBALS'],
            'TITLE_EXPLAIN'       => $lang['PHP_REGISTER_GLOBALS_EXPLAIN'],
            'RESULT'              => $result,

            'S_EXPLAIN'           => true,
            'S_LEGEND'            => false,
        ));        

        // Check for available databases
        $tpl->assign_block_vars('checks', array(
            'S_LEGEND'            => true,
            'LEGEND'              => $lang['PHP_SUPPORTED_DB'],
            'LEGEND_EXPLAIN'      => $lang['PHP_SUPPORTED_DB_EXPLAIN'],
        ));

        // Show of support for multiple databases should be added here
        $available_dbms = get_available_dbms(false, true);
        $passed['db'] = $available_dbms['ANY_DB_SUPPORT'];
        unset($available_dbms['ANY_DB_SUPPORT']);

        foreach ($available_dbms as $db_name => $db_ary)
        {
            if (!$db_ary['AVAILABLE'])
            {
                $tpl->assign_block_vars('checks', array(
                    'TITLE'       => $lang['DLL_' . strtoupper($db_name)],
                    'RESULT'      => '<span style="color:red">' . $lang['UNAVAILABLE'] . '</span>',

                    'S_EXPLAIN'   => false,
                    'S_LEGEND'    => false,
                ));
            }
            else
            {
                $tpl->assign_block_vars('checks', array(
                    'TITLE'       => $lang['DLL_' . strtoupper($db_name)],
                    'RESULT'      => '<strong style="color:green">' . $lang['AVAILABLE'] . '</strong>',

                    'S_EXPLAIN'   => false,
                    'S_LEGEND'    => false,
                ));
            }
        }

        // Check for other modules
        $tpl->assign_block_vars('checks', array(
            'S_LEGEND'            => true,
            'LEGEND'              => $lang['PHP_OPTIONAL_MODULE'],
            'LEGEND_EXPLAIN'      => $lang['PHP_OPTIONAL_MODULE_EXPLAIN'],
        ));

        // zLib Module
        $our_zlib    = ( extension_loaded('zlib') )  ? '<strong style="color:green">' . $lang['YES'] . '</strong>' : '<strong style="color:red">' . $lang['NO'] . '</strong>';
        $their_zlib  = 'No';
    
        clearstatcache();
    
        // Check for url_fopen 
        if (@ini_get('allow_url_fopen') == '1' || strtolower(@ini_get('allow_url_fopen')) == 'on')
        {
            $result = '<strong style="color:green">' . $lang['YES'] . '</strong>';
        }
        else
        {
            $result = '<strong style="color:red">' . $lang['NO'] . '</strong>';
        }

        $tpl->assign_block_vars('checks', array(
            'TITLE'               => $lang['PHP_URL_FOPEN_SUPPORT'],
            'TITLE_EXPLAIN'       => $lang['PHP_URL_FOPEN_SUPPORT_EXPLAIN'],
            'RESULT'              => $result,

            'S_EXPLAIN'           => true,
            'S_LEGEND'            => false,
        ));

        // Check to make sure necessary directories exist and are writeable
        $tpl->assign_block_vars('checks', array(
            'S_LEGEND'            => true,
            'LEGEND'              => $lang['FILES_REQUIRED'],
            'LEGEND_EXPLAIN'      => $lang['FILES_REQUIRED_EXPLAIN'],
        ));

        $directories = array('templates/cache/',);

        umask(0);

        $passed['files'] = true;
        foreach ($directories as $dir)
        {
            $exists = $write = false;

            // Try to create the directory if it does not exist
            if (!file_exists($eqdkp_root_path . $dir))
            {
                if( !@mkdir($eqdkp_root_path . $dir, 0777))
                {
                    $tpl->error_append('The templates cache directory could not be created, please create one manually in the templates directory.
                                        <br />You can do this by changing to the EQdkp root directory and typing <b>mkdir -p templates/cache/</b>');
                }
                else
                {
                    $tpl->message_append('A templates cache directory was created in your templates directory, removing this directory could interfere
                                          with the operation of your EQdkp installation.');
                }
                @chmod($eqdkp_root_path . $dir, 0777);
            }

            // Now really check
            if (file_exists($eqdkp_root_path . $dir) && is_dir($eqdkp_root_path . $dir))
            {
                if (!@is_writable($eqdkp_root_path . $dir))
                {
                    if( !@chmod($eqdkp_root_path . $dir, 0777))
                    {
                        $tpl->error_append('The templates cache directory exists, but is not set to be writeable and could not be changed automatically.
                                            <br />Please change the permissions to 0777 manually by executing <b>chmod 0777 templates/cache</b> on your server.');
                    }
                    else
                    {
                        $tpl->message_append('The templates cache directory ahs been set to be writeable in order to let the Templating engine create cached
                                              versions of the compiled templates and speed up the displaying of EQdkp pages.');
                    }
                }
                $exists = true;
            }

            // Now check if it is writable by storing a simple file
            $fp = @fopen($eqdkp_root_path . $dir . 'test_lock', 'wb');
            if ($fp !== false)
            {
                $write = true;
            }
            @fclose($fp);

            @unlink($eqdkp_root_path . $dir . 'test_lock');

            $passed['files'] = ($exists && $write && $passed['files']) ? true : false;

            $exists = ($exists) ? '<strong style="color:green">' . $lang['FOUND'] . '</strong>' : '<strong style="color:red">' . $lang['NOT_FOUND'] . '</strong>';
            $write = ($write) ? ', <strong style="color:green">' . $lang['WRITABLE'] . '</strong>' : (($exists) ? ', <strong style="color:red">' . $lang['UNWRITABLE'] . '</strong>' : '');

            $tpl->assign_block_vars('checks', array(
                'TITLE'           => $dir,
                'RESULT'          => $exists . $write,

                'S_EXPLAIN'       => false,
                'S_LEGEND'        => false,
            ));
        }    

        // Check permissions on files/directories it would be useful access to
        $tpl->assign_block_vars('checks', array(
            'S_LEGEND'            => true,
            'LEGEND'              => $lang['FILES_OPTIONAL'],
            'LEGEND_EXPLAIN'      => $lang['FILES_OPTIONAL_EXPLAIN'],
        ));

        $directories = array('config.php',);

        foreach ($directories as $dir)
        {
            $write = $exists = true;
            if (file_exists($eqdkp_root_path . $dir))
            {
                if (!@is_writable($eqdkp_root_path . $dir))
                {
                    $write = false;
                }
            }
            else
            {
                $write = $exists = false;
            }

            $exists_str = ($exists) ? '<strong style="color:green">' . $lang['FOUND'] . '</strong>' : '<strong style="color:red">' . $lang['NOT_FOUND'] . '</strong>';
            $write_str = ($write) ? ', <strong style="color:green">' . $lang['WRITABLE'] . '</strong>' : (($exists) ? ', <strong style="color:red">' . $lang['UNWRITABLE'] . '</strong>' : '');

            $tpl->assign_block_vars('checks', array(
                'TITLE'           => $dir,
                'RESULT'          => $exists_str . $write_str,

                'S_EXPLAIN'       => false,
                'S_LEGEND'        => false,
            ));
        }

        // Figure out where we're bound for next
        $url     = (!in_array(false, $passed)) ? $this->install_url . "?mode=$mode&amp;sub=requirements" : $this->install_url . "?mode=$mode&amp;sub=database";
        $submit  = (!in_array(false, $passed)) ? $lang['INSTALL_TEST'] : $lang['INSTALL_START'];

        $message = (!in_array(false, $passed)) ? $lang['INSTALL_MINREQ_FAIL'] : $lang['INSTALL_MINREQ_PASS'];
            
        //
        // Output the page
        //
        $tpl->assign_vars(array(
            'MESSAGE'         => $message,

            'L_SUBMIT'        => $submit,
            'U_ACTION'        => $url,
        ));

        $tpl->generate_navigation($mode, $this->submenu_ary, $sub);
            
        $tpl->page_header();
        $tpl->page_tail();
    }

    /**
     * Obtain Database Settings Step
     */
    function obtain_database_settings($mode, $sub)
    {
        global $eqdkp_root_path, $lang, $DEFAULTS;
    
        define('DEBUG', 2);

        $tpl = new Template_Wrap('install_install.html');

        $tpl->assign_vars(array(
            'TITLE'           => $lang['DATABASE_TITLE'],
            'BODY'            => $lang['DATABASE_BODY'],
            
        ));

        // Obtain any submitted data
        $data = $this->get_submitted_data();

        // Prepare for displaying database-related information
        $connect_test = false;
        $error = array();
        $available_dbms = get_available_dbms(false, true);

        // Has the user opted to test the connection?
        if (isset($_POST['testdb']))
        {
            if (!isset($available_dbms[$data['dbms']]) || !$available_dbms[$data['dbms']]['AVAILABLE'])
            {
                $error['db'][] = $lang['INST_ERR_NO_DB'];
                $connect_test = false;
            }
            else
            {
                $connect_test = connect_check_db(true, $error, $available_dbms[$data['dbms']], $data['table_prefix'], $data['dbhost'], $data['dbuser'], $data['dbpass'], $data['dbname'], $data['dbport']);
            }

            $tpl->assign_block_vars('checks', array(
                'S_LEGEND'            => true,
                'LEGEND'              => $lang['DB_CONNECTION'],
                'LEGEND_EXPLAIN'      => false,
            ));

            if ($connect_test)
            {
                $tpl->assign_block_vars('checks', array(
                    'TITLE'           => $lang['DB_TEST'],
                    'RESULT'          => '<strong style="color:green">' . $lang['SUCCESSFUL_CONNECT'] . '</strong>',

                    'S_EXPLAIN'       => false,
                    'S_LEGEND'        => false,
                ));
            }
            else
            {
                $tpl->assign_block_vars('checks', array(
                    'TITLE'           => $lang['DB_TEST'],
                    'RESULT'          => '<strong style="color:red">' . implode('<br />', $error) . '</strong>',

                    'S_EXPLAIN'       => false,
                    'S_LEGEND'        => false,
                ));
            }
            
            $tpl->assign_vars(array(
                'S_CHECKS' => true,
            ));
        }
    
        if (!$connect_test)
        {
            // Update the list of available DBMS modules to only contain those which can be used
            $available_dbms_temp = array();
            foreach ($available_dbms as $type => $dbms_ary)
            {
                if (!$dbms_ary['AVAILABLE'])
                {
                    continue;
                }

                $available_dbms_temp[$type] = $dbms_ary;
            }

            $available_dbms = &$available_dbms_temp;

            //
            // Determine server settings
            //
            $server_name = ( !empty($_SERVER['HTTP_HOST']) ) ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
        
            if ( (!empty($_SERVER['SERVER_PORT'])) || (!empty($_ENV['SERVER_PORT'])) )
            {
                $server_port = ( !empty($_SERVER['SERVER_PORT']) ) ? $_SERVER['SERVER_PORT'] : $_ENV['SERVER_PORT'];
            }
            else
            {
                $server_port = '80';
            }

            // Note to self: Try to replace the server path input with an automatic generation of the path
            $script_name = (!empty($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
            if (!$script_name)
            {
                $script_name = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
            }
            
            $server_path = trim(dirname($script_name));
            $server_path = preg_replace('#install$#', '', $server_path);
            $server_path = preg_replace('#[\\\\/]{2,}#', '/', $server_path);

            // And now for the main part of this page
            $data['table_prefix'] = (!empty($data['table_prefix']) ? $data['table_prefix'] : 'eqdkp_');
            $data['server_name']  = (!empty($data['server_name']) ? $data['server_name'] : $server_name);
            $data['server_port']  = (!empty($data['server_port']) ? $data['server_port'] : $server_port);
            $data['server_path']  = (!empty($data['server_path']) ? $data['server_path'] : $server_path);
            
            foreach (array($this->default_config_options, $this->db_config_options, $this->server_config_options) as $option_groups)
            {
                foreach ($option_groups as $config_key => $vars)
                {
                    if (!is_array($vars) && strpos($config_key, 'legend') === false)
                    {
                        continue;
                    }
    
                    if (strpos($config_key, 'legend') !== false)
                    {
                        $tpl->assign_block_vars('options', array(
                            'S_LEGEND'        => true,
                            'LEGEND'          => $lang[$vars]
                        ));
    
                        continue;
                    }
    
                    $options = isset($vars['options']) ? $vars['options'] : '';
    
                    $tpl->assign_block_vars('options', array(
                        'KEY'             => $config_key,
                        'TITLE'           => $lang[$vars['lang']],
                        'S_EXPLAIN'       => $vars['explain'],
                        'S_LEGEND'        => false,
                        'TITLE_EXPLAIN'   => ($vars['explain']) ? $lang[$vars['lang'] . '_EXPLAIN'] : '',
                        'CONTENT'         => input_field($config_key, $vars['type'], $data[$config_key], $options),
                    ));
                }
            }
        }

        // Figure out where we're bound for next
        if( !isset($_POST['testdb']) )
        {
            $url     = $this->install_url . "?mode=$mode&amp;sub=database";
            $submit  = $lang['DB_TEST'];
            
            $message = $lang['DB_TEST_NOTE'];
        }
        else
        {
            $url     = (!$connect_test) ? $this->install_url . "?mode=$mode&amp;sub=database" : $this->install_url . "?mode=$mode&amp;sub=administrator";
            $submit  = (!$connect_test) ? $lang['INSTALL_TEST'] : $lang['NEXT_STEP'];
    
            $message = (!$connect_test) ? $lang['INSTALL_NEXT_FAIL'] : '';
        }
        
        // Create the hidden fields
        $s_hidden_fields = '';
        $s_hidden_fields .= ($connect_test) ? '' : '<input type="hidden" name="testdb" value="true" />';

		// If there has been a successfull connection test, write all the values for the valid variables.
        if ($connect_test)
        {
			// In this step, we are retrieving the default, database and server config options.
			// We're not retrieving admin or game options, so we won't generate them.
			$config_options = array_diff_key($data, $this->admin_config_options, $this->game_config_options);
            $s_hidden_fields .= build_hidden_fields($config_options, true);
        }

        // 
        // Output the page
        //
        $tpl->assign_vars(array(
            'MESSAGE'           => $message,

            'L_SUBMIT'          => $submit,

            'S_HIDDEN'          => $s_hidden_fields,
            'S_OPTIONS'         => ($connect_test) ? false : true,
            'U_ACTION'          => $url,
        ));

        $tpl->generate_navigation($mode, $this->submenu_ary, $sub);

        $tpl->page_header();
        $tpl->page_tail();
    }
    
    /**
     * Obtain Administrative Information Step
     */
    function obtain_administrator_info($mode, $sub)
    {
        global $eqdkp_root_path, $lang, $DEFAULTS;
    
        define('DEBUG', 2);

        $tpl = new Template_Wrap('install_install.html');
    
        $tpl->assign_vars(array(
            'TITLE'     => $lang['ADMINISTRATOR_TITLE'],
            'BODY'      => '',
        ));
    
        // Obtain any submitted data
        $data = $this->get_submitted_data();

        if ($data['dbms'] == '')
        {
            // Someone's been silly and tried calling this page direct
            // So we send them back to the start to do it again properly
            auto_redirect($this->install_url . "?mode=$mode&sub=intro");
        }
        
        $passed = false;
        $s_hidden_fields = '';

        $data['default_lang'] = ($data['default_lang'] !== '') ? $data['default_lang'] : $data['language'];

        if (isset($_POST['check']))
        {
            $error = array();

            // Check the entered email address and password
            if ($data['admin_name'] == '' || $data['admin_pass1'] == '' || $data['admin_pass2'] == '' || $data['admin_email1'] == '' || $data['admin_email2'] == '')
            {
                $error[] = $lang['INST_ERR_MISSING_DATA'];
            }

            if ($data['admin_pass1'] != $data['admin_pass2'] && $data['admin_pass1'] != '')
            {
                $error[] = $lang['INST_ERR_PASSWORD_MISMATCH'];
            }

            // Test against the default username rules
            if ($data['admin_name'] != '' && strlen($data['admin_name']) < 3)
            {
                $error[] = $lang['INST_ERR_USER_TOO_SHORT'];
            }

            if ($data['admin_name'] != '' && strlen($data['admin_name']) > 20)
            {
                $error[] = $lang['INST_ERR_USER_TOO_LONG'];
            }

            // Test against the default password rules
            if ($data['admin_pass1'] != '' && strlen($data['admin_pass1']) < 6)
            {
                $error[] = $lang['INST_ERR_PASSWORD_TOO_SHORT'];
            }

            if ($data['admin_pass1'] != '' && strlen($data['admin_pass1']) > 30)
            {
                $error[] = $lang['INST_ERR_PASSWORD_TOO_LONG'];
            }

            if ($data['admin_email1'] != $data['admin_email2'] && $data['admin_email1'] != '')
            {
                $error[] = $lang['INST_ERR_EMAIL_MISMATCH'];
            }

            if ($data['admin_email1'] != '' && !preg_match('/^[a-z0-9&\'\.\-_\+]+@(?:([a-z0-9\-]+\.([a-z0-9\-]+\.)*[a-z]+)|localhost)$/i', $data['admin_email1']))
            {
                $error[] = $lang['INST_ERR_EMAIL_INVALID'];
            }

            $tpl->assign_block_vars('checks', array(
                'S_LEGEND'            => true,
                'LEGEND'              => $lang['STAGE_ADMINISTRATOR'],
                'LEGEND_EXPLAIN'      => false,
            ));

            if (!count($error))
            {
                $passed = true;
                $tpl->assign_block_vars('checks', array(
                    'TITLE'           => $lang['ADMIN_TEST'],
                    'RESULT'          => '<strong style="color:green">' . $lang['TESTS_PASSED'] . '</strong>',

                    'S_EXPLAIN'       => false,
                    'S_LEGEND'        => false,
                ));
            }
            else
            {
                $tpl->assign_block_vars('checks', array(
                    'TITLE'           => $lang['ADMIN_TEST'],
                    'RESULT'          => '<strong style="color:red">' . implode('<br />', $error) . '</strong>',

                    'S_EXPLAIN'       => false,
                    'S_LEGEND'        => false,
                ));
            }
            
            $tpl->assign_vars(array(
                'S_CHECKS' => true,
            ));
        }

        // If the tests didn't pass (or haven't run yet), display the form elements for the admin details fields
        if (!$passed)
        {
            foreach ($this->admin_config_options as $config_key => $vars)
            {
                if (!is_array($vars) && strpos($config_key, 'legend') === false)
                {
                    continue;
                }

                if (strpos($config_key, 'legend') !== false)
                {
                    $tpl->assign_block_vars('options', array(
                        'S_LEGEND'        => true,
                        'LEGEND'          => $lang[$vars]
                    ));

                    continue;
                }

                $options = isset($vars['options']) ? $vars['options'] : '';

                $tpl->assign_block_vars('options', array(
                    'KEY'             => $config_key,
                    'TITLE'           => $lang[$vars['lang']],
                    'S_EXPLAIN'       => $vars['explain'],
                    'S_LEGEND'        => false,
                    'TITLE_EXPLAIN'   => ($vars['explain']) ? $lang[$vars['lang'] . '_EXPLAIN'] : '',
                    'CONTENT'         => input_field($config_key, $vars['type'], $data[$config_key], $options),
                ));
            }
        }

        
        // Figure out where we're bound for next
        $url     = (!$passed) ? $this->install_url . "?mode=$mode&amp;sub=administrator" : $this->install_url . "?mode=$mode&amp;sub=config_file";
        $submit  = $lang['NEXT_STEP'];

        $message = (!$passed) ? $lang['INSTALL_NEXT_FAIL'] : '';

        // Build hidden fields
		// If we have tested the values entered in this step and they are valid, we will let the hidden fields be generated for them.
		// Otherwise, we will exclude them from being created as hidden fields. 
		// In both cases, we don't want to create hidden fields for the game options.
        $config_options = ($passed) ? array_diff_key($data, $this->game_config_options) : array_diff_key($data, $this->admin_config_options, $this->game_config_options);

		$s_hidden_fields .= build_hidden_fields($config_options, true);
        $s_hidden_fields .= ($passed) ? '' : '<input type="hidden" name="check" value="true" />';

        //
        // Output the page
        //
        $tpl->assign_vars(array(
            'MESSAGE'         => $message,

            'L_SUBMIT'        => $submit,

            'S_HIDDEN'        => $s_hidden_fields,
            'S_OPTIONS'       => ($passed) ? false : true,
            'U_ACTION'        => $url,
        ));

        $tpl->generate_navigation($mode, $this->submenu_ary, $sub);

        $tpl->page_header();
        $tpl->page_tail();
    }

    /**
     * Create Configuration File Step
     */
    function create_config_file($mode, $sub)
    {
        global $eqdkp_root_path, $lang, $DEFAULTS;
    
        define('DEBUG', 2);

        $tpl = new Template_Wrap('install_install.html');

        $tpl->assign_vars(array(
            'TITLE'      => $lang['CONFIG_FILE'],
            'BODY'       => '',
        ));
    
        // Obtain any submitted data
        $data = $this->get_submitted_data();

        if ($data['dbms'] == '')
        {
            // Someone's been silly and tried calling this page direct
            // So we send them back to the start to do it again properly
            auto_redirect($this->install_url . "?mode=$mode&sub=intro");
        }

        // Set a variable for the result of our attempt to create the config file
        $written = false;

        // Create a lock file to indicate that there is an install in progress
        $fp = @fopen($eqdkp_root_path . 'templates/cache/install_lock', 'wb');
        if ($fp === false)
        {
            // We were unable to create the lock file - abort
            error($lang['UNABLE_WRITE_LOCK'], __LINE__, __FILE__);
        }
        @fclose($fp);

        @chmod($eqdkp_root_path . 'templates/cache/install_lock', 0666);

        // Write the config file information
        $config_file  = "";
        $config_file .= "<?php\n\n";
        $config_file .= "\$dbms         = '" . $this->parse_for_config($data['dbms'])          . "'; \n";
        $config_file .= "\$dbhost       = '" . $this->parse_for_config($data['dbhost'])        . "'; \n";
        $config_file .= "\$dbname       = '" . $this->parse_for_config($data['dbname'])        . "'; \n";
        $config_file .= "\$dbuser       = '" . $this->parse_for_config($data['dbuser'])        . "'; \n";
        $config_file .= "\$dbpass       = '" . $this->parse_for_config($data['dbpass'])        . "'; \n";
        $config_file .= "\$ns           = '" . $this->parse_for_config($data['server_name'])   . "'; \n";
        $config_file .= "\$table_prefix = '" . $this->parse_for_config($data['table_prefix'])  . "';\n\n";
        $config_file .= "\$debug        = '0'; \n";
        $config_file .= "\n" . 'define(\'EQDKP_INSTALLED\', true);' . "\n";
        $config_file .= "?" . ">";
        
        // Attempt to write out the config file directly. If it works, this is the easiest way to do it ...
        if ((file_exists($eqdkp_root_path . 'config.php') && is_writable($eqdkp_root_path . 'config.php')) || is_writable($eqdkp_root_path))
        {
            // Assume it will work ... if nothing goes wrong below
            $written = true;

            if (!($fp = @fopen($eqdkp_root_path . 'config.php', 'w')))
            {
                // Something went wrong ... so let's try another method
                $written = false;
            }

            if (!(@fwrite($fp, $config_file)))
            {
                // Something went wrong ... so let's try another method
                $written = false;
            }

            @fclose($fp);

            if ($written)
            {
                @chmod($eqdkp_root_path . 'config.php', 0644);
            }
        }

        if (isset($_POST['dldone']))
        {
            // Do a basic check to make sure that the file has been uploaded
            // Note that all we check is that the file has _something_ in it
            // We don't compare the contents exactly - if they can't upload
            // a single file correctly, it's likely they will have other problems....
            if (filesize($eqdkp_root_path . 'config.php') > 10)
            {
                $written = true;
            }
        }

        // Build hidden fields
		// We're going to include everything except for the game options.
        $config_options = array_diff_key($data, $this->game_config_options);

        $s_hidden_fields = '';
		$s_hidden_fields .= build_hidden_fields($config_options, true);

        if (!$written)
        {
            // OK, so it didn't work. Let's try the alternatives

            if (isset($_POST['dlconfig']))
            {
                // They want a copy of the file to download, so send the relevant headers and dump out the data
                header("Content-Type: text/x-delimtext; name=\"config.php\"");
                header("Content-disposition: attachment; filename=config.php");
                echo $config_file;
                exit;
            }

            // The option to download the config file is always available, so output it here
            $tpl->assign_vars(array(
                'TITLE'                  => '',
                'BODY'                   => $lang['CONFIG_FILE_UNABLE_WRITE'],

                'L_DL_CONFIG'            => $lang['DL_CONFIG'],
                'L_DL_CONFIG_EXPLAIN'    => $lang['DL_CONFIG_EXPLAIN'],
                'L_DL_DONE'              => $lang['DONE'],
                'L_DL_DOWNLOAD'          => $lang['DL_DOWNLOAD'],

                'S_SHOW_DOWNLOAD'        => true,
            ));
        }
        else
        {
            $tpl->assign_vars(array(
                'TITLE'              => '',
                'BODY'               => $lang['CONFIG_FILE_WRITTEN'],

                'S_SHOW_DOWNLOAD'    => false,
            ));
        }

        // Figure out where we're bound for next
        $url = (!$written) ? $this->install_url . "?mode=$mode&amp;sub=config_file" : $this->install_url . "?mode=$mode&amp;sub=game_settings";

        //
        // Output the page
        //
        $tpl->assign_vars(array(
            'L_SUBMIT'               => (!$written) ? false : $lang['NEXT_STEP'],

            'S_HIDDEN'               => $s_hidden_fields,
            'U_ACTION'               => $url,
        ));

        $tpl->generate_navigation($mode, $this->submenu_ary, $sub);

        $tpl->page_header();
        $tpl->page_tail();
    }

    /**
     * Obtain EQdkp Game Information Step
     */
    function obtain_game_info($mode, $sub)
    {
        global $eqdkp_root_path, $lang, $DEFAULTS;

        $tpl = new Template_Wrap('install_install.html');

        $tpl->assign_vars(array(
            'TITLE'     => $lang['EQDKP_SETTINGS_TITLE'],
            'BODY'      => $lang['EQDKP_SETTINGS_BODY'],
        ));

        $error = array();

        // Obtain any submitted data
        $data = $this->get_submitted_data();

        if ($data['dbms'] == '')
        {
            // Someone's been silly and tried calling this page direct
            // So we send them back to the start to do it again properly
            auto_redirect($this->install_url . "?mode=$mode&sub=intro");
        }

        include($eqdkp_root_path . 'config.php');
        
        $passed = false;
        $s_hidden_fields = '';

        $data['default_lang'] = ($data['default_lang'] !== '') ? $data['default_lang'] : $data['language'];

        if (isset($_POST['check']))
        {
            // TODO: Check for a DKP points name?

            $tpl->assign_block_vars('checks', array(
                'S_LEGEND'            => true,
                'LEGEND'              => $lang['STAGE_EQDKP_SETTINGS'],
                'LEGEND_EXPLAIN'      => false,
            ));

            if (!count($error))
            {
                $passed = true;
                $tpl->assign_block_vars('checks', array(
                    'TITLE'           => $lang['EQDKP_TEST'],
                    'RESULT'          => '<strong style="color:green">' . $lang['TESTS_PASSED'] . '</strong>',

                    'S_EXPLAIN'       => false,
                    'S_LEGEND'        => false,
                ));
            }
            else
            {
                $tpl->assign_block_vars('checks', array(
                    'TITLE'           => $lang['EQDKP_TEST'],
                    'RESULT'          => '<strong style="color:red">' . implode('<br />', $error) . '</strong>',

                    'S_EXPLAIN'       => false,
                    'S_LEGEND'        => false,
                ));
            }
            
            $tpl->assign_vars(array(
                'S_CHECKS' => true,
            ));
        }

        if (!$passed)
        {
            $data['dkp_name'] = (!empty($data['dkp_name'])) ? $data['dkp_name'] : $DEFAULTS['dkp_name'];
        
            foreach ($this->game_config_options as $config_key => $vars)
            {
                if (!is_array($vars) && strpos($config_key, 'legend') === false)
                {
                    continue;
                }

                if (strpos($config_key, 'legend') !== false)
                {
                    $tpl->assign_block_vars('options', array(
                        'S_LEGEND'        => true,
                        'LEGEND'          => $lang[$vars]
                    ));

                    continue;
                }

                $options = isset($vars['options']) ? $vars['options'] : '';

                $tpl->assign_block_vars('options', array(
                    'KEY'             => $config_key,
                    'TITLE'           => $lang[$vars['lang']],
                    'S_EXPLAIN'       => $vars['explain'],
                    'S_LEGEND'        => false,
                    'TITLE_EXPLAIN'   => ($vars['explain']) ? $lang[$vars['lang'] . '_EXPLAIN'] : '',
                    'CONTENT'         => input_field($config_key, $vars['type'], $data[$config_key], $options),
                ));
            }
        }
        
        // Build hidden fields
		// If we have valid data for the game options, we want every single data entry.
		// Otherwise, we're going to avoid building hidden values for the game config options.
        $config_options = ($passed) ? $data : array_diff_key($data, $this->game_config_options);

        $s_hidden_fields .= build_hidden_fields($config_options, true);
        $s_hidden_fields .= ($passed) ? '' : '<input type="hidden" name="check" value="true" />';


        // Figure out where we're bound for next
        $url    = (!$passed) ? $this->install_url . "?mode=$mode&amp;sub=game_settings" : $this->install_url . "?mode=$mode&amp;sub=create_table";
        $submit = $lang['NEXT_STEP'];

        //
        // Output the page
        //
        $tpl->assign_vars(array(
            'BODY'                   => $lang['STAGE_GAME_SETTINGS_EXPLAIN'],

            'L_SUBMIT'               => $submit,

            'S_OPTIONS'              => ($passed) ? false : true,
            'S_HIDDEN'               => $s_hidden_fields,
            'U_ACTION'               => $url,
        ));

        $tpl->generate_navigation($mode, $this->submenu_ary, $sub);

        $tpl->page_header();
        $tpl->page_tail();
    }
    
    /**
     * Create Database Tables Step
     */
    function create_database_tables($mode, $sub)
    {
        global $eqdkp_root_path, $lang, $db, $table_prefix, $DEFAULTS;

        define('DEBUG', 2);

        $tpl = new Template_Wrap('install_install.html');

        $tpl->assign_vars(array(
            'TITLE'     => $lang['CREATE_DATABASE_TABLES_TITLE'],
            'BODY'      => '',
        ));

        $error = array();

        // Obtain any submitted data
        $data = $this->get_submitted_data();

        if ($data['dbms'] == '')
        {
            // Someone's been silly and tried calling this page direct
            // So we send them back to the start to do it again properly
            auto_redirect($this->install_url . "?mode=$mode&sub=intro");
        }

        include($eqdkp_root_path . 'config.php');

        define('CONFIG_TABLE', $data['table_prefix'] . 'config');
        define('USERS_TABLE',  $data['table_prefix'] . 'users');
        define('STYLES_TABLE', $data['table_prefix'] . 'styles');
    
        $table_prefix = $data['table_prefix'];
    
        //
        // Database population
        //
        // If we get here and the extension isn't loaded it should be safe to just go ahead and load it 
        $available_dbms = get_available_dbms($data['dbms']);

        $dbal_file = $eqdkp_root_path . 'includes/db/' . $available_dbms[$data['dbms']]['DRIVER'] . '.php';
        if ( !file_exists($dbal_file) )
        {
            $tpl->message_die('Unable to find the database abstraction layer for <b>' . $available_dbms[$data['dbms']]['DRIVER'] . '</b>, check to make sure ' . $dbal_file . ' exists.');
        }
        include($dbal_file);

        // Connect to our database
        $sql_db = 'dbal_' . $available_dbms[$data['dbms']]['DRIVER'];
        $db = new $sql_db();
        $db->sql_connect($data['dbhost'], $data['dbname'], $data['dbuser'], $data['dbpass'], false);

        // Set some nice names for the sql files to use to populate the database
        $db_structure_file = 'schemas/' . $available_dbms[$data['dbms']]['SCHEMA'] . '_structure.sql';
        $db_data_file      = 'schemas/' . $available_dbms[$data['dbms']]['SCHEMA'] . '_data.sql';
    
        $remove_remarks_function = $available_dbms[$data['dbms']]['COMMENTS'];
        $delimiter = $available_dbms[$data['dbms']]['DELIM'];
        
        // Parse structure file and create database tables
        // TODO: Can we change the schema and data files to use the __table format since we're using our database class?
        $sql = @fread(@fopen($db_structure_file, 'r'), @filesize($db_structure_file));
        $sql = preg_replace('#eqdkp\_(\S+?)([\s\.,]|$)#', $data['table_prefix'] . '\\1\\2', $sql);

        $sql = $remove_remarks_function($sql);
        $sql = parse_sql($sql, $available_dbms[$data['dbms']]['DELIM']);

        // FIXME: No way to roll back changes if any particular query fails.
        $sql_count = count($sql);
        $i = 0;
        
        while ( $i < $sql_count ) 
        {
            if (isset($sql[$i]) && $sql[$i] != "") 
            {
                if ( !($db->query($sql[$i]) )) 
                {
                    $tpl->message_die('Failed to connect to database <b>' . $data['dbname'] . '</b> as <b>' . $data['dbuser'] . '@' . $data['dbhost'] . '</b>
                               <br /><br /><a href="install.php">Restart Installation</a>');
                    $error[] = $sql[$i];
                }
            }

            $i++;
        }
        unset($sql);
    
        // Parse the data file and populate the database tables
        $sql = @fread(@fopen($db_data_file, 'r'), @filesize($db_data_file));
        $sql = preg_replace('#eqdkp\_(\S+?)([\s\.,]|$)#', $data['table_prefix'] . '\\1\\2', $sql);
    
        $sql = $remove_remarks_function($sql);
        $sql = parse_sql($sql, $available_dbms[$data['dbms']]['DELIM']);
    
        // FIXME: No way to roll back changes if any particular query fails.
        $sql_count = count($sql);
        $i = 0;
    
        while ( $i < $sql_count ) 
        {    
            if (isset($sql[$i]) && $sql[$i] != "") 
            {
                if ( !($db->query($sql[$i]) )) 
                {
                    $tpl->message_die('Failed to connect to database <b>' . $data['dbname'] . '</b> as <b>' . $data['dbuser'] . '@' . $data['dbhost'] . '</b>
                                       <br /><br /><a href="index.php">Restart Installation</a>');
                    $error[] = $sql[$i];
                }
            }
    
            $i++;
        }
        unset($sql);
        
        // Game installation
        if (!class_exists('Game_Installer'))
        {
            include($eqdkp_root_path . 'games/game_installer.php');
        }
        $gm = new Game_Installer();
        
        $gm->set_current_game($data['game_id']);
        $gm->install_game();
        
        // Script path fix
        $data['server_path'] .= (substr($data['server_path'], strlen($data['server_path'])-1) == '/') ? '' : '/';
        
        //
        // Update some config settings
        //
        // FIXME: No way to roll back changes if any particular query fails.

		// FIXME: Necessary evil because of the find-replace for database table prefix done above
        $db->query('UPDATE ' . CONFIG_TABLE . " SET config_name='eqdkp_start' WHERE config_name='" . $data['table_prefix'] . "start'");
		$db->query('UPDATE ' . CONFIG_TABLE . " SET config_name='eqdkp_version' WHERE config_name='" . $data['table_prefix'] . "version'");

        config_set('server_name', $data['server_name']);
        config_set('server_port', $data['server_port']);
        config_set('server_path', $data['server_path']);
        config_set('default_lang', $data['default_lang']);
        config_set('default_locale', $data['default_locale']);
        
		config_set('current_game', $gm->current_game);
		config_set('current_game_name', $gm->games[$gm->current_game]['name']);
		
        config_set('main_title', $data['site_name']);
        config_set('sub_title', $data['site_desc']);
        config_set('dkp_name', $data['dkp_name']);
        config_set('guildtag', $data['guildtag']);
		
        //
        // Update admin account
        //
        // Encrypt the admin's password
        $admin_salt = generate_salt();
        $admin_password = hash_password($data['admin_pass1'], $admin_salt);
        
		// And now we're done with the $eqdkp object, so let's clean up after ourselves.
		unset($eqdkp);
		
        $query = $db->build_query('UPDATE', array(
            'user_name'          => $data['admin_name'],
            'user_password'      => $admin_password,
            'user_salt'          => $admin_salt,
            'user_lang'          => $data['default_lang'],
            'user_email'         => $data['admin_email1'],
            'user_active'        => '1',
        ));

        $db->query('UPDATE ' . USERS_TABLE . ' SET ' . $query . " WHERE user_id='1'");
        config_set('admin_email', $data['admin_email1']);

        // Figure out where we're bound for next
        $url    = (count($error)) ? $this->install_url . "?mode=$mode&amp;sub=intro" : $this->install_url . "?mode=$mode&amp;sub=final";
        $submit = (count($error)) ? false : $lang['NEXT_STEP'];

		// Build hidden fields
		// We're going to generate hidden fields for everything this time.
        $s_hidden_fields = '';
        $s_hidden_fields .= build_hidden_fields($data, true);

        //
        // Output the page
        //
        $tpl->assign_vars(array(
            'BODY'                   => $lang['STAGE_CREATE_TABLE_EXPLAIN'],

            'L_SUBMIT'               => $submit,

            'S_HIDDEN'               => $s_hidden_fields,
            'U_ACTION'               => $url,
        ));

        $tpl->generate_navigation($mode, $this->submenu_ary, $sub);

        $tpl->page_header();
        $tpl->page_tail();
    }
    
    /**
     * Final step: Finish installation
     */
    function finish_install($mode, $sub)
    {
        global $eqdkp_root_path, $db, $lang, $DEFAULTS;
    
        define('DEBUG', 0);
    
        // FIXME: need some way of stopping people just jumping to this step. Not that i think they will, but just for completeness' sake.
        //        add file_exists check for install_lock
        if (!file_exists($eqdkp_root_path . 'config.php'))
        {
            // Someone's been silly and tried calling this page direct
            // So we send them back to the start to do it again properly
            auto_redirect($this->install_url . "?mode=$mode&sub=intro");
        }

        $tpl = new Template_Wrap('install_install.html');
        
        // Remove the lock file
        @unlink($eqdkp_root_path . 'templates/cache/install_lock');

		$url = $eqdkp_root_path . "login.php?redirect=" . urlencode('admin/settings.php');
    
        $tpl->assign_vars(array(
            'TITLE'                  => $lang['INSTALL_CONGRATS'],
            'BODY'                   => sprintf($lang['INSTALL_CONGRATS_EXPLAIN'], $DEFAULTS['version'], '<a href="' . $url . '">', '</a>'),
        ));
    
        $tpl->generate_navigation($mode, $this->submenu_ary, $sub);

        $tpl->page_header();
        $tpl->page_tail();
    }

    ## ########################################################################
    ## Helper methods
    ## ########################################################################

    /**
	 * Parse for configuration file
	 */
	function parse_for_config($variable)
	{
		$variable = addslashes($variable);
		return $variable;
	}

    /**
     * Get submitted data
     */
    function get_submitted_data()
    {
        global $in;
    
        return array(
            'language'        => basename($in->get('language', '')),

            'dbms'            => $in->get('dbms', ''),
            'dbhost'          => $in->get('dbhost', ''),
            'dbport'          => $in->get('dbport', ''),
            'dbuser'          => $in->get('dbuser', ''),
            'dbpass'          => $in->get('dbpass', ''),
            'dbname'          => $in->get('dbname', ''),
            'table_prefix'    => $in->get('table_prefix', ''),

            'default_lang'    => basename($in->get('default_lang', '')),
            'default_locale'  => basename($in->get('default_locale', '')),

            'admin_name'      => $in->get('admin_name', ''),
            'admin_pass1'     => $in->get('admin_pass1', ''),
            'admin_pass2'     => $in->get('admin_pass2', ''),
            'admin_email1'    => strtolower($in->get('admin_email1', '')),
            'admin_email2'    => strtolower($in->get('admin_email2', '')),
            
            'game_id'         => $in->get('game_id', ''),
            'guildtag'        => $in->get('guildtag', ''),
            'dkp_name'        => $in->get('dkp_name', ''),
            
            'site_name'       => $in->get('site_name', ''),
            'site_desc'       => $in->get('site_desc', ''),
            
            'server_name'     => $in->get('server_name', ''),
            'server_port'     => $in->get('server_port', ''),
            'server_path'     => $in->get('server_path', ''),
        );
    } 

    /**#@+
     * The fields for each step of the installation process
     * Used to automatically generate the input fields per page
     */
    var $default_config_options = array(
        'legend1'               => 'DEFAULT_CONFIG',
        'default_lang'          => array('lang' => 'DEFAULT_LANG',      'type' => 'select', 'options' => 'inst_language_select(\'{VALUE}\')', 'explain' => false),
        'default_locale'        => array('lang' => 'DEFAULT_LOCALE',    'type' => 'select', 'options' => 'inst_locale_select(\'{VALUE}\')', 'explain' => false),
    );

    var $db_config_options = array(
        'legend1'               => 'DB_CONFIG',
        'dbms'                  => array('lang' => 'DB_TYPE',           'type' => 'select', 'options' => 'dbms_select(\'{VALUE}\')', 'explain' => false),
        'dbhost'                => array('lang' => 'DB_HOST',           'type' => 'text:25:100', 'explain' => false),
        'dbname'                => array('lang' => 'DB_NAME',           'type' => 'text:25:100', 'explain' => false),
        'dbuser'                => array('lang' => 'DB_USERNAME',       'type' => 'text:25:100', 'explain' => false),
        'dbpass'                => array('lang' => 'DB_PASSWORD',       'type' => 'password:25:100', 'explain' => false),
        'table_prefix'          => array('lang' => 'TABLE_PREFIX',      'type' => 'text:25:100', 'explain' => false),
    );

    var $server_config_options = array(
        'legend1'               => 'SERVER_CONFIG',
        'server_name'           => array('lang' => 'SERVER_NAME',       'type' => 'text:40:255', 'explain' => false),
        'server_port'           => array('lang' => 'DB_PORT',           'type' => 'text:5:5', 'explain' => true),
        'server_path'           => array('lang' => 'SERVER_PATH',       'type' => 'text::255', 'explain' => true),
    );

    var $admin_config_options = array(
        'legend1'               => 'ADMIN_CONFIG',
        'admin_name'            => array('lang' => 'ADMIN_USERNAME',            'type' => 'text:25:100', 'explain' => true),
        'admin_pass1'           => array('lang' => 'ADMIN_PASSWORD',            'type' => 'password:25:100', 'explain' => true),
        'admin_pass2'           => array('lang' => 'ADMIN_PASSWORD_CONFIRM',    'type' => 'password:25:100', 'explain' => false),
        'admin_email1'          => array('lang' => 'ADMIN_EMAIL',               'type' => 'text:25:100', 'explain' => false),
        'admin_email2'          => array('lang' => 'ADMIN_EMAIL_CONFIRM',       'type' => 'text:25:100', 'explain' => false),
    );
    
    var $game_config_options = array(        
        'legend1'               => 'GAME_CONFIG',
        'game_id'               => array('lang' => 'GAME_NAME',         'type' => 'select', 'options' => 'game_select(\'{VALUE}\')', 'explain' => false),

        'legend2'               => 'OTHER_SETTINGS',
        'guildtag'              => array('lang' => 'GUILD_NAME',        'type' => 'text:25:100', 'explain' => false),
        'site_name'             => array('lang' => 'SITE_NAME',         'type' => 'text:25:100', 'explain' => false),
        'site_desc'             => array('lang' => 'SITE_DESC',         'type' => 'textarea:3:25', 'explain' => false),
        'dkp_name'              => array('lang' => 'DKP_NAME',          'type' => 'text:5:5', 'explain' => false),
    );
    /**#@-*/
}
?>