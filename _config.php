<?php

if(basename(dirname(__FILE__)) != "jabber") {
        user_error("silverstripe-jabber should be a folder named jabber", E_USER_ERROR);
}

/* config */

# JabberConfig::$Animate          = false;
# JabberConfig::$Domain           = 'mydomain.com';
# JabberConfig::$ConferenceDomain = 'conference.mydomain.com';
# JabberConfig::$BOSHUrl          = 'http-bind/';
