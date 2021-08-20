# VerificaBot

NOTE: This bot was made for use on Portuguese Wikipedia, so all instructions and comments are in Portuguese. Feel free to contact me if you have any questions.

Código-fonte utilizado nas operações do robô [[User:VerificaBot]] na Wikipédia em Português. Há três scripts diferentes:

* Script 1: responsável pela remoção dos pedidos concluídos de [[Wikipédia:Pedidos a verificadores]], além da adição dos mesmos em [[Wikipédia:Pedidos a verificadores/Recentes]];
* Script 2: responsável pela remoção dos pedidos antigos de [[Wikipédia:Pedidos a verificadores/Recentes]] e arquivamento na respectiva página do mês;
* Script 3: responsável por aplicar {{subst:}} nas predefinições de resultado nos arquivos permanentes.

Os três scripts são independentes e podem ser executados separadamente.

## Instruções
1) Renomear "globals_sample.php" para "globals.php" e preenchê-lo conforme necessário;
2) Criar um arquivo de log em branco com o nome determinado;
3) Personalizar sumários de edição e tempo de arquivamento (para o script 2) conforme necessário nos arquivos de cada script.

## Licença
MIT License

## Declaração
Esses scripts foram feitos por um programador amador, então provavelmente não seguem as boas práticas de programação. Por mais que tenha me esforçado em fazê-lo o mais eficiente e seguro possível, não há qualquer garantia de funcionamento. Sugestões de aprimoramentos são sempre muito bem-vindas :)

This code was made by a non-professional programmer, so don't be rude :) Suggestions are very welcome!
