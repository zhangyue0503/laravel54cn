<?php

namespace Illuminate\Foundation\Testing\Concerns;

trait InteractsWithSession
{
    /**
     * Set the session to the given array.
     *
     * 将会话设置为给定的数组
     *
     * @param  array  $data
     * @return $this
     */
    public function withSession(array $data)
    {
        //将会话设置为给定的数组
        $this->session($data);

        return $this;
    }

    /**
     * Set the session to the given array.
     *
     * 将会话设置为给定的数组
     *
     * @param  array  $data
     * @return $this
     */
    public function session(array $data)
    {
        $this->startSession(); //启动应用程序的会话

        foreach ($data as $key => $value) {
            //将键/值对或数组中的键/值对放入session
            $this->app['session']->put($key, $value);
        }

        return $this;
    }

    /**
     * Start the session for the application.
     *
     * 启动应用程序的会话
     *
     * @return $this
     */
    protected function startSession()
    {
        //确定会话是否已启动
        if (! $this->app['session']->isStarted()) {
            $this->app['session']->start();//启动会话，从处理程序读取数据
        }

        return $this;
    }

    /**
     * Flush all of the current session data.
     *
     * 刷新所有当前会话数据
     *
     * @return $this
     */
    public function flushSession()
    {
        $this->startSession();//启动应用程序的会话

        $this->app['session']->flush();//从会话中移除所有项目

        return $this;
    }
}
