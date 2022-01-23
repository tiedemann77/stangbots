# Stangbot

Código-fonte utilizado em algumas operações do robô [[User:Stangbot]] na Wikipédia em Português e no Wikidata. Há cinco scripts diferentes:

* Script 1: responsável por atualizar [[User:Stangbot/feed]] com a quantidade de pedidos em aberto em várias páginas da Wikipédia;
* Script 2: responsável por atualizar [[Wikipédia:Burocratas/Atividade dos administradores]] com estatísticas sobre administradores;
* Script 3: responsável por esvaziar periodicamente [[Wikidata:Sandbox]] no Wikidata;
* Script 4: atualiza vários relatórios sobre problemas com ficheiros na Wikipédia em Português;
* mr-js-css: atualiza um feed com mudanças recentes em páginas .js e .css.

## Instruções
1) Renomear "settings_example.php" para "settings.php" e preenchê-lo conforme necessário;
2) Personalizar configurações, páginas-alvo e sumários de edição conforme necessário nos arquivos de cada script;
3) Os script 4 e mr-js-css dependem de acesso às réplicas do banco de dados.

## Licença
MIT License
