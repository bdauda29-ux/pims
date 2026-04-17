<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

#[Signature('app:migrate-sqlite-to-mysql {--sqlite= : Path to the SQLite database file (defaults to database/database.sqlite)} {--chunk=500 : Insert chunk size} {--truncate=1 : Truncate MySQL tables before import (1 or 0)}')]
#[Description('Migrate data from SQLite (legacy) into the current MySQL database connection')]
class MigrateSqliteToMysql extends Command
{
    private const DEFAULT_ORDER = [
        'states',
        'lgas',
        'formations',
        'directorates',
        'offices',
        'users',
        'password_reset_tokens',
        'sessions',
        'formation_postings',
        'office_postings',
        'promotion_histories',
        'custom_fields',
        'custom_field_values',
        'role_permissions',
        'audit_logs',
        'user_notifications',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function handle(): int
    {
        $sqlitePath = (string) ($this->option('sqlite') ?: database_path('database.sqlite'));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $truncate = (string) $this->option('truncate') === '1';

        if (! is_file($sqlitePath)) {
            $this->error("SQLite file not found: {$sqlitePath}");
            return self::FAILURE;
        }

        config([
            'database.connections.sqlite_migrate' => [
                'driver' => 'sqlite',
                'database' => $sqlitePath,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        $sqlite = DB::connection('sqlite_migrate');
        $mysql = DB::connection();

        $sqliteTables = collect($sqlite->select("select name from sqlite_master where type='table' and name not like 'sqlite_%'"))
            ->map(fn ($r) => (string) ($r->name ?? ''))
            ->filter(fn ($t) => $t !== '' && $t !== 'migrations')
            ->values()
            ->all();

        $mysqlTables = collect($mysql->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'))
            ->map(function ($row) {
                $arr = (array) $row;
                foreach ($arr as $k => $v) {
                    if ($k !== 'Table_type') {
                        return (string) $v;
                    }
                }
                return '';
            })
            ->filter()
            ->values()
            ->all();

        $tablesToImport = array_values(array_intersect($sqliteTables, $mysqlTables));

        $ordered = [];
        foreach (self::DEFAULT_ORDER as $t) {
            if (in_array($t, $tablesToImport, true)) {
                $ordered[] = $t;
            }
        }
        foreach ($tablesToImport as $t) {
            if (! in_array($t, $ordered, true)) {
                $ordered[] = $t;
            }
        }

        $this->line('SQLite: '.$sqlitePath);
        $this->line('MySQL database: '.(string) ($mysql->getDatabaseName() ?? ''));
        $this->line('Tables: '.implode(', ', $ordered));

        $mysql->statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            if ($truncate) {
                foreach (array_reverse($ordered) as $table) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }
                    $mysql->table($table)->truncate();
                }
            }

            foreach ($ordered as $table) {
                $this->importTable($sqlite, $mysql, $table, $chunkSize);
            }
        } finally {
            $mysql->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('SQLite → MySQL migration completed.');
        return self::SUCCESS;
    }

    private function importTable($sqlite, $mysql, string $table, int $chunkSize): void
    {
        if (! Schema::hasTable($table)) {
            $this->warn("Skipping (missing in MySQL): {$table}");
            return;
        }

        $mysqlColumns = Schema::getColumnListing($table);
        if ($mysqlColumns === []) {
            $this->warn("Skipping (no columns): {$table}");
            return;
        }

        $sqliteColumns = collect($sqlite->select("pragma table_info('{$table}')"))
            ->map(fn ($r) => (string) ($r->name ?? ''))
            ->filter()
            ->values()
            ->all();

        $columns = array_values(array_intersect($mysqlColumns, $sqliteColumns));
        if ($columns === []) {
            $this->warn("Skipping (no matching columns): {$table}");
            return;
        }

        $count = (int) ($sqlite->table($table)->count() ?? 0);
        if ($count === 0) {
            $this->line("Imported {$table}: 0 rows");
            return;
        }

        $hasId = in_array('id', $columns, true);
        $query = $sqlite->table($table)->select($columns);
        if ($hasId) {
            $query->orderBy('id');
        }

        $inserted = 0;
        $query->chunk($chunkSize, function ($rows) use ($mysql, $table, &$inserted, $columns) {
            $payload = [];
            foreach ($rows as $row) {
                $arr = (array) $row;
                $item = [];
                foreach ($columns as $c) {
                    $item[$c] = $arr[$c] ?? null;
                }
                $payload[] = $item;
            }

            if ($payload !== []) {
                $mysql->table($table)->insert($payload);
                $inserted += count($payload);
            }
        });

        $this->line("Imported {$table}: {$inserted} rows");
    }
}

