<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

/** Páginas públicas e apresentação do WooCommerce, sem substituir seu fluxo de compra. */
final class Site
{
    private const DEFINITIONS = [
        'home' => ['Nortume', 'nortume', '[nortume_home]'],
        'shop' => ['Testes Nortume', 'testes', '[nortume_loja]'],
        'launch' => ['Conheça a Nortume', 'lancamento', '[nortume_lancamento]'],
        'cart' => ['Carrinho', 'carrinho-nortume', '[woocommerce_cart]'],
        'checkout' => ['Finalizar compra', 'finalizar-compra-nortume', '[woocommerce_checkout]'],
    ];

    public static function register(): void
    {
        foreach (['nortume_home'=>'home','nortume_loja'=>'shop','nortume_lancamento'=>'launch'] as $shortcode=>$view) {
            add_shortcode($shortcode, static fn() => self::render($view));
        }
        add_action('wp_enqueue_scripts', [self::class, 'assets']);
        add_filter('template_include', static fn($template) => self::route() ? PMP_PATH . 'templates/site-page.php' : $template, 999);
        // Nosso template usa os hooks clássicos. A ponte de blocos não deve removê-los ao renderizar descrições.
        add_filter('woocommerce_disable_compatibility_layer', static fn($disabled) => self::route() ? true : $disabled, 20);
        add_filter('body_class', static function ($classes) {
            if ($route = self::route()) { $classes[] = 'nt-site-body'; $classes[] = 'nt-view-' . $route; }
            return $classes;
        });
        add_action('wp_head', static function () {
            $route = self::route();
            if (!in_array($route, ['home','launch','shop'], true) || class_exists('WPSEO_Options') || defined('RANK_MATH_VERSION')) return;
            echo '<meta name="description" content="' . esc_attr('Nortume: perfis e desenvolvimento humano. Explore comportamento, liderança, carreira e serviço com gráficos, leitura detalhada e próximos passos.') . '">' . "\n";
        });
        add_action('template_redirect', static function () {
            if (in_array(self::route(), ['cart','checkout','account'], true)) {
                if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
                nocache_headers();
            }
        });
        add_action('woocommerce_before_single_product_summary', [self::class, 'product_art'], 5);
        add_action('woocommerce_single_product_summary', [self::class, 'product_eyebrow'], 4);
        add_action('woocommerce_single_product_summary', [self::class, 'product_facts'], 25);
        add_action('woocommerce_single_product_summary', [self::class, 'product_includes'], 31);
        add_action('woocommerce_single_product_summary', [self::class, 'product_packs'], 35);
        add_action('woocommerce_after_single_product_summary', [self::class, 'product_details'], 5);
        add_filter('woocommerce_available_variation', static function ($data, $parent, $variation) {
            $config = Commerce::config($variation);
            if (!get_option('pmp_site_enabled') || !$config) return $data;
            $module = Modules::get($config['module']);
            $data['variation_description'] = ($data['variation_description'] ?? '') . '<div class="nt-selected-variation"><strong>' . esc_html($module['title']) . '</strong><p>' . (int) $config['size'] . ($config['size'] === 1 ? ' aplicação individual' : ' aplicações individuais') . ' deste módulo · ' . count($module['questions']) . ' perguntas por pessoa.</p></div>';
            return $data;
        }, 10, 3);
        add_action('wp', static function () {
            if (self::route() === 'product') {
                $product = wc_get_product(get_queried_object_id());
                if ($product && !$product->get_image_id()) remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
            }
        });
    }

    public static function brand(): string { return sanitize_text_field(get_option('pmp_brand', 'Nortume')); }

