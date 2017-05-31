<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesComponents
{
    /**
     * Compile the component statements into valid PHP.
     *
     * 将组件语句编译成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileComponent($expression)
    {
        return "<?php \$__env->startComponent{$expression}; ?>";
    }

    /**
     * Compile the end-component statements into valid PHP.
     *
     * 将最终组件语句编译成有效的PHP
     *
     * @return string
     */
    protected function compileEndComponent()
    {
        return '<?php echo $__env->renderComponent(); ?>';
    }

    /**
     * Compile the slot statements into valid PHP.
     *
     * 将槽语句编译成有效的PHP
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileSlot($expression)
    {
        return "<?php \$__env->slot{$expression}; ?>";
    }

    /**
     * Compile the end-slot statements into valid PHP.
     *
     * 将最终槽语句编译成有效的PHP
     *
     * @return string
     */
    protected function compileEndSlot()
    {
        return '<?php $__env->endSlot(); ?>';
    }
}
