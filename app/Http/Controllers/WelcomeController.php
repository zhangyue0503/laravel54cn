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
use App\User;
use Illuminate\Container\Container;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;


class WelcomeController extends Container
{
	//Laravel框架关键技术解析十
	public function indexTen1(){
		$data = User::all();
		var_dump($data);
	}
	//Laravel框架关键技术解析十
	public function indexTen()
	{


		//增加
//		DB::table('users')->insert([
//			'name' => 'aa', 'email' => 'aaa', 'password' => 111
//		]);
//
//		DB::table('users')->insert([
//			['name' => 'aa', 'email' => 'bbb', 'password' => 222],
//			['name' => 'aa', 'email' => 'ccc', 'password' => 333],
//		]);
//
//		$id = DB::table('users')->insertGetId([
//			'name' => 'aa', 'email' => 'ddd', 'password' => 444
//		]);
//
//		var_dump($id);

		//删除
//		DB::table('users')->where('id', '>', '20')->delete();
//		DB::table('users')->delete();
//		DB::table('users')->truncate();

		//更新
		DB::table('users')->where('id', 2)->update(['password' => "222222"]);
		DB::table('users')->increment('password');
		DB::table('users')->increment('password', 10);
		DB::table('users')->decrement('password');
		DB::table('users')->decrement('password', 10);

		//查询
		$users = DB::table('users')->get();
		var_dump($users);
		$users = DB::table('users')->where('id', '>', 2)->get();
		var_dump($users);
		$users = DB::table('users')->where('id', '>', 2)->orWhere('email', 'bbb')->get();
		var_dump($users);
		$users = DB::table('users')->whereBetween('password',[100,400])->get();
		var_dump($users);
		$users = DB::table('users')->whereIn('id',[1,2,3])->get();
		var_dump($users);
		$users = DB::table('users')->whereNotIn('id',[1,2,3])->get();
		var_dump($users);

	}

	//Laravel框架关键技术解析九
	public function indexRedirect()
	{
		return redirect('/');
	}

	//Laravel框架关键技术解析九
	public function indexResponse2()
	{
		// Illuminate\Routing\ResponseFactory::view() 从应用程序返回新的视图响应
		return response()->view('welcome')->header('Content-Type', 'text/html; chatset=UTF-8');
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