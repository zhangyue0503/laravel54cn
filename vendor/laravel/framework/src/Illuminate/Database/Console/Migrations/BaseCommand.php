<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /**
     * Get all of the migration paths.
     *
     * 获取所有的迁移路径
     *
     * @return array
     */
    protected function getMigrationPaths()
    {
        // Here, we will check to see if a path option has been defined. If it has we will
        // use the path relative to the root of the installation folder so our database
        // migrations may be run for any customized path from within the application.
        //
        // 在这里，我们将检查是否定义了path选项
        // 如果有的话，我们将使用相对于安装文件夹的根的路径，这样我们的数据库迁移就可以从应用程序内的任何定制路径运行
        //
        //返回true,如果存在一个InputOption对象的名字          获取命令选项的值
        if ($this->input->hasOption('path') && $this->option('path')) {
            //得到Laravel安装的基本路径                获取命令选项的值
            return [$this->laravel->basePath().'/'.$this->option('path')];
        }

        return array_merge(
            //获取到迁移目录的路径
            [$this->getMigrationPath()], $this->migrator->paths()
        );
    }

    /**
     * Get the path to the migration directory.
     *
     * 获取到迁移目录的路径
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        //                      获取数据库目录的路径
        return $this->laravel->databasePath().DIRECTORY_SEPARATOR.'migrations';
    }
}
