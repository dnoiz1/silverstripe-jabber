<?php

class QueueJabberConferenceAffiliateJobTask extends BuildTask
{
    protected $description = 'Queue the Jabber Conference Affiliate Job';

    public function run($request) {
        $job = new JabberConferenceAffiliateJob();
        singleton('QueuedJobService')->queueJob($job);
    }
}


class JabberConferenceAffiliateJob extends AbstractQueuedJob
{
	public function __construct()
    {
        $this->repeat = 3600;
	}

    private $conferences = array();

	public function getTitle()
    {
		return "Scheduled Updated of Jabber Conference Affiliates";
	}

    public function getJobType()
    {
        return QueuedJob::IMMEDIATE;
    }

    public function setup()
    {
        $conferences_online = JabberXMLRPC::muc_online_rooms(array('host' => JabberConfig::$ConferenceDomain));
        $conferences = array();

        foreach($conferences_online->rooms as $room) {
            $jid = new JabberJid($room['room']);
            $conferences[] = $jid->User;
        }

        $this->conferences = JabberConference::get()->filter('Title', $conferences)->getIDList();
        $this->totalSteps = count($this->conferences);

    }

	public function process()
    {
        $conference_id = array_shift($this->conferences);
        $conference = JabberConference::get()->byID($conference_id);

        if($conference) {
            $title = strtolower($conference->Title);

            $admins  = array();
            $members = array();

            if($conference->AdminAffiliatesID) {
                $admins  = array_map(function($a){
                    $jid = new JabberJid($a);
                    return $jid->Jid();
                }, $conference->AdminAffiliates()->Members()->column('JabberUser'));
            }

            if($conference->MemberAffiliatesID) {
                $members = array_map(function($a){
                    $jid = new JabberJid($a);
                    return $jid->Jid();
                }, $conference->MemberAffiliates()->Members()->column('JabberUser'));
            }

            $affiliates = JabberXMLRPC::get_room_affiliations(array(
                'name'    => $title,
                'service' => JabberConfig::$ConferenceDomain
            ));

            $affiliations = array(
                'owner'  => array(),
                'admin'  => array(),
                'member' => array()
            );

            //cleanup the mess, create full JIDs
            foreach($affiliates->affiliations as $aff) {
                $aff = $aff['affiliation'];

                $jid = new JabberJid($aff['0']['username']);
                $jid->Domain = $aff[1]['domain'];

                $affiliations[$aff[2]['affiliation']][] = $jid->Jid();
            }

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

		$this->currentStep++;

        if(!count($this->conferences)) {

    		if($this->repeat) {
    	    	$job = new JabberConferenceAffiliateJob();
    			singleton('QueuedJobService')->queueJob($job, date('Y-m-d H:i:s', time() + $this->repeat));
        	}

    		$this->isComplete = true;
        }
	}
}
