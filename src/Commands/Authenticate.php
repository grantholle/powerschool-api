<?php


namespace GrantHolle\PowerSchool\Commands;


use GrantHolle\PowerSchool\Facades\PowerSchool;
use Illuminate\Console\Command;

class Authenticate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerschool:auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves and caches the authorization token from PowerSchool.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $request = PowerSchool::getRequest();

        $request->authenticate(true);

        $this->info('Auth token cached!');
    }
}
