<?php

namespace Illuminate\Database\Eloquent;

class SoftDeletingScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * 添加到构建器的所有扩展
     *
     * @var array
     */
    protected $extensions = ['Restore', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * 将范围应用于给定的Eloquent的查询构建器
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        //   向查询添加“where null”子句    获得完全符合条件的“删除”列
        $builder->whereNull($model->getQualifiedDeletedAtColumn());
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * 使用所需的函数扩展查询构建器
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
        //注册一个替换默认删除函数
        $builder->onDelete(function (Builder $builder) {
            //获取构建器的“删除at”列
            $column = $this->getDeletedAtColumn($builder);
            //更新数据库中的记录
            return $builder->update([
                //              获取被查询的模型实例    为模型获取一个新的时间戳
                $column => $builder->getModel()->freshTimestampString(),
            ]);
        });
    }

    /**
     * Get the "deleted at" column for the builder.
     *
     * 获取构建器的“删除at”列
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getDeletedAtColumn(Builder $builder)
    {
        //         获取底层查询生成器实例
        if (count($builder->getQuery()->joins) > 0) {
            //              获取被查询的模型实例   获得完全符合条件的“删除”列
            return $builder->getModel()->getQualifiedDeletedAtColumn();
        }
        //        获取被查询的模型实例   获取构建器的“删除at”列
        return $builder->getModel()->getDeletedAtColumn();
    }

    /**
     * Add the restore extension to the builder.
     *
     * 将恢复扩展添加到构建器中
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addRestore(Builder $builder)
    {
        //注册一个自定义宏
        $builder->macro('restore', function (Builder $builder) {
            //向构建器添加未添加的扩展名
            $builder->withTrashed();
            //更新数据库中的记录              获取被查询的模型实例   获取构建器的“删除at”列
            return $builder->update([$builder->getModel()->getDeletedAtColumn() => null]);
        });
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * 向构建器添加未添加的扩展名
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithTrashed(Builder $builder)
    {
        //注册一个自定义宏
        $builder->macro('withTrashed', function (Builder $builder) {
            //             移除注册的全局作用域
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     *
     * 添加without-trashed扩展生成器
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithoutTrashed(Builder $builder)
    {
        //注册一个自定义宏
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();//获取被查询的模型实例
            //         移除注册的全局作用域              向查询添加“where null”子句
            $builder->withoutGlobalScope($this)->whereNull(
                // 获得完全符合条件的“删除”列
                $model->getQualifiedDeletedAtColumn()
            );

            return $builder;
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * 添加only-trashed扩展生成器
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        //注册一个自定义宏
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();//获取被查询的模型实例
            //         移除注册的全局作用域            向查询添加“where not null”子句
            $builder->withoutGlobalScope($this)->whereNotNull(
            // 获得完全符合条件的“删除”列
                $model->getQualifiedDeletedAtColumn()
            );

            return $builder;
        });
    }
}
