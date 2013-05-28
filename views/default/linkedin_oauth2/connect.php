<?php

    $url = elgg_get_site_url() . 'linkedin/';
    if (($friend_guid = get_input('friend_guid')) && ($invitecode = get_input('invitecode')))
               $url .= "$friend_guid/$invitecode/";
?>
<img id="linkedin_oauth2" src="<?=elgg_get_site_url();?>mod/linkedin_oauth2/graphics/linkedin.png" />
<script>
    $(document).ready(function() {
        $('#linkedin_oauth2').click(function() {
            window.open("<?=$url;?>", "Sign On", "width=800,height=600");
        });
    });
</script>