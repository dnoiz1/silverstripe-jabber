# Silverstripe Jabber Module

A module for integrating ejabberd, Jappix mini and Silverstripe

## Features
- [x] Javascript XMPP client (jappix mini)
- [x] Token authentication against ejabberd
- [x] MUC affiliation management via Security Groups
- [ ] Jappix MUC autoconnect
- [ ] Group Broadcast
- [ ] ejabberd vCard updating
- [ ] ejabberd Shared Roster Groups management via Security Groups


## Maintainer Contacts

* Tim Noise <tim@drkns.net>

## Requirements

* SilverStripe 3.1+
* [silverstripe-queuedjobs](https://github.com/nyeholt/silverstripe-queuedjobs)
* eJabberd 2.1.11+
* eJabberd mod_muc_admin, mod_admin_extra, ejabberd_xmlrpc

## Installation

* Clone to into your base in a directory called jabber
* Run /dev/build
* Configure ejabberd to use external authenticator (see scripts/ejabberd-silverstripe-auth.py)
* Create a proxy pass bind to ejabberd's httpd module (see scripts/nginx-ejabberd-upstream.conf)
* Add the Jabber permission to groups you wish to allow jabber access via CMS
* Create a Jabber Member Config Page in your Site Tree

## Known Issues
* Only Top level and Second level groups can be authenticated
* Auth script log paths are set for linux defaults (/var/log/ejabberd)
