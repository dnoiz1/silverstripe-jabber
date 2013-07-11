<?php

class JabberPageExtension extends Extension
{
    public function onAfterInit()
    {
        $member = Member::CurrentUser();

        if($member) {
            if(Permission::check('JABBER') && $member->JabberUser && $member->JabberToken) {
                $nickname    = preg_replace('/[^a-zA-Z0-9_ ]/', '', sprintf("%s %s", $member->FirstName, $member->LastName));
                $animate     = (JabberConfig::$Animate) ? 'true' : 'false';
                $sitename    = SiteConfig::current_site_config()->Title;
                $autoconnect = ($member->JabberAutoConnect) ? 'true' : 'false';
                $username    = $member->JabberUser;
                $token       = $member->JabberToken;
                $domain      = JabberConfig::$Domain;
                $mucdomain   = JabberConfig::$ConferenceDomain;
                $bosh_url    = JabberConfig::$BOSHUrl;

                // require jquery also
                Requirements::JavaScript('jabber/thirdparty/mini/js/mini.js');
                Requirements::CustomScript(<<<JS
                    $(function(){
                        JAPPIX_STATIC = '/jabber/thirdparty/mini/';
                        HOST_BOSH     = '//' + location.host + '/{$bosh_url}';
                        MINI_ANIMATE  = {$animate};
                        MINI_NICKNAME = '{$nickname}';
                        MINI_RESOURCE = '{$sitename}/mini';

                        //MINI_GROUPCHATS = ['some-muc@{$mucdomain}'];

                        launchMini({$autoconnect}, false, '{$domain}', '{$username}', '{$token}');
                    });

JS
                );

                Requirements::CustomCSS(<<<CSS
                    a.jm_logo { display: none !important; }
                    #jappix_mini div.jm_search input.jm_searchbox { background-position: 9px -376px !important; }
CSS
                );
            }
        }
    }
}
