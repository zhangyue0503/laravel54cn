<?php

namespace Illuminate\Broadcasting;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Broadcast;

class BroadcastController extends Controller
{
    /**
     * Authenticate the request for channel access.
     *
     * 对通道访问的请求进行身份验证
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        //对给定通道的传入请求进行身份验证
        return Broadcast::auth($request);
    }
}
