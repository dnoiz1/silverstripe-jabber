<?php

class JabberConference extends DataObject
{
    private static $db = array(
        'Title'            => 'Varchar(255)',
        'Private'          => 'Boolean',
        'GlobalAutoJoin'   => 'Boolean'
    );

    private static $has_one = array(
        'AdminAffiliates'   => 'Group',
        'MemberAffiliates'  => 'Group'
    );

    public function getCMSFields()
    {
        $groups = Group::get()->map();

        $fields = parent::getCMSFields();
        $fields->replaceField('AdminAffiliatesID',
            DropDownField::create('AdminAffiliatesID', 'Admin Affiliates', $groups)
                ->setEmptyString('(none)')
        );
        $fields->replaceField('MemberAffiliatesID',
            DropDownField::create('MemberAffiliatesID', 'Member Affiliates', $groups)
                ->setEmptyString('(none)')
        );

        return $fields;
    }

    public function Affiliates()
    {
        $affiliates = JabberXMLRPC::get_room_affiliations(array(
            'name'    => strtolower($this->Title),
            'service' => JabberConfig::$ConferenceDomain
        ));

        $affiliations = array(
            'owner'  => array(),
            'admin'  => array(),
            'member' => array()
        );

        if(!property_exists($affiliates, 'affiliations')) return $affiliations;

        //cleanup the mess, create full JIDs
        foreach($affiliates->affiliations as $aff) {
            $aff = $aff['affiliation'];

            $jid = new JabberJid($aff['0']['username']);
            $jid->Domain = $aff[1]['domain'];

            $affiliations[$aff[2]['affiliation']][] = $jid->Jid();
        }

        return $affiliations;

    }

    public function AdminAffiliatesJids()
    {
        // GroupID == 0, all members.. that is bad.
        if(!$this->AdminAffiliatesID) return array();

        return array_map(function($a){
            $jid = new JabberJid($a);
            return $jid->Jid();
        }, $this->AdminAffiliates()->Members()->column('JabberUser'));
    }

    public function MemberAffiliatesJids()
    {
        // GroupID == 0, all members.. that is bad.
        if(!$this->MemberAffiliatesID) return array();

         return array_map(function($a){
             $jid = new JabberJid($a);
             return $jid->Jid();
         }, $this->MemberAffiliates()->Members()->column('JabberUser'));
    }

    public function SyncAffiliates()
    {
        $title = strtolower($this->Title);

        $affiliations = $this->Affiliates();

        $admins  = $this->AdminAffiliatesJids();
        $members = $this->MemberAffiliatesJids();

        //user_error(print_r($members,true), E_USER_NOTICE);
        //user_error(print_r($admins,true), E_USER_NOTICE);

        $remove_admins  = array_diff($affiliations['admin'], $admins);
        $remove_members = array_diff($affiliations['member'], $members);

        $add_admins  = array_diff($admins, $affiliations['admin']);
        $add_members = array_diff($members, $affiliations['member']);

        //var_dump($remove_admins, $remove_members);
        //var_dump($add_admins, $add_members);

        /* TODO: we need to make sure we dont overwrite our own changes */

        foreach($remove_admins as $remove) {
            JabberXMLRPC::set_room_affiliation(array(
                'name'        => $title,
                'service'     => JabberConfig::$ConferenceDomain,
                'jid'         => $remove,
                'affiliation' => 'none'
            ));
        }

        foreach($remove_members as $remove) {
            if(in_array($remove, $admins)) continue;

            JabberXMLRPC::set_room_affiliation(array(
                'name'  => $title,
                'service' => JabberConfig::$ConferenceDomain,
                'jid'   => $remove,
                'affiliation' => 'none'
            ));
        }

        foreach($add_members as $add)  {
            if(in_array($add, $admins)) continue;

            JabberXMLRPC::set_room_affiliation(array(
                'name'        => $title,
                'service'     => JabberConfig::$ConferenceDomain,
                'jid'         => $add,
                'affiliation' => 'member'
            ));
        }

        foreach($add_admins as $add)  {
            JabberXMLRPC::set_room_affiliation(array(
                'name'        => $title,
                'service'     => JabberConfig::$ConferenceDomain,
                'jid'         => $add,
                'affiliation' => 'admin'
            ));
        }

    }
}
