<?php

namespace Illuminate\Queue;

use ReflectionClass;
use ReflectionProperty;

trait SerializesModels
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * Prepare the instance for serialization.
	 *
	 * 准备序列化的实例
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = (new ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            //                                       获取用于序列化的属性值
            $property->setValue($this, $this->getSerializedPropertyValue(
                $this->getPropertyValue($property)//获得给定属性的属性值
            ));
        }

        return array_map(function ($p) {
            return $p->getName();
        }, $properties);
    }

    /**
     * Restore the model after serialization.
     *
     * 在序列化之后恢复模型
     *
     * @return void
     */
    public function __wakeup()
    {
        foreach ((new ReflectionClass($this))->getProperties() as $property) {
            //                                在反序列化之后得到恢复的属性值
            $property->setValue($this, $this->getRestoredPropertyValue(
                $this->getPropertyValue($property)//获得给定属性的属性值
            ));
        }
    }

    /**
     * Get the property value for the given property.
     *
     * 获得给定属性的属性值
     *
     * @param  \ReflectionProperty  $property
     * @return mixed
     */
    protected function getPropertyValue(ReflectionProperty $property)
    {
        $property->setAccessible(true);

        return $property->getValue($this);
    }
}
