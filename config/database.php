<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pg_master' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DBMASTER_HOST', '127.0.0.1'),
            'port' => env('DBMASTER_PORT', '5432'),
            'database' => env('DBMASTER_DATABASE', 'forge'),
            'username' => env('DBMASTER_USERNAME', 'forge'),
            'password' => env('DBMASTER_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
        'pg_global' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DBGLOBAL_HOST', '192.168.2.241'),
            'port' => env('DBGLOBAL_PORT', '5432'),
            'database' => env('DBGLOBAL_DATABASE', 'pro1'),
            'username' => env('DBGLOBAL_USERNAME', 'postgres'),
            'password' => env('DBGLOBAL_PASSWORD', 'p@ssw0rd'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
        'pos101_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS101_DB_HOST', '127.0.0.1'),
            'port' => env('POS101_DB_PORT', '5432'),
            'database' => env('POS101_DB_DATABASE', 'forge'),
            'username' => env('POS101_DB_USERNAME', 'forge'),
            'password' => env('POS101_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos102_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS102_DB_HOST', '127.0.0.1'),
            'port' => env('POS102_DB_PORT', '5432'),
            'database' => env('POS102_DB_DATABASE', 'forge'),
            'username' => env('POS102_DB_USERNAME', 'forge'),
            'password' => env('POS102_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos103_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS103_DB_HOST', '127.0.0.1'),
            'port' => env('POS103_DB_PORT', '5432'),
            'database' => env('POS103_DB_DATABASE', 'forge'),
            'username' => env('POS103_DB_USERNAME', 'forge'),
            'password' => env('POS103_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos104_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS104_DB_HOST', '127.0.0.1'),
            'port' => env('POS104_DB_PORT', '5432'),
            'database' => env('POS104_DB_DATABASE', 'forge'),
            'username' => env('POS104_DB_USERNAME', 'forge'),
            'password' => env('POS104_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos105_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS105_DB_HOST', '127.0.0.1'),
            'port' => env('POS105_DB_PORT', '5432'),
            'database' => env('POS105_DB_DATABASE', 'forge'),
            'username' => env('POS105_DB_USERNAME', 'forge'),
            'password' => env('POS105_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos106_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS106_DB_HOST', '127.0.0.1'),
            'port' => env('POS106_DB_PORT', '5432'),
            'database' => env('POS106_DB_DATABASE', 'forge'),
            'username' => env('POS106_DB_USERNAME', 'forge'),
            'password' => env('POS106_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos107_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS107_DB_HOST', '127.0.0.1'),
            'port' => env('POS107_DB_PORT', '5432'),
            'database' => env('POS107_DB_DATABASE', 'forge'),
            'username' => env('POS107_DB_USERNAME', 'forge'),
            'password' => env('POS107_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos108_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS108_DB_HOST', '127.0.0.1'),
            'port' => env('POS108_DB_PORT', '5432'),
            'database' => env('POS108_DB_DATABASE', 'forge'),
            'username' => env('POS108_DB_USERNAME', 'forge'),
            'password' => env('POS108_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

          'pos109_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS109_DB_HOST', '127.0.0.1'),
            'port' => env('POS109_DB_PORT', '5432'),
            'database' => env('POS109_DB_DATABASE', 'forge'),
            'username' => env('POS109_DB_USERNAME', 'forge'),
            'password' => env('POS109_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos110_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS110_DB_HOST', '127.0.0.1'),
            'port' => env('POS110_DB_PORT', '5432'),
            'database' => env('POS110_DB_DATABASE', 'forge'),
            'username' => env('POS110_DB_USERNAME', 'forge'),
            'password' => env('POS110_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos112_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS112_DB_HOST', '127.0.0.1'),
            'port' => env('POS112_DB_PORT', '5432'),
            'database' => env('POS112_DB_DATABASE', 'forge'),
            'username' => env('POS112_DB_USERNAME', 'forge'),
            'password' => env('POS112_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos113_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS113_DB_HOST', '127.0.0.1'),
            'port' => env('POS113_DB_PORT', '5432'),
            'database' => env('POS113_DB_DATABASE', 'forge'),
            'username' => env('POS113_DB_USERNAME', 'forge'),
            'password' => env('POS113_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos114_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS114_DB_HOST', '127.0.0.1'),
            'port' => env('POS114_DB_PORT', '5432'),
            'database' => env('POS114_DB_DATABASE', 'forge'),
            'username' => env('POS114_DB_USERNAME', 'forge'),
            'password' => env('POS114_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
            'pos115_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS115_DB_HOST', '127.0.0.1'),
            'port' => env('POS115_DB_PORT', '5432'),
            'database' => env('POS115_DB_DATABASE', 'forge'),
            'username' => env('POS115_DB_USERNAME', 'forge'),
            'password' => env('POS115_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
        'pos201_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS201_DB_HOST', '127.0.0.1'),
            'port' => env('POS201_DB_PORT', '5432'),
            'database' => env('POS201_DB_DATABASE', 'forge'),
            'username' => env('POS201_DB_USERNAME', 'forge'),
            'password' => env('POS201_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
        'pos202_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS202_DB_HOST', '127.0.0.1'),
            'port' => env('POS202_DB_PORT', '5432'),
            'database' => env('POS202_DB_DATABASE', 'forge'),
            'username' => env('POS202_DB_USERNAME', 'forge'),
            'password' => env('POS202_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos203_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS203_DB_HOST', '127.0.0.1'),
            'port' => env('POS203_DB_PORT', '5432'),
            'database' => env('POS203_DB_DATABASE', 'forge'),
            'username' => env('POS203_DB_USERNAME', 'forge'),
            'password' => env('POS203_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
        'pos205_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS205_DB_HOST', '127.0.0.1'),
            'port' => env('POS205_DB_PORT', '5432'),
            'database' => env('POS205_DB_DATABASE', 'forge'),
            'username' => env('POS205_DB_USERNAME', 'forge'),
            'password' => env('POS205_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
        'pos504_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS504_DB_HOST', '127.0.0.1'),
            'port' => env('POS504_DB_PORT', '5432'),
            'database' => env('POS504_DB_DATABASE', 'forge'),
            'username' => env('POS504_DB_USERNAME', 'forge'),
            'password' => env('POS504_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'pos505_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS505_DB_HOST', '127.0.0.1'),
            'port' => env('POS505_DB_PORT', '5432'),
            'database' => env('POS505_DB_DATABASE', 'forge'),
            'username' => env('POS505_DB_USERNAME', 'forge'),
            'password' => env('POS505_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
        'pos509_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS509_DB_HOST', '127.0.0.1'),
            'port' => env('POS509_DB_PORT', '5432'),
            'database' => env('POS509_DB_DATABASE', 'forge'),
            'username' => env('POS509_DB_USERNAME', 'forge'),
            'password' => env('POS509_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
        'pos510_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS510_DB_HOST', '127.0.0.1'),
            'port' => env('POS510_DB_PORT', '5432'),
            'database' => env('POS510_DB_DATABASE', 'forge'),
            'username' => env('POS510_DB_USERNAME', 'forge'),
            'password' => env('POS510_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],
        'pos511_pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('POS511_DB_HOST', '127.0.0.1'),
            'port' => env('POS511_DB_PORT', '5432'),
            'database' => env('POS511_DB_DATABASE', 'forge'),
            'username' => env('POS511_DB_USERNAME', 'forge'),
            'password' => env('POS511_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'cycan_scan' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('Cycan_Scan_DB_Host', '127.0.0.1'),
            'port' => env('Cycan_Scan_DB_Port', '5432'),
            'database' => env('Cycan_Scan_DB_Database', 'Mobile_Count_Test'),
            'username' => env('Cycan_Scan_DB_Username', 'postgres'),
            'password' => env('Cycan_Scan_DB_Password', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => env('DB_SSLMODE', 'disable'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
