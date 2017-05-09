<?php

namespace Illuminate\Console;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutputStyle extends SymfonyStyle
{
    /**
     * The output instance.
     *
     * 输出实例
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * Create a new Console OutputStyle instance.
     *
     * 创建一个新的控制台OutputStyle实例
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        //输出装饰助手Symfony风格指南
        parent::__construct($input, $output);
    }

    /**
     * Returns whether verbosity is quiet (-q).
     *
     * 返回是否安静的冗长(-q)
     *
     * @return bool
     */
    public function isQuiet()
    {
        //返回是否安静的冗长(-q)
        return $this->output->isQuiet();
    }

    /**
     * Returns whether verbosity is verbose (-v).
     *
     * 返回是否冗长冗长(- v)
     *
     * @return bool
     */
    public function isVerbose()
    {
        //返回是否冗长冗长(- v)
        return $this->output->isVerbose();
    }

    /**
     * Returns whether verbosity is very verbose (-vv).
     *
     * 返回是否非常冗长冗长(vv)
     *
     * @return bool
     */
    public function isVeryVerbose()
    {
        //返回是否非常冗长冗长(vv)
        return $this->output->isVeryVerbose();
    }

    /**
     * Returns whether verbosity is debug (-vvv).
     *
     * 返回是否调试冗长(-vvv)
     *
     * @return bool
     */
    public function isDebug()
    {
        //返回是否调试冗长(-vvv)
        return $this->output->isDebug();
    }
}
