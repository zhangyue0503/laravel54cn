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


use App\Blog;
use App\Comment;
use App\ServiceTest\GeneralService;
use App\ServiceTest\ServiceContract;
use App\Subject;
use App\User;
use Illuminate\Container\Container;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;


class WelcomeController extends Container
{
	//Laravel框架关键技术解析十
	public function indexTen1()
	{

		//添加
//		Blog::create([
//			'title'=>'Python的未来'
//		]);
//		$blog = new Blog();
//		$blog->title = 'Ruby的未来';
//		$blog->save();
//		//先检查存不存在然后再添加或new
//		Blog::firstOrCreate([
//			'title'=>'JavaScript的未来'
//		]);
//		$blog = Blog::firstOrNew([
//			'title'=>'CSS3的未来'
//		]);
//		$blog->save();

		//查询
		$blogs = Blog::all();
		var_dump($blogs);
		var_dump(Blog::find(1));
		var_dump(Blog::where('title', '=', 'PHP的未来')->first());
		var_dump(Comment::where('words', '>', 10)->get());
		var_dump(Comment::whereRaw('words>10 or words<15')->get());

		//更新
//		$comment          = Comment::where('content', '=', 'PHP是无类型编程语言')->first();
//		$comment->content = 'PHP变量名以$开头';
//		$comment->words   = 10;
//		$comment->save();

//		$affectedRows = Comment::where('content', '=', 'PHP变量名以$开头')->update(['content' => 'PHP是无类型编程语言1']);
//		var_dump($affectedRows);
//
//		//删除
//		$blog = Blog::find(6);
//		$blog->delete();
//
//		Comment::where('words', '>', 10)->delete();
//
//		Blog::destroy(7);
//		Blog::destroy(1,2,3);

		//关系查询
		$php  = Blog::where('title', '=', 'PHP的未来')->first();
		//一对一
		$auth = $php->author;
		var_dump($auth->name);
		var_dump($php->author->name);
		//一对多
		foreach ($php->comments as $comment) {
			var_dump($comment->content . ' ' . $comment->words);
		}
		//多对多
		foreach($php->subjects as $subject){
			var_dump($subject->name);
		}
		$program = Subject::where('name','=','编程语言')->first();
		foreach($program->blogs as $blog){
			var_dump($blog->title);
		}



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
		$users = DB::table('users')->whereBetween('password', [100, 400])->get();
		var_dump($users);
		$users = DB::table('users')->whereIn('id', [1, 2, 3])->get();
		var_dump($users);
		$users = DB::table('users')->whereNotIn('id', [1, 2, 3])->get();
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