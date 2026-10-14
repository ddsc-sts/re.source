# Manual de Instalação --- Re.Source com Docker

**Objetivo:** orientar uma pessoa a baixar, configurar e executar o
Re.Source no próprio computador usando Docker, sem precisar instalar
PHP, Apache ou configurar o servidor manualmente no Windows.

> **Importante:** este manual considera que o projeto já contém os
> arquivos `Dockerfile`, `compose.yaml`, `.env.docker` e
> `docker/re.source-alias.conf`. Os nomes dos serviços, variáveis de
> ambiente e configurações do banco devem ser conferidos nos arquivos
> entregues junto ao projeto.

## 1. O que você precisa instalar

Antes de começar, instale:

-   **Docker Desktop:** https://www.docker.com/products/docker-desktop/
-   **Git** (opcional, caso vá baixar o projeto usando o Git):
    https://git-scm.com/downloads

Se você receber o projeto em um arquivo ZIP, não precisa instalar o Git.

### Verificar se o Docker está funcionando

1.  Abra o Docker Desktop.
2.  Aguarde até que ele indique que o Docker está em execução.
3.  Abra o PowerShell ou o terminal do Windows.
4.  Execute:

``` powershell
docker --version
docker compose version
```

Se os dois comandos exibirem suas versões, continue para a próxima
etapa. Se aparecer um erro informando que o comando não foi encontrado,
verifique a instalação do Docker Desktop e abra um novo terminal.

## 2. Baixar o projeto

Use **uma** das opções abaixo.

### Opção A --- Baixar o ZIP

1.  Abra o link do repositório do Re.Source fornecido pela equipe.
2.  Clique em **Code** e depois em **Download ZIP**.
3.  Quando o download terminar, extraia o ZIP para uma pasta simples,
    por exemplo:

``` text
C:\Projetos\re.source
```

4.  Abra essa pasta e confirme que ela contém, entre outros, os
    arquivos:

``` text
Dockerfile
compose.yaml
.env.docker
docker\re.source-alias.conf
```

Se esses arquivos não estiverem presentes, confirme com a equipe se você
baixou a versão correta do projeto.

### Opção B --- Baixar usando Git

Abra o PowerShell na pasta em que deseja guardar o projeto e execute o
comando com a URL real do repositório:

``` powershell
git clone URL_DO_REPOSITORIO
```

Substitua `URL_DO_REPOSITORIO` pelo endereço fornecido pela equipe.
Depois, entre na pasta criada pelo Git.

## 3. Configurar o arquivo de ambiente

Na raiz do projeto, localize o arquivo `.env.docker`.

1.  Abra-o com um editor de texto, como o Bloco de Notas ou o Visual
    Studio Code.
2.  Confira as variáveis de ambiente que já estão preenchidas.
3.  Preencha apenas os valores necessários, seguindo as instruções
    fornecidas pela equipe.

A URL local usada nesta configuração é:

``` env
APP_URL=http://localhost:8080
```

O projeto pode utilizar variáveis de banco de dados, como host, porta,
nome do banco, usuário e senha. **Não invente esses valores:** utilize
os valores definidos no arquivo de configuração do projeto ou fornecidos
pelo responsável pela instalação.

No ambiente Docker, `APP_BASE_PATH` deve ficar vazio quando a aplicação
estiver configurada para abrir pela raiz do endereço:

``` env
APP_BASE_PATH=
```

Se o arquivo `.env.docker` já estiver configurado corretamente, não é
necessário alterá-lo.

> Não compartilhe publicamente senhas ou credenciais que estejam no
> arquivo de ambiente.

## 4. Iniciar o Re.Source

1.  Abra o PowerShell.
2.  Entre na pasta raiz do projeto. Exemplo:

``` powershell
cd C:\Projetos\re.source
```

Ajuste o caminho conforme o local onde você extraiu ou clonou o projeto.

3.  Confirme que o terminal está na pasta que contém `compose.yaml`.
4.  Execute:

``` powershell
docker compose --env-file .env.docker -f compose.yaml up -d --build
```

O que o comando faz:

-   `--env-file .env.docker`: utiliza as configurações de ambiente do
    Docker.
-   `-f compose.yaml`: informa qual arquivo do Compose usar.
-   `up`: cria e inicia os serviços definidos no projeto.
-   `-d`: executa os serviços em segundo plano.
-   `--build`: constrói ou reconstrói a imagem da aplicação.

Na primeira execução, o Docker pode precisar baixar imagens e instalar
dependências. Isso pode demorar alguns minutos, dependendo da conexão.

## 5. Abrir o sistema

Quando o Docker terminar sem apresentar erros, abra o navegador e
acesse:

**http://localhost:8080/**

A configuração do Apache também pode aceitar o endereço:

