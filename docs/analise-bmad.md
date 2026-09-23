# Análise do guia BMAD — MVP Plataforma B2B

## O que o documento confirma

O arquivo fornecido é um guia operacional inspirado no estilo BMAD para um MVP acadêmico; ele próprio esclarece que não é uma implementação oficial do método. A tecnologia obrigatória é **PHP com Laravel**. A stack recomendada é um monólito com **Blade, MySQL e um único framework CSS** (Tailwind foi escolhido neste projeto).

O valor da demonstração está no fluxo abaixo, não em reproduzir um ERP:

```text
Login → busca por SKU → carrinho → orçamento → pedido → status → administração
```

Há dois papéis: `ADMIN` e `REVENDEDOR`.

| Área | Requisitos do guia |
|---|---|
| Autenticação | Login, logout e separação de acesso por perfil. |
| Produtos | SKU único, nome, descrição, preço, estoque, status; administração cadastra/edita e revendedor busca. |
| Carrinho | Adicionar, remover, alterar quantidade, subtotais e validação contra estoque. |
| Orçamento | Persistir cabeçalho, itens e valores originados do carrinho. |
| Pedido | Converter um orçamento uma única vez; usar `PENDENTE`, `APROVADO`, `CONCLUIDO`. |
| Comercial | `STANDARD` 0%, `GOLD` 5%, `PREMIUM` 10%. |
| Administração | Indicadores de pedidos, valor, produtos e revendedores; gráfico simples e lista recente. |
| Experiência | Mensagens de erro, estados vazios, responsividade básica e visual corporativo industrial. |

As entidades centrais indicadas são Usuário, Revendedor, Produto, Carrinho, Item de Carrinho, Orçamento, Item de Orçamento, Pedido e Item de Pedido. Categoria é opcional e, por isso, não entra no primeiro esqueleto.

## O que fica explicitamente fora do MVP

- ERP e nota fiscal reais;
- motor comercial complexo, promoções e múltiplas tabelas de preço;
- microserviços, filas, Redis, WebSockets e infraestrutura avançada;
- analytics sofisticado e previsão por IA.

## Lacunas do documento e decisões de implementação

O guia não define baixa ou reserva de estoque, validade/cancelamento de orçamento, frete, impostos, pagamentos, regra efetiva para limite de crédito nem a fórmula temporal de “total vendido”. O esqueleto não deve apresentar essas decisões como requisitos.

Para manter o fluxo demonstrável, este projeto adota somente estas decisões técnicas mínimas:

1. Há um carrinho persistente por revendedor; ele é limpo após gerar o orçamento.
2. Orçamento e pedido gravam snapshots de produto e valores, protegendo o histórico contra mudanças posteriores no catálogo.
3. O pedido valida estoque na conversão, mas não o baixa ou reserva.
4. `orders.quote_id` é único no banco para impedir duas conversões do mesmo orçamento.
5. O painel soma pedidos persistidos; se a banca exigir uma definição diferente de “vendido”, essa regra deve ser alinhada antes da entrega final.

Essas decisões mantêm o MVP simples e rastreável, sem adicionar um ERP disfarçado.
