<?php

namespace GrantHolle\PowerSchool\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Cache\Simple\FilesystemCache;
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
    protected $description = 'Removes existing authorization token cache.';

    /**
     * The cache object
     *
     * @var FilesystemCache
     */
    private $cache;

    public function __construct(FilesystemCache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->cache->deleteItem(Request::AUTH_TOKEN);
        $this->info('Auth token cache cleared!');
    }
}
