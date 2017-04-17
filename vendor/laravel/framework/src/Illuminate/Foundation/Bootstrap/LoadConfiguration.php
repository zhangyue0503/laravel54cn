<?php

namespace Illuminate\Foundation\Bootstrap;

use SplFileInfo;
use Illuminate\Config\Repository;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Config\Repository as RepositoryContract;

class LoadConfiguration
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
        $items = [];

        // First we will see if we have a cache configuration file. If we do, we'll load
        // the configuration items from that file so that it is very quick. Otherwise
        // we will need to spin through every configuration file and load them all.
		//
		// 首先我们将看看是否有缓存配置文件
		// 如果我们这样做，我们将加载配置文件从该文件，以便它是非常快
		// 否则，我们需要旋转每一个配置文件，并加载它们
		//
		//                            确定应用程序的配置信息是否被缓存
        if (file_exists($cached = $app->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }

        // Next we will spin through all of the configuration files in the configuration
        // directory and load each one into the repository. This will make all of the
        // options available to the developer for use in various parts of this app.
		//
		// 接下来，我们将旋转配置目录中的所有配置文件，并加载到库中的每一个
		// 这将使所有的选项可供开发人员在这个应用程序的各个部分使用
		//
		//    在容器中注册一个已存在的实例        创建一个新的配置库
        $app->instance('config', $config = new Repository($items));

        if (! isset($loadedFromCache)) {
			//  从所有文件加载配置项
            $this->loadConfigurationFiles($app, $config);
        }

        // Finally, we will set the application's environment based on the configuration
        // values that were loaded. We will pass a callback which will be used to get
        // the environment in a web context where an "--env" switch is not present.
		//
		// 最后，我们将根据加载的配置值设置应用程序的环境
		// 我们将传递一个回调函数，该回调将被用于在Web环境中获取环境，而在该环境中，“--env”交换机不存在
		//
		//   检测应用程序的当前环境
        $app->detectEnvironment(function () use ($config) {
            return $config->get('app.env', 'production');  //获取指定的配置值
        });

        date_default_timezone_set($config->get('app.timezone', 'UTC')); // 设置配置文件中定义的时区

        mb_internal_encoding('UTF-8'); // 设置字符编码
    }

    /**
     * Load the configuration items from all of the files.
	 *
	 * 从所有文件加载配置项
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Config\Repository  $repository
     * @return void
     */
    protected function loadConfigurationFiles(Application $app, RepositoryContract $repository)
    {
		//          获取应用程序的所有配置文件
        foreach ($this->getConfigurationFiles($app) as $key => $path) {
            $repository->set($key, require $path); // 设置给定的配置值
        }
    }

    /**
     * Get all of the configuration files for the application.
	 *
	 * 获取应用程序的所有配置文件
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return array
     */
    protected function getConfigurationFiles(Application $app)
    {
        $files = [];

        $configPath = realpath($app->configPath()); //获取应用程序配置文件的路径

		// Symfony\Component\Finder\Finder::创建一个新的查找器->仅限制对文件的匹配->添加文件必须匹配的规则->搜索符合定义规则的文件和目录
        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $directory = $this->getNestedDirectory($file, $configPath); //获取配置文件嵌套路径

            $files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Get the configuration file nesting path.
	 *
	 * 获取配置文件嵌套路径
     *
     * @param  \SplFileInfo  $file
     * @param  string  $configPath
     * @return string
     */
    protected function getNestedDirectory(SplFileInfo $file, $configPath)
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }
}
