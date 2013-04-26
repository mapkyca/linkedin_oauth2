<?php


?>
<img id="linkedin_oauth2" src="<?=elgg_get_site_url();?>mod/linkedin_oauth2/graphics/linkedin.png" />
<script>
    $(document).ready(function() {
        $('#linkedin_oauth2').click(function() {
            window.open("<?=  elgg_get_site_url() ?>linkedin/", "Sign On", "width=800,height=600");
        });
    });
</script>