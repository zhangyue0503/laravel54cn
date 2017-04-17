<?php
/**
 * Created by PhpStorm.
 *
 * Lavarl框架关键技术解析，第8章
 *
 * User: zhangyue
 * Date: 2017/4/16
 * Time: 下午11:53
 */

namespace App\Http\Controllers;


use App\ServiceTest\GeneralService;
use App\ServiceTest\ServiceContract;
use Illuminate\Container\Container;
use Illuminate\Http\Response;


class WelcomeController extends Container
{

	//Laravel框架关键技术解析九
	public function indexRedirect()
	{
		return redirect('/');
	}
	//Laravel框架关键技术解析九
	public function indexResponse2(){
		// Illuminate\Routing\ResponseFactory::view() 从应用程序返回新的视图响应
		return response()->view('welcome')->header('Content-Type','text/html; chatset=UTF-8');
	}
	//Laravel框架关键技术解析九
	public function indexResponse()
	{
		return (new Response('Hello world', 200))->header('Content-type', 'text/html; charset=UTF-8');
	}

	//Laravel框架关键技术解析八
	public function index(GeneralService $generalService)
	{
		dd($generalService);
	}
	//Laravel框架关键技术解析八
	public function interfaceIndex(ServiceContract $generalService)
	{
		dd($generalService);
	}
}