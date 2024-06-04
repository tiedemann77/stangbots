# Stangbots

NOTE: These bots were made for use on Portuguese Wikipedia and Wikidata, so (almost) all instructions and comments are in Portuguese. Feel free to contact [[User:Stanglavine]] if you have any questions.

Código-fonte utilizado na operação de alguns robôs na Wikipédia em Português e no Wikidata. O repositório está dividido em duas partes.

1) Conteúdo na pasta raíz e em src/: classes básicas, usadas para interação com o Mediawiki. Caso deseje utilizá-las na construção de seu próprio robô, basta:
	1) Clonar a pasta raíz e src/;
	2) Criar uma pasta para seus scripts (eu uso "scripts");
	3) Dentro desta pasta, criar um arquivo com as credencias para interação com o MediaWiki, geradas via BotPasswords (ver arquivo scripts/settings_example.php para um exemplo);
	4) Criar seu robô em si (veja o arquivo scripts/script_example.php para um exemplo);

2) Conteúdo na pasta scripts/: robôs que desenvolvi para a Wikipédia em Português e o Wikidata.

## Licença
MIT License

## Declaração
This code was made by a non-professional programmer, so don't be rude :) Suggestions are very welcome!

Esses scripts foram feitos por um programador amador, então provavelmente não seguem as boas práticas de programação. Por mais que tenha me esforçado em fazê-lo o mais eficiente e seguro possível, não há qualquer garantia de funcionamento. Sugestões de aprimoramentos são sempre muito bem-vindas :)
