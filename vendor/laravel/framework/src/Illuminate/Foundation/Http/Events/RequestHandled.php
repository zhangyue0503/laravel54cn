<?php

namespace Illuminate\Foundation\Http\Events;

class RequestHandled
{
    /**
     * The request instance.
     *
     * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * The resonse instance.
     *
     * 响应实例
     *
     * @var \Illuminate\Http\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
