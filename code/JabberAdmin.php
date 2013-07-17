<?php

class JabberAdmin extends ModelAdmin
{
    static $managed_models = array('JabberConference', 'JabberSharedRoster');
    static $url_segment = 'jabber';
    static $menu_title = 'Jabber';

    // disable import
    static $model_importers = array();

}
