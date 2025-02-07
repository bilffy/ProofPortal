<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Schema;

class UpdateDatetimeToAEST extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-datetime-to-aest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all datetime data in the database to AEST timezone';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = env('DB_DATABASE');
        $datetimeColumns = [];

        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_$databaseName"};
            $columns = Schema::getColumnListing($tableName);

            foreach ($columns as $column) {
                $type = DB::getSchemaBuilder()->getColumnType($tableName, $column);

                if (in_array($type, ['datetime', 'timestamp'])) {
                    $datetimeColumns[$tableName][] = $column;
                }
            }
        }

        foreach ($datetimeColumns as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)->update([
                    $column => DB::raw("CONVERT_TZ($column, 'UTC', 'Australia/Sydney')")
                ]);
            }
        }

        $this->info('All datetime data has been updated from UTC to AEST timezone.');
    }
}