**http://localhost:8080/re.source/**

O endereço principal recomendado é `http://localhost:8080/`.

## 6. Verificar se está funcionando

Se a página não abrir ou aparecer sem estilos:

1.  Volte ao PowerShell na pasta do projeto.
2.  Execute:

``` powershell
docker compose --env-file .env.docker -f compose.yaml ps
```

Confira se os serviços necessários estão em execução.

Para consultar os registros da aplicação:

``` powershell
docker compose --env-file .env.docker -f compose.yaml logs --tail=100
```

Para acompanhar os registros em tempo real:

``` powershell
docker compose --env-file .env.docker -f compose.yaml logs -f
```

Para interromper a visualização dos registros, pressione `Ctrl + C`.
Isso encerra a visualização do log; normalmente, não interrompe os
serviços que estão rodando em segundo plano.

### Se CSS, JavaScript ou imagens não carregarem

1.  Atualize o navegador com `Ctrl + Shift + R`.
2.  Confira se o arquivo `docker/re.source-alias.conf` existe.
3.  Confira se o `Dockerfile` copia e ativa essa configuração do Apache.
4.  Reconstrua a aplicação:

``` powershell
docker compose --env-file .env.docker -f compose.yaml up -d --build
```

Não altere manualmente todas as rotas das páginas para tentar corrigir o
problema. A configuração do Apache foi preparada para atender aos
caminhos usados pelo projeto.

### Se a porta 8080 já estiver ocupada

Se o Docker informar que não consegue usar a porta 8080, outro programa
pode estar utilizando essa porta. Feche o programa que a estiver
ocupando ou peça ao responsável pelo projeto orientação para alterar a
porta publicada no `compose.yaml`. Se mudar a porta, o endereço do
navegador também muda.

### Se ocorrer erro de conexão com o banco

-   Confira as variáveis do banco no `.env.docker`.
-   Verifique se o serviço de banco está definido e em execução, caso o
    projeto o inclua no Compose.
-   Consulte os registros com o comando `logs` acima.

O Docker não garante que o banco já esteja inicializado ou que as
credenciais estejam corretas. Siga as configurações e instruções de
banco fornecidas com a versão do projeto.

## 7. Parar e iniciar novamente

### Parar os serviços

Na raiz do projeto, execute:

``` powershell
docker compose --env-file .env.docker -f compose.yaml down
```

Isso para e remove os containers e a rede criados pelo Compose. Volumes
nomeados normalmente são preservados por esse comando.

### Iniciar novamente

``` powershell
docker compose --env-file .env.docker -f compose.yaml up -d
```

### Atualizar o projeto após receber mudanças

Se você recebeu novos arquivos ou alterou arquivos que são copiados para
a imagem durante a construção, execute:

``` powershell
docker compose --env-file .env.docker -f compose.yaml up -d --build
```

Se o projeto estiver configurado com volumes de montagem, algumas
alterações podem aparecer sem reconstruir a imagem. Em caso de dúvida,
use o comando com `--build`.

## 8. Cuidados com os dados

-   Não apague volumes do Docker sem saber o que eles armazenam.
-   Não use `docker compose down -v` sem autorização: a opção `-v` pode
    apagar volumes e dados persistentes.
-   Mantenha o arquivo `.env.docker` protegido se contiver senhas.
-   Faça backup dos dados importantes antes de reinstalar ou limpar o
    ambiente.

## 9. Comandos úteis --- resumo

Execute os comandos na pasta que contém `compose.yaml`.

  -------------------------------------------------------------------------------------------------------------
  Ação                                Comando
  ----------------------------------- -------------------------------------------------------------------------
  Iniciar e construir                 `docker compose --env-file .env.docker -f compose.yaml up -d --build`

  Ver status                          `docker compose --env-file .env.docker -f compose.yaml ps`

  Ver logs recentes                   `docker compose --env-file .env.docker -f compose.yaml logs --tail=100`

  Acompanhar logs                     `docker compose --env-file .env.docker -f compose.yaml logs -f`

  Parar os serviços                   `docker compose --env-file .env.docker -f compose.yaml down`

  Abrir o sistema                     `http://localhost:8080/`
  -------------------------------------------------------------------------------------------------------------

## 10. Precisa de ajuda?

Se a instalação falhar, envie à equipe:

1.  A mensagem de erro completa do terminal.
2.  O resultado de
    `docker compose --env-file .env.docker -f compose.yaml ps`.
3.  Os registros relevantes de
    `docker compose --env-file .env.docker -f compose.yaml logs --tail=100`.

Antes de compartilhar os registros, remova senhas, tokens e outros dados
privados.

------------------------------------------------------------------------

**Manual do usuário --- Re.Source**\
*Instalação local com Docker Desktop no Windows.*
