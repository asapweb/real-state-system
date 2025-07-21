<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestUVACommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indices:test-uva';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test command for UVA';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('✅ Test UVA command works!');
        return 0;
    }
}
