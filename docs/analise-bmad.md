# Alinhamento do MVP com a apresentação Eficaz

## Escopo que aparece nos slides

Os slides 5 e 6 descrevem um portal B2B para revendedores, com busca por SKU, autonomia para gerar orçamentos e pedidos, regras comerciais por grupo e acompanhamento de vendas/estoque por SKU e comportamento dos parceiros. A stack indicada é PHP/Laravel; este projeto usa Laravel, Blade, Tailwind e MySQL.

O fluxo de demonstração é:

```text
Login → busca por SKU → carrinho → orçamento → pedido → status → administração
```

| Área | Requisito do slide |
|---|---|
| Acesso | Login exclusivo para revendedores e painel administrativo. |
| Catálogo | Busca rápida por SKU, preço e disponibilidade em tempo real. |
| Compra | Gerar e acompanhar orçamentos e pedidos com autonomia. |
| Regras por grupo | Exibir condição de pagamento, limite de crédito e promoções automaticamente. |
| Gestão | Administrar preços, catálogo, pedidos e grupos comerciais. |
| Visibilidade | Acompanhar volume de vendas, giro por SKU e comportamento dos parceiros. |

## Escopo operacional deste MVP

O projeto mantém o fluxo como monólito e não inclui ERP, emissão fiscal, pagamentos, frete ou integrações externas. As regras comerciais são configuráveis por administrador e intencionalmente pequenas: percentual por grupo, texto de condição de pagamento e promoções percentuais por grupo com vigência. Não há tabela variável por SKU nem automação financeira.

## Regras implementadas e limites conhecidos

O slide não fixa percentuais promocionais, prazos, a regra de acúmulo de promoções, a semântica de crédito ou cancelamento. Para manter valores comerciais sob controle do administrador, as condições de pagamento começam vazias na base e não há promoção ativa por padrão. O MVP aplica estas regras explícitas:

1. Os descontos iniciais `STANDARD` 0%, `GOLD` 5% e `PREMIUM` 10% preservam as regras que já existiam no projeto; o administrador pode alterá-los.
2. Um orçamento guarda snapshots de grupo, descontos, promoção, pagamento, produtos e valores. Mudanças posteriores só afetam novos orçamentos.
3. Promoções têm grupo, percentual e datas. Entre ofertas simultâneas vale a de maior desconto efetivo; o administrador marca se ela acumula com o desconto do grupo. A soma é limitada a 100%.
4. Um limite ausente bloqueia a emissão do pedido. Pedidos `PENDENTE` e `APROVADO` comprometem crédito; `CONCLUIDO` libera o valor. O painel mostra crédito disponível.
5. A conversão do orçamento baixa o estoque dentro da transação e impede emissão duplicada ou saldo negativo. A interface administrativa permite corrigir o estoque.
6. Status só avança; não existe cancelamento/devolução nem reposição automática de estoque neste escopo.
7. O painel mede vendas financeiras por pedidos concluídos; a movimentação por SKU considera pedidos emitidos e exibe também o estoque atual. A atividade dos parceiros considera vendas concluídas nos últimos seis meses.

Os dados de condição usados pelo seeder local são marcados como demonstração, nunca como prazo comercial real. A disponibilidade 24/7 do slide ainda depende de hospedagem, HTTPS, backup e monitoramento fora do repositório.