    public static function install(bool $activate = true)
    {
        if (!class_exists('WooCommerce')) return Plugin::error('woo', 'Ative o WooCommerce antes de preparar as páginas.');
        $pages = (array) get_option('pmp_site_pages', []);
        foreach (self::DEFINITIONS as $key=>[$title,$slug,$content]) {
            $page = !empty($pages[$key]) ? get_post((int) $pages[$key]) : null;
            if ($page && $page->post_type === 'page' && $page->post_status !== 'trash') continue;
            // Não adota uma página alheia pelo slug nem altera conteúdo já editado.
            $id = wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$title,'post_name'=>$slug,'post_content'=>$content], true);
            if (is_wp_error($id)) return $id;
            $pages[$key] = (int) $id;
            update_post_meta($id, '_pmp_site_page', $key);
            update_option('pmp_site_pages', $pages, false);
        }
        if (wc_get_page_id('myaccount') <= 0) {
            $id = wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Minha conta','post_name'=>'minha-conta','post_content'=>'[woocommerce_my_account]'], true);
            if (is_wp_error($id)) return $id;
            update_option('woocommerce_myaccount_page_id', (int) $id);
        }
        if ($activate) {
            if (!get_option('pmp_site_previous')) {
                $previous = [];
                foreach (array_keys(self::assignments($pages)) as $option) $previous[$option] = get_option($option, '');
                update_option('pmp_site_previous', $previous, false);
            }
            foreach (self::assignments($pages) as $option=>$value) update_option($option, $value);
            update_option('pmp_site_enabled', true, false);
            flush_rewrite_rules();
        }
        return $pages;
    }

    private static function assignments(array $pages): array
    {
        return ['show_on_front'=>'page','page_on_front'=>$pages['home'] ?? 0,'woocommerce_shop_page_id'=>$pages['shop'] ?? 0,'woocommerce_cart_page_id'=>$pages['cart'] ?? 0,'woocommerce_checkout_page_id'=>$pages['checkout'] ?? 0];
    }

    public static function disable(): void
    {
        $assignments = self::assignments((array) get_option('pmp_site_pages', []));
        $previous = (array) get_option('pmp_site_previous', []);
        $owns_home = (int) get_option('page_on_front') === (int) $assignments['page_on_front'];
        foreach ($assignments as $option=>$value) {
            if (in_array($option, ['show_on_front','page_on_front'], true) && !$owns_home) continue;
            if ((string) get_option($option) === (string) $value && array_key_exists($option, $previous)) update_option($option, $previous[$option]);
        }
        delete_option('pmp_site_previous');
        update_option('pmp_site_enabled', false, false);
        flush_rewrite_rules();
    }

    public static function url(string $key = 'home', array $args = []): string
    {
        $pages = (array) get_option('pmp_site_pages', []);
        $page = (int) ($pages[$key] ?? 0);
        $url = $page && get_post_status($page) === 'publish' ? get_permalink($page) : ($key === 'shop' && function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/'));
        return $args ? add_query_arg($args, $url) : $url;
    }

    public static function route(): ?string
    {
        if (Frontend::is_app_page()) return null;
        if (is_admin() || !get_option('pmp_site_enabled')) return null;
        foreach ((array) get_option('pmp_site_pages', []) as $key=>$id) if ($id && is_page((int) $id)) return $key;
        if (function_exists('is_shop') && is_shop()) return 'shop';
        if (function_exists('is_product') && is_product() && Commerce::config(wc_get_product(get_queried_object_id()))) return 'product';
        if (function_exists('is_checkout') && is_checkout()) return 'checkout';
        if (function_exists('is_cart') && is_cart()) return 'cart';
        if (function_exists('is_account_page') && is_account_page()) return 'account';
        return null;
    }

    public static function assets(): void
    {
        global $post;
        if (!self::route() && !(is_object($post) && (has_shortcode($post->post_content, 'nortume_home') || has_shortcode($post->post_content, 'nortume_loja') || has_shortcode($post->post_content, 'nortume_lancamento')))) return;
        self::enqueue_assets();
    }

    public static function enqueue_assets(): void
    {
        wp_enqueue_style('nortume-site', PMP_URL . 'assets/site.css', [], PMP_VERSION);
        wp_enqueue_script('nortume-site', PMP_URL . 'assets/site.js', [], PMP_VERSION, true);
        wp_script_add_data('nortume-site', 'strategy', 'defer');
    }

    public static function icon(string $name = 'north'): string
    {
        $paths = [
            'north'=>'m12 2 8 19-8-5-8 5 8-19Zm0 0v14',
            'arrow'=>'M4 12h16m-6-6 6 6-6 6',
            'check'=>'m5 12 4 4L19 6',
            'bag'=>'M5 7h14l1 14H4L5 7Zm3 0V6a4 4 0 0 1 8 0v1',
            'user'=>'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21v-2a6 6 0 0 1 6-5h4a6 6 0 0 1 6 5v2',
            'spark'=>'m12 2 3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7Z',
            'team'=>'M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM2 21v-2a6 6 0 0 1 6-5h2a6 6 0 0 1 6 5v2M17 4a4 4 0 0 1 0 8M22 21v-2a6 6 0 0 0-4-5.5',
            'heart'=>'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z',
        ];
        return '<svg class="nt-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . ($paths[$name] ?? $paths['north']) . '"/></svg>';
    }

    public static function header(string $route): void
    {
        $launch = $route === 'launch';
        $account_page = $route === 'account';
        $shop = self::url('shop');
        $account = Plugin::url(is_user_logged_in() ? [] : ['pmp_view'=>'login']);
        $pages = (array)get_option('pmp_site_pages', []);
        $has_home = !empty($pages['home']) && get_post_status((int)$pages['home']) === 'publish';
        $has_launch = !empty($pages['launch']) && get_post_status((int)$pages['launch']) === 'publish';
        $count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        echo '<a class="nt-skip" href="' . ($account_page ? '#pmp-page-content' : '#nt-main') . '">Ir para o conteúdo</a>';
        if ($route === 'home') echo '<div class="nt-announcement">Um novo olhar. Um próximo passo. <a href="' . esc_url(self::url('launch')) . '">Conheça a Nortume ' . self::icon('arrow') . '</a></div>';
        echo '<header class="nt-header"><div class="nt-wrap nt-header-inner"><a class="nt-wordmark" href="' . esc_url(self::url()) . '" aria-label="' . esc_attr(self::brand()) . ' início"><span class="nt-symbol">' . self::icon() . '</span>' . esc_html(self::brand()) . '<span class="nt-wordmark-dot">.</span></a>';
        echo '<button class="nt-menu-toggle" type="button" aria-expanded="false" aria-controls="nt-navigation"><span></span><span></span><span class="nt-sr-only">Abrir menu</span></button>';
        echo '<nav class="nt-navigation" id="nt-navigation" aria-label="Navegação principal">' . ($account_page ? '<a href="' . esc_url(self::url()) . '">Início</a>' : '') . '<a href="' . esc_url($shop) . '" ' . ($route === 'shop' ? 'aria-current="page"' : '') . '>Explorar testes</a>' . ($has_home ? '<a href="' . esc_url(self::url() . '#como-funciona') . '">Como funciona</a>' : '') . (!$account_page ? '<a href="' . esc_url(Plugin::url(['pmp_view'=>'tests'])) . '">Meus resultados</a>' : '') . (!$launch && $has_launch ? '<a href="' . esc_url(self::url('launch')) . '">Lançamento</a>' : '') . '</nav>';
        $name = is_user_logged_in() ? wp_get_current_user()->display_name : '';
        $avatar = $name ? strtoupper(function_exists('mb_substr') ? mb_substr($name,0,1) : substr($name,0,1)) : '';
        echo '<div class="nt-header-actions"><a class="nt-account' . ($account_page ? ' nt-account-active' : '') . '" href="' . esc_url($account) . '" ' . ($account_page ? 'aria-current="page"' : '') . '>' . ($account_page && $name ? '<span class="nt-account-caption"><small>Minha conta</small><span data-nt-account-name>' . esc_html($name) . '</span></span><span class="nt-account-avatar" data-nt-account-avatar>' . esc_html($avatar) . '</span>' : self::icon('user') . '<span>Minha conta</span>') . '</a><a class="nt-cart-link" href="' . esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : self::url('cart')) . '" aria-label="Carrinho, ' . (int) $count . ' itens">' . self::icon('bag') . ($count ? '<span>' . (int) $count . '</span>' : '') . '</a></div></div></header>';
    }

    public static function footer(): void
    {
        echo '<footer class="nt-footer"><div class="nt-wrap nt-footer-top"><div><a class="nt-wordmark" href="' . esc_url(self::url()) . '"><span class="nt-symbol">' . self::icon() . '</span>' . esc_html(self::brand()) . '<span class="nt-wordmark-dot">.</span></a><p>Clareza sobre você.<br>Direção para crescer.</p></div><div><span>Seu próximo passo</span><a href="' . esc_url(self::url('shop')) . '">Explorar testes</a><a href="' . esc_url(Plugin::url()) . '">Meus testes e resultados</a><a href="' . esc_url(Plugin::url(['pmp_view'=>'packages'])) . '">Pacotes e convites</a></div><div><span>Conheça a Nortume</span><a href="' . esc_url(self::url() . '#sobre') . '">Nossa proposta</a><a href="' . esc_url(self::url('launch')) . '">Lançamento</a><a href="' . esc_url(self::url() . '#perguntas') . '">Perguntas frequentes</a></div></div><div class="nt-wrap nt-footer-bottom"><span>© ' . esc_html(wp_date('Y')) . ' ' . esc_html(self::brand()) . ' · Perfis e desenvolvimento humano</span><div>';
        if ($privacy = get_privacy_policy_url()) echo '<a href="' . esc_url($privacy) . '">Privacidade</a>';
        if (function_exists('wc_get_page_permalink') && wc_get_page_id('terms') > 0) echo '<a href="' . esc_url(wc_get_page_permalink('terms')) . '">Termos de compra</a>';
        echo '</div></div></footer>';
    }

    public static function render(string $view): string
    {
        ob_start();
        echo '<div class="nt-content nt-content-' . esc_attr($view) . '">';
        $file = PMP_PATH . 'templates/site-' . $view . '.php';
        if (in_array($view, ['home','shop','launch'], true) && is_file($file)) include $file;
        echo '</div>';
        return ob_get_clean();
    }

    public static function module_name(string $key): string
    {
        return ['comportamento'=>'Perfil','lideranca'=>'Liderança','carreira'=>'Carreira','servico'=>'Serviço'][$key] ?? (Modules::get($key)['title'] ?? $key);
    }

    public static function products(string $module = ''): array
    {
        if (!function_exists('wc_get_product')) return [];
        $ids = get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids','orderby'=>['menu_order'=>'ASC','title'=>'ASC'],'meta_query'=>[['key'=>'_pmp_module','value'=>$module ? [$module] : array_keys(Modules::all()),'compare'=>'IN']]]);
        $products = [];
        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if ($product && $product->is_visible() && Commerce::config($product)) $products[] = $product;
        }
        $order = array_flip(array_keys(Modules::all()));
        usort($products, static function ($a, $b) use ($order) {
            $ca = Commerce::config($a); $cb = Commerce::config($b);
            return (($order[$ca['module']] ?? 999) <=> ($order[$cb['module']] ?? 999)) ?: ($ca['size'] <=> $cb['size']) ?: ($a->get_id() <=> $b->get_id());
        });
        return $products;
    }

    public static function button(string $label, string $url, string $class = ''): string
    {
        return '<a class="nt-button ' . esc_attr($class) . '" href="' . esc_url($url) . '">' . esc_html($label) . self::icon('arrow') . '</a>';
    }

    public static function module_cards(): void
    {
        $icons = ['comportamento'=>'north','lideranca'=>'team','carreira'=>'spark','servico'=>'heart'];
        $subtitles = ['comportamento'=>'Seu jeito de agir e se comunicar.','lideranca'=>'Sua forma de construir com pessoas.','carreira'=>'Seus interesses, novos caminhos.','servico'=>'Sua participação na comunidade.'];
        echo '<div class="nt-module-grid">';
        $index = 0;
        foreach (Modules::all() as $key=>$module) {
            echo '<a class="nt-module-card nt-module-' . esc_attr($key) . '" href="' . esc_url(self::url('shop', ['nmodule'=>$key])) . '"><div class="nt-module-card-top"><span>' . self::icon($icons[$key] ?? 'north') . '</span><small>0' . ++$index . '</small></div><p class="nt-kicker">' . esc_html(self::brand()) . '</p><h3>' . esc_html(self::module_name($key)) . '</h3><p>' . esc_html($subtitles[$key] ?? $module['description']) . '</p><div class="nt-module-card-bottom"><span>' . count($module['questions']) . ' perguntas · ' . esc_html($module['minutes']) . ' min</span>' . self::icon('arrow') . '</div></a>';
        }
        echo '</div>';
    }

    public static function product_card($product): void
    {
        $config = Commerce::config($product); $module = Modules::get($config['module']);
        echo '<article class="nt-shop-card nt-module-' . esc_attr($config['module']) . '"><a class="nt-shop-art" href="' . esc_url($product->get_permalink()) . '" aria-label="Ver ' . esc_attr($product->get_name()) . '"><span class="nt-product-edition">' . esc_html(self::brand()) . ' / ' . esc_html(self::module_name($config['module'])) . '</span><span class="nt-shop-symbol">' . self::icon(['lideranca'=>'team','carreira'=>'spark','servico'=>'heart'][$config['module']] ?? 'north') . '</span><span class="nt-pack-badge">' . ($product->is_type('variable') ? 'Escolha sua opção' : $config['size'] . ($config['size'] === 1 ? ' aplicação' : ' aplicações')) . '</span></a><div class="nt-shop-card-content"><p class="nt-kicker">' . esc_html($module['title']) . '</p><h2><a href="' . esc_url($product->get_permalink()) . '">' . esc_html($product->get_name()) . '</a></h2><p>' . esc_html($module['description']) . '</p><div class="nt-shop-facts"><span>' . count($module['questions']) . ' perguntas por pessoa</span><span>Relatório individual completo</span></div><div class="nt-shop-price">' . ($product->get_price() !== '' ? wp_kses_post($product->get_price_html()) : '<span>Disponibilidade em preparação</span>') . '</div>';
        if (!$product->is_type('variable') && $product->get_price() !== '' && $config['size'] > 1) echo '<p class="nt-per-application">' . wp_kses_post(wc_price(wc_get_price_to_display($product) / $config['size'])) . ' por aplicação neste pacote</p>';
        echo self::button('Conhecer este teste', $product->get_permalink(), 'nt-button-outline') . '</div></article>';
    }

    /** Demonstração com dados sintéticos, nunca exibida como resultado de alguém. */
    public static function report_demo(bool $compact = false): void
    {
        echo '<div class="nt-demo' . ($compact ? ' nt-demo-compact' : '') . '"><div class="nt-demo-bar"><span><i></i><i></i><i></i></span><small>Um exemplo do seu espaço Nortume</small></div><div class="nt-demo-body"><div class="nt-demo-heading"><div><span class="nt-kicker">NORTUME / PERFIL</span><h3>Suas respostas,<br>em perspectiva.</h3></div><span class="nt-demo-icon">' . self::icon() . '</span></div><div class="nt-demo-chart"><svg viewBox="0 0 260 220" role="img" aria-label="Radar ilustrativo de quatro dimensões"><g fill="none" stroke="#e4e0ef"><path d="m130 34 76 76-76 76-76-76Z"/><path d="m130 53 57 57-57 57-57-57Z"/><path d="m130 72 38 38-38 38-38-38Z"/><path d="m130 91 19 19-19 19-19-19Z"/><path d="M130 34v152M54 110h152"/></g><path d="m130 45 55 65-55 48-65-48Z" fill="#9985e1" fill-opacity=".18" stroke="#8470d6" stroke-width="2"/><g fill="#8470d6"><circle cx="130" cy="45" r="4"/><circle cx="185" cy="110" r="4"/><circle cx="130" cy="158" r="4"/><circle cx="65" cy="110" r="4"/></g><g fill="#6d7183" font-size="10" font-family="system-ui,sans-serif"><text x="130" y="18" text-anchor="middle">Iniciativa</text><text x="213" y="114">Conexão</text><text x="130" y="207" text-anchor="middle">Constância</text><text x="47" y="114" text-anchor="end">Critério</text></g></svg><div class="nt-demo-bars">';
        foreach ([['Iniciativa','4,3',86],['Conexão','3,6',72],['Constância','3,2',64],['Critério','4,3',86]] as [$label,$value,$width]) echo '<div><span>' . $label . '<b>' . $value . '<small>/5</small></b></span><i><em style="width:' . $width . '%"></em></i></div>';
        echo '</div></div><div class="nt-demo-reading"><span>' . self::icon('spark') . ' Perfil em palavras</span><p>Neste exemplo, iniciativa e critério compartilham a maior média. A leitura preserva o empate e propõe observar como agir e conferir informações aparecem no cotidiano.</p></div><p class="nt-demo-caption">Exemplo ilustrativo, com dados fictícios. Seu resultado será baseado nas suas respostas.</p></div></div>';
    }

    public static function steps(): void
    {
        echo '<div class="nt-steps"><article><span>01</span><h3>Escolha seu objetivo</h3><p>Encontre o módulo e a quantidade de aplicações. Você pode comprar para si ou distribuir os créditos desse módulo.</p></article><article><span>02</span><h3>Responda ou convide</h3><p>Após a confirmação do pagamento, use sua conta para iniciar o teste ou enviar convites individuais por e-mail.</p></article><article><span>03</span><h3>Conecte descobertas e ações</h3><p>Consulte gráficos, resumo detalhado e experiências sugeridas. Retome seu relatório sempre que precisar conversar sobre os próximos passos.</p></article></div>';
    }

    public static function faq(): void
    {
        $items = [
            'Posso comprar para mim e para outras pessoas?' => 'Sim. As aplicações ficam em sua conta, separadas por módulo. Você pode usar um crédito para si ou enviar um convite para outra pessoa. Quem recebe o convite precisa se cadastrar com o mesmo e-mail e aceitar a aplicação.',
            'Como funcionam os pacotes?' => 'Cada produto informa o módulo e o número de aplicações por unidade. Um crédito corresponde a uma aplicação daquele módulo para uma pessoa. Os créditos de um módulo não são trocados automaticamente por créditos de outro.',
            'Quem pode ver o resultado de uma aplicação por convite?' => 'O participante e quem comprou aquela aplicação podem consultar seu relatório final. Esse compartilhamento é informado antes do aceite. O comprador não recebe as respostas individuais nem relatórios de outros testes do participante.',
            'O que recebo no relatório?' => 'Um mapa visual das médias por dimensão, resumo detalhado do conjunto de respostas, leituras específicas, pontos de atenção, perguntas para reflexão e sugestões de próximos passos. Você também pode imprimir ou salvar em PDF pelo navegador.',
            'Os testes são aplicações oficiais de DISC ou PEAKS?' => 'Os questionários são autorais e estão em versão piloto, com referências conceituais informadas em cada módulo. Não são aplicações oficiais de DISC ou PEAKS e não herdam sua validação. As médias não são diagnóstico, percentil, medida de aptidão ou previsão de desempenho.',
            'Quando os testes são liberados?' => 'As aplicações são liberadas após a confirmação do pagamento pelo WooCommerce. A disponibilidade depende do status do pedido e do método de pagamento escolhido. Você poderá acompanhá-las na área de testes e pacotes.',
            'Posso responder pelo celular e continuar depois?' => 'Sim. A interface foi pensada para o celular. O progresso salvo fica na sua conta, e você pode retomar a aplicação pelo mesmo acesso. Espere a confirmação de salvamento antes de fechar o navegador.',
        ];
        echo '<div class="nt-faq">';
        foreach ($items as $question=>$answer) echo '<details><summary>' . esc_html($question) . '<span>+</span></summary><p>' . esc_html($answer) . '</p></details>';
        echo '</div>';
    }

    private static function current_product()
    {
        global $product;
        return self::route() === 'product' && $product && Commerce::config($product) ? $product : null;
    }

    public static function product_art(): void
    {
        $product = self::current_product();
        if (!$product || $product->get_image_id()) return;
        $config = Commerce::config($product);
        echo '<div class="nt-product-art nt-module-' . esc_attr($config['module']) . '"><p class="nt-kicker">' . esc_html(self::brand()) . ' / ' . esc_html(self::module_name($config['module'])) . '</p><h2>Um olhar mais claro.<br>Um próximo passo.</h2>';
        self::report_demo(true);
        echo '<div class="nt-product-art-bottom">Perfis e desenvolvimento humano ' . self::icon() . '</div></div>';
    }

    public static function product_eyebrow(): void
    {
        if (!$product = self::current_product()) return;
        echo '<p class="nt-kicker">' . esc_html(self::brand()) . ' / ' . esc_html(self::module_name(Commerce::config($product)['module'])) . '</p>';
    }

    public static function product_includes(): void
    {
        if (!$product = self::current_product()) return;
        $config = Commerce::config($product); $module = Modules::get($config['module']);
        echo '<div class="nt-product-includes"><p>' . ($product->is_type('variable') ? 'Módulo, aplicações e perguntas conforme a opção selecionada acima.' : '<strong>' . $config['size'] . ($config['size'] === 1 ? ' aplicação individual' : ' aplicações individuais') . '</strong> deste módulo. Para você ou para convidar pessoas.') . '</p><ul><li>' . self::icon('check') . 'Gráficos, resumo detalhado e próximos passos</li><li>' . self::icon('check') . 'Acesso ao relatório na conta e impressão em PDF</li><li>' . self::icon('check') . 'Convites individuais para aplicações a outras pessoas</li></ul><small>Liberação após a confirmação do pagamento. Nas aplicações por convite, participante e comprador acessam o relatório final.</small></div>';
    }

    public static function product_facts(): void
    {
        if (!$product = self::current_product()) return;
        $config = Commerce::config($product); $module = Modules::get($config['module']);
        echo '<p class="nt-selection-facts">' . ($product->is_type('variable') ? 'Selecione uma opção para conferir o módulo e as aplicações.' : '<strong>' . (int) $config['size'] . ($config['size'] === 1 ? ' aplicação' : ' aplicações') . '</strong> · ' . count($module['questions']) . ' perguntas por pessoa · ' . esc_html($module['minutes']) . ' min estimados') . '</p>';
    }

    public static function product_packs(): void
    {
        if (!$product = self::current_product()) return;
        $config = Commerce::config($product); $products = self::products($config['module']);
        if (count($products) < 2) return;
        echo '<nav class="nt-other-packs" aria-label="Outras quantidades deste módulo"><span>Outras opções deste módulo</span><div>';
        foreach ($products as $other) {
            if ($other->get_id() === $product->get_id()) continue;
            $other_config = Commerce::config($other);
            echo '<a href="' . esc_url($other->get_permalink()) . '">' . esc_html($other->is_type('variable') ? $other->get_name() : $other_config['size'] . ($other_config['size'] === 1 ? ' aplicação' : ' aplicações')) . '</a>';
        }
        echo '</div></nav>';
    }

    public static function product_details(): void
    {
        if (!$product = self::current_product()) return;
        $config = Commerce::config($product); $module = Modules::get($config['module']);
        echo '<section class="nt-product-detail-section"><div class="nt-section-heading"><p class="nt-kicker">O QUE VOCÊ VAI EXPLORAR</p><h2>Uma leitura que conecta<br>respostas e contexto.</h2><p>' . esc_html($module['description']) . '</p></div><div class="nt-dimension-list">';
        foreach ($module['dimensions'] as $dimension) echo '<article><span>' . self::icon('check') . '</span><div><h3>' . esc_html($dimension['label']) . '</h3><p>' . esc_html($dimension['description']) . '</p></div></article>';
        echo '</div></section><section class="nt-product-detail-section nt-product-delivery"><div><p class="nt-kicker">SEU RELATÓRIO INCLUI</p><h2>Mais do que um gráfico.<br>Uma conversa para começar.</h2><p>Síntese do perfil, leitura de cada dimensão, pontos de atenção, perguntas para reflexão e um plano de experiências em 7, 14 e 30 dias.</p></div><div><h3>Para usar com consciência</h3><p>' . esc_html($module['reference']) . '</p><p>Os resultados descrevem respostas e interesses. Não determinam profissão, aptidão, desempenho ou função ministerial. Relacione a leitura às experiências de cada pessoa.</p></div></section>';
    }
}
