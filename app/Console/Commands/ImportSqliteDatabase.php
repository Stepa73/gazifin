<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ImportSqliteDatabase extends Command
{
    protected $signature = 'db:import-sqlite
        {--path= : Cesta k původnímu souboru database.sqlite}
        {--truncate : Smazat existující data v cílových tabulkách před importem}
        {--chunk=500 : Počet řádků na dávku}';

    protected $description = 'Přenese data z původní SQLite databáze do aktuálního (výchozího) spojení.';

    /**
     * Pořadí respektuje cizí klíče — rodiče první.
     * Tabulky frameworku (cache, sessions, jobs, migrations) se nepřenášejí,
     * ty si cíl vytvoří sám a jejich obsah je jednorázový.
     */
    private const TABLES = [
        'users',
        'clients',
        'products',
        'invoices',
        'invoice_items',
    ];

    public function handle(): int
    {
        $path = $this->option('path') ?: database_path('database.sqlite');

        if (! is_file($path)) {
            $this->error("SQLite soubor nenalezen: {$path}");

            return self::FAILURE;
        }

        $target = DB::getDefaultConnection();

        if (realpath(Config::get("database.connections.{$target}.database")) === realpath($path)) {
            $this->error('Zdroj i cíl ukazují na stejný soubor. Nastavte DB_CONNECTION na cílovou databázi.');

            return self::FAILURE;
        }

        Config::set('database.connections.sqlite_import', [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        $source = DB::connection('sqlite_import');

        $this->line("Zdroj: {$path}");
        $this->line('Cíl:   '.$target.' ('.Config::get("database.connections.{$target}.driver").')');
        $this->newLine();

        $chunk = max(1, (int) $this->option('chunk'));
        $tables = array_values(array_filter(
            self::TABLES,
            fn (string $table) => Schema::connection('sqlite_import')->hasTable($table)
        ));

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Cílová tabulka {$table} neexistuje. Spusťte nejdřív: php artisan migrate --force");

                return self::FAILURE;
            }

            if (DB::table($table)->exists() && ! $this->option('truncate')) {
                $this->error("Cílová tabulka {$table} není prázdná. Použijte --truncate, nebo cíl vyčistěte ručně.");

                return self::FAILURE;
            }
        }

        try {
            $this->withoutForeignKeyChecks(function () use ($source, $tables, $chunk) {
                if ($this->option('truncate')) {
                    // DELETE místo TRUNCATE — TRUNCATE dělá v MySQL implicitní commit
                    // a rozbilo by atomicitu celého importu.
                    foreach (array_reverse($tables) as $table) {
                        DB::table($table)->delete();
                    }
                }

                foreach ($tables as $table) {
                    $columns = Schema::getColumnListing($table);
                    $total = $source->table($table)->count();
                    $copied = 0;

                    $source->table($table)->orderBy('id')->chunk($chunk, function ($rows) use ($table, $columns, &$copied) {
                        $payload = $rows->map(function ($row) use ($columns) {
                            // Sloupce, které v cíli neexistují, zahodíme — schéma je řízené migracemi.
                            return array_intersect_key((array) $row, array_flip($columns));
                        })->all();

                        DB::table($table)->insert($payload);
                        $copied += count($payload);
                    });

                    $this->line(sprintf('%-15s %d / %d', $table, $copied, $total));
                }
            });
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('Import selhal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Hotovo.');

        return self::SUCCESS;
    }

    private function withoutForeignKeyChecks(callable $callback): void
    {
        Schema::withoutForeignKeyConstraints(function () use ($callback) {
            DB::transaction($callback);
        });
    }
}
