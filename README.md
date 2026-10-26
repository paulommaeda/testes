# Maeda Perfis

Plugin WordPress com testes de perfil, venda de aplicações por módulo no WooCommerce, convites individuais e relatórios em formato de dashboard.

**Versão inicial 0.1.0.** Os quatro bancos de perguntas são autorais e estão em fase piloto. A plataforma funciona com esses bancos, mas sua disponibilização não demonstra validade psicométrica. Revise os instrumentos e a finalidade de uso antes de comercializar aplicações. O plugin não é uma implementação oficial de Everything DiSC nem de PEAKS.

## Instalação

1. Ative o WooCommerce no WordPress.
2. Baixe **maeda-perfis-0.1.0.zip** na seção [Releases](https://github.com/paulommaeda/testes/releases). Use o arquivo do plugin anexado à versão, e não o arquivo “Source code”.
3. Acesse **Plugins → Adicionar plugin → Enviar plugin**, selecione o ZIP e ative.
4. Abra **Maeda Perfis** no painel. O plugin cria a página **Meus perfis**, com o shortcode `[maeda_perfis]`.
5. Clique em **Criar produtos iniciais em rascunho**. Serão criados produtos com 1, 5 e 10 aplicações de cada módulo, sem preços inventados.
6. Defina os preços, revise a descrição e publique os produtos que deseja oferecer.
7. Confira os e-mails transacionais e faça uma compra com seu gateway em modo de testes antes de abrir vendas.

Também é possível configurar um produto existente: em **Dados do produto → Geral**, selecione o módulo e a quantidade de aplicações por unidade. Os produtos de testes são virtuais. Variações podem definir módulo e quantidade próprios, com herança do produto principal quando os campos ficam vazios.

## Módulos incluídos

| Módulo | Perguntas | Dimensões |
| --- | ---: | ---: |
| Comportamento e comunicação | 24 | 4 |
| Liderança e colaboração | 25 | 5 |
| Interesses profissionais | 30 | 6 |
| Serviço e atuação ministerial | 24 | 6 |

DISC e PEAKS são referências conceituais para o desenvolvimento; os questionários, a pontuação e os textos incluídos são próprios. O módulo de interesses profissionais organiza atividades com referência conceitual ao RIASEC, sem reproduzir o O*NET Interest Profiler. O módulo ministerial explora interesses de serviço e não determina dons, chamado ou maturidade espiritual.

O editor de módulos fica no painel, em **Maeda Perfis → Módulos**. O esquema JSON é validado antes do salvamento. Ao modificar conteúdo, altere o campo `version`. Cada aplicação recebe uma cópia do instrumento ao ser liberada, e o relatório concluído preserva a pontuação e os textos daquela versão.

## Compras, créditos e convites

- Um crédito libera **uma aplicação de um módulo para uma pessoa**. Os créditos são separados por módulo.
- A quantidade comprada multiplica as aplicações por unidade: duas unidades de um pacote de cinco liberam dez aplicações.
- Pedidos pendentes não liberam créditos. Pagamentos confirmados em pedidos pagos são reconciliados sem duplicar aplicações quando o gateway repete notificações.
- O comprador usa o saldo em **Fazer meu teste** ou **Enviar convite individual**.
- Um convite reserva o crédito. O destinatário entra ou cria uma conta com o mesmo e-mail, confirma ciência do compartilhamento e aceita o convite.
- Abrir o link por GET não resgata o convite. O aceite exige sessão, nonce, token e correspondência do e-mail.
- O crédito passa a ser utilizado quando o participante inicia o teste. Voltar à aplicação iniciada não consome outro crédito.
- Convites pendentes expiram em sete dias por padrão, com configuração de 1 a 90 dias. A rotina de expiração devolve o saldo reservado. Os créditos não têm expiração automática nesta versão.
- Convites pendentes e aplicações aceitas ainda não iniciadas podem ser cancelados pelo comprador. Uma aplicação própria ainda não iniciada também pode voltar ao saldo.
- Reenvios geram um token novo e invalidam o link anterior. Existe intervalo de um minuto entre reenvios e limite operacional de 100 envios por comprador por hora.
- O plugin usa `wp_mail`. O retorno de envio não comprova entrega na caixa postal; verifique a configuração de e-mails da hospedagem e a reputação do remetente.

As áreas **Meus testes**, **Pacotes e convites** e **Resultados dos participantes** também ficam integradas à Minha conta do WooCommerce. A mesma conta pode comprar e responder testes.

## Relatórios e acesso

Os dashboards exibem radar, barras por dimensão, médias em escala de 1 a 5, identificação da versão e sugestões de reflexão. Os gráficos são SVG locais, com textos alternativos e valores visíveis; não dependem de bibliotecas em CDN ou serviços externos. O botão **Imprimir / salvar PDF** usa a impressão do navegador, com folha de estilos própria.

O participante acessa seus relatórios. Quem comprou acessa o relatório final de cada aplicação que financiou, após sua conclusão; não recebe respostas individuais nem outros resultados da conta do participante. O compartilhamento é informado antes do aceite do convite. A administração do plugin tem uma permissão específica, concedida inicialmente apenas aos administradores do site.

Médias são descritivas das respostas. O plugin não calcula percentis populacionais, índices de empregabilidade ou um “perfil ideal”. Empates são preservados e não há classificação geral de pessoas. A pontuação roda no servidor, considera os itens invertidos e só conclui o teste com todas as respostas válidas.

## Reembolsos e alterações de pedido

Reembolsos com quantidade por item reduzem os créditos daquele item, priorizando créditos disponíveis e ainda não iniciados. Um pedido totalmente reembolsado, cancelado, falho ou que deixe de estar pago bloqueia seus créditos. O histórico das operações permanece.

Uma aplicação concluída bloqueada por reembolso deixa de estar disponível ao comprador, mas o participante mantém acesso ao próprio relatório. Reembolsos de valor sem quantidade exigem revisão manual; o plugin adiciona uma nota ao pedido. Nesta versão, desfazer um reembolso ou voltar um pedido bloqueado para pago não restaura automaticamente créditos já revogados. A administração deve conferir o histórico e, se apropriado, conceder novos créditos manualmente.

## Privacidade e operação

- Respostas, relatórios e vínculos ficam no banco do WordPress, em quatro tabelas próprias. Dados dos participantes não são enviados ao GitHub.
- Tokens de convite são armazenados como hash SHA-256; o token original é usado no e-mail e não é guardado na tabela.
- As rotas privadas exigem autenticação e nonce, com autorização por aplicação. As páginas pessoais e os dados REST privados recebem cabeçalhos para evitar cache público.
- O plugin participa das ferramentas de exportação e remoção de dados pessoais do WordPress e fornece texto de apoio para a política de privacidade.
- A remoção pessoal de aplicações apaga respostas e resultados daquela pessoa. Registros comerciais e aplicações de terceiros podem ser retidos para conciliação, com mensagem à administração.
- Desativar ou excluir o plugin preserva os dados por padrão. A exclusão definitiva pode ser habilitada nas configurações antes de apagar o plugin. Produtos e contas de usuários não são apagados.
- Defina a política de retenção e o fluxo específico de autorização de responsáveis antes de atender menores. Esta versão inicial não implementa um fluxo próprio de coleta dessa autorização.
- Use HTTPS e exclua as páginas de conta, a página do aplicativo e as rotas privadas de qualquer cache externo que ignore os cabeçalhos do WordPress.
- A expiração programada depende do WP-Cron ou do cron real configurado para a instalação.

## Shortcodes

```text
[maeda_perfis]
[testes_perfil]
[maeda_perfis view="catalog"]
[maeda_perfis view="tests"]
[maeda_perfis view="packages"]
[maeda_perfis view="team"]
```

A página criada pelo plugin usa um template próprio para o dashboard ocupar a largura disponível. Os shortcodes em outras páginas respeitam a área de conteúdo do tema.

## Atualizações pelo GitHub

Origem: [paulommaeda/testes](https://github.com/paulommaeda/testes).

O atualizador consulta releases públicas, verifica a versão e procura o arquivo `maeda-perfis-VERSÃO.zip`. Quando há uma versão mais nova, o WordPress a oferece na lista de plugins. Para instalar sem clicar em Atualizar, habilite **Ativar atualizações automáticas** na linha do plugin. As verificações seguem o agendamento do WordPress e o cache do atualizador.

O workflow `.github/workflows/release.yml` valida o código, monta o ZIP e cria a release correspondente à versão do cabeçalho quando um commit entra em `main`. Uma release existente não é sobrescrita. Para atualizar o plugin instalado, incremente a versão em `maeda-perfis/maeda-perfis.php`, atualize o changelog e envie o commit. Também há execução manual pelo GitHub Actions. As permissões do repositório precisam permitir o workflow e gravação de releases.

Nenhum token pessoal do GitHub é incluído no plugin. A primeira versão do atualizador atende repositórios públicos.

## Desenvolvimento e verificação

Requisitos de instalação: WordPress 6.5+, PHP 8.1+, WooCommerce ativo e tabelas transacionais InnoDB no MySQL/MariaDB. A integração usa as APIs de pedidos do WooCommerce, com declaração de compatibilidade HPOS e checkout em blocos.

```sh
php tests/unit.php
node --check maeda-perfis/assets/app.js
python3 scripts/build.py
```

Para os testes reais em WordPress Playground, use Node 24.18+:

```sh
npm ci
npm test
```

O ambiente é descartável, intercepta o envio de e-mails e usa contas e produtos sintéticos. O diretório `tests` não é incluído no ZIP instalável. Consulte [TESTING.md](TESTING.md) para o escopo das verificações.

Licença do código: GPL-2.0-or-later.
