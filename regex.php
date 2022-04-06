<?php

// Regex comuns
// Possibilidades de {{respondido}}
$closedRegex = "/(\{\{((R|r)esp){1,1}(ondid(o|a)(2){0,1}){0,1}\|.*\|)|(A discussão a seguir está marcada como '{0,3}respondida'{0,3})/";

// Linhas em branco
$blanklineRegex = "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/";

// Comentários HTML
$htmlcommentRegex = "/\<\!\-\-(?:.|\n|\r)*?-->/";

?>
