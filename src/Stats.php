<?php

require_once "Common.php";
require_once "Debug.php";

// Classe para estatísticas
class Stats extends Common
{

    private $stats;
    private $time;

    public function __construct()
    {
        $this->stats = [];
        $this->startDuration();
    }

    public function __destruct()
    {
        if($this->isDebug()) {
            if(count($this->stats)>0) {

                $this->endDuration();

                echo "Exibindo estatísticas para o modo de teste:\r\n";

                foreach ($this->stats as $key => $value) {
                     echo $key . ": " . $value . "\r\n";
                }

            }
        }
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
