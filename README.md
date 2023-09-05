# Desafio | Backend

O desafio consiste em usar a base de dados em SQLite disponibilizada e criar uma **rota de uma API REST** que **liste e filtre** todos os dados. Serão 10 registros sobre os quais precisamos que seja criado um filtro utilizando parâmetros na url (ex: `/registros?deleted=0&type=sugestao`) e retorne todos resultados filtrados em formato JSON.

Você é livre para escolher o framework que desejar, ou não utilizar nenhum. O importante é que possamos buscar todos os dados acessando a rota `/registros` da API e filtrar utilizando os parâmetros `deleted` e `type`.

* deleted: Um filtro de tipo `boolean`. Ou seja, quando filtrado por `0` (false) deve retornar todos os registros que **não** foram marcados como removidos, quando filtrado por `1` (true) deve retornar todos os registros que foram marcados como removidos.
* type: Categoria dos registros. Serão 3 categorias, `denuncia`, `sugestao` e `duvida`. Quando filtrado por um `type` (ex: `denuncia`), deve retornar somente os registros daquela categoria.

O código deve ser implementado no diretorio /source. O bando de dados em formato SQLite estão localizados em /data/db.sq3.

Caso tenha alguma dificuldade em configurar seu ambiente e utilizar o SQLite, vamos disponibilizar os dados em formato array. Atenção: dê preferência à utilização do banco SQLite.

Caso você já tenha alguma experiência com Docker ou queira se aventurar, inserimos um `docker-compose.yml` configurado para rodar o ambiente (utilizando a porta 8000).

Caso ache a tarefa muito simples e queira implementar algo a mais, será muito bem visto. Nossa sugestão é implementar novos filtros (ex: `order_by`, `limit`, `offset`), outros métodos REST (`GET/{id}`, `POST`, `DELETE`, `PUT`, `PATCH`), testes unitários etc. Só pedimos que, caso faça algo do tipo, nos explique na _Resposta do participante_ abaixo.

# Resposta do participante
A parte mais complicada foi a configuração do ambiente docker, como já esperado, sempre acontece alguma coisa que dificulta a criação do ambiente, e nesse caso, foi a instalação do php composer na imagem docker.
Minha solução consiste em usar o arquivo index.php para fazer o roteamento dos endpoints usando um microframework chamado Flight (https://github.com/mikecao/flight) o qual instalei via php composer.

A conexão com a database é feita em um arquivo separado, que se encontra dentro do diretório `app/database/`, o arquivo `Connection.php`, que cria o objeto PDO de conexão e retorna o mesmo.
O resto do código, com controller e model, também se encontram dentro do diretório `app`, porém na pasta `registros`.

Separei as rotas utilizadas em um array no arquivo `routes.php` que é chamado no `index.php` por um require. 
Esse array é então lido por um **foreach**, que inicializa as rotas usando Flight e redireciona para o controller certo baseado na url requisitada (Como é uma api de teste, temos apenas um controller, mas em uma situação real onde teriamos múltiplos endpoints, essa estrutura se encaixaria perfeitamente).

Chegando no controller, e tendo anteriormente criado um objeto do mesmo para poder acessá-lo, temos o objeto da model criado pelo construtor e o único método passível de ser chamado por um objeto de sua classe, o método `request`, que decide se vai acionar ou não os endpoints que exigem um id.
Dentro de ambos os métodos `resourceHandler` e `collectionHandler`, temos um switch que irá decidir qual método da model chamar baseado no tipo de requisição http feita, ou se retornará **405(tipo não permitido)** caso o tipo passado não seja aceito.

Caso o switch não caia no caso **default (tipo não permitido)**, chegamos na model, que irá buscar um, buscar vários, deletar, criar ou atualizar um registro, baseado no tipo http que tiver sido requisitado.
Uma requisição de atualização, exclusão ou busca de um registro exige a passagem de um id (/registros/id), e retornarão, respectivamente, os dados do registro alterado, o id do registro alterado em uma mensagem, os dados do objeto buscado.

*As passagens de parâmetro mencionadas abaixo devem ser do tipo json ou multipart-form*<br>
A tentativa de atualização de um registro requer ao menos um dos campos contidos no array `$keys` (array instanciado no controller de registros) seja passado, caso contrário a tentativa de atualização retornará **422 (entidade não processável)** com uma mensagem informando que ao menos um dos campos deve ser passado, e informando o nome de tais campos. Tal validação acontece graças ao método `updateValidation` contido no controller da aplicação (a atualização de um registro pode ser feita tanto pelo verbo `PUT` quanto por `PATCH`). Caso a atualização seja bem-sucedida, teremos um retorno **200(ok)** com os dados do registro recém atualizado.

A tentativa de criação de um registro se comporta de maneira parecida, porém exigindo que todas as colunas da tabela marcadas como **NOT NULL** sejam passadas como parâmetro, do contrário o script retornará **422 (entidade não processável)** com uma mensagem informando todos os campos a serem passados (campos esses que são os mesmos da validação de atualização de registro, contidos no array `$keys`). Tal validação acontece graças ao método `insertValidation` contido no controller da aplicação. Caso a criação seja bem-sucedido, teremos um retorno **201(criado)** com os dados do registro recém criado.
Outros tipos http fora os mencionados acima não passam por validação e simplesmente retornam **404** com uma mensagem de erro caso o registro não seja encontrado, retornam o(s) próprio(s) registro(s) caso seja(m), ou uma mensagem informando a exclusão do registro no caso do verbo `DELETE`.

A busca por registros múltiplos ou individuais contém os filtros exigidos, como era esperado. Ambas as buscas irão retornar `type` e `deleted` requisitados, e podem ser inseridos na busca independente de sua ordem.
Os filtros `order_by`, `limit` e `offset` também foram adicionados, porém alguns com nomes diferentes, sendo eles:
`order_by` nomeado como `order` (precisando ser um dos campos contidos no array `$allowed_columns`).
direção do `order_by` nomeado como `order_dir` (precisando ser um dos campos contidos no array `$allowed_dir`, não sendo case sensitive).
`limit` e `offset` mantém os mesmos nomes.

O arquivo `ErrorHandler.php` dentro da pasta `error_handler` é simplemente uma maneira de tratar qualquer erro que possa acontecer durante a execução do script, e forçar o retorno do erro em formato json.