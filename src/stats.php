<?php

require_once "common.php";
require_once "debug.php";

// Classe para estatísticas
class stats extends common
{

		private $start;
    private $time;

    public function __construct()
    {
        $this->stats = array();
        $this->startDuration();
    }

    public function bye($message)
    {

    }

    private function endDuration()
    {
        $this->time['end'] = new DateTime(date("Y-m-d H:i:s"));
        $this->stats["duration"] = $this->time['end']->getTimestamp() - $this->time['start']->getTimestamp();
    }

    public function getEnd()
    {
        $this->endDuration();
        return $this->time['end'];
    }

    public function getStart()
    {
        return $this->time['start'];
    }

    public function getStats()
    {
        $this->endDuration();
        return $this->stats;
    }

    public function increaseStats($type)
    {

        if(!isset($this->stats[$type])) {
            $this->stats[$type] = 1;
        }else{
            $this->stats[$type]++;
        }

    }

    protected function isDebug()
    {

        if(!isset($this->debug)) {
            $this->debug = debug::isDebug();
        }

        return $this->debug;

    }

    private function startDuration()
    {
        $this->time['start'] = new DateTime(date("Y-m-d H:i:s"));
    }

}

?>
