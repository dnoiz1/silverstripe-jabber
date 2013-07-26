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

                $global_auto_join  = JabberConference::get()->filter('GlobalAutoJoin', true);
                $private_auto_join = $member->AutoJoinConferences();

                $auto_join_rooms = ArrayList::create();

                $auto_join_rooms->merge($global_auto_join);
                $auto_join_rooms->merge($private_auto_join);
                $auto_join_rooms->removeDuplicates();

                $auto_join_rooms = $auto_join_rooms->column('Title');

                foreach($auto_join_rooms as &$room) {
                    $room = sprintf('%s@%s', $room, $mucdomain);
                }

                $auto_join_rooms = json_encode($auto_join_rooms);

                // require jquery also
                Requirements::JavaScript('jabber/thirdparty/mini/js/mini.js');
                Requirements::CustomScript(<<<JS
                    $(function(){
                        JAPPIX_STATIC = '/jabber/thirdparty/mini/';
                        HOST_BOSH     = '//' + location.host + '/{$bosh_url}';
                        MINI_ANIMATE  = {$animate};
                        MINI_NICKNAME = '{$nickname}';
                        MINI_RESOURCE = '{$sitename}/mini';

                        MINI_GROUPCHATS = {$auto_join_rooms};

                        launchMini({$autoconnect}, false, '{$domain}', '{$username}', '{$token}');
                    });

JS
                );

                Requirements::CustomCSS(<<<CSS
                    #jappix_mini { z-index: 9999 !important; }
                    #jappix_mini div.jm_roster { width: 210px !important; }
                    a.jm_logo { display: none !important; }
                    #jappix_mini div.jm_search input.jm_searchbox { background-position: 9px -376px !important; }
                    #jappix_mini div.jm_chat-content { height: 305px !important; }
CSS
                );
            }
        }
    }
}
