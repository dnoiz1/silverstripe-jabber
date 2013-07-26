<?php

class JabberGroupExtension extends DataExtension
{
    // this is no where near as good as hooking the manymanylist
    // but that doesnt seem to work, so committing a code crime
    // in eacc module, group->write() is called after modifying members..
    public function onBeforeWrite()
    {
        //DataObject::isChanged() doesnt work for relations apparently.
        $admin_conferences = JabberConference::get()->filter('AdminAffiliatesID', $this->owner->ID);
        $member_conferences = JabberConference::get()->filter('MemberAffiliatesID', $this->owner->ID);

        $conferences = ArrayList::create();
        $conferences->merge($admin_conferences);
        $conferences->merge($member_conferences);
        $conferences->removeDuplicates();

        foreach($conferences as $conference) {
            $conference->SyncAffiliates();
        }

        parent::onBeforeWrite();
    }
}
