<?php

class File
{

    private  $csv;
    public   $log;
    private  $state;
    private  $url;
    private  $zip;

    public function __construct( $state, $log )
    {
        $this->state = $state;
        $this->log = $log;
        $this->url = "https://cdn.tse.jus.br/estatistica/sead/odsele/perfil_eleitor_secao/perfil_eleitor_secao_ATUAL_{$state}.zip";
    }

    private function download()
    {

        $filename = __DIR__ . "/../data/zip/" . $this->state . ".zip";

        if(file_put_contents($filename, file_get_contents($this->url))) {
            $this->zip = $filename;
            echo $this->log->log("Download de {$this->zip} concluído!\r\n");
            return true;
        }else
        {
            echo $this->log->log("Erro ao realizar download de {$this->zip}!\r\n");
            return false;
        }

    }

    public function getState()
    {
        return $this->state;
    }

    public function process()
    {

        echo $this->log->log("Processando {$this->state}...\r\n");

        echo $this->log->log("Realizando download do arquivo .zip do TSE...\r\n");

        if($this->download()===false) {
            return;
        }

        echo $this->log->log("Extraindo arquivo...\r\n");

        if($this->unZip()===false) {
            return;
        }

        echo $this->log->log("Lendo csv...\r\n");

        return $this->readCSV();

    }

    private function readCSV()
    {

        echo $this->log->log("Lendo {$this->csv}...\r\n");

        $file = fopen($this->csv, 'r');

        // Onde salvar os valores
        $data = array();

        while (($row = fgetcsv($file, 0, ";")) !== false) {

            /*
            CAMPOS:
            3 = código do estado;
            4 = código do município;
            18 = total de eleitores no perfil
            */

            // Pula cabeçalho
            if($row[3]=="SG_UF") {
                continue;
            }

            // Processamos primeiro o estado
            if(!isset($data[$row[3]])) {
                $data[$row[3]]['0'] = $row[18];
            }else{
                $data[$row[3]]['0'] += $row[18];
            }

            // Agora processamos o município
            if(!isset($data[$row[3]][$row[4]])) {
                $data[$row[3]][$row[4]] = $row[18];
            }else{
                $data[$row[3]][$row[4]] += $row[18];
            }

        }

        fclose($file);

        echo $this->log->log("Arquivo csv lido!\r\n");

        // Exclui o csv
        unlink($this->csv);

        echo $this->log->log("Arquivo csv excluído!\r\n");

        return $data;

    }

    private function unZip()
    {

        $handle = new ZipArchive;

        if($handle->open($this->zip)===true) {

            $handle->extractTo(__DIR__ . "/../data/csv/");

            $handle->close();

            if(file_exists(__DIR__ . "/../data/csv/perfil_eleitor_secao_ATUAL_{$this->state}.csv")) {

                rename(__DIR__ . "/../data/csv/perfil_eleitor_secao_ATUAL_{$this->state}.csv", __DIR__ . "/../data/csv/{$this->state}.csv");

                $this->csv = __DIR__ . "/../data/csv/{$this->state}.csv";

                echo $this->log->log("Extração de {$this->csv} concluída!\r\n");

                unlink($this->zip);

                // Deletar pdf também
                unlink(__DIR__ . "/../data/csv/leiame.pdf");

                echo $this->log->log("Arquivo zip excluído!\r\n");

                return true;

            }else{
                echo $this->log->log("Erro ao extrair {$this->zip}!\r\n");
                return false;
            }
        }else{
            echo $this->log->log("Erro ao extrair {$this->zip}!\r\n");
            return false;
        }
    }

}

?>
