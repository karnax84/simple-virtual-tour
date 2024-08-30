<script>
    var ga_tracking_id = '<?php echo $settings['ga_tracking_id']; ?>';
    var font_provider = '<?php echo $settings['font_provider']; ?>';
    var share_providers = '<?php echo $settings['share_providers']; ?>';
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
    if(font_provider=='google' || share_providers!='') {
        if(share_providers!='') {
            categories.functionality = {
                enabled: false,
                services: {
                    'Social Share (AddToAny)': {
                        label: 'Social Share (AddToAny)'
                    }
                }
            }
        } else {
            categories.functionality = {
                enabled: false
            }
        }
        sections.push({
            title: `<?php echo _("Functional"); ?>`,
            description: `<?php echo _("Functional cookies help perform certain functionalities like sharing the content of the website on social media platforms, collecting feedback, and other third-party features."); ?>`,
            linkedCategory: "functionality"
        });
    }
    var cookie_name = 'cc_backend_sn';
    if(ga_tracking_id!='')  cookie_name+='ga';
    if(font_provider=='google') cookie_name+='fg';
    if(share_providers!='') cookie_name+='as';
    CookieConsent.run({
        onChange: function({changedCategories, changedServices}){
            if(changedCategories.includes('functionality')){
                if(changedServices['functionality'].includes('Google Fonts')){
                    if(!CookieConsent.acceptedService('Google Fonts', 'functionality')){
                        $('#font_backend_link').remove();
                    }
                }
                if(changedServices['functionality'].includes('Social Share (AddToAny)')){
                    if(!CookieConsent.acceptedService('Social Share (AddToAny)', 'functionality')){
                        $('#cookie_denied_msg').show();
                        $('.a2a_kit').hide();
                    } else {
                        $('#cookie_denied_msg').hide();
                        if(window.selected_language!==null) {
                            if($('.input_lang[data-target-id="share_link"][data-lang="'+window.selected_language+'"]').length) {
                                $('.input_lang[data-target-id="share_link"][data-lang="'+window.selected_language+'"]').show();
                            } else {
                                $('#share_link').show();
                            }
                        } else {
                            $('.a2a_kit').show();
                        }
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
                        footer: ''
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
    if(!CookieConsent.acceptedService('Social Share (AddToAny)', 'functionality')){
        $('#cookie_denied_msg').show();
        $('.a2a_kit').hide();
    } else {
        $('#cookie_denied_msg').hide();
        if(window.selected_language!==null) {
            if($('.input_lang[data-target-id="share_link"][data-lang="'+window.selected_language+'"]').length) {
                $('.input_lang[data-target-id="share_link"][data-lang="'+window.selected_language+'"]').show();
            } else {
                $('#share_link').show();
            }
        } else {
            $('.a2a_kit').show();
        }
    }
</script>