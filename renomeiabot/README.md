# RenomeiaBot

Código-fonte utilizado nas operações do robô [[User:RenomeiaBot]] na Wikipédia em Português. Sua função é auxiliar no processamento dos pedidos em [[Wikipédia:Renomeação de conta]].

## Instruções
1) Renomear "globals_example.php" para "globals.php" e preenchê-lo conforme necessário. Nota: esse bot utiliza as réplicas do Toolforge para algumas checagens, por isso lembre-se de ajustar a variável $toolforge para 0 caso não esteja sendo executado no Toolforge;
2) Criar um arquivo para log em branco com o nome determinado em "globals.php";
3) Personalizar sumários de edição conforme necessário nos arquivos de cada script;
4) Se o script não tiver acesso às réplicas do banco de dados ($toolforge = 0), algumas checagens podem não ser efetuadas.

## Licença
MIT License
