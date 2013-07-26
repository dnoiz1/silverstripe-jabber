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
        $this->repeat = 600;
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
            $conference->SyncAffiliates();
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
