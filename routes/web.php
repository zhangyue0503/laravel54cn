<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (\Illuminate\Http\Request $request) {
    dd();
	return view('welcome');
});

//请求示例，Laravel框架关键技术解析九
Route::post('/auth/register', 'AuthController@postRegister');
Route::get('/auth/form', 'AuthController@redirectForm');


//响应示例，Laravel框架关键技术解析九
Route::get('/welcome/index_response', 'WelcomeController@indexResponse');
Route::get('/welcome/index_response2', 'WelcomeController@indexResponse2');
Route::get('/welcome/index_redirect', 'WelcomeController@indexRedirect');
//查询构造器，Laravel框架关键技术解析十
Route::get('/welcome/index_ten', 'WelcomeController@indexTen');
Route::get('/welcome/index_ten1', 'WelcomeController@indexTen1');
//Redis，Laravel框架关键技术解析十一
Route::get('/welcome/index_eleven', 'WelcomeController@indexEleven');
Route::get('/welcome/index_eleven1', function () { //发布、订阅消息
	\Illuminate\Support\Facades\Redis::publish('redis-msg', 'visit welcom time=' . time() . "\n");
});
//Session，Laravel框架关键技术解析十二
Route::get('/welcome/index_twelve', 'WelcomeController@indexTwelve');
//消息队列，Laravel框架关键技术解析十三
Route::get('/welcome/index_thirteen', 'WelcomeController@indexThirteen');
Route::get('/welcome/index_thirteen1', 'WelcomeController@indexThirteen1');

//容器示例
Route::get('/container', function (\Illuminate\Http\Request $request) {
	$app = $container = app();

	class Concrete
	{
		public function show()
		{
			echo 'show is ok';
		}
	}

	// 与容器注册绑定
	$container->bind('iabstract', Concrete::class);
	// 从容器中解析给定类型
	$c = $container->make('iabstract');
	$c->show();

	//Lavarl框架关键技术解析，第8章
	class SingleService
	{
		public $serviceName;
	}

	//到bindings数组，普通绑定
	$app->bind(\App\ServiceTest\GeneralService::class, function ($app) {
		return new \App\ServiceTest\GeneralService();
	});
	//到bindings数组，单例绑定
	$app->singleton(SingleService::class, function ($app) {
		return new SingleService();
	});
	//实例对象服务绑定，到instances数组中
	$instance = new \App\ServiceTest\InstanceService();
	$app->instance('InstanceService', $instance);

	//接口名、服务名形式
	$app->bind(\App\ServiceTest\ServiceContract::class, \App\ServiceTest\GeneralService::class);

	//查看bindings和instances数组
	var_dump($app);

	//4种解析方式
	$generalServiceOne   = $app->make(\App\ServiceTest\GeneralService::class);
	$generalServiceTwo   = $app[\App\ServiceTest\GeneralService::class];
	$generalServiceThree = app(\App\ServiceTest\GeneralService::class);
	$generalServiceFour  = \App::make(\App\ServiceTest\GeneralService::class);
});
//服务容器与服务提供者
Route::get('/container/welcome', 'WelcomeController@index');
Route::get('/container/welcome/interface', 'WelcomeController@interfaceIndex');


//管道示例
Route::get('/pips', function (\Illuminate\Http\Request $request) {
	//管道闭包，中间件直接使用别名数组，then()中的carry()中使用服务容器实例化对象即完成
	//左一>>左二>>芯>>右一>>右二
	$closures = [
		function ($info, Closure $next) { //1
			echo "左一洋葱皮，函数1，" . $info;
			return $next($info);
		},
		function ($info, Closure $next) { //2
			echo "左二洋葱皮，函数2，" . $info;
			$n = $next($info);
			echo "右二洋葱皮，函数2，" . $info;
			return $n;
		},
		function ($info, Closure $next) { //3
			$n = $next($info);
			echo "右一洋葱皮，函数3，" . $info;
			return $n;
		}
	];

	//管道演示
	(new \Illuminate\Pipeline\Pipeline($this->container))
		->send("OK<br/>")
		->through($closures)
		->then(function ($info) {
			echo "洋葱芯" . $info;
		});


});

Auth::routes();

Route::get('/home', 'HomeController@index');
