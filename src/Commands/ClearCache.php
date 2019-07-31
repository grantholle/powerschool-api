<?php

namespace GrantHolle\PowerSchool\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use GrantHolle\PowerSchool\Request;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerschool:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes existing authorization token from the cache.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Cache::forget(Request::AUTH_TOKEN);

        $this->info('Auth token cache cleared!');
    }
}
