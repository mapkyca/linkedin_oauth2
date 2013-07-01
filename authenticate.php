<?php

    //require_once (dirname(dirname(dirname(__FILE__))) . "/engine/start.php");
    require_once (dirname(__FILE__) .'/vendor/PHP-OAuth2/Client.php');
    require_once (dirname(__FILE__) .'/vendor/PHP-OAuth2/GrantType/IGrantType.php');
    require_once (dirname(__FILE__) .'/vendor/PHP-OAuth2/GrantType/AuthorizationCode.php');

    $CLIENT_ID     = elgg_get_plugin_setting('client_id', 'linkedin_oauth2');
    $CLIENT_SECRET = elgg_get_plugin_setting('client_secret', 'linkedin_oauth2');

        $state = md5(elgg_get_site_url() . dirname(__FILE__));

    $REDIRECT_URI           = elgg_get_site_url() . 'linkedin/';
    if (($friend_guid = get_input('friend_guid')) && ($invitecode = get_input('invitecode')))
        $REDIRECT_URI .= "$friend_guid/$invitecode/";
    
    $AUTHORIZATION_ENDPOINT = 'https://www.linkedin.com/uas/oauth2/authorization';
    $TOKEN_ENDPOINT         = 'https://www.linkedin.com/uas/oauth2/accessToken';

    $client = new OAuth2\Client($CLIENT_ID, $CLIENT_SECRET);
    if (!get_input('code'))
    {
        $params = array('scope' => 'r_fullprofile r_emailaddress', 'response_type' => 'code', 'state' => $state);
        $params = elgg_trigger_plugin_hook('linkedin_oauth2', 'permissions', null, $params); // Allow customisation permissions
        $auth_url = $client->getAuthenticationUrl($AUTHORIZATION_ENDPOINT, $REDIRECT_URI, $params);
        header('Location: ' . $auth_url);
        die('Redirect');
    }
    else
    {
        $params = array('code' => $_GET['code'], 'redirect_uri' => $REDIRECT_URI, 'state' => $state);
        $params = elgg_trigger_plugin_hook('linkedin_oauth2', 'get_access_token', null, $params); // Allow customisation of access token parameters
        $response = $client->getAccessToken($TOKEN_ENDPOINT/* . "?state=$state" */, 'authorization_code', $params);

        $access_token = $response['result']['access_token'];
//print_r($access_token); die();
//print_r($response);
//        parse_str($response['result'], $info);
        $client->setAccessToken($access_token);
//print_r($info['access_token']);
        $response = $client->fetch('https://api.linkedin.com/v1/people/~:(id,first-name,last-name,picture-url)', array('oauth2_access_token' => $access_token, 'format' => 'json'));
     //   var_dump($response, $response['result']);
        $profile = $response['result'];

        if ((!$profile) || (!$profile['id'])) {register_error('Invalid response from server, try again in a bit.'); forward();}
        
        $response = $client->fetch('https://api.linkedin.com/v1/people/~/email-address', array('oauth2_access_token' => $access_token, 'format' => 'json'));
        $email = $response['result'];

        $ia = elgg_set_ignore_access(); // Ensure we get disabled users as well.
        $users = elgg_get_entities_from_metadata(array(
            'types' => 'user',
            'metadata_name_value_pairs' => array('name' => 'linkedin_id', 'value' => $profile['id']),
            'limit' => 1
        ));
        elgg_set_ignore_access($ia);
        
        if ($users) {
	    $user = $users[0];
	    $user->name = $profile['firstName'] . ' ' . $profile['lastName'];
	    $user->linkedin_picture_url  = $profile['pictureUrl'];

            try {
                if (elgg_trigger_plugin_hook('linkedin_oauth2', 'user', array(
                    'oauth_client' =>$client,
                    'user' => $user,
                    'profile' => $profile,
                    'oauth_access_token' => $access_token
                ), true))
                    login($user);
            } catch(Exception $e) {
                register_error($e->getMessage());
            }
        }
	else
        {
            // New user
            
            $password = generate_random_cleartext_password();
            $username = strtolower(preg_replace("/[^a-zA-Z0-9\s]/", "", $profile['firstName'] . $profile['lastName']));
            if (get_user_by_username($username)) {
                $n = 1;
                while (get_user_by_username($username . $n)) {$n++;}
                $username = $username . $n; 
            }
            
            $user = new ElggUser();
            $user->subtype = 'linkedin';
            $user->username = $username;
            $user->name = $profile['firstName'] . ' ' . $profile['lastName'];
            $user->email = $email;
            $user->access_id = ACCESS_PUBLIC;
            $user->salt = generate_random_cleartext_password();
            $user->password = generate_user_password($user, $password);
            $user->owner_guid = 0;
            $user->container_guid = 0;
            
            $user->save();
            
            $user->linkedin_id = $profile['id'];
            
            $user->linkedin_picture_url  = $profile['pictureUrl'];
            
            // Linkedin validates emails, so we're going to assume they did a good job.
            elgg_set_user_validation_status($user->guid, true, 'linkedin_oauth2');   
            
            // Trigger register hook
            $params = array(
                'user' => $user,
                'password' => $password,
                'friend_guid' => get_input('friend_guid'),
                'invitecode' => get_input('invitecode'),
            );
            if (!elgg_trigger_plugin_hook('register', 'user', $params, TRUE)) {
                    $user->delete();
                    throw new RegistrationException(elgg_echo('registerbad'));
            }
            
            // If $friend_guid has been set, make mutual friends
            if ($friend_guid = get_input('friend_guid')) {
                    if ($friend_user = get_user($friend_guid)) {
                            if (get_input('invitecode') == generate_invite_code($friend_user->username)) {
                                    $user->addFriend($friend_guid);
                                    $friend_user->addFriend($user->guid);

                                    add_to_river('river/relationship/friend/create', 'friend', $user->getGUID(), $friend_guid);
                                    add_to_river('river/relationship/friend/create', 'friend', $friend_guid, $user->getGUID());
                            }
                    }
            }
            
            try {
                if (elgg_trigger_plugin_hook('linkedin_oauth2', 'user', array(
                    'user' => $user,
                    'profile' => $profile,
                    'oauth_client' =>$client,
                    'oauth_access_token' => $access_token
                ), true)) 
                        login($user);
            } catch(Exception $e) {
                register_error($e->getMessage());
            }
        }

        ?>
<html>
		<head>
		<script>
			function init() {
				window.opener.location.reload();
				window.close();
			}
		</script>
		</head>
		<body onload="init();">
		</body>
		</html>
                <?php

    }

