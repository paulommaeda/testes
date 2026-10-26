# Nortume

Plugin WordPress com testes de perfil, venda de aplicações por módulo no WooCommerce, convites individuais, relatórios detalhados e páginas comerciais na identidade Nortume.

**Versão 0.3.0.** Os quatro bancos de perguntas são autorais e estão em fase piloto. A plataforma funciona com esses bancos, mas sua disponibilização não demonstra validade psicométrica. Revise os instrumentos e a finalidade de uso antes de comercializar aplicações. O plugin não é uma implementação oficial de Everything DiSC nem de PEAKS.

## Instalação

1. Ative o WooCommerce no WordPress.
2. Baixe **maeda-perfis-0.3.0.zip** na seção [Releases](https://github.com/paulommaeda/testes/releases). Use o arquivo do plugin anexado à versão, e não o arquivo “Source code”.
3. Acesse **Plugins → Adicionar plugin → Enviar plugin**, selecione o ZIP e ative.
4. Abra **Nortume** no painel. O plugin cria a página **Minha conta**, com o shortcode `[nortume_perfis]`. Essa área reúne pedidos, dados da conta, testes, pacotes e resultados. O endereço antigo da página é mantido nas atualizações.
5. Clique em **Criar produtos iniciais em rascunho**. Serão criados produtos com 1, 5 e 10 aplicações de cada módulo, sem preços inventados.
6. Defina os preços, revise a descrição e publique os produtos que deseja oferecer.
7. Em **Nortume → Páginas do site**, clique em **Criar e ativar páginas Nortume** para preparar a home, a loja, o carrinho, o checkout e a LP de lançamento.
8. Confira os e-mails transacionais e faça uma compra com seu gateway em modo de testes antes de abrir vendas.

Quem já instalou uma versão anterior pode instalar o novo ZIP como atualização ou usar o atualizador do WordPress. A pasta técnica `maeda-perfis`, as tabelas, os produtos, as contas e os shortcodes existentes são mantidos. O nome padrão anterior passa a ser Nortume; nomes personalizados nas configurações são preservados.

## Site e lançamento

A instalação das páginas é uma ação explícita do administrador. Ela publica somente as páginas que faltam, seleciona a home estática e configura as páginas de loja, carrinho e checkout no WooCommerce. A seleção anterior é guardada. Repetir a ação não sobrescreve conteúdo editado nem duplica as páginas registradas. Se um slug já pertence a outra página, o WordPress escolhe outro slug para a página nova.

| Página | Conteúdo e comportamento |
| --- | --- |
| Home | Proposta Nortume, quatro módulos, exemplo ilustrativo do relatório, públicos, processo, marca e perguntas frequentes. |
| Loja | Produtos publicados vinculados aos módulos, preços reais e filtros por módulo e quantidade de aplicações. |
| Produto | Identidade própria para produtos de testes, conteúdo do módulo, aplicações do pacote, formulário nativo de compra e opções de outros pacotes. Imagem cadastrada no produto tem preferência sobre a ilustração padrão. |
| Carrinho e checkout | Templates do WooCommerce dentro da identidade Nortume. Cupons, variações, campos e gateways continuam sob controle do WooCommerce. O checkout criado usa `[woocommerce_checkout]`. |
| Lançamento | Página própria de apresentação e conversão, com exemplo de relatório, conteúdo da entrega, módulos e links para pacotes e preços da loja. Sem depoimentos, prazos ou descontos fictícios. |

As páginas podem ser abertas e editadas nessa mesma tela do painel. Os shortcodes geram a estrutura comercial; é possível acrescentar conteúdo pelo editor ou substituir o shortcode por conteúdo próprio. A página de produto é um template dinâmico, e seus dados comerciais são editados no WooCommerce. O template Nortume se aplica aos produtos vinculados ao plugin; outros produtos conservam o template do tema.

A configuração **WooCommerce → Configurações → Visibilidade do site** é preservada. Em lojas novas com o modo “Em breve” ativo, conclua sua preparação e selecione a visibilidade pública quando desejar abrir as vendas.

**Restaurar páginas anteriores** desativa os templates públicos e restaura as configurações que ainda apontam para as páginas Nortume. Alterações posteriores feitas pelo proprietário são respeitadas. Desativar o plugin também restaura essa seleção. As páginas, os produtos e os resultados permanecem no banco.

Fontes de sistema, CSS, JavaScript, ícones e gráficos locais dispensam serviços externos para apresentar o site. Os exemplos visuais têm identificação explícita de dados fictícios. O estilo atende temas clássicos e de blocos por meio de um template de página próprio; a ponte de hooks de blocos do WooCommerce é desativada somente nessas páginas, que usam os hooks clássicos. Teste personalizações de tema, construtor e gateway da instalação antes de abrir vendas.

Também é possível configurar um produto existente: em **Dados do produto → Geral**, selecione o módulo e a quantidade de aplicações por unidade. Os produtos de testes são virtuais. Variações podem definir módulo e quantidade próprios, com herança do produto principal quando os campos ficam vazios.

## Minha conta Nortume

A área de testes passa a ser a conta completa do cliente, com interface própria e o mesmo menu superior das páginas comerciais. A visão geral mostra compras recentes, dados pessoais e atalhos. O menu inclui **Meus pedidos**, **Dados da conta**, testes, pacotes, resultados e saída.

- **Pedidos:** listagem paginada de todas as compras vinculadas à conta, detalhe dos itens, quantidades, aplicações adquiridas, totais, pagamento e dados registrados na compra. Apenas notas destinadas ao cliente são exibidas.
- **Pagamento e cancelamento:** pedidos que ainda exigem pagamento oferecem o checkout nativo. Cancelamento exige confirmação e somente é permitido nos estados autorizados pelo WooCommerce; pedidos pagos e pedidos de outra conta são bloqueados.
- **Dados pessoais:** edição de nome, sobrenome, nome de exibição e e-mail. Alterar e-mail ou senha exige a senha atual. A nova senha requer confirmação e de 12 a 128 caracteres. A autenticação continua sendo do WordPress.
- **Faturamento:** edição dos campos padrão do cliente do WooCommerce, com validação de país, estado, código postal, telefone e e-mail. Alterar esse cadastro não reescreve pedidos anteriores nem muda o e-mail de acesso.
- **Acesso e cadastro:** formulários próprios na interface Nortume, processados pelos mecanismos de login e registro do WooCommerce. Os convites mantêm seu token e vínculo com o e-mail. Recuperação de senha usa o fluxo do WordPress.

A página Nortume é selecionada automaticamente como Minha conta nas configurações do WooCommerce. O conteúdo da página anterior não é alterado. Os endereços anteriores e endpoints de pedidos, detalhe, dados e testes encaminham às seções correspondentes. Links de recuperação de senha enviados anteriormente continuam encaminhando ao fluxo de redefinição. Ao desativar o plugin, a seleção anterior de Minha conta é restaurada quando ainda aponta para a Nortume.

As rotas privadas de pedidos e perfil exigem sessão e nonce. A consulta de pedidos verifica o ID do comprador, sem liberar compras apenas por coincidência de e-mail. Não há edição de permissões, perfil de acesso ou metadados arbitrários pelo formulário da conta.

## Módulos incluídos

| Módulo | Perguntas | Dimensões |
| --- | ---: | ---: |
| Comportamento e comunicação | 24 | 4 |
| Liderança e colaboração | 25 | 5 |
| Interesses profissionais | 30 | 6 |
| Serviço e atuação ministerial | 24 | 6 |

DISC e PEAKS são referências conceituais para o desenvolvimento; os questionários, a pontuação e os textos incluídos são próprios. O módulo de interesses profissionais organiza atividades com referência conceitual ao RIASEC, sem reproduzir o O*NET Interest Profiler. O módulo ministerial explora interesses de serviço e não determina dons, chamado ou maturidade espiritual.

O editor de módulos fica no painel, em **Nortume → Módulos**. O esquema JSON é validado antes do salvamento. Ao modificar conteúdo, altere o campo `version`. Cada aplicação recebe uma cópia do instrumento ao ser liberada, e o relatório concluído preserva a pontuação e os textos daquela versão.

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

O relatório inclui **resumo detalhado do perfil**, leitura integrada, interpretação de cada dimensão, possibilidades para explorar, pontos de atenção, perguntas de reflexão e experiências em 7, 14 e 30 dias. Há 63 leituras condicionais para as 21 dimensões dos quatro instrumentos. O motor usa médias, diferenças entre médias e variação das respostas; não envia respostas a um serviço de IA.

As faixas textuais são descritivas da escala: abaixo de 2,5; de 2,5 a menos de 3,5; de 3,5 a 5. Diferença de até 0,35 entre a maior e a menor média é qualificada como proximidade. São decisões editoriais explícitas, sem significado diagnóstico ou corte validado. Empates são preservados, uma maior média baixa não é descrita como intensidade alta e uma menor média ainda alta não é tratada como fragilidade. Carreira e serviço recebem linguagem de interesses.

Novas conclusões gravam a versão da leitura junto ao resultado. Relatórios antigos recebem esse conteúdo na primeira consulta autorizada, usando as respostas e o instrumento originalmente preservados. A leitura é gravada uma vez; consultas seguintes não reescrevem o conteúdo. Instrumentos editados com dimensões diferentes recebem uma leitura descritiva baseada nos próprios textos, evitando aplicar a semântica de outro questionário.

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
[nortume_perfis]
[nortume_home]
[nortume_loja]
[nortume_lancamento]
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
