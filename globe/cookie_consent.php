<script>
    var ga_tracking_id = '<?php echo $ga_tracking_id; ?>';
    var font_provider = '<?php echo $font_provider; ?>';
    var show_cookie_policy = <?php echo ((!empty($cookie_policy) && $cookie_policy!='<p></p>') ? 1 : 0); ?>;
    var cookie_link = '<?php echo (strpos($cookie_policy, 'http') === 0) ? $cookie_policy : ''; ?>';
    var footer = '';
    if(show_cookie_policy==1) {
        if(cookie_link=='') {
            footer = '<a data-toggle=\'modal\' data-target=\'#modal_cookie_policy_b\' style="margin:0 auto;" href="#"><?php echo _("Cookie Policy"); ?></a>';
        } else {
            footer = '<a target="_blank" style="margin:0 auto;" href="'+cookie_link+'"><?php echo _("Cookie Policy"); ?></a>';
        }
    }
    var categories = {
        necessary: {
            readOnly: true
        }
    };
    var sections = [
        {
            title: `<?php echo _("Usage"); ?>`,
            description: `<?php echo _("We use cookies to help you navigate efficiently and perform certain functions. You will find detailed information about all cookies under each consent category below."); ?>`
        },
        {
            title: `<?php echo _("Strictly Necessary <span class=\"pm__badge\">Always Enabled</span>"); ?>`,
            description: `<?php echo _("Necessary cookies help make a website usable by enabling basic functions like page navigation and access to secure areas of the website. The website cannot function properly without these cookies."); ?>`,
            linkedCategory: "necessary"
        }
    ];
    if(ga_tracking_id!='') {
        categories.analytics = {
            enabled: false
        };
        sections.push({
            title: `<?php echo _("Analytics"); ?>`,
            description: `<?php echo _("Analytical cookies are used to understand how visitors interact with the website. These cookies help provide information on metrics such as the number of visitors, bounce rate, traffic source, etc."); ?>`,
            linkedCategory: "analytics"
        });
    }
    if(font_provider=='google') {
        categories.functionality = {
            enabled: false
        }
        sections.push({
            title: `<?php echo _("Functional"); ?>`,
            description: `<?php echo _("Functional cookies help perform certain functionalities like sharing the content of the website on social media platforms, collecting feedback, and other third-party features."); ?>`,
            linkedCategory: "functionality"
        });
    }
    var cookie_name = 'cc_globe_sn';
    if(ga_tracking_id!='')  cookie_name+='ga';
    if(font_provider=='google') cookie_name+='fg';
    CookieConsent.run({
        onChange: function({changedCategories, changedServices}){
            if(changedCategories.includes('functionality')){
                if(changedServices['functionality'].includes('Google Fonts')){
                    if(!CookieConsent.acceptedService('Google Fonts', 'functionality')){
                        $('#font_backend_link').remove();
                    }
                }
            }
            if(changedCategories.includes('analytics')){
                if(changedServices['analytics'].includes('Google Analytics')){
                    if(!CookieConsent.acceptedService('Google Analytics', 'analytics')){
                        CookieConsent.eraseCookies(['_gid', /^_ga/], '/', location.hostname);
                    }
                }
            }
        },
        onModalShow: function() {
            $('#cookie_consent_preferences').hide();
        },
        onModalReady: function() {
            $('#cookie_consent_preferences').hide();
        },
        onModalHide: function() {
            $('#cookie_consent_preferences').show();
            $('#cookie_consent_preferences span').css('display','inline-block');
            setTimeout(function() {
                $('#cookie_consent_preferences span').css('display','');
            },1000);
        },
        cookie: {
            name: cookie_name
        },
        guiOptions: {
            consentModal: {
                layout: "box",
                position: "bottom right",
                equalWeightButtons: false,
                flipButtons: false
            },
            preferencesModal: {
                layout: "box",
                position: "right",
                equalWeightButtons: false,
                flipButtons: false
            }
        },
        categories: categories,
        language: {
            default: "en",
            translations: {
                en: {
                    consentModal: {
                        title: `<?php echo _("We value your privacy!"); ?>`,
                        description: `<?php echo _("We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking \"Accept All\", you consent to our use of cookies."); ?>`,
                        acceptAllBtn: `<?php echo _("Accept all"); ?>`,
                        acceptNecessaryBtn: `<?php echo _("Reject all"); ?>`,
                        showPreferencesBtn: `<?php echo _( "Manage preferences"); ?>`,
                        footer: footer
                    },
                    preferencesModal: {
                        title: `<?php echo _("Cookie Preferences"); ?>`,
                        acceptAllBtn: `<?php echo _("Accept all"); ?>`,
                        acceptNecessaryBtn: `<?php echo _("Reject all"); ?>`,
                        savePreferencesBtn: `<?php echo _("Save preferences"); ?>`,
                        closeIconLabel: `<?php echo _("Close modal"); ?>`,
                        serviceCounterLabel: `<?php echo _("Service|Services"); ?>`,
                        sections: sections
                    }
                }
            }
        }
    });
</script>