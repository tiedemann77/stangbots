<?php

class Moves
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

        $this->getMoves();

    }

    private function getMoves(){

        $params = [
            "action" => "query",
            "list" => "logevents",
            "leuser" => $this->user,
            "leend" => $this->period['end'],
            "lestart" => $this->period['start'],
			"leaction" => "move/move",
            "lelimit" => "500"
        ];

        $result = $this->robot->api->request($params);

        //Checa se moveu algum artigo
        if(count($result['query']['logevents'])==0){

            return FALSE;

        }
		
		foreach($result['query']['logevents'] as $key => $value){
			//Se sim e for para domínio principal, salva no relatório
			if($value['params']['target_ns']===0){
				
				echo $this->robot->log->log("{$this->user} MOVEU {$value['title']} para {$value['params']['target_title']}!\r\n");
				
				$this->report .= "
*'''MOVIMENTO''': [[{$value['title']}]] movido para [[{$value['params']['target_title']}]]";

			}
			
		}

    }

}

?>
