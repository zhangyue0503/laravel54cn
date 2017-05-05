<?php

namespace Illuminate\Auth\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authorize
{
    /**
     * The authentication factory instance.
     *
     * 身份验证工厂实例
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * The gate instance.
     *
     * gage实例
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * Create a new middleware instance.
     *
     * 创建一个新的中间件实例
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function __construct(Auth $auth, Gate $gate)
    {
        $this->auth = $auth;
        $this->gate = $gate;
    }

    /**
     * Handle an incoming request.
     *
     * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $ability
     * @param  array|null  $models
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle($request, Closure $next, $ability, ...$models)
    {
        //用户已经通过了身份验证
        $this->auth->authenticate();
        //确定当前用户是否授予给定的能力(获取gate的参数参数)
        $this->gate->authorize($ability, $this->getGateArguments($request, $models));

        return $next($request);
    }

    /**
     * Get the arguments parameter for the gate.
     *
     * 获取gate的参数参数
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array|null  $models
     * @return array|string|\Illuminate\Database\Eloquent\Model
     */
    protected function getGateArguments($request, $models)
    {
        if (is_null($models)) {
            return [];
        }
        //                    在每个项目上运行map
        return collect($models)->map(function ($model) use ($request) {
            //                                             获取模型的授权
            return $model instanceof Model ? $model : $this->getModel($request, $model);
        })->all();//获取集合中的所有项目
    }

    /**
     * Get the model to authorize.
     *
     * 获取模型的授权
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $model
     * @return string|\Illuminate\Database\Eloquent\Model
     */
    protected function getModel($request, $model)
    {
        //检查给定的字符串是否看起来像一个完全合格的类名           获取路由处理请求
        return $this->isClassName($model) ? $model : $request->route($model);
    }

    /**
     * Checks if the given string looks like a fully qualified class name.
     *
     * 检查给定的字符串是否看起来像一个完全合格的类名
     *
     * @param  string  $value
     * @return bool
     */
    protected function isClassName($value)
    {
        return strpos($value, '\\') !== false;
    }
}
