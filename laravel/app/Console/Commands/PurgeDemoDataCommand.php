<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class PurgeDemoDataCommand extends Command
{
    protected $signature = 'demo:purge';

    protected $description = 'Elimina los datos de demostración insertados por DemoDataSeeder.';

    public function handle(): int
    {
        DemoDataSeeder::purge();

        $this->info('Datos demo eliminados.');

        return self::SUCCESS;
    }
}
