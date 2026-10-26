<?php
namespace Maeda\Perfis;

defined('ABSPATH') || exit;

final class Database
{
    public static function table(string $name): string
    {
        global $wpdb;
        $allowed = ['credits', 'invites', 'attempts', 'events'];
        if (!in_array($name, $allowed, true)) {
            throw new \InvalidArgumentException('Tabela inválida.');
        }
        return $wpdb->prefix . 'pmp_' . $name;
    }

    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $credits = self::table('credits');
        $invites = self::table('invites');
        $attempts = self::table('attempts');
        $events = self::table('events');
        dbDelta("CREATE TABLE $credits (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            buyer_id bigint(20) unsigned NOT NULL,
            module_key varchar(64) NOT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            order_item_id bigint(20) unsigned DEFAULT NULL,
            unit_no int unsigned NOT NULL DEFAULT 1,
            status varchar(24) NOT NULL DEFAULT 'available',
            previous_status varchar(24) DEFAULT NULL,
            assignee_id bigint(20) unsigned NOT NULL DEFAULT 0,
            attempt_id bigint(20) unsigned NOT NULL DEFAULT 0,
            revoked_reason varchar(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY order_unit (order_item_id,unit_no),
            KEY buyer_module (buyer_id,module_key,status),
            KEY assignee (assignee_id),
            KEY order_id (order_id)
        ) ENGINE=InnoDB $charset;");
        dbDelta("CREATE TABLE $invites (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            credit_id bigint(20) unsigned NOT NULL,
            buyer_id bigint(20) unsigned NOT NULL,
            email varchar(254) NOT NULL,
            token_hash char(64) NOT NULL,
            status varchar(24) NOT NULL DEFAULT 'pending',
            expires_at datetime NOT NULL,
            accepted_by bigint(20) unsigned NOT NULL DEFAULT 0,
            accepted_at datetime DEFAULT NULL,
            last_sent_at datetime DEFAULT NULL,
            mail_status varchar(16) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY credit_id (credit_id),
            UNIQUE KEY token_hash (token_hash),
            KEY buyer_status (buyer_id,status),
            KEY email_status (email,status),
            KEY expiration (status,expires_at)
        ) ENGINE=InnoDB $charset;");
        dbDelta("CREATE TABLE $attempts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            credit_id bigint(20) unsigned NOT NULL,
            buyer_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            module_key varchar(64) NOT NULL,
            module_version varchar(32) NOT NULL,
            snapshot longtext NOT NULL,
            status varchar(24) NOT NULL DEFAULT 'assigned',
            answers longtext NOT NULL,
            result longtext NOT NULL,
            revision int unsigned NOT NULL DEFAULT 0,
            sharing_accepted_at datetime DEFAULT NULL,
            sharing_disclosure text NOT NULL,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY credit_id (credit_id),
            KEY participant_status (user_id,status),
            KEY buyer_status (buyer_id,status)
        ) ENGINE=InnoDB $charset;");
        dbDelta("CREATE TABLE $events (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
            event_key varchar(64) NOT NULL,
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            metadata text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY actor_event (actor_id,event_key),
            KEY object_id (object_id)
        ) ENGINE=InnoDB $charset;");
        update_option('pmp_db_version', PMP_VERSION, false);
    }

    public static function transaction(callable $callback)
    {
        global $wpdb;
        $began = $wpdb->query('START TRANSACTION');
        if ($began === false) {
            return Plugin::error('database', 'Não foi possível iniciar a operação. Tente novamente.', 500);
        }
        try {
            $result = $callback();
            if (is_wp_error($result)) {
                $wpdb->query('ROLLBACK');
                return $result;
            }
            if ($wpdb->query('COMMIT') === false) {
                $wpdb->query('ROLLBACK');
                return Plugin::error('database', 'A operação não pôde ser confirmada.', 500);
            }
            return $result;
        } catch (\Throwable $error) {
            $wpdb->query('ROLLBACK');
            error_log('Maeda Perfis: operação interrompida em ' . get_class($error));
            return Plugin::error('database', 'Não foi possível concluir a operação. Tente novamente.', 500);
        }
    }

    public static function audit(string $event, int $id, array $metadata = [], ?int $actor = null): void
    {
        global $wpdb;
        $wpdb->insert(self::table('events'), [
            'actor_id' => $actor ?? get_current_user_id(),
            'event_key' => sanitize_key($event), 'object_id' => $id,
            'metadata' => wp_json_encode($metadata), 'created_at' => Plugin::now(),
        ]);
    }
}
