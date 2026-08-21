# Banco de dados — Re.Source

Toda a preparação do banco pode ser feita com SQL puro pelo phpMyAdmin.

Para a apresentação, o schema consolidado cria e seleciona automaticamente o banco `resource`. Confirme que o arquivo `.env` também usa:

```env
DB_DATABASE=resource
```

## Reset completo

No phpMyAdmin, importe os arquivos nesta ordem:

```text
1. database/seeders/re.sourcebanco.sql
2. database/inserts/create_admin.sql
3. database/inserts/empresa_demo.sql      (opcional, recomendado para apresentação)
4. database/inserts/produto.sql           (opcional, depende de empresa_demo.sql)
5. database/inserts/saldo_demo.sql        (opcional, depende dos dois anteriores)
6. database/migrations/001_product_innovation.sql
```

No ambiente Docker essa ordem é aplicada automaticamente. Em instalações existentes,
execute apenas as migrations ainda não aplicadas, na ordem numérica.

O primeiro arquivo cria o banco `resource`, seleciona o banco com `USE` e monta toda a estrutura necessária para um reset completo.

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

Também existe `database/inserts/promover_admin.sql`, que promove uma conta cadastrada normalmente pelo site para administrador local.
