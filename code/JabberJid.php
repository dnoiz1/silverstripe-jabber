<?php
class JabberJid
{
    public $User;
    public $Domain;
    public $Resource;

    public function __construct($jid)
    {
        $split = preg_split('/(@|\/)/', $jid, 3);

        $this->Domain = JabberConfig::$Domain;

        switch(count($split)) {
            case 3:
                $this->Resource = $split[2];
            case 2:
                $this->Domain   = $split[1];
            case 1:
                $this->User     = $split[0];
        }
    }

    public function Jid()
    {
        return sprintf("%s@%s", $this->User, $this->Domain);
    }
}
