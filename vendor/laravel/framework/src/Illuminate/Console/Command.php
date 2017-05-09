<?php

namespace Illuminate\Console;

use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Command extends SymfonyCommand
{
    /**
     * The Laravel application instance.
     *
     * Laravel应用程序实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $laravel;

    /**
     * The input interface implementation.
     *
     * 输入接口实现
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
     *
     * 输出接口实现
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $output;

    /**
     * The name and signature of the console command.
     *
     * 控制台命令的名称和签名
     *
     * @var string
     */
    protected $signature;

    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description;

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * 指示该命令是否应该显示在工匠命令列表中
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * The default verbosity of output commands.
     *
     * 默认的输出命令的冗长
     *
     * @var int
     */
    protected $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * The mapping between human readable verbosity levels and Symfony's OutputInterface.
     *
     * 人类可读的详细级别之间的映射和Symfony的OutputInterface
     *
     * @var array
     */
    protected $verbosityMap = [
        'v' => OutputInterface::VERBOSITY_VERBOSE,
        'vv' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        'vvv' => OutputInterface::VERBOSITY_DEBUG,
        'quiet' => OutputInterface::VERBOSITY_QUIET,
        'normal' => OutputInterface::VERBOSITY_NORMAL,
    ];

    /**
     * Create a new console command instance.
     *
     * 创建一个新的控制台命令实例
     *
     * @return void
     */
    public function __construct()
    {
        // We will go ahead and set the name, description, and parameters on console
        // commands just to make things a little easier on the developer. This is
        // so they don't have to all be manually specified in the constructors.
        //
        // 我们将继续在控制台命令上设置名称、描述和参数，以便让开发人员更容易一些
        // 因此，它们不需要在构造函数中手工指定
        //
        if (isset($this->signature)) {
            //使用流利的定义配置控制台命令
            $this->configureUsingFluentDefinition();
        } else {
            //Symfony\Component\Console\Command\Command
            parent::__construct($this->name);
        }

        // Once we have constructed the command, we'll set the description and other
        // related properties of the command. If a signature wasn't used to build
        // the command we'll set the arguments and the options on this command.
        //
        // 一旦我们构造了这个命令，我们将设置该命令的描述和其他相关属性
        // 如果没有使用一个签名来构建命令，我们将在这个命令中设置参数和选项
        //
        // 设置命令的描述
        $this->setDescription($this->description);

        $this->setHidden($this->hidden);

        if (! isset($this->signature)) {
            //在命令中指定参数和选项
            $this->specifyParameters();
        }
    }

    /**
     * Configure the console command using a fluent definition.
     *
     * 使用流利的定义配置控制台命令
     *
     * @return void
     */
    protected function configureUsingFluentDefinition()
    {
        //                                 将给定的控制台命令定义解析为一个数组
        list($name, $arguments, $options) = Parser::parse($this->signature);
        //Symfony\Component\Console\Command\Command
        parent::__construct($this->name = $name);

        // After parsing the signature we will spin through the arguments and options
        // and set them on this command. These will already be changed into proper
        // instances of these "InputArgument" and "InputOption" Symfony classes.
        //
        // 在解析签名之后，我们将会对参数和选项进行旋转，并将它们设置在这个命令上
        // 这些已经变成了正确的实例“InputArgument”和“InputOption”Symfony类
        //
        foreach ($arguments as $argument) {
            //获得这个命令的InputDefinition->添加一个InputArgument对象
            $this->getDefinition()->addArgument($argument);
        }

        foreach ($options as $option) {
            //获得这个命令的InputDefinition->添加一个InputArgument对象
            $this->getDefinition()->addOption($option);
        }
    }

    /**
     * Specify the arguments and options on the command.
     *
     * 在命令中指定参数和选项
     *
     * @return void
     */
    protected function specifyParameters()
    {
        // We will loop through all of the arguments and options for the command and
        // set them all on the base command instance. This specifies what can get
        // passed into these commands as "parameters" to control the execution.
        //
        // 我们将循环遍历该命令的所有参数和选项，并将它们全部设置在基本命令实例上
        // 这指定了哪些可以作为“参数”传入这些命令以控制执行
        //
        //            获得控制台命令参数
        foreach ($this->getArguments() as $arguments) {
            call_user_func_array([$this, 'addArgument'], $arguments);
        }
        //获得控制台命令选项
        foreach ($this->getOptions() as $options) {
            call_user_func_array([$this, 'addOption'], $options);
        }
    }

    /**
     * Run the console command.
     *
     * 运行控制台命令
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        //运行命令
        return parent::run(
            $this->input = $input, $this->output = new OutputStyle($input, $output)
        );
    }

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $method = method_exists($this, 'handle') ? 'handle' : 'fire';
        //调用给定的闭包/类@方法并注入它的依赖项
        return $this->laravel->call([$this, $method]);
    }

    /**
     * Call another console command.
     *
     * 调用另一个控制台命令
     *
     * @param  string  $command
     * @param  array   $arguments
     * @return int
     */
    public function call($command, array $arguments = [])
    {
        $arguments['command'] = $command;
        //获取该命令的应用程序实例->通过名称或别名找到一个命令->运行命令
        return $this->getApplication()->find($command)->run(
            new ArrayInput($arguments), $this->output
        );
    }

    /**
     * Call another console command silently.
     *
     * 调用另一个控制台命令
     *
     * @param  string  $command
     * @param  array   $arguments
     * @return int
     */
    public function callSilent($command, array $arguments = [])
    {
        $arguments['command'] = $command;
        //获取该命令的应用程序实例->通过名称或别名找到一个命令->运行命令
        return $this->getApplication()->find($command)->run(
            new ArrayInput($arguments), new NullOutput
        );
    }

    /**
     * Determine if the given argument is present.
     *
     * 确定给定的参数是否存在
     *
     * @param  string|int  $name
     * @return bool
     */
    public function hasArgument($name)
    {
        //返回true,如果一个InputArgument对象存在的名字或位置
        return $this->input->hasArgument($name);
    }

    /**
     * Get the value of a command argument.
     *
     * 获取一个命令参数的值
     *
     * @param  string  $key
     * @return string|array
     */
    public function argument($key = null)
    {
        if (is_null($key)) {
            //返回所有给定的参数与默认值合并
            return $this->input->getArguments();
        }
        //返回给定参数名的参数值
        return $this->input->getArgument($key);
    }

    /**
     * Get all of the arguments passed to the command.
     *
     * 将所有参数传递给该命令
     *
     * @return array
     */
    public function arguments()
    {
        //获取一个命令参数的值
        return $this->argument();
    }

    /**
     * Determine if the given option is present.
     *
     * 确定给定的选项是否存在
     *
     * @param  string  $name
     * @return bool
     */
    public function hasOption($name)
    {
        //返回true,如果存在一个InputOption对象的名字
        return $this->input->hasOption($name);
    }

    /**
     * Get the value of a command option.
     *
     * 获取命令选项的值
     *
     * @param  string  $key
     * @return string|array
     */
    public function option($key = null)
    {
        if (is_null($key)) {
            //返回与默认值合并的所有选项
            return $this->input->getOptions();
        }
        //              返回给定选项名的选项值
        return $this->input->getOption($key);
    }

    /**
     * Get all of the options passed to the command.
     *
     * 将所有的选项都传递给命令
     *
     * @return array
     */
    public function options()
    {
        //          获取命令选项的值
        return $this->option();
    }

    /**
     * Confirm a question with the user.
     *
     * 与用户确认一个问题
     *
     * @param  string  $question
     * @param  bool    $default
     * @return bool
     */
    public function confirm($question, $default = false)
    {
        return $this->output->confirm($question, $default);
    }

    /**
     * Prompt the user for input.
     *
     * 提示用户输入
     *
     * @param  string  $question
     * @param  string  $default
     * @return string
     */
    public function ask($question, $default = null)
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * 提示用户输入自动完成
     *
     * @param  string  $question
     * @param  array   $choices
     * @param  string  $default
     * @return string
     */
    public function anticipate($question, array $choices, $default = null)
    {
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * 提示用户输入自动完成
     *
     * @param  string  $question
     * @param  array   $choices
     * @param  string  $default
     * @return string
     */
    public function askWithCompletion($question, array $choices, $default = null)
    {
        $question = new Question($question, $default);
        //集值自动完成
        $question->setAutocompleterValues($choices);

        return $this->output->askQuestion($question);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * 提示用户输入，但将答案隐藏在控制台
     *
     * @param  string  $question
     * @param  bool    $fallback
     * @return string
     */
    public function secret($question, $fallback = true)
    {
        $question = new Question($question);
        //             设置用户响应是否必须隐藏       设置是否可以在非隐藏的问题上后退如果响应不能被隐藏
        $question->setHidden(true)->setHiddenFallback($fallback);

        return $this->output->askQuestion($question);
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * 从一系列答案中给用户一个选择
     *
     * @param  string  $question
     * @param  array   $choices
     * @param  string  $default
     * @param  mixed   $attempts
     * @param  bool    $multiple
     * @return string
     */
    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
    {
        //代表一个选择问题
        $question = new ChoiceQuestion($question, $choices, $default);
        //设置最大的尝试次数                       多选选择
        $question->setMaxAttempts($attempts)->setMultiselect($multiple);

        return $this->output->askQuestion($question);
    }

    /**
     * Format input to textual table.
     *
     * 格式输入到文本表
     *
     * @param  array   $headers
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $rows
     * @param  string  $style
     * @return void
     */
    public function table(array $headers, $rows, $style = 'default')
    {
        $table = new Table($this->output);

        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();//获取数组实例
        }

        $table->setHeaders($headers)->setRows($rows)->setStyle($style)->render();
    }

    /**
     * Write a string as information output.
     *
     * 将字符串写入信息输出
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        //将字符串作为标准输出写入
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string as standard output.
     *
     * 将字符串作为标准输出写入
     *
     * @param  string  $string
     * @param  string  $style
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;
        //                                在symfony的输出水平方面的详细程度
        $this->output->writeln($styled, $this->parseVerbosity($verbosity));
    }

    /**
     * Write a string as comment output.
     *
     * 编写一个字符串作为注释输出
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', $verbosity);//将字符串作为标准输出写入
    }

    /**
     * Write a string as question output.
     *
     * 编写一个字符串作为问题输出
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function question($string, $verbosity = null)
    {
        $this->line($string, 'question', $verbosity);//将字符串作为标准输出写入
    }

    /**
     * Write a string as error output.
     *
     * 将字符串写入错误输出
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', $verbosity);//将字符串作为标准输出写入
    }

    /**
     * Write a string as warning output.
     *
     * 编写一个字符串作为警告输出
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        //                                    检查输出格式器是否具有指定名称的样式
        if (! $this->output->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');
            //                                设置一个新的样式
            $this->output->getFormatter()->setStyle('warning', $style);
        }

        $this->line($string, 'warning', $verbosity);//将字符串作为标准输出写入
    }

    /**
     * Set the verbosity level.
     *
     * 设置冗长级别
     *
     * @param  string|int  $level
     * @return void
     */
    protected function setVerbosity($level)
    {
        //在symfony的输出水平方面的详细程度
        $this->verbosity = $this->parseVerbosity($level);
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
     *
     * 在symfony的输出水平方面的详细程度
     *
     * @param  string|int  $level
     * @return int
     */
    protected function parseVerbosity($level = null)
    {
        if (isset($this->verbosityMap[$level])) {
            $level = $this->verbosityMap[$level];
        } elseif (! is_int($level)) {
            $level = $this->verbosity;
        }

        return $level;
    }

    /**
     * Get the console command arguments.
     *
     * 获得控制台命令参数
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * 获得控制台命令选项
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    /**
     * Get the output implementation.
     *
     * 得到输出实现
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Get the Laravel application instance.
     *
     * 获取Laravel应用程序实例
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getLaravel()
    {
        return $this->laravel;
    }

    /**
     * Set the Laravel application instance.
     *
     * 设置Laravel应用程序实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $laravel
     * @return void
     */
    public function setLaravel($laravel)
    {
        $this->laravel = $laravel;
    }
}
