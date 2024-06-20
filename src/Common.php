<?php

abstract class Common{

	protected $debug;

	abstract public function bye($message);

	abstract protected function isDebug();

	// Função para converter meses em números em meses por extenso
	public function monthstoPT($month){
	  switch ($month) {
	    case "01":
	      $month = "janeiro";
	      break;
	    case "02":
	      $month = "fevereiro";
	      break;
	    case "03":
	      $month = "março";
	      break;
	    case "04":
	      $month = "abril";
	      break;
	    case "05":
	      $month = "maio";
	      break;
	    case "06":
	      $month = "junho";
	      break;
	    case "07":
	      $month = "julho";
	      break;
	    case "08":
	      $month = "agosto";
	      break;
	    case "09":
	      $month = "setembro";
	      break;
	    case "10":
	      $month = "outubro";
	      break;
	    case "11":
	      $month = "novembro";
	      break;
	    case "12":
	      $month = "dezembro";
	      break;
	    default:
	      $month = "Error!";
	  }

	  return $month;
	}

	// Função para converter meses por extenso em meses em números
	public function monthsfromPT($month){
	  switch ($month) {
	    case "janeiro":
	      $month = "01";
	      break;
	    case "fevereiro":
	      $month = "02";
	      break;
	    case "março":
	      $month = "03";
	      break;
	    case "abril":
	      $month = "04";
	      break;
	    case "maio":
	      $month = "05";
	      break;
	    case "junho":
	      $month = "06";
	      break;
	    case "julho":
	      $month = "07";
	      break;
	    case "agosto":
	      $month = "08";
	      break;
	    case "setembro":
	      $month = "09";
	      break;
	    case "outubro":
	      $month = "10";
	      break;
	    case "novembro":
	      $month = "11";
	      break;
	    case "dezembro":
	      $month = "12";
	      break;
	    default:
	      $month = "Error!";
	  }

	  return $month;
	}

}

?>
