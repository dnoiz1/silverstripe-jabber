<?php
class JabberMemberExtension extends DataExtension
{
    private static $db = array(
        'JabberUser'        => 'Varchar(255)',
        'JabberToken'       => 'Varchar(40)',
        'JabberAutoConnect' => 'Boolean'
    );

    private static $defaults = array(
        'JabberAutoConnect' => true
    );

    public function FirstNameToJabberUser($suffix = 0)
    {
        $nn = $this->owner->FirstName;
        $nn = strtolower($nn);
        $nn = trim($nn);
        $nn = str_replace(' ', '_', $nn);
        $nn = preg_replace('/[^a-zA-Z0-9_]/', '', $nn);
        if($suffix > 0) $nn .= $suffix;

        if($m = Member::get()->filter('JabberUser', $nn)->exclude('ID', $this->owner->ID)) {
            if($m->count() > 0) $nn = $this->FirstNameToJabberUser($suffix+1);
        }
        return $nn;
    }

    public function AllowedJabber()
    {
        return Permission::checkMember($this->owner->ID, 'JABBER');
    }

    public function onBeforeWrite()
    {
        if($this->owner->isChanged('FirstName') || $this->owner->JabberUser == '') {
            $this->owner->JabberUser = $this->FirstNameToJabberUser();
        }

        if($this->owner->isChanged('NumVisit')) {
            $gen = new RandomGenerator();
            $this->owner->JabberToken = $gen->randomToken('sha1');
        }

        return parent::onBeforeWrite();
    }

}
