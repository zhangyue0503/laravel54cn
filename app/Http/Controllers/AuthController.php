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

use Illuminate\Container\Container;
use Illuminate\Http\Request;


class AuthController extends Container
{
	//Laravel框架关键技术解析九
	public function postRegister(Request $request)
	{
		var_dump($request->method());
		var_dump($request->url());
		var_dump($request->fullUrl());
		var_dump($request->path());
		var_dump($request->input());
		var_dump($request->query());
		var_dump($request->all());
		var_dump($request->input('email'));
		var_dump($request->only('email', 'password'));
		var_dump($request->except('email', 'password'));

		//一次性存储
		$request->flash();
		$request->flashOnly('email', 'password');
		$request->flashExcept('password');

		return redirect('/auth/form')->withInput();
	}
	//Laravel框架关键技术解析九
	public function redirectForm(Request $request){
		var_dump($request->old());
	}

}