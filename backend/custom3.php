<?php
session_start();
$id_user = $_SESSION['id_user'];
$user_info = get_user_info($id_user);
$custom_menu = true;
if($user_info['role']!='administrator') {
    if ($user_info['id_plan'] != 0) {
        $plan = get_plan($user_info['id_plan']);
        $customize_menu_json = $plan['customize_menu'];
        if (!empty($customize_menu_json)) {
            $customize_menu = json_decode($customize_menu_json, true);
            if(isset($customize_menu['custom3'])) {
                if ($customize_menu['custom3'] == 0) {
                    $custom_menu = false;
                }
            }
        }
    }
}
$settings = get_settings();
$extra_menu_items = $settings['extra_menu_items'];
if(!empty($extra_menu_items)) {
    $extra_menu_items=json_decode($extra_menu_items,true);
    if(empty($extra_menu_items[2]['link']) || $extra_menu_items[2]['type']!='iframe') {
        $custom_menu = false;
    }
} else {
    $custom_menu = false;
}
?>

<?php if(!$custom_menu): ?>
    <div class="text-center">
        <div class="error mx-auto" data-text="401">401</div>
        <p class="lead text-gray-800 mb-5"><?php echo _("Permission denied"); ?></p>
        <p class="text-gray-500 mb-0"><?php echo _("It looks like that you do not have permission to access this page"); ?></p>
        <a href="index.php?p=dashboard">← <?php echo _("Back to Dashboard"); ?></a>
    </div>
<?php die(); endif; ?>

<div style="display: none;margin-bottom:-10px;" id="iframe_div">
   <iframe allow='accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;' width="100%" height="100vh" frameborder="0" scrolling="yes" marginheight="0" marginwidth="0" src="<?php echo $extra_menu_items[2]['link']; ?>"></iframe>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        $(document).ready(function () {
            var container_h = $('#content-wrapper').height() - 120;
            $('#iframe_div iframe').attr('height',container_h+'px');
            $('#iframe_div').show();
        });
        $(window).resize(function () {
            var container_h = $('#content-wrapper').height() - 120;
            $('#iframe_div iframe').attr('height',container_h+'px');
        });
    })(jQuery); // End of use strict
</script>