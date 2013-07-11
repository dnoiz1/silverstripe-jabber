# Silverstripe Jabber Module

A module for integrating ejabberd, Jappix mini and Silverstripe

## Maintainer Contacts

* Tim Noise <tim@drkns.net>

## Requirements

* SilverStripe 3.1+

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

## WIP features
* MUC affiliation management via Security Groups
* Jappix MUC autoconnect
* Group Broadcast
* ejabberd vCard updating
* ejabberd Shared Roster Groups management vis Security Groups
