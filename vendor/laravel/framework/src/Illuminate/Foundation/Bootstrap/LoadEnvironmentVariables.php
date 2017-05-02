<?php

namespace Illuminate\Foundation\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Symfony\Component\Console\Input\ArgvInput;
use Illuminate\Contracts\Foundation\Application;

/**
 * Class LoadEnvironmentVariables
 *
 * 环境检测
 *
 * @package Illuminate\Foundation\Bootstrap
 */
class LoadEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * 引导给定应用程序
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if ($app->configurationIsCached()) { //确定应用程序的配置信息是否被缓存
            return;
        }

        $this->checkForSpecificEnvironmentFile($app); //检测自定义环境匹配的文件APP_ENV是否存在

        try {
			//   dotenv类         获取环境文件目录的路径   获取应用程序正在使用的环境文件  ->在给定目录中加载环境文件
            (new Dotenv($app->environmentPath(), $app->environmentFile()))->load();
        } catch (InvalidPathException $e) { // 这是无效路径异常类
            //
        }
    }

    /**
     * Detect if a custom environment file matching the APP_ENV exists.
	 *
	 * 检测自定义环境匹配的文件APP_ENV是否存在
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected function checkForSpecificEnvironmentFile($app)
    {
        if (php_sapi_name() == 'cli' && with($input = new ArgvInput)->hasParameterOption('--env')) {
            $this->setEnvironmentFilePath( //加载自定义环境文件
				//     获取应用程序正在使用的环境文件         返回原始选项的值（未解析）Symfony\Component\Console\Input\ArgvInput::getParameterOption()
                $app, $app->environmentFile().'.'.$input->getParameterOption('--env')
            );
        }
		// 获取环境变量的值
        if (! env('APP_ENV')) {
            return;
        }

        $this->setEnvironmentFilePath( //加载自定义环境文件
			//     获取应用程序正在使用的环境文件      获取环境变量的值
            $app, $app->environmentFile().'.'.env('APP_ENV')
        );
    }

    /**
     * Load a custom environment file.
	 *
	 * 加载自定义环境文件
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $file
     * @return void
     */
    protected function setEnvironmentFilePath($app, $file)
    {
        if (file_exists($app->environmentPath().'/'.$file)) { //获取环境文件目录的路径
            $app->loadEnvironmentFrom($file); // 在引导期间设置要加载的环境文件
        }
    }
}
