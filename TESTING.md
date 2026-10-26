# Verificação da versão 0.3.0

## Ambiente

Verificação local em WordPress Playground, com WordPress 7.1.3, WooCommerce 11.2.1 e PHP 8.3.33. O banco de testes usa a integração SQLite do Playground. Contas, produtos, pedidos e respostas são sintéticos; o envio de e-mails é interceptado no ambiente descartável.

Os testes isolados de esquema e cálculo também foram executados em PHP 8.1, 8.3 e 8.4: **70 verificações aprovadas em cada versão**.

## Integração

Foram aprovadas **63 verificações**: 48 do fluxo de aplicações e 15 de páginas, recuperação e relatórios antigos. Incluem:

- Esquema dos quatro módulos e pontuação conhecida com itens invertidos.
- Rejeição de respostas ausentes, fora da escala, desconhecidas ou com tipo inválido.
- Pedido pendente sem liberação e pedido pago com a quantidade correta de aplicações.
- Repetição de notificações de pagamento sem duplicação de créditos.
- Preservação da configuração do pacote no item comprado.
- Reserva, início, retomada e conclusão de aplicações próprias.
- Devolução ao saldo de uma aplicação própria ainda não iniciada.
- Salvamento parcial com revisão e rejeição de edição de uma aba desatualizada.
- Bloqueio de alteração de respostas por outra conta.
- Convite vinculado ao e-mail, aceite informado e proteção contra aceite duplicado.
- Abertura de link sem consumo ou aceite por GET.
- Comprador com acesso ao relatório final, sem respostas individuais.
- Bloqueio de relatório para um comprador diferente.
- Preservação da versão do instrumento e do relatório após edição do módulo.
- Cancelamento, expiração, intervalo de reenvio e substituição de tokens.
- Reembolso parcial por quantidade e reembolso total, com manutenção do relatório do participante.
- Autenticação e nonce nas rotas privadas.
- Geração de relatórios dos quatro módulos.
- Instalação das cinco páginas públicas, seleção da home e checkout com shortcode nativo.
- Reinstalação sem duplicação e preservação de conteúdo editado.
- Restauração da seleção anterior, respeitando mudanças posteriores do proprietário.
- Recuperação de uma página que foi movida para a lixeira.
- Catálogo com produtos reais por módulo e quantidade própria de uma variação.
- Enriquecimento autorizado de relatório antigo a partir do instrumento original e preservação da nova leitura.

## Interface no navegador

Na entrega 0.2, foram aprovadas **94 verificações no Chromium**: 20 do aplicativo, 68 das páginas e fluxo comercial e 6 da revisão final de produto e impressão, com inspeção visual das telas:

- Login real do WordPress e carregamento dos indicadores da conta.
- Relatórios sem rolagem horizontal em 320, 390, 768 e 1440 pixels.
- Radar e barras nos relatórios, incluindo seis dimensões no celular.
- Ciência das informações antes do início do teste.
- Retomada após recarregamento da página.
- Aplicação completa de liderança pelo formulário e geração do relatório.
- Rejeição de entrada malformada sem erro interno.
- Preservação do convite no cadastro, cadastro pelo WooCommerce e retorno ao aceite.
- Aceite com liberação do módulo recebido.
- Ausência de erros JavaScript nas telas verificadas.
- Home, loja, produto, carrinho, checkout e lançamento sem rolagem horizontal em 320, 390, 768 e 1440 pixels.
- Menu de celular com abertura por botão e fechamento com Escape.
- Filtros de módulo e quantidade ligados aos produtos cadastrados.
- Inclusão de produto simples e variação no carrinho.
- Seleção de variação com informação de módulo e aplicações antes da compra.
- Checkout nativo com nonce, cadastro e método de pagamento de teste, até a página de pedido recebido.
- Relatório desenvolvido nos tamanhos de celular e desktop.
- Acesso ao botão de compra na primeira tela do produto em 320 e 390 pixels.
- Impressão A4 gerada pelo Chromium e conteúdo conferido no PDF de teste.

## Interpretação textual

Os testes isolados também verificam leituras baixas, intermediárias e altas, empates, médias próximas, respostas divergentes na mesma dimensão, separação entre interesses e comportamentos, cobertura das dimensões e adaptação a instrumentos editados. Esses testes avaliam regras de software e coerência das condições; não demonstram validade psicológica.

## Conta Nortume (0.3.0)

Foram executadas 28 verificações adicionais de integração para a conta, além das 63 anteriores. Elas cobrem seleção da página própria, paginação de pedidos, isolamento entre compradores, notas destinadas ao cliente, pagamento pendente, cancelamento permitido e bloqueio de pedidos pagos, validação de dados pessoais, e-mail, senha e faturamento, preservação do histórico e restauração da seleção de página.

A conferência da conta incluiu 43 verificações no navegador, com cinco verificações adicionais para o ajuste de largura da visão geral. Os formulários de conta foram verificados em 320, 390, 768 e 1440 pixels, com inspeção da visão geral, pedidos, detalhe e dados pessoais. A verificação interativa cobre menu superior, paginação, encaminhamento dos endereços antigos, edição de perfil e faturamento, troca de senha com continuidade de sessão, saída, entrada e convite pelo cadastro próprio. Nenhuma tela padrão de Minha conta do WooCommerce é usada nesses fluxos.

## Escopo e limites

As verificações cobrem funcionamento de software. Não demonstram validade psicométrica, precisão de inferências sobre pessoas nem adequação do conteúdo para uma faixa etária específica.

Não foram feitas cobranças em gateway real nem confirmação de entrega de e-mails em caixa postal externa. A instalação do cliente deve verificar essas integrações com suas próprias configurações. O teste local em SQLite também não substitui a verificação de concorrência e configuração transacional no MySQL/MariaDB de produção.

O pipeline do GitHub executa análise de sintaxe PHP, os testes isolados de cálculo em três versões do PHP e a análise de sintaxe JavaScript antes de publicar uma release. O roteiro de integração pode ser repetido com `npm ci` e `npm test`.
