<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

#[Signature('app:export-mysql-schema {--path= : Output SQL path (defaults to database/schema/pims_mysql_schema.sql)} {--zip=1 : Also create a zip file (1 or 0)}')]
#[Description('Export the current MySQL schema (DDL only) to a .sql file')]
class ExportMysqlSchema extends Command
{
    public function handle(): int
    {
        $path = (string) ($this->option('path') ?: base_path('database/schema/pims_mysql_schema.sql'));
        $zip = (string) $this->option('zip') === '1';

        File::ensureDirectoryExists(dirname($path));

        $db = DB::connection();
        $dbName = (string) ($db->getDatabaseName() ?? '');

        $tables = collect($db->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'))
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

        sort($tables);

        $out = [];
        $out[] = 'SET NAMES utf8mb4;';
        $out[] = 'SET FOREIGN_KEY_CHECKS=0;';
        if ($dbName !== '') {
            $out[] = 'USE `'.$this->escapeIdent($dbName).'`;';
        }
        $out[] = '';

        foreach ($tables as $table) {
            $create = $db->select('SHOW CREATE TABLE `'.$this->escapeIdent($table).'`');
            $createSql = '';
            if (isset($create[0])) {
                $row = (array) $create[0];
                foreach ($row as $k => $v) {
                    if (stripos((string) $k, 'Create Table') !== false) {
                        $createSql = (string) $v;
                        break;
                    }
                }
            }
            if ($createSql === '') {
                continue;
            }

            $out[] = 'DROP TABLE IF EXISTS `'.$this->escapeIdent($table).'`;';
            $out[] = $createSql.';';
            $out[] = '';
        }

        $out[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $out[] = '';

        File::put($path, implode(PHP_EOL, $out));
        $this->info('Schema exported to: '.$path);

        if ($zip) {
            $zipPath = preg_replace('/\\.sql$/i', '.zip', $path) ?: ($path.'.zip');
            if (! class_exists(\ZipArchive::class)) {
                $this->warn('ZipArchive not available; skipping zip creation.');
                return self::SUCCESS;
            }

            $za = new \ZipArchive();
            $res = $za->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            if ($res !== true) {
                $this->warn('Unable to create zip file: '.$zipPath);
                return self::SUCCESS;
            }

            $za->addFile($path, basename($path));
            $za->close();

            $this->info('Schema zip created: '.$zipPath);
        }

        return self::SUCCESS;
    }

    private function escapeIdent(string $ident): string
    {
        return str_replace('`', '``', $ident);
    }
}

