<?php

class Creations
{

    private  $user;
    private  $robot;
    private  $report;
    private  $period;

    public function __construct( $user, $robot, $period )
    {
        $this->user = $user;
        $this->robot = $robot;
        $this->report = "";
        $this->period = $period;
        $this->process();
    }

    public function returnReport()
    {

        if($this->report==""){
          return FALSE;
        }else{
          return $this->report;
        }

    }

    private function process(){

        $this->getCreations();

    }

    private function getCreations(){

        $params = [
            "action" => "query",
            "list" => "logevents",
            "leuser" => $this->user,
            "leend" => $this->period['end'],
            "lestart" => $this->period['start'],
			"letype" => "create",
			"lenamespace" => 0,
            "lelimit" => "500"
        ];

        $result = $this->robot->api->request($params);

        //Checa se criou algum artigo
        if(count($result['query']['logevents'])==0){
            return FALSE;

        }

		//Se sim, salva no relatório		
		foreach($result['query']['logevents'] as $key => $value){
			
			 echo $this->robot->log->log("{$this->user} CRIOU {$value['title']}!\r\n");
			 
			$this->report .= "
*[[{$value['title']}]]
::'''Sumário de edição''': <nowiki>{$value['comment']}</nowiki>";
		}

    }

}

?>
