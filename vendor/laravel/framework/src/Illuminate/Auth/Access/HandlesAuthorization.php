<?php

namespace Illuminate\Auth\Access;

trait HandlesAuthorization
{
    /**
     * Create a new access response.
     *
     * 创建一个新的访问响应
     *
     * @param  string|null  $message
     * @return \Illuminate\Auth\Access\Response
     */
    protected function allow($message = null)
    {
        //          创建一个新的响应
        return new Response($message);
    }

    /**
     * Throws an unauthorized exception.
     *
     * 抛出未授权的异常
     *
     * @param  string  $message
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function deny($message = 'This action is unauthorized.')
    {
        throw new AuthorizationException($message);
    }
}
