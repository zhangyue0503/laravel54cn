<?php

namespace Illuminate\Support\Debug;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
//倾倒  包装var_dump()
class Dumper
{
    /**
     * Dump a value with elegance.
     *
     * 简洁地倾倒一个值
     *
     * @param  mixed  $value
     * @return void
     */
    public function dump($value)
    {
        //               CliDumper转储命令行输出变量
        if (class_exists(CliDumper::class)) {
            //                           CliDumper转储命令行输出变量 //HtmlDumper将变量转储为HTML
            $dumper = 'cli' === PHP_SAPI ? new CliDumper : new HtmlDumper;
            //                            克隆PHP变量
            $dumper->dump((new VarCloner)->cloneVar($value));
        } else {
            var_dump($value);
        }
    }
}
