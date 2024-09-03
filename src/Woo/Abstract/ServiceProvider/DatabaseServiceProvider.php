<?php

namespace Twint\Woo\Abstract\ServiceProvider;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    const DATABASE_SERVICE_PROVIDER = 'databaseServiceProvider';
    const DATABASE_TRIGGER_STATE_ERROR_CODE = '45000';

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

//    public function createTwintPairingTable(): void
//    {
////        $table = $table_prefix . 'twint_pairing';
////        $view = $table_prefix . 'twint_pairing_view';
////        $dropViewIfExists = "DROP VIEW IF EXISTS {$view};";
////        $createView = "CREATE VIEW {$view} AS
////            SELECT {$table}.*,
////            (UNIX_TIMESTAMP() - UNIX_TIMESTAMP({$table}.checked_at)) AS checked_ago
////        FROM {$table};";
//
//        $createTrigger = "
//            CREATE TRIGGER before_update_twint_pairing BEFORE UPDATE ON {$table} FOR EACH ROW BEGIN
//                DECLARE changed_columns INT;
//                IF OLD.version <> NEW.version THEN
//                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version conflict detected. Update aborted.';
//                END IF;
//                SET changed_columns = 0;
//                IF NEW.status <> OLD.status THEN
//                    SET changed_columns = changed_columns + 1;
//                END IF;
//                IF changed_columns > 0 THEN
//                    SET NEW.version = OLD.version + 1;
//                END IF
//            END;";
//
////        $wpdb->query($dropViewIfExists);
////        $wpdb->query($createView);
////        $res = mysqli_multi_query($wpdb->dbh, $createTrigger);
//    }
}
