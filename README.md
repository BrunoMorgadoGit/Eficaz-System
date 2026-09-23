# Eficaz B2B — MVP de aquisição e gestão de estoque

Monólito acadêmico em **PHP 8.2 + Laravel 12 + Blade + Tailwind + MySQL**. O front-end, regras de negócio e banco vivem neste único projeto.

## Fluxo demonstrável

```text
Revendedor: login → busca por SKU → carrinho → orçamento → pedido → status
Administrador: login → dashboard → produtos / revendedores / pedidos
```

O escopo vem do guia BMAD fornecido: não há ERP real, nota fiscal, pagamento, frete, promoções avançadas, microserviços, filas, Redis ou IA.

## Estrutura

```text
app/
  Http/Controllers/     ações HTTP por área
  Http/Middleware/      separação ADMIN e REVENDEDOR
  Http/Requests/        validação no servidor
  Models/               entidades Eloquent
  Services/             preço, orçamento e pedido
database/
  migrations/           esquema MySQL
  seeders/              dados de demonstração
resources/views/        telas Blade e componentes
routes/web.php          rotas do portal
```

## Modelo de dados

`users` possui o papel `ADMIN` ou `REVENDEDOR`. Cada revendedor possui um perfil comercial (`STANDARD`, `GOLD`, `PREMIUM`), um carrinho, orçamentos e pedidos. Produtos têm SKU único, preço, estoque e situação ativa. Itens de orçamento e pedido guardam snapshots de SKU, nome e valores para preservar o histórico.

O banco garante que `orders.quote_id` seja único: um orçamento só pode gerar um pedido.

## Decisões explícitas do esqueleto

O guia não define baixa/reserva de estoque, validade/cancelamento de orçamento, frete, impostos ou regra de crédito. Portanto, este MVP **valida o estoque** ao incluir itens e converter o pedido, mas **não baixa nem reserva estoque**. Ao gerar orçamento, o carrinho é limpo e os itens/valores ficam congelados no orçamento. Essas são decisões técnicas documentadas, não requisitos adicionais do guia.

## Executar localmente

Pré-requisitos: PHP 8.2+, Composer, Node 22+ e Docker (opcional, para o MySQL local).

```bash
composer install
cp .env.example .env
docker compose up -d
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

O `docker-compose.yml` expõe MySQL em `127.0.0.1:3307`, já compatível com `.env.example`. Se usar outro MySQL, ajuste somente as variáveis `DB_*` no seu `.env` local.

## Dados de demonstração

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `admin@eficaz.test` | `password` |
| Revendedor GOLD | `revendedor@eficaz.test` | `password` |

Essas contas são exclusivas para ambiente de demonstração. Não use essas credenciais em produção.

## Verificação

```bash
php artisan test
npm run build
```

Os testes usam SQLite em memória; a aplicação local usa MySQL conforme `.env`.
