<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Admin
{
    public static function register(): void
    {
        add_action('admin_menu',static function () {
            add_menu_page('Nortume','Nortume','manage_pmp','maeda-perfis',[self::class,'dashboard'],'dashicons-chart-area',56);
            add_submenu_page('maeda-perfis','Módulos e questionários','Módulos','manage_pmp','pmp-modules',[self::class,'modules']);
            add_submenu_page('maeda-perfis','Créditos e aplicações','Créditos e aplicações','manage_pmp','pmp-credits',[self::class,'credits']);
            add_submenu_page('maeda-perfis','Configurações','Configurações','manage_pmp','pmp-settings',[self::class,'settings']);
            add_submenu_page('maeda-perfis','Páginas do site','Páginas do site','manage_pmp','pmp-site',[self::class,'site']);
        });
        add_action('admin_post_pmp_admin',[self::class,'handle']);
        add_action('admin_enqueue_scripts',static function ($hook) {
            if (strpos((string) $hook,'maeda-perfis') !== false || strpos((string) $hook,'pmp-') !== false) {
                wp_enqueue_style('pmp-admin',PMP_URL . 'assets/admin.css',[],PMP_VERSION);
            }
        });
    }

    private static function open(string $title): void
    {
        if (!current_user_can('manage_pmp')) wp_die('Acesso não permitido.');
        echo '<div class="wrap pmp-admin"><p class="pmp-eyebrow">NORTUME · ADMINISTRAÇÃO</p><h1>' . esc_html($title) . '</h1>';
        if (!empty($_GET['pmp_message']) && is_string($_GET['pmp_message'])) {
            echo '<div class="notice notice-' . (empty($_GET['pmp_error']) ? 'success' : 'error') . '"><p>' . esc_html(sanitize_text_field(wp_unslash($_GET['pmp_message']))) . '</p></div>';
        }
    }

    private static function form(string $task): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('pmp_admin');
        echo '<input type="hidden" name="action" value="pmp_admin"><input type="hidden" name="task" value="' . esc_attr($task) . '">';
    }

    public static function dashboard(): void
    {
        global $wpdb;
        self::open('Seu centro de aplicações');
        echo '<p class="pmp-admin-lead">Gerencie os módulos, as aplicações compradas e os convites em um só lugar.</p><div class="pmp-admin-stats">';
        foreach ([['Módulos',count(Modules::all())],['Créditos disponíveis',(int) $wpdb->get_var("SELECT COUNT(*) FROM " . Database::table('credits') . " WHERE status = 'available'")],['Testes concluídos',(int) $wpdb->get_var("SELECT COUNT(*) FROM " . Database::table('attempts') . " WHERE status = 'completed'")],['Convites pendentes',(int) $wpdb->get_var("SELECT COUNT(*) FROM " . Database::table('invites') . " WHERE status = 'pending'")]] as [$label,$value]) {
            echo '<div class="pmp-admin-card"><span>' . esc_html($label) . '</span><strong>' . esc_html((string) $value) . '</strong></div>';
        }
        echo '</div><div class="pmp-admin-card"><h2>Preparar a loja</h2><ol><li>Revise os quatro questionários piloto na área de módulos.</li><li>Crie os produtos iniciais abaixo ou vincule seus produtos no WooCommerce.</li><li>Defina preços e publique os produtos que deseja vender.</li><li>Faça uma compra de teste com um método de pagamento em modo de testes e confira o envio de e-mails.</li></ol>';
        self::form('seed_products');
        submit_button('Criar produtos iniciais em rascunho','primary','submit',false);
        echo '</form><p>Serão preparados produtos com 1, 5 e 10 aplicações de cada módulo, sem preço definido. A ação pode ser repetida sem duplicar os produtos iniciais.</p></div>';
        echo '<div class="pmp-admin-card"><h2>Seu site Nortume</h2><p>Home, loja, páginas de produto, carrinho, checkout e landing page de lançamento com a identidade Nortume.</p><p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=pmp-site')) . '">Preparar páginas do site</a> <a class="button" target="_blank" rel="noopener" href="' . esc_url(Plugin::url()) . '">Área dos participantes</a> <a class="button" href="' . esc_url(admin_url('edit.php?post_type=product')) . '">Produtos do WooCommerce</a></p><p>Área de testes: <code>[nortume_perfis]</code>. Os shortcodes antigos continuam funcionando.</p></div>';
        $events = $wpdb->get_results('SELECT * FROM ' . Database::table('events') . ' ORDER BY id DESC LIMIT 20',ARRAY_A);
        echo '<div class="pmp-admin-card"><h2>Atividade recente</h2><table class="widefat striped"><thead><tr><th>Data UTC</th><th>Evento</th><th>Registro</th></tr></thead><tbody>';
        foreach ($events as $event) echo '<tr><td>' . esc_html($event['created_at']) . '</td><td>' . esc_html($event['event_key']) . '</td><td>#' . (int) $event['object_id'] . '</td></tr>';
        if (!$events) echo '<tr><td colspan="3">As operações serão registradas aqui.</td></tr>';
        echo '</tbody></table></div></div>';
    }

    public static function modules(): void
    {
        self::open('Módulos e questionários');
        echo '<p>Os bancos incluídos são versões autorais piloto, sem estudos de validação ou normas populacionais. Revise conteúdo e finalidade antes de comercializar as aplicações.</p>';
        $all = Modules::all();
        $selected = isset($_GET['module']) && is_string($_GET['module']) ? sanitize_key($_GET['module']) : array_key_first($all);
        echo '<nav class="nav-tab-wrapper">';
        foreach ($all as $key=>$module) echo '<a class="nav-tab ' . ($selected === $key ? 'nav-tab-active' : '') . '" href="' . esc_url(add_query_arg(['page'=>'pmp-modules','module'=>$key],admin_url('admin.php'))) . '">' . esc_html($module['title']) . '</a>';
        echo '</nav>';
        if (isset($all[$selected])) {
            $module = $all[$selected];
            echo '<div class="pmp-admin-card"><h2>' . esc_html($module['title']) . '</h2><p>' . count($module['questions']) . ' perguntas · ' . count($module['dimensions']) . ' dimensões · versão ' . esc_html($module['version']) . '</p><p>' . esc_html($module['reference'] ?? '') . '</p><p>O editor permite alterar textos, perguntas e dimensões. Ao editar, altere também <code>version</code>. As aplicações já liberadas mantêm sua cópia original.</p>';
            self::form('module');
            echo '<input type="hidden" name="module_key" value="' . esc_attr($selected) . '"><label for="pmp-module-json"><strong>Definição do módulo em JSON</strong></label><textarea id="pmp-module-json" class="large-text code" name="module_json" rows="28" spellcheck="false">' . esc_textarea(wp_json_encode($module,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</textarea>';
            submit_button('Salvar nova versão do módulo');
            echo '</form></div>';
        }
        echo '</div>';
    }

    public static function credits(): void
    {
        global $wpdb;
        self::open('Créditos e aplicações');
        echo '<div class="pmp-admin-card"><h2>Liberar aplicações manualmente</h2><p>O destinatário do saldo deve ter uma conta no site. Os créditos ficarão disponíveis para uso próprio ou envio de convites.</p>';
        self::form('grant');
        echo '<div class="pmp-admin-fields"><label>E-mail da conta<input type="email" name="email" required></label><label>Módulo<select name="module">';
        foreach (Modules::all() as $key=>$module) echo '<option value="' . esc_attr($key) . '">' . esc_html($module['title']) . '</option>';
        echo '</select></label><label>Aplicações<input type="number" name="quantity" min="1" max="500" value="1" required></label></div>';
        submit_button('Liberar créditos','primary','submit',false);
        echo '</form></div>';
        $page = max(1,absint($_GET['paged'] ?? 1));
        $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . Database::table('credits') . ' ORDER BY id DESC LIMIT 40 OFFSET %d',($page-1)*40),ARRAY_A);
        echo '<div class="pmp-admin-card"><h2>Aplicações recentes</h2><div class="pmp-table-scroll"><table class="widefat striped"><thead><tr><th>Crédito</th><th>Comprador</th><th>Módulo</th><th>Situação</th><th>Pedido</th><th>Ação</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr><td>#' . (int) $row['id'] . '</td><td>' . esc_html(Assessments::user_name((int) $row['buyer_id'])) . '</td><td>' . esc_html($row['module_key']) . '</td><td>' . esc_html(self::status_label($row['status'])) . '</td><td>' . ($row['order_id'] ? '#' . (int) $row['order_id'] : 'Manual') . '</td><td>';
            if ($row['status'] === 'completed' && $row['attempt_id']) echo '<a class="button button-small" target="_blank" rel="noopener" href="' . esc_url(Plugin::url(['pmp_report'=>(int) $row['attempt_id']])) . '">Relatório</a> ';
            if ($row['status'] !== 'revoked') {
                self::form('revoke');
                echo '<input type="hidden" name="credit_id" value="' . (int) $row['id'] . '"><button type="submit" class="button button-small">Revogar acesso</button></form>';
            }
            echo '</td></tr>';
        }
        if (!$rows) echo '<tr><td colspan="6">Nenhum crédito nesta página.</td></tr>';
        echo '</tbody></table></div><p>';
        if ($page>1) echo '<a class="button" href="' . esc_url(add_query_arg(['page'=>'pmp-credits','paged'=>$page-1],admin_url('admin.php'))) . '">Anterior</a> ';
        if (count($rows) === 40) echo '<a class="button" href="' . esc_url(add_query_arg(['page'=>'pmp-credits','paged'=>$page+1],admin_url('admin.php'))) . '">Próxima</a>';
        echo '</p><p>A revogação bloqueia o uso do crédito e o acesso do comprador ao relatório daquela aplicação. Um participante mantém acesso ao próprio relatório já concluído.</p></div></div>';
    }

    public static function status_label(string $status): string
    {
        return ['available'=>'Disponível','reserved'=>'Convite pendente','assigned'=>'Liberado','in_progress'=>'Em andamento','completed'=>'Concluído','revoked'=>'Revogado','cancelled'=>'Cancelado','expired'=>'Expirado','pending'=>'Pendente','accepted'=>'Aceito'][$status] ?? $status;
    }

    public static function settings(): void
    {
        self::open('Configurações');
        echo '<div class="pmp-admin-card">';
        self::form('settings');
        echo '<table class="form-table"><tr><th><label for="pmp-brand">Nome exibido</label></th><td><input class="regular-text" id="pmp-brand" name="brand" maxlength="60" value="' . esc_attr(get_option('pmp_brand','Nortume')) . '" required></td></tr><tr><th><label for="pmp-days">Validade dos convites</label></th><td><input id="pmp-days" type="number" name="invite_days" min="1" max="90" value="' . (int) get_option('pmp_invite_days',7) . '"> dias<p class="description">A expiração de um convite pendente devolve o crédito. Os créditos não têm expiração automática nesta versão.</p></td></tr><tr><th>Remoção de dados</th><td><label><input type="checkbox" name="delete_data" value="1" ' . checked((bool) get_option('pmp_delete_data'),true,false) . '> Apagar os dados do plugin ao excluí-lo pelo WordPress</label><p class="description">Desativar o plugin preserva os dados. A exclusão também preserva os dados, salvo se esta opção estiver marcada.</p></td></tr></table>';
        submit_button('Salvar configurações');
        echo '</form></div><div class="pmp-admin-card"><h2>Atualizações pelo GitHub</h2><p>Origem: <a href="https://github.com/paulommaeda/testes" target="_blank" rel="noopener">paulommaeda/testes</a>. O atualizador consulta releases públicas com o arquivo <code>maeda-perfis-VERSÃO.zip</code> anexado. Para instalar automaticamente, habilite as atualizações automáticas na lista de plugins do WordPress.</p><p>Os dados dos participantes ficam no banco do seu WordPress. Eles não são enviados ao GitHub.</p></div><div class="pmp-admin-card"><h2>Privacidade e envio de e-mails</h2><p>O plugin participa das ferramentas de exportação e remoção de dados pessoais do WordPress. Revise a política de privacidade e os prazos de retenção da sua operação. O envio usa <code>wp_mail</code>; confirme a entrega com a configuração de e-mails da sua hospedagem.</p><p>Antes de atender menores, defina o fluxo de autorização dos responsáveis e revise os instrumentos para a faixa etária. Esta primeira versão não implementa uma coleta específica de autorização de responsáveis.</p></div></div>';
    }

    public static function site(): void
    {
        self::open('Páginas do site Nortume');
        $pages = (array) get_option('pmp_site_pages', []);
        echo '<div class="pmp-admin-card"><h2>Identidade e navegação em um só lugar</h2><p>Prepare a home, a loja, a landing page de lançamento, o carrinho e o checkout. A página individual de cada produto de teste recebe a mesma identidade, com os formulários do WooCommerce.</p><p>A ação abaixo cria as páginas que faltam, publica essas novas páginas e seleciona a home e as páginas de loja, carrinho e checkout nas configurações do WordPress e do WooCommerce. Páginas existentes não são apagadas e o conteúdo das páginas Nortume já criadas não é sobrescrito. A seleção anterior é guardada para restauração.</p>';
        self::form('site_install');
        submit_button($pages ? 'Ativar páginas Nortume / completar páginas ausentes' : 'Criar e ativar páginas Nortume', 'primary', 'submit', false);
        echo '</form><p>O checkout preparado usa o shortcode nativo <code>[woocommerce_checkout]</code>, compatível com os campos, cupons e métodos de pagamento configurados no WooCommerce. Produtos e preços são os cadastrados na sua loja.</p><p>A configuração de visibilidade do WooCommerce é preservada. Se sua loja estiver no modo “Em breve”, revise <a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=site-visibility')) . '">WooCommerce → Configurações → Visibilidade do site</a> quando desejar abrir o acesso público.</p></div>';
        if ($pages) {
            echo '<div class="pmp-admin-card"><h2>Suas páginas</h2><table class="widefat striped"><thead><tr><th>Página</th><th>Situação</th><th>Ações</th></tr></thead><tbody>';
            foreach (['home'=>'Home','shop'=>'Loja','launch'=>'LP de lançamento','cart'=>'Carrinho','checkout'=>'Checkout'] as $key=>$label) {
                $id = (int) ($pages[$key] ?? 0);
                echo '<tr><td>' . esc_html($label) . '</td><td>' . esc_html($id ? (get_post_status($id) ?: 'Ausente') : 'Ausente') . '</td><td>';
                if ($id && get_post($id)) echo '<a class="button button-small" target="_blank" rel="noopener" href="' . esc_url(get_permalink($id)) . '">Abrir</a> <a class="button button-small" href="' . esc_url(get_edit_post_link($id)) . '">Editar página</a>';
                echo '</td></tr>';
            }
            echo '</tbody></table><p>Shortcodes: <code>[nortume_home]</code>, <code>[nortume_loja]</code> e <code>[nortume_lancamento]</code>. Você pode acrescentar conteúdo pelo editor do WordPress. Para alterar a estrutura completa, substitua o shortcode pelo conteúdo desejado.</p></div>';
        }
        if (get_option('pmp_site_enabled')) {
            echo '<div class="pmp-admin-card"><h2>Restaurar a seleção anterior</h2><p>Desativa os templates públicos Nortume e restaura a seleção anterior de páginas onde ela ainda aponta para as páginas criadas pelo plugin. Os conteúdos, produtos, compras e resultados são preservados.</p>';
            self::form('site_disable'); submit_button('Restaurar páginas anteriores', 'secondary', 'submit', false); echo '</form></div>';
        }
        echo '</div>';
    }

    public static function handle(): void
    {
        if (!current_user_can('manage_pmp')) wp_die('Acesso não permitido.',403);
        check_admin_referer('pmp_admin');
        $task = isset($_POST['task']) && is_string($_POST['task']) ? sanitize_key($_POST['task']) : '';
        $page = 'maeda-perfis';
        $message = 'Operação concluída.';
        $error = false;
        if ($task === 'site_install') {
            $page = 'pmp-site';
            $result = Site::install();
            if (is_wp_error($result)) { $error = true; $message = $result->get_error_message(); }
            else $message = 'Páginas Nortume preparadas e ativadas. Confira a home, os produtos publicados e seu checkout.';
        } elseif ($task === 'site_disable') {
            $page = 'pmp-site'; Site::disable(); $message = 'Templates públicos desativados e seleções anteriores restauradas quando aplicável.';
        } elseif ($task === 'settings') {
            $page = 'pmp-settings';
            update_option('pmp_brand',substr(sanitize_text_field(wp_unslash($_POST['brand'] ?? 'Nortume')),0,60),false);
            update_option('pmp_invite_days',max(1,min(90,absint($_POST['invite_days'] ?? 7))),false);
            update_option('pmp_delete_data',!empty($_POST['delete_data']),false);
            $message = 'Configurações salvas.';
        } elseif ($task === 'seed_products') {
            if (!class_exists('WC_Product_Simple')) {
                $error = true; $message = 'Ative o WooCommerce antes de criar os produtos.';
            } else {
                $created = 0;
                foreach (Modules::all() as $key=>$module) {
                    foreach ([1,5,10] as $size) {
                        $seed = $key . ':' . $size;
                        $existing = get_posts(['post_type'=>'product','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_pmp_seed','meta_value'=>$seed]);
                        if ($existing) continue;
                        $product = new \WC_Product_Simple();
                        $product->set_name($module['title'] . ' — ' . $size . ($size === 1 ? ' aplicação' : ' aplicações'));
                        $product->set_status('draft');
                        $product->set_virtual(true);
                        $product->set_description($module['description'] . "\n\nAplicação de questionário autoral piloto, com relatório individual e gráficos por dimensão. Um crédito corresponde a uma aplicação deste módulo. Para si ou para convidar outra pessoa, que deverá criar uma conta no site.");
                        $product->update_meta_data('_pmp_module',$key);
                        $product->update_meta_data('_pmp_pack_size',$size);
                        $product->update_meta_data('_pmp_seed',$seed);
                        $product->save();
                        $created++;
                    }
                }
                $message = $created . ' produtos criados em rascunho. Defina os preços e publique os produtos desejados.';
            }
        } elseif ($task === 'grant') {
            $page = 'pmp-credits';
            $user = get_user_by('email',sanitize_email(wp_unslash($_POST['email'] ?? '')));
            $result = $user ? Database::transaction(static fn() => Credits::grant((int) $user->ID,sanitize_key($_POST['module'] ?? ''),max(1,min(500,absint($_POST['quantity'] ?? 1))))) : Plugin::error('user','Nenhuma conta foi encontrada com esse e-mail.');
            if (is_wp_error($result)) { $error = true; $message = $result->get_error_message(); }
            else { Database::audit('manual_credits_granted',(int) $user->ID,['quantity'=>count($result)]); $message = count($result) . ' créditos liberados.'; }
        } elseif ($task === 'revoke') {
            $page = 'pmp-credits';
            $id = absint($_POST['credit_id'] ?? 0);
            $result = Database::transaction(static fn() => Credits::revoke($id,'admin'));
            if (is_wp_error($result) || !$result) { $error = true; $message = 'Não foi possível revogar esse crédito.'; }
            else $message = 'Crédito revogado. O histórico foi preservado.';
        } elseif ($task === 'module') {
            $page = 'pmp-modules';
            $key = sanitize_key($_POST['module_key'] ?? '');
            $current = Modules::get($key);
            $module = json_decode(wp_unslash($_POST['module_json'] ?? ''),true);
            $valid = is_array($module) ? Modules::validate($module) : Plugin::error('json','JSON inválido.');
            if (!$current || is_wp_error($valid) || $module['key'] !== $key) {
                $error = true; $message = is_wp_error($valid) ? $valid->get_error_message() : 'O identificador do módulo deve ser preservado.';
            } elseif ($module['version'] === $current['version'] && $module !== $current) {
                $error = true; $message = 'Altere o campo version para salvar uma nova revisão.';
            } else {
                $overrides = (array) get_option('pmp_modules',[]);
                $overrides[$key] = $module;
                update_option('pmp_modules',$overrides,false);
                Database::audit('module_updated',0,['module'=>$key,'version'=>$module['version']]);
                $message = 'Módulo atualizado. Aplicações existentes mantiveram a versão original.';
            }
        } else {
            $error = true; $message = 'Operação não reconhecida.';
        }
        wp_safe_redirect(add_query_arg(['page'=>$page,'pmp_message'=>$message,'pmp_error'=>$error ? '1' : ''],admin_url('admin.php')));
        exit;
    }
}
