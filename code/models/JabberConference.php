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
}
