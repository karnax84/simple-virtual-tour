<?php
require_once('functions.php');
?>
<footer id="footer_login" class="sticky-footer glass_effect py-2 noselect">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>&copy; <?php echo $settings['name']; ?> <?php echo date('Y'); ?> - <?php echo _("Version")." ".$settings['version']; ?></span>
            <?php
            if(!empty($settings['footer_link_1']) || !empty($settings['footer_link_2']) || !empty($settings['footer_link_3']) || !empty($settings['terms_and_conditions']) || !empty($settings['privacy_policy'])) {
                echo "&nbsp;&nbsp;&nbsp;";
            }
            $footer_items_html = "";
            if(strpos($settings['terms_and_conditions'], 'http') === 0) {
                $footer_items_html.= "<span class='footer_link'><a target='_blank' href='".$settings['terms_and_conditions']."'>"._("Terms and Conditions")."</a></span> | ";
            } else if(!empty($settings['terms_and_conditions']) && $settings['terms_and_conditions']!='<p></p>') {
                $footer_items_html.= "<span class='footer_link'><a href='#' data-toggle='modal' data-target='#modal_terms_and_conditions_b'>"._("Terms and Conditions")."</a></span> | ";
            }
            if(strpos($settings['privacy_policy'], 'http') === 0) {
                $footer_items_html.= "<span class='footer_link'><a target='_blank' href='".$settings['privacy_policy']."'>"._("Privacy Policy")."</a></span> | ";
            } else if(!empty($settings['privacy_policy']) && $settings['privacy_policy']!='<p></p>') {
                $footer_items_html.= "<span class='footer_link'><a href='#' data-toggle='modal' data-target='#modal_privacy_policy_b'>"._("Privacy Policy")."</a></span> | ";
            }
            if(strpos($settings['cookie_policy'], 'http') === 0) {
                $footer_items_html.= "<span class='footer_link'><a target='_blank' href='".$settings['cookie_policy']."'>"._("Cookie Policy")."</a></span> | ";
            } else if(!empty($settings['cookie_policy']) && $settings['cookie_policy']!='<p></p>') {
                $footer_items_html.= "<span class='footer_link'><a href='#' data-toggle='modal' data-target='#modal_cookie_policy_b'>"._("Cookie Policy")."</a></span> | ";
            }
            if ($settings['cookie_consent']) {
                $footer_items_html.= "<span class='footer_link'><a data-cc=\"show-consentModal\" href='#'><i class=\"fa-solid fa-cookie-bite\"></i>&nbsp;"._("Cookie Preferences")."</a></span> | ";
            }
            if(!empty($settings['footer_link_1'])) {
                if(strpos($settings['footer_value_1'], 'http') === 0) {
                    $footer_items_html.= "<span class='footer_link'><a target='_blank' href='".$settings['footer_value_1']."'>".$settings['footer_link_1']."</a></span> | ";
                } else if(!empty($settings['footer_value_1']) && $settings['footer_value_1']!='<p></p>') {
                    $footer_items_html.= "<span class='footer_link'><a href='#' data-toggle='modal' data-target='#modal_footer_value_1'>".$settings['footer_link_1']."</a></span> | ";
                } else {
                    $footer_items_html.= "<span>".$settings['footer_link_1']."</span> | ";
                }
            }
            if(!empty($settings['footer_link_2'])) {
                if(strpos($settings['footer_value_2'], 'http') === 0) {
                    $footer_items_html.= "<span class='footer_link'><a target='_blank' href='".$settings['footer_value_2']."'>".$settings['footer_link_2']."</a></span> | ";
                } else if(!empty($settings['footer_value_2']) && $settings['footer_value_2']!='<p></p>') {
                    $footer_items_html.= "<span class='footer_link'><a href='#' data-toggle='modal' data-target='#modal_footer_value_2'>".$settings['footer_link_2']."</a></span> | ";
                } else {
                    $footer_items_html.= "<span>".$settings['footer_link_2']."</span> | ";
                }
            }
            if(!empty($settings['footer_link_3'])) {
                if(strpos($settings['footer_value_3'], 'http') === 0) {
                    $footer_items_html.= "<span class='footer_link'><a target='_blank' href='".$settings['footer_value_3']."'>".$settings['footer_link_3']."</a></span> | ";
                } else if(!empty($settings['footer_value_3']) && $settings['footer_value_3']!='<p></p>') {
                    $footer_items_html.= "<span class='footer_link'><a href='#' data-toggle='modal' data-target='#modal_footer_value_3'>".$settings['footer_link_3']."</a></span> | ";
                } else {
                    $footer_items_html.= "<span>".$settings['footer_link_3']."</span> | ";
                }
            }
            $footer_items_html = rtrim($footer_items_html,' | ');
            echo $footer_items_html;
            ?>
        </div>
    </div>
</footer>

<?php if(!empty($settings['terms_and_conditions']) && $settings['terms_and_conditions']!='<p></p>') : ?>
    <div id="modal_terms_and_conditions_b" class="modal" tabindex="-1" role="dialog">
        <div style="max-width: 1280px;" class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo _("Terms and Conditions"); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo $settings['terms_and_conditions']; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _("Close"); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if(!empty($settings['privacy_policy']) && $settings['privacy_policy']!='<p></p>') : ?>
    <div id="modal_privacy_policy_b" class="modal" tabindex="-1" role="dialog">
        <div style="max-width: 1280px;" class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo _("Privacy Policy"); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo $settings['privacy_policy']; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _("Close"); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if(!empty($settings['cookie_policy']) && $settings['cookie_policy']!='<p></p>') : ?>
    <div id="modal_cookie_policy_b" class="modal" tabindex="-1" role="dialog">
        <div style="max-width: 1280px;" class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo _("Cookie Policy"); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo $settings['cookie_policy']; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _("Close"); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if(!empty($settings['footer_value_1']) && $settings['footer_value_1']!='<p></p>') : ?>
<div id="modal_footer_value_1" class="modal" tabindex="-1" role="dialog">
    <div style="max-width: 1280px;" class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $settings['footer_link_1']; ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo $settings['footer_value_1']; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _("Close"); ?></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if(!empty($settings['footer_value_2']) && $settings['footer_value_2']!='<p></p>') : ?>
    <div id="modal_footer_value_2" class="modal" tabindex="-1" role="dialog">
        <div style="max-width: 1280px;" class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $settings['footer_link_2']; ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo $settings['footer_value_2']; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _("Close"); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if(!empty($settings['footer_value_3']) && $settings['footer_value_3']!='<p></p>') : ?>
    <div id="modal_footer_value_3" class="modal" tabindex="-1" role="dialog">
        <div style="max-width: 1280px;" class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $settings['footer_link_3']; ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo $settings['footer_value_3']; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _("Close"); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>