# Especificação de testes

Esta especificação traduz os requisitos de [analise-bmad.md](analise-bmad.md) em verificações automatizadas. O fluxo de negócio central é login, busca de produto, carrinho, orçamento, pedido e atualização de status.

## Suítes

| Suíte | Local | Escopo |
|---|---|---|
| Unitários | `tests/Unit/` | Cálculo monetário, descontos por perfil, predicados de papel e regras isoladas de autorização. |
| Funcionais | `tests/Feature/` | Rotas Laravel, validação, persistência SQLite, permissões, serviços de carrinho/orçamento/pedido, autenticação e administração. |
| E2E | `tests/E2E/` | Navegador Chrome real: login do revendedor, pesquisa, carrinho, orçamento, pedido, aprovação administrativa, credenciais inválidas e bloqueio de perfil. |

Os testes E2E criam um SQLite temporário, executam as migrations e o seeder de demonstração, iniciam o servidor Laravel local e removem os arquivos temporários ao terminar. Eles não usam o banco de desenvolvimento.

Para E2E, instale as dependências Node com `npm ci` e mantenha Google Chrome disponível no `PATH`.

## Como executar

```sh
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit
npm run test:e2e
```

Para impor o objetivo de cobertura de linhas do código PHP da aplicação:

```sh
php artisan test --coverage --min=99
```

Esse comando requer Xdebug ou PCOV habilitado no PHP usado pelo PHPUnit. Sem um desses drivers, PHPUnit executa os testes, mas não consegue calcular nem validar a porcentagem. A suíte E2E complementa os testes PHP e não substitui a medição de cobertura PHP.
