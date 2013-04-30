<?php

    //require_once (dirname(dirname(dirname(__FILE__))) . "/engine/start.php");
    require_once (dirname(__FILE__) .'/vendor/PHP-OAuth2/Client.php');
    require_once (dirname(__FILE__) .'/vendor/PHP-OAuth2/GrantType/IGrantType.php');
    require_once (dirname(__FILE__) .'/vendor/PHP-OAuth2/GrantType/AuthorizationCode.php');

    $CLIENT_ID     = elgg_get_plugin_setting('client_id', 'linkedin_oauth2');
    $CLIENT_SECRET = elgg_get_plugin_setting('client_secret', 'linkedin_oauth2');

        $state = md5(elgg_get_site_url() . dirname(__FILE__));

    $REDIRECT_URI           = elgg_get_site_url() . 'linkedin/';
    $AUTHORIZATION_ENDPOINT = 'https://www.linkedin.com/uas/oauth2/authorization';
    $TOKEN_ENDPOINT         = 'https://www.linkedin.com/uas/oauth2/accessToken';

    $client = new OAuth2\Client($CLIENT_ID, $CLIENT_SECRET);
    if (!get_input('code'))
    {
        $params = array('scope' => 'r_fullprofile r_emailaddress');
        $params = elgg_trigger_plugin_hook('linkedin_oauth2', 'permissions', null, $params); // Allow customisation permissions
        $auth_url = $client->getAuthenticationUrl($AUTHORIZATION_ENDPOINT . "?response_type=code&state=$state", $REDIRECT_URI, $params);
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

        $response = $client->fetch('https://api.linkedin.com/v1/people/~/email-address', array('oauth2_access_token' => $access_token, 'format' => 'json'));
        $email = $response['result'];

        $users = elgg_get_entities_from_metadata(array(
            'types' => 'user',
            'metadata_name_value_pairs' => array('name' => 'linkedin_id', 'value' => $profile['id']),
            'limit' => 1
        ));
        
        if ($users) {
	    $user = $users[0];
	    $user->name = $profile['firstName'] . ' ' . $profile['lastName'];
	    $user->linkedin_picture_url  = $profile['pictureUrl'];

	    if (elgg_trigger_plugin_hook('linkedin_oauth2', 'user', array(
		'user' => $user,
		'profile' => $profile
	    ), true))
	    	login($user);
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
            
            if (elgg_trigger_plugin_hook('linkedin_oauth2', 'user', array(
		'user' => $user,
		'profile' => $profile
	    ), true))
	    	login($user);
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

