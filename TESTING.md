# Verificação da versão 0.1.0

## Ambiente

Verificação local em WordPress Playground, com WordPress 7.1.3, WooCommerce 11.2.1 e PHP 8.3.33. O banco de testes usa a integração SQLite do Playground. Contas, produtos, pedidos e respostas são sintéticos; o envio de e-mails é interceptado no ambiente descartável.

Os testes isolados de esquema e cálculo também foram executados em PHP 8.1, 8.3 e 8.4: **39 verificações aprovadas em cada versão**.

## Integração

Foram aprovadas **48 verificações**, incluindo:

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

## Interface no navegador

Foram aprovadas **20 verificações no Chromium**, com inspeção visual das telas:

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

## Escopo e limites

As verificações cobrem funcionamento de software. Não demonstram validade psicométrica, precisão de inferências sobre pessoas nem adequação do conteúdo para uma faixa etária específica.

Não foram feitas cobranças em gateway real nem confirmação de entrega de e-mails em caixa postal externa. A instalação do cliente deve verificar essas integrações com suas próprias configurações. O teste local em SQLite também não substitui a verificação de concorrência e configuração transacional no MySQL/MariaDB de produção.

O pipeline do GitHub executa análise de sintaxe PHP, os testes isolados de cálculo em três versões do PHP e a análise de sintaxe JavaScript antes de publicar uma release. O roteiro de integração pode ser repetido com `npm ci` e `npm test`.
