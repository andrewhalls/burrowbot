<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * The test suite runs on SQLite, which silently accepts identifiers of any
 * length AND doesn't even store foreign-key constraint names (SQLite's
 * grammar omits the `constraint "name"` clause entirely) - so neither
 * SQLite's tolerance nor introspecting SQLite's live schema catches an
 * overlong MySQL identifier. Production runs on MySQL, which rejects any
 * index/constraint name over 64 characters.
 *
 * Two incidents so far, both unnamed constraints on long table/column
 * combinations: `event_role_signups`'s 3-column index (72 chars) and
 * `standard_giveaway_entries`'s FK to `standard_giveaway_occurrence_id`
 * (68 chars). Give every compound index/unique an explicit short name via
 * `$table->unique([...], 'short_name')`, and every foreign key on a long
 * column an explicit name via
 * `$table->foreignId('col')->constrained(indexName: 'short_name')`
 * (or `$table->foreign('col', 'short_name')->references(...)->on(...)`).
 *
 * To actually catch this, every migration's `up()` is run in --pretend mode
 * against a fake `mysql`-driver connection, and the MySQL-flavoured
 * compiled SQL text is inspected directly - that's the only way to see the
 * identifier MySQL would actually reject. pretend() never opens a real
 * socket (it short-circuits before touching the PDO), so no MySQL server
 * needs to be running for this test.
 */
it('keeps every index and foreign key name within MySQLs 64-character identifier limit', function () {
    Config::set('database.connections.mysql_probe', [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'schema_identifier_length_probe',
        'username' => 'probe',
        'password' => 'probe',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ]);

    $files = glob(database_path('migrations/*.php'));
    sort($files);

    $queries = DB::connection('mysql_probe')->pretend(function () use ($files) {
        Config::set('database.default', 'mysql_probe');

        foreach ($files as $file) {
            (require $file)->up();
        }
    });

    Config::set('database.default', 'sqlite');

    $sql = collect($queries)->pluck('query')->implode("\n");

    preg_match_all('/`([a-z0-9_]{65,})`/i', $sql, $matches);

    $tooLong = collect($matches[1])
        ->unique()
        ->map(fn (string $identifier) => "{$identifier} (".strlen($identifier).' chars)')
        ->values()
        ->all();

    expect($tooLong)->toBe([]);
});
