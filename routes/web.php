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
    return view('welcome');
});
//容器示例
Route::get('/container', function (\Illuminate\Http\Request $request) {
    $container = new \Illuminate\Container\Container();
    class Concrete{
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
});

//管道示例
Route::get('/pips', function (\Illuminate\Http\Request $request) {
    //管道闭包，中间件直接使用别名数组，then()中的carry()中使用服务容器实例化对象即完成
    //左一>>左二>>芯>>右一>>右二
    $closures = [
        function($info,Closure $next){ //1
            echo "左一洋葱皮，函数1，" . $info;
            return $next($info);
        },
        function($info,Closure $next){ //2
            echo "左二洋葱皮，函数2，" . $info;
            $n = $next($info);
            echo "右二洋葱皮，函数2，" . $info;
            return $n;
        },
        function($info,Closure $next){ //3
            $n = $next($info);
            echo "右一洋葱皮，函数3，" . $info;
            return $n;
        }
    ];

    //管道演示
    (new \Illuminate\Pipeline\Pipeline($this->container))
        ->send("OK<br/>")
        ->through($closures)
        ->then(function($info){
            echo "洋葱芯" . $info;
        });


});
