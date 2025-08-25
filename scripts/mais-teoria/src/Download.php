<?php

class Download
{

    public   $log;
    private  $event;
    private  $outreachdashboard;
    private  $url;

    public function __construct( $event, $outreachdashboard, $log )
    {
        $this->event = $event;
        $this->log = $log;
        $this->url = "https://outreachdashboard.wmflabs.org/course_students_csv?course=" . $outreachdashboard;
    }

    public function getFile()
    {

        $filename = __DIR__ . "/../temp/" . $this->event . ".csv";
		
		$errors = 0;
		while($errors<3){
			if(file_put_contents($filename, file_get_contents($this->url))) {

				$error_string = 'This file is being generated. Please try again shortly.';

				$file_content = file_get_contents($filename);

				if(strpos($file_content, $error_string) !== false){
					$errors++;
					echo $this->log->log("Erro ao realizar download de {$this->url} (arquivo incompleto), tentativa {$errors}!\r\n");
					//Aguarda 5 segundos
					sleep(5);
					
				}else {
					echo $this->log->log("Download de {$this->url} concluído!\r\n");
					return true;
				}

			}else{
				$errors++;
				echo $this->log->log("Erro ao realizar download de {$this->url}, tentativa {$errors}!\r\n");
				//Aguarda 5 segundos
				sleep(5);
			}
		}
		
		//Se o erro continuar após tentativas, retornar false
		echo $this->log->log("Erro persiste ao realizar download de {$this->url} após {$errors} tentativas!\r\n");
		return false;

    }

}

?>
