<?php
	/**
	 * OAuth 2.0 LinkedIn Connector
	 * 
	 * @link http://www.marcus-povey.co.uk
	 * @author Marcus Povey <marcus@marcus-povey.co.uk>
	 */
	
	function linkedin_oath2_init()
	{
            // Extend login & registration forms
            elgg_extend_view('forms/login', 'linkedin_oauth2/connect');
            elgg_extend_view('forms/register', 'linkedin_oauth2/connect');
            
            // Walled garden bypasss
            elgg_register_plugin_hook_handler('public_pages', 'walled_garden', function($hook, $type, $return_value, $params) {
                // add to the current list of public pages that should be available from the walled garden
                //$return_value[] = 'mod/linkedin_oauth2/authenticate.php';
                $return_value[] = 'linkedin';
                $return_value[] = 'linkedin/Redirect';
                $return_value[] = 'linkedin/.*/.*';
       
                
                // return the modified value
                return $return_value;
            });
            
            // Authentication page handler
            elgg_register_page_handler('linkedin', function($pages) {
                
                set_input('friend_guid', $pages[0]);
                set_input('invitecode', $pages[1]);
                
                require_once(dirname(__FILE__) . '/authenticate.php');
                
                return true;
            });
            
            // Register Icon URL
            elgg_register_plugin_hook_handler('entity:icon:url', 'user', function ($hook, $entity_type, $return_value, $params) {
				$user = $params['entity'];
				$size = $params['size'];
				
				if ($return_value) {
					return $return_value;
				}

				
				if (!elgg_instanceof($user, 'user')) {
					return null;
				}
				
				if ($url = $user->linkedin_picture_url)
					return $url;
			});
        }
        
	elgg_register_event_handler('init','system','linkedin_oath2_init');
