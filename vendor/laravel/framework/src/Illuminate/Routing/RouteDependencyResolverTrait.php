<?php

namespace Illuminate\Routing;

use ReflectionMethod;
use ReflectionParameter;
use Illuminate\Support\Arr;
use ReflectionFunctionAbstract;
//     路由 附属       解决者
trait RouteDependencyResolverTrait
{
    /**
     * Resolve the object method's type-hinted dependencies.
     *
     * 解决对象方法的类型暗示依赖
     *
     * @param  array  $parameters
     * @param  object  $instance
     * @param  string  $method
     * @return array
     */
    protected function resolveClassMethodDependencies(array $parameters, $instance, $method)
    {
        //如果实例中包含方法
        if (! method_exists($instance, $method)) {
            return $parameters;
        }
        //解决给定方法的类型暗示依赖
        return $this->resolveMethodDependencies(
            $parameters, new ReflectionMethod($instance, $method)
        );
    }

    /**
     * Resolve the given method's type-hinted dependencies.
     *
     * 解决给定方法的类型暗示依赖
     *
     * @param  array  $parameters
     * @param  \ReflectionFunctionAbstract  $reflector
     * @return array
     */
    public function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector)
    {
        $results = [];

        $instanceCount = 0;

        $values = array_values($parameters);
        //通过反射循环参数
        foreach ($reflector->getParameters() as $key => $parameter) {
            $instance = $this->transformDependency( //尝试将给定的参数转换为类实例
                $parameter, $parameters
            );

            if (! is_null($instance)) {
                $instanceCount++;

                $results[] = $instance;
            } else {
                $results[] = isset($values[$key - $instanceCount])
                    ? $values[$key - $instanceCount] : $parameter->getDefaultValue();
            }
        }

        return $results;
    }

    /**
     * Attempt to transform the given parameter into a class instance.
     *
     * 尝试将给定的参数转换为类实例
     *
     * @param  \ReflectionParameter  $parameter
     * @param  array  $parameters
     * @return mixed
     */
    protected function transformDependency(ReflectionParameter $parameter, $parameters)
    {
        $class = $parameter->getClass();

        // If the parameter has a type-hinted class, we will check to see if it is already in
        // the list of parameters. If it is we will just skip it as it is probably a model
        // binding and we do not want to mess with those; otherwise, we resolve it here.
        //
        // 如果参数具有类型暗示类，我们将检查它是否已在参数列表中。如果是，我们将跳过它，因为它可能是一个模型绑定，我们不想弄乱这些，否则，我们在这里解决它
        //
        //                  确定的对象是在给定的类的参数列表
        if ($class && ! $this->alreadyInParameters($class->name, $parameters)) {
            return $this->container->make($class->name); //从容器中解析给定类型
        }
    }

    /**
     * Determine if an object of the given class is in a list of parameters.
     *
     * 确定的对象是在给定的类的参数列表
     *
     * @param  string  $class
     * @param  array  $parameters
     * @return bool
     */
    protected function alreadyInParameters($class, array $parameters)
    {
        //                通过给定的真值测试返回数组中的第一个元素
        return ! is_null(Arr::first($parameters, function ($value) use ($class) {
            return $value instanceof $class;
        }));
    }
}
