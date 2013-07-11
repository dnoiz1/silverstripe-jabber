<?php

class JabberMemberConfigPage extends Page implements PermissionProvider
{
    public static $defaults = array(
        'Content' => 'Your Jabber ID is: $JabberID'
    );

    public function providePermissions()
    {
        return array(
            'JABBER' => array(
                'name'  =>  'Allow Jabber Access',
                'category' => 'External Services'
            )
        );
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->insertBefore(LiteralField::create('', 'use $JabberID to display the current users JID'), 'Content');
        return $fields;
    }

    public function Content()
    {
        $member = Member::CurrentUser();
        $jid = sprintf("%s@%s", $member->JabberUser, JabberConfig::$Domain);
        $content = str_ireplace('$JabberID', $jid, $this->Content);
        return $content;

    }

    public function canView($member = null)
    {
        return Permission::check('JABBER', 'any',  $member);
    }
}

class JabberMemberConfigPage_controller extends Page_controller
{
    public function Form()
    {
        $member = Member::CurrentUser();

        $form = Form::create(
            $this,
            'Form',
            FieldList::create(
                CheckBoxField::create('JabberAutoConnect', 'Connect to Jabber automatically when the page loads', $member->JabberAutoConnect)
            ),
            FieldList::create(
                FormAction::create('save', 'Save')
            )
        );

        if(Session::get('JabberFormUpdated')) {
            $form->setMessage('Settings Saved', 'good');
            Session::clear('JabberFormUpdated');
        }
        return $form;
    }

    public function save($data, $form)
    {
        $member = Member::CurrentUser();

        if($member) {
            if(array_key_exists('JabberAutoConnect', $data)) {
                $auto_connect = ($data['JabberAutoConnect'] == 1) ? true : false;
            } else {
                $auto_connect = false;
            }
            $member->JabberAutoConnect = $auto_connect;
            $member->write();
            Session::set('JabberFormUpdated', true);
        }

        return $this->redirectBack();
    }
}

