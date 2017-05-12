<?php

namespace Illuminate\Database\Eloquent;

trait SoftDeletes
{
    /**
     * Indicates if the model is currently force deleting.
     *
     * 指示该模型当前是否强制删除
     *
     * @var bool
     */
    protected $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     *
     * 启动一个模型的软删除特性
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        //在模型上注册一个新的全局范围
        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * 强制删除一个软删除的模型
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        $this->forceDeleting = true;

        $deleted = $this->delete();

        $this->forceDeleting = false;

        return $deleted;
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * 在这个模型实例上执行实际的删除查询
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            //      获取一个新的查询生成器，它没有任何全局作用域                                        强制删除软删除模型
            return $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey())->forceDelete();
        }
        //在这个模型实例上执行实际的删除查询
        return $this->runSoftDelete();
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * 在这个模型实例上执行实际的删除查询
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        // 获取一个新的查询生成器，它没有任何全局作用域
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());
        //获取“删除at”列的名称                             为模型获取一个新的时间戳
        $this->{$this->getDeletedAtColumn()} = $time = $this->freshTimestamp();
        //              获取构建器的“删除at”列                 DateTime转换为存储字符串
        $query->update([$this->getDeletedAtColumn() => $this->fromDateTime($time)]);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * 恢复一个软删除的模型实例
     *
     * @return bool|null
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        //
        // 如果恢复事件不返回false，我们将继续执行这个恢复操作
        // 否则，我们就会被保释，这样开发者就会完全停止恢复
        // 我们将清除已删除的时间戳并保存
        //
        //         触发模型的给定的事件
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }
        //         获取“删除at”列的名称
        $this->{$this->getDeletedAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        //
        // 一旦我们保存了模型，我们就会触发“恢复”事件，这样，在恢复操作完成之后，开发人员就可以做任何他们需要做的事情
        // 然后我们将返回保存调用的结果
        //
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * 确定模型实例是否已被软删除
     *
     * @return bool
     */
    public function trashed()
    {
        //                              获取“删除at”列的名称
        return ! is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Register a restoring model event with the dispatcher.
     *
     * 使用dispatcher注册一个恢复模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        //  向调度程序注册一个模型事件
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a restored model event with the dispatcher.
     *
     * 使用分派器注册一个已恢复的模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback)
    {
        //     向调度程序注册一个模型事件
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Determine if the model is currently force deleting.
     *
     * 确定模型是否当前强制删除
     *
     * @return bool
     */
    public function isForceDeleting()
    {
        return $this->forceDeleting;
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * 获取“删除at”列的名称
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * 获得完全符合条件的“删除”列
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        //      获取与模型相关联的表         获取“删除at”列的名称
        return $this->getTable().'.'.$this->getDeletedAtColumn();
    }
}
