<?php
	/**
	 * OAuth 2.0 LinkedIn Connector
	 * 
	 * @link http://www.marcus-povey.co.uk
	 * @author Marcus Povey <marcus@marcus-povey.co.uk>
	 */
	
	function linkedin_oath2_init()
	{
            elgg_extend_view('forms/login', 'linkedin_oauth2/connect');
            
            elgg_register_plugin_hook_handler('public_pages', 'walled_garden', function($hook, $type, $return_value, $params) {
                // add to the current list of public pages that should be available from the walled garden
                //$return_value[] = 'mod/linkedin_oauth2/authenticate.php';
                $return_value[] = 'linkedin';
                $return_value[] = 'linkedin/Redirect';
       
                
                // return the modified value
                return $return_value;
            });
            
            elgg_register_page_handler('linkedin', function($pages) {
                require_once(dirname(__FILE__) . '/authenticate.php');
                
                return true;
            });
        }
        
	elgg_register_event_handler('init','system','linkedin_oath2_init');
