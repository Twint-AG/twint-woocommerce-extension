<?php

namespace Twint\Woo\Abstract\ServiceProvider;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    const DATABASE_SERVICE_PROVIDER = 'databaseServiceProvider';

    public function __construct($args = [])
    {
        parent::__construct($args);
    }

    protected function boot(): DatabaseServiceProvider
    {
        return $this;
    }

    protected function load(): DatabaseServiceProvider
    {
        $this->registerToServiceContainer(self::DATABASE_SERVICE_PROVIDER, $this);
        return $this;
    }

    public static function GET_INSTANCE()
    {
        $instance = self::getServiceByName(self::DATABASE_SERVICE_PROVIDER);

        if ($instance === null) {
            $instance = self::INSTANCE();
        }

        return $instance;
    }

    public static function INSTANCE($args = []): DatabaseServiceProvider
    {
        return (new self($args))
            ->boot()
            ->load();
    }

    /**
     * @param string $tableName
     * @return bool
     */
    public function checkSettingsTableExist(string $tableName): bool
    {
        global $wpdb;
        global $table_prefix;
        return $wpdb->query("SHOW TABLES LIKE '" . $table_prefix . $tableName . "';");
    }

    public function createTwintTransactionsLogTable(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        global $table_prefix;
        $sql = "CREATE TABLE IF NOT EXISTS " . $table_prefix . "twint_transactions_log (
            record_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id INT UNSIGNED NOT NULL,
			transaction_id INT UNSIGNED NOT NULL,
			order_status varchar(191) NOT NULL default '',
			request longtext NOT NULL,
			response longtext NOT NULL,
			soap_request longtext NOT NULL,
			soap_resquest longtext NOT NULL,
			soap_response longtext NOT NULL,
			exception_text longtext NOT NULL,
			created_at INT(11) UNSIGNED DEFAULT '0',
			updated_at INT(11) UNSIGNED DEFAULT '0',
			PRIMARY KEY (record_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
