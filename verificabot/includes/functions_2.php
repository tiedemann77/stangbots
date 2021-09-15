<?php

// Função para obter casos antigos da página de recentes
function getOldCases($older,$recentsContent){

  global $older;

  // Verificar se a página está vazia (com {{nenhum}})
  if (preg_match("/\{\{nenhum\}\}/", $recentsContent)) {

    // Se sim, nada a fazer, parar
    exit(logging("Não há casos na página de recentes ({{nenhum}}). Fechando...\r\n"));

  }

  // Filtrando nome do caso, data e resultado em uma array
  // NOTA: essa regex mantém "}}" no resultado, por isso não precisa adicionar depois
  preg_match_all("/\|.{1,}\|[0-9]{1,2} de .{4,9} de [0-9]{4,4}\|.{1,}/", $recentsContent, $out);

  foreach ($out[0] as $key => $value) {
    $explode[$key] = explode("|", $value);
  }

  foreach ($explode as $key2 => $value2) {
    $deleted = array_shift($explode[$key2]);
  }

  // Verificando data
  // Convertendo data para números (dd-mm-yyyy)
  foreach ($explode as $key3 => $value3) {

    $explode[$key3][1] = str_replace(" de janeiro de ","-01-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de fevereiro de ","-02-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de março de ","-03-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de abril de ","-04-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de maio de ","-05-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de junho de ","-06-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de julho de ","-07-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de agosto de ","-08-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de setembro de ","-09-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de outubro de ","-10-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de novembro de ","-11-",$explode[$key3][1]);
    $explode[$key3][1] = str_replace(" de dezembro de ","-12-",$explode[$key3][1]);

  }

  // Controle para o próximo loop
  $control = 0;

  // Loop para verificar a quanto tempo o caso foi fechado
  foreach ($explode as $key4 => $value4) {

    // Reconvertendo, invertendo para yyyy-mm-dd
    $explode2 = explode("-", $explode[$key4][1]);

    $dateCase = $explode2[2] . "-" . $explode2[1] . "-" . $explode2[0];

    // Convertendo em date object
    $dateCase = new DateTime($dateCase);

    // Obtém a data atual
    $day = date("d");
    $month = date("m");
    $year = date("Y");

    $dateNow = $year . "-" . $month . "-" . $day;

    // Convertendo em date object
    $dateNow = new DateTime($dateNow);

    // Verificando a diferença
    $dateInterval = $dateCase->diff($dateNow);

    // Verifica se não é uma data futura
    $future = $dateInterval->invert;

    if($future==1){
      // Se sim, parar script
      exit(logging("Há uma data futura na página de recentes, correção manual é necessária. Fechando...\r\n"));
    }

    // Convertendo para dias
    $dateInterval = $dateInterval->days;

    // Se for maior ou igual a $older, adicionar em $olderCases
    if($dateInterval>=$older){

      $olderCases[$control] = $explode[$key4];
      $control++;

    }

  }

  // Se não há casos antigos para arquivar, para script
  if (!isset($olderCases)) {
    exit(logging("Não há casos antigos para arquivar na página de recentes (mais antigos que " . $older . " dias). Fechando...\r\n"));
  }

  // Reconvertendo datas para formato por extenso
  foreach ($olderCases as $key5 => $value5) {
    $explode3[$key5] = explode("-", $olderCases[$key5][1]);

    $explode3[$key5][1] = str_replace("01"," de janeiro de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("02"," de fevereiro de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("03"," de março de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("04"," de abril de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("05"," de maio de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("06"," de junho de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("07"," de julho de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("08"," de agosto de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("09"," de setembro de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("10"," de outubro de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("11"," de novembro de ",$explode3[$key5][1]);
    $explode3[$key5][1] = str_replace("12"," de dezembro de ",$explode3[$key5][1]);

    // Salvando
    $olderCases[$key5][1] = $explode3[$key5][0] . $explode3[$key5][1] . $explode3[$key5][2];

  }

  // Retorna os casos antigos
  return $olderCases;

}

// Função para remover casos antigos da lista de recentes
function removingRecentList($olderCases, $recentsContent){

  // Num primeiro momento, o novo conteúdo é igual ao antigo
  $newrecentsContent = $recentsContent;

  // Loop para remover linha por linha
  foreach ($olderCases as $key => $value) {

    // Remove o caso antigo
    $newrecentsContent = preg_replace("/{{Wikipédia:Pedidos a verificadores\/ListarArquivo\|$value[0]\|$value[1]\|$value[2]/",NULL,$newrecentsContent);

  }

  // Filtra linhas em branco
  $newrecentsContent = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $newrecentsContent);

  // Verifica se a página de recentes não ficou vazia
  $empty = '__NOTOC__
= Investigações encerradas recentemente =
{| class="wikitable sortable center"
|+
!Caso
!Encerrado em
!Resultado
|}';

  if($newrecentsContent==$empty){

    // Se sim, simplesmente substitui por modelo com {{nenhum}}
    $newrecentsContent = '__NOTOC__
= Investigações encerradas recentemente =
{| class="wikitable sortable center"
|+
!Caso
!Encerrado em
!Resultado
|-
|{{nenhum}}
|{{nenhum}}
|{{nenhum}}
|}';

  }

  // Retorna o novo conteúdo
  return $newrecentsContent;

}

// Função para separar os casos antigos em dois meses
function separeMonths($olderCases){

  // Definindo mês atual e anterior
  $month = date("m");

  if($month=="01"){
    $previousMonth = 12;
  }else{
    $previousMonth = $month-1;
  }

  // Fix para adicionar "0"
  if($previousMonth<10){
    $previousMonth = "0" . $previousMonth;
  }

  foreach ($olderCases as $key => $value) {

    // Convertendo datas
    $date[$key] = explode(" ", $value[1]);

    switch ($date[$key][2]) {
      case "janeiro":
        $date[$key][2] = "01";
        break;
      case "fevereiro":
        $date[$key][2] = "02";
        break;
      case "março":
        $date[$key][2] = "03";
        break;
      case "abril":
        $date[$key][2] = "04";
        break;
      case "maio":
        $date[$key][2] = "05";
        break;
      case "junho":
        $date[$key][2] = "06";
        break;
      case "julho":
        $date[$key][2] = "07";
        break;
      case "agosto":
        $date[$key][2] = "08";
        break;
      case "setembro":
        $date[$key][2] = "09";
        break;
      case "outubro":
        $date[$key][2] = "10";
        break;
      case "novembro":
        $date[$key][2] = "11";
        break;
      case "dezembro":
        $date[$key][2] = "12";
        break;
      default:
        exit(logging("Erro obtendo data para arquivar (function separeMonths). Fechando...\r\n"));
    }

    // Separando mês: array [1] para atual e [0] para anterior
    if($date[$key][2]==$month){
      $olderCasesFilter[1][$key] = $olderCases[$key];
    }elseif ($date[$key][2]==$previousMonth) {
      $olderCasesFilter[0][$key] = $olderCases[$key];
    }else {
      exit(logging("Erro determinando o mês correto para arquivar (talvez mais de dois?). Fechando...\r\n"));
    }

  }

 return $olderCasesFilter;

}

// Função para criar novas páginas de arquivos
function archiveNew( $source, $olderCasesFilter, $month, $monthPT, $year ){

  // Conteúdo é igual ao modelo em branco
  $newContent = $source;

  // Adiciona título da seção
  $newContent = str_replace("month de year", $monthPT . " de " . $year, $newContent);

  // Chave para substituição
  $textKey = "|}";

  // Loop para adicionar arquivos
  foreach ($olderCasesFilter as $key => $value) {

  // Manter a quebra de linha
    $textKey = "{{Wikipédia:Pedidos a verificadores/ListarArquivo|" . $value[0] . "|" . $value[1] . "|" . $value[2] . "
" . $textKey;

  }

  // Substituindo, adicionando novos arquivos
  $newContent = str_replace("|}", $textKey, $newContent);

  // Filtrando linhas em branco
  $newContent = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $newContent);

  // Retornando novo conteúdo
  return $newContent;

}

// Função para editar arquivos existentes
function archiveOld( $previousContent, $olderCasesFilter ){

  // No início, conteúdo é igual ao modelo em branco
  $newPreviousContent = $previousContent;

  // Chave para substituir
  $textKey = "|}";

  // Criando linhas, manter a quebra de linha
  foreach ($olderCasesFilter as $key => $value) {
    $textKey = "{{Wikipédia:Pedidos a verificadores/ListarArquivo|" . $value[0] . "|" . $value[1] . "|" . $value[2] . "
" . $textKey;
  }

  // Substituindo e adicionando
  $newPreviousContent = preg_replace("/^\|\}$/m", $textKey, $newPreviousContent);

  // Filtrando linhas em branco
  $newPreviousContent = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $newPreviousContent);

  // Retornando novo conteúdo
  return $newPreviousContent;

}

?>
