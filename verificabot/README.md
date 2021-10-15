# VerificaBot

Código-fonte utilizado nas operações do robô [[User:VerificaBot]] na Wikipédia em Português. Há três scripts diferentes:

* Script 1: responsável pela remoção dos pedidos concluídos de [[Wikipédia:Pedidos a verificadores]], além da adição dos mesmos em [[Wikipédia:Pedidos a verificadores/Recentes]];
* Script 2: responsável pela remoção dos pedidos antigos de [[Wikipédia:Pedidos a verificadores/Recentes]] e arquivamento na respectiva página do mês;
* Script 3: responsável por aplicar {{subst:}} nas predefinições de resultado nos arquivos permanentes.

Os três scripts são independentes e podem ser executados separadamente.

## Instruções
1) Renomear "settings_example.php" para "settings.php" e preenchê-lo conforme necessário;
2) Personalizar sumários de edição e tempo de arquivamento (para o script 2) conforme necessário nos arquivos de cada script.

## Licença
MIT License
