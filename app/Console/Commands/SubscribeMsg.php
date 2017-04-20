<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

//Redis，Laravel框架关键技术解析十一，发布订阅消息
class SubscribeMsg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Sub:Msg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Redis Subscribe Command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //发布、订阅消息
		Redis::subscribe(['redis-msg'],function($message){
			echo $message;
		});
    }
}
