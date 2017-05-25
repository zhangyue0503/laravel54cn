<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Queue\QueueableCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait SerializesAndRestoresModelIdentifiers
{
    /**
     * Get the property value prepared for serialization.
     *
     * 获取用于序列化的属性值
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function getSerializedPropertyValue($value)
    {
        if ($value instanceof QueueableCollection) {
            //                         获取正在排队的实体的类型                 获取所有实体的标识符
            return new ModelIdentifier($value->getQueueableClass(), $value->getQueueableIds());
        }

        if ($value instanceof QueueableEntity) {
            return new ModelIdentifier(get_class($value), $value->getQueueableId());
        }

        return $value;
    }

    /**
     * Get the restored property value after deserialization.
     *
     * 在反序列化之后得到恢复的属性值
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function getRestoredPropertyValue($value)
    {
        if (! $value instanceof ModelIdentifier) {
            return $value;
        }

        return is_array($value->id)
                ? $this->restoreCollection($value)//恢复一个可排队的集合实例
                : $this->getQueryForModelRestoration(new $value->class)//获取恢复的查询
                            ->useWritePdo()->findOrFail($value->id);//用写的PDO的查询->通过主键找到模型或抛出异常
    }

    /**
     * Restore a queueable collection instance.
     *
     * 恢复一个可排队的集合实例
     *
     * @param  \Illuminate\Contracts\Database\ModelIdentifier  $value
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function restoreCollection($value)
    {
        if (! $value->class || count($value->id) === 0) {
            return new EloquentCollection;
        }

        $model = new $value->class;
        //                  获取恢复的查询                     用写的PDO的查询
        return $this->getQueryForModelRestoration($model)->useWritePdo()
            //在查询中添加“where in”子句    获取表格的键名                     将查询执行为“SELECT”语句
                    ->whereIn($model->getQualifiedKeyName(), $value->id)->get();
    }

    /**
     * Get the query for restoration.
     *
     * 获取恢复的查询
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getQueryForModelRestoration($model)
    {
        //                 获取一个新的查询生成器，它没有任何全局作用域
        return $model->newQueryWithoutScopes();
    }
}
