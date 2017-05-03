<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\ParameterBag;

class TransformsRequest
{
    /**
     * The additional attributes passed to the middleware.
     *
     * 传递给中间件的附加属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Handle an incoming request.
     *
     * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$attributes)
    {
        $this->attributes = $attributes;
        //清除请求的数据
        $this->clean($request);

        return $next($request);
    }

    /**
     * Clean the request's data.
     *
     * 清除请求的数据
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clean($request)
    {
        $this->cleanParameterBag($request->query);//在参数包中清理数据

        $this->cleanParameterBag($request->request);//在参数包中清理数据
        //确定请求是否发送JSON
        if ($request->isJson()) {
            $this->cleanParameterBag($request->json());//在参数包中清理数据(获取请求的JSON有效载荷)
        }
    }

    /**
     * Clean the data in the parameter bag.
     *
     * 在参数包中清理数据
     *
     * @param  \Symfony\Component\HttpFoundation\ParameterBag  $bag
     * @return void
     */
    protected function cleanParameterBag(ParameterBag $bag)
    {
        //替换参数数组(在给定数组中清除数据(返回所有参数数组))
        $bag->replace($this->cleanArray($bag->all()));
    }

    /**
     * Clean the data in the given array.
     *
     * 在给定数组中清除数据
     *
     * @param  array  $data
     * @return array
     */
    protected function cleanArray(array $data)
    {
        //                      在每个项目上运行map
        return collect($data)->map(function ($value, $key) {
            return $this->cleanValue($key, $value);//清除给定值
        })->all();//获取集合中的所有项目
    }

    /**
     * Clean the given value.
     *
     * 清除给定值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function cleanValue($key, $value)
    {
        if (is_array($value)) {
            //在给定数组中清除数据
            return $this->cleanArray($value);
        }
        //               转换给定值
        return $this->transform($key, $value);
    }

    /**
     * Transform the given value.
     *
     * 转换给定值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        return $value;
    }
}
