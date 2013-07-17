<?php
class JabberSharedRoster extends DataObject
{
    private static $db = array(
        'Global'    => 'Boolean'
    );

    private static $has_one = array(
        'Group' => 'Group'
    );

    private $has_many = array(
        'JabberSharedRoster' => 'JabberSharedRoster'
    );

    public static $summary_fields = array(
        'Group.Title' => 'Group'
    );

    public function getCMSFields()
    {
        $f = parent::getCMSFields();
        $f->replaceField('GroupID', DropDownField::create('GroupID', 'Group', Group::get()->map()));
        return $f;
    }
}
