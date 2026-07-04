# Banco de dados — Re.Source

Toda a preparação do banco pode ser feita com SQL puro pelo phpMyAdmin.

O aplicativo usa o banco definido por `DB_DATABASE` no arquivo `.env`. Os SQLs
não possuem `CREATE DATABASE` nem `USE`, evitando que uma importação seja
desviada para outro banco.

## Reset completo

1. Crie um banco vazio com collation `utf8mb4_unicode_ci`.
2. Coloque o nome desse banco em `DB_DATABASE` no `.env`.
3. Selecione o banco no phpMyAdmin.
4. Importe os arquivos nesta ordem:

```text
1. database/seeders/re.sourcebanco.sql
2. database/inserts/create_admin.sql
3. database/inserts/empresa_demo.sql      (opcional, recomendado para apresentação)
4. database/inserts/produto.sql           (opcional, depende de empresa_demo.sql)
5. database/inserts/saldo_demo.sql        (opcional, depende dos dois anteriores)
```

O primeiro arquivo contém toda a estrutura necessária. Não é necessário
executar `ALTER TABLE` ou código PHP depois de um reset completo.

## Contas acadêmicas

O insert `create_admin.sql` cria:

```text
E-mail: admin@resource.com.br
Senha: Admin@2026!
```

O insert `empresa_demo.sql` usa a senha abaixo em todas as empresas:

```text
Resource@2026
```

Usuários disponíveis:

```text
carlos@metaljoin.com.br
ana@madeirasul.com.br
roberto@plasticonord.com.br
fernanda@textilcat.com.br
marina@empresapendente.com.br  (empresa pending)
```

Essas credenciais são fictícias e exclusivas do ambiente acadêmico. Antes de
publicar o repositório ou utilizar o sistema fora da demonstração, substitua-as
e reescreva o histórico conforme o planejamento de publicação segura.

Também existe `database/inserts/promover_admin.sql`, que promove uma conta
cadastrada normalmente pelo site para administrador local.

## Banco criado com schema antigo

Para preservar os dados e apenas atualizar status/papéis, execute:

```text
database/migrations/20260701_001_pending_and_admin_roles.sql
```

Depois execute `database/inserts/create_admin.sql` no mesmo banco.
