<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Updater
{
    private const REPO = 'paulommaeda/testes';
    private const CACHE = 'pmp_github_release';

    public static function register(): void
    {
        add_filter('update_plugins_github.com',[self::class,'update'],10,4);
        add_filter('plugins_api',[self::class,'information'],10,3);
        add_action('upgrader_process_complete',static function () { delete_transient(self::CACHE); });
        add_filter('plugin_action_links_' . plugin_basename(PMP_FILE),static function ($links) {
            array_unshift($links,'<a href="' . esc_url(admin_url('admin.php?page=maeda-perfis')) . '">Configurar</a>');
            return $links;
        });
    }

    public static function release(): ?array
    {
        $cached = get_transient(self::CACHE);
        if ($cached !== false) return is_array($cached) && !empty($cached['version']) ? $cached : null;
        $response = wp_remote_get('https://api.github.com/repos/' . self::REPO . '/releases/latest',[
            'timeout'=>10,'redirection'=>2,'headers'=>['Accept'=>'application/vnd.github+json','User-Agent'=>'Nortume/' . PMP_VERSION],
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            set_transient(self::CACHE,[],HOUR_IN_SECONDS);
            return null;
        }
        $data = json_decode(wp_remote_retrieve_body($response),true);
        $version = ltrim((string) ($data['tag_name'] ?? ''),'v');
        if (!empty($data['prerelease']) || !empty($data['draft']) || !preg_match('/^\d+\.\d+\.\d+(?:\.[0-9]+)?$/',$version)) {
            set_transient(self::CACHE,[],HOUR_IN_SECONDS);
            return null;
        }
        $package = '';
        $manifest_url = '';
        foreach (($data['assets'] ?? []) as $asset) {
            $url = (string) ($asset['browser_download_url'] ?? '');
            $path = (string) wp_parse_url($url,PHP_URL_PATH);
            if (wp_parse_url($url,PHP_URL_SCHEME) !== 'https' || wp_parse_url($url,PHP_URL_HOST) !== 'github.com' || strpos($path,'/' . self::REPO . '/releases/download/') !== 0) continue;
            if (preg_match('/^maeda-perfis(?:-[0-9.]+)?\.zip$/',(string) ($asset['name'] ?? ''))) $package = $url;
            if (($asset['name'] ?? '') === 'update.json') $manifest_url = $url;
        }
        if (!$package) {
            set_transient(self::CACHE,[],HOUR_IN_SECONDS);
            return null;
        }
        $manifest = [];
        if ($manifest_url) {
            $file = wp_remote_get($manifest_url,['timeout'=>8,'limit_response_size'=>16000]);
            if (!is_wp_error($file) && wp_remote_retrieve_response_code($file) === 200) $manifest = json_decode(wp_remote_retrieve_body($file),true) ?: [];
        }
        $release = ['version'=>$version,'package'=>$package,'url'=>'https://github.com/' . self::REPO,
            'requires'=>sanitize_text_field($manifest['requires'] ?? '6.5'),
            'requires_php'=>sanitize_text_field($manifest['requires_php'] ?? '8.1'),
            'tested'=>sanitize_text_field($manifest['tested'] ?? ''),
            'notes'=>sanitize_textarea_field($data['body'] ?? ''),'updated'=>sanitize_text_field($data['published_at'] ?? '')];
        set_transient(self::CACHE,$release,6*HOUR_IN_SECONDS);
        return $release;
    }

    public static function update($update,array $plugin_data,string $plugin_file,array $locales)
    {
        if ($plugin_file !== plugin_basename(PMP_FILE)) return $update;
        $release = self::release();
        if (!$release || !version_compare($release['version'],$plugin_data['Version'] ?? PMP_VERSION,'>')) return $update;
        return ['id'=>'https://github.com/' . self::REPO,'slug'=>'maeda-perfis','plugin'=>plugin_basename(PMP_FILE),
            'version'=>$release['version'],'url'=>$release['url'],'package'=>$release['package'],
            'requires'=>$release['requires'],'requires_php'=>$release['requires_php'],'tested'=>$release['tested']];
    }

    public static function information($result,string $action,$args)
    {
        if ($action !== 'plugin_information' || ($args->slug ?? '') !== 'maeda-perfis') return $result;
        $release = self::release();
        return (object) ['name'=>'Nortume','slug'=>'maeda-perfis','version'=>$release['version'] ?? PMP_VERSION,
            'author'=>'Paulo Maeda','homepage'=>'https://github.com/' . self::REPO,
            'requires'=>$release['requires'] ?? '6.5','requires_php'=>$release['requires_php'] ?? '8.1',
            'download_link'=>$release['package'] ?? '',
            'sections'=>['description'=>'Testes de perfil, pacotes por módulo, convites individuais e dashboards integrados ao WooCommerce.',
                'changelog'=>nl2br(esc_html($release['notes'] ?? 'Primeira versão do plugin.'))]];
    }
}
