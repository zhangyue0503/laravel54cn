<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

trait InteractsWithPivotTable
{
    /**
     * Toggles a model (or models) from the parent.
     *
     * 切换模式(或模型)的父母
     *
     * Each existing model is detached, and non existing ones are attached.
     *
     * 每个现有的模型都是分离的，而非现有的模型是附加的
     *
     * @param  mixed  $ids
     * @param  bool   $touch
     * @return array
     */
    public function toggle($ids, $touch = true)
    {
        $changes = [
            'attached' => [], 'detached' => [],
        ];
        //格式化同步/切换记录列表，以使其按ID键(从给定的混合值中获取所有的id)
        $records = $this->formatRecordsList((array) $this->parseIds($ids));

        // Next, we will determine which IDs should get removed from the join table by
        // checking which of the given ID/records is in the list of current records
        // and removing all of those rows from this "intermediate" joining table.
        //
        // 接下来,我们将决定哪些ID应该被检查从连接表中删除给定ID /记录的是当前列表中的记录和删除所有这些从这个“中间”加入表行
        //
        $detach = array_values(array_intersect(
            //为pivot表创建一个新的查询构建器->为pivot表创建一个新的查询构建器->获取集合中的所有项目
            $this->newPivotQuery()->pluck($this->relatedKey)->all(),
            array_keys($records)
        ));

        if (count($detach) > 0) {
            //从关系中分离模型
            $this->detach($detach, false);
            //将给定的键值转换为整数，如果它们是数值或字符串
            $changes['detached'] = $this->castKeys($detach);
        }

        // Finally, for all of the records which were not "detached", we'll attach the
        // records into the intermediate table. Then, we will add those attaches to
        // this change list and get ready to return these results to the callers.
        //
        // 最后，对于所有没有“分离”的记录，我们将把记录附加到中间表中
        // 然后，我们将添加这些附加到这个更改列表，并准备将这些结果返回给调用者
        //
        $attach = array_diff_key($records, array_flip($detach));

        if (count($attach) > 0) {
            //向父节点添加一个模型
            $this->attach($attach, [], false);

            $changes['attached'] = array_keys($attach);
        }

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        //
        // 一旦我们完成添加或移除的记录,我们将看到如果我们所做的任何添加或移除,如果我们有,我们将联系这些关系是否涉及任何数据库更新配置
        //
        if ($touch && (count($changes['attached']) ||
                       count($changes['detached']))) {
            $this->touchIfTouching();//如果我们接触到父模型，触摸
        }

        return $changes;
    }

    /**
     * Sync the intermediate tables with a list of IDs without detaching.
     *
     * 将中间表与一个id列表同步，而不需要分离
     *
     * @param  \Illuminate\Database\Eloquent\Collection|array  $ids
     * @return array
     */
    public function syncWithoutDetaching($ids)
    {
        //将中间表与一个IDs列表或模型集合同步
        return $this->sync($ids, false);
    }

    /**
     * Sync the intermediate tables with a list of IDs or collection of models.
     *
     * 将中间表与一个IDs列表或模型集合同步
     *
     * @param  \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|array  $ids
     * @param  bool   $detaching
     * @return array
     */
    public function sync($ids, $detaching = true)
    {
        $changes = [
            'attached' => [], 'detached' => [], 'updated' => [],
        ];

        // First we need to attach any of the associated models that are not currently
        // in this joining table. We'll spin through the given IDs, checking to see
        // if they exist in the array of current ones, and if not we will insert.
        //
        // 首先，我们需要附加任何当前不在这个连接表中的关联模型
        // 我们将对给定的id进行旋转，检查它们是否存在于当前数组中，如果不这样，我们将插入
        //
        //          为pivot表创建一个新的查询构建器->用给定列的值获取数组
        $current = $this->newPivotQuery()->pluck(
            $this->relatedKey
        )->all();//获取集合中的所有项目

        $detach = array_diff($current, array_keys(
            //             格式化同步/切换记录列表，以使其按ID键(从给定的混合值中获取所有的id)
            $records = $this->formatRecordsList($this->parseIds($ids))
        ));

        // Next, we will take the differences of the currents and given IDs and detach
        // all of the entities that exist in the "current" array but are not in the
        // array of the new IDs given to the method which will complete the sync.
        //
        // 接下来,我们将采取不同的电流和给定id和分离所有的实体中存在的“当前”数组的数组但不新id的方法将完成同步
        //
        if ($detaching && count($detach) > 0) {
            $this->detach($detach);//从关系中分离模型
            //将给定的键值转换为整数，如果它们是数值或字符串
            $changes['detached'] = $this->castKeys($detach);
        }

        // Now we are finally ready to attach the new records. Note that we'll disable
        // touching until after the entire operation is complete so we don't fire a
        // ton of touch operations until we are totally done syncing the records.
        //
        // 现在，我们终于准备好添加新的记录了
        // 请注意，在整个操作完成之后，我们将禁用触摸，因此我们不会触发大量的触摸操作，直到我们完全完成了同步记录
        //
        $changes = array_merge(
            $changes, $this->attachNew($records, $current, false)//将当前记录中没有的所有记录附加到
        );

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        //
        // 一旦我们完成添加或移除的记录,我们将看到如果我们所做的任何添加或移除,如果我们有,我们将联系这些关系是否涉及任何数据库更新配置
        //
        if (count($changes['attached']) ||
            count($changes['updated'])) {
            $this->touchIfTouching();//如果我们接触到父模型，触摸
        }

        return $changes;
    }

    /**
     * Format the sync / toggle record list so that it is keyed by ID.
     *
     * 格式化同步/切换记录列表，以使其按ID键
     *
     * @param  array  $records
     * @return array
     */
    protected function formatRecordsList(array $records)
    {
        //                           在每个项目上运行关联映射
        return collect($records)->mapWithKeys(function ($attributes, $id) {
            if (! is_array($attributes)) {
                list($id, $attributes) = [$attributes, []];
            }

            return [$id => $attributes];
        })->all();//获取集合中的所有项目
    }

    /**
     * Attach all of the records that aren't in the given current records.
     *
     * 将当前记录中没有的所有记录附加到
     *
     * @param  array  $records
     * @param  array  $current
     * @param  bool   $touch
     * @return array
     */
    protected function attachNew(array $records, array $current, $touch = true)
    {
        $changes = ['attached' => [], 'updated' => []];

        foreach ($records as $id => $attributes) {
            // If the ID is not in the list of existing pivot IDs, we will insert a new pivot
            // record, otherwise, we will just update this existing record on this joining
            // table, so that the developers will easily update these records pain free.
            //
            // 如果ID不是在现有主ID列表,我们将插入一个新的主记录,否则,我们将会更新现有的记录在这个加入表,以便开发人员轻松地更新这些记录疼痛免费
            //
            if (! in_array($id, $current)) {
                //向父节点添加一个模型
                $this->attach($id, $attributes, $touch);
                //如果是数字，则将给定的键转换为整数
                $changes['attached'][] = $this->castKey($id);
            }

            // Now we'll try to update an existing pivot record with the attributes that were
            // given to the method. If the model is actually updated we will add it to the
            // list of updated pivot records so we return them back out to the consumer.
            //
            // 现在，我们将尝试使用赋予该方法的属性来更新一个已存在的轴心记录
            // 如果模型实际更新了，我们将把它添加到更新的主记录列表中，这样我们就可以将它们返回给消费者
            //
            elseif (count($attributes) > 0 &&
                //更新表中现有的主数据记录
                $this->updateExistingPivot($id, $attributes, $touch)) {
                //如果是数字，则将给定的键转换为整数
                $changes['updated'][] = $this->castKey($id);
            }
        }

        return $changes;
    }

    /**
     * Update an existing pivot record on the table.
     *
     * 更新表中现有的主数据记录
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return int
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        //获取“更新at”列的名称
        if (in_array($this->updatedAt(), $this->pivotColumns)) {
            //在附加记录上设置创建和更新时间戳
            $attributes = $this->addTimestampsToAttachment($attributes, true);
        }
        //为给定的“其他”ID获取一个新的轴心语句->更新数据库中的记录
        $updated = $this->newPivotStatementForId($id)->update($attributes);

        if ($touch) {
            $this->touchIfTouching();//如果我们接触到父模型，触摸
        }

        return $updated;
    }

    /**
     * Attach a model to the parent.
     *
     * 向父节点添加一个模型
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return void
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        // Here we will insert the attachment records into the pivot table. Once we have
        // inserted the records, we will touch the relationships if necessary and the
        // function will return. We can parse the IDs before inserting the records.
        //
        // 在这里，我们将把附件记录插入到主表中
        // 一旦我们插入了记录，我们就会在必要的时候接触到这些关系，并且函数会返回
        // 在插入记录之前，我们可以解析这些id
        //
        // 为pivot表获取一个新的普通查询构建器->将新记录插入数据库(创建一个记录数组，插入到pivot表中(从给定的混合值中获取所有的id))
        $this->newPivotStatement()->insert($this->formatAttachRecords(
            (array) $this->parseIds($id), $attributes
        ));

        if ($touch) {
            $this->touchIfTouching();//如果我们接触到父模型，触摸
        }
    }

    /**
     * Create an array of records to insert into the pivot table.
     *
     * 创建一个记录数组，插入到pivot表中
     *
     * @param  array  $ids
     * @param  array  $attributes
     * @return array
     */
    protected function formatAttachRecords($ids, array $attributes)
    {
        $records = [];
        //确定给定的列是否被定义为一个主列(获取“创建at”列的名称)
        $hasTimestamps = ($this->hasPivotColumn($this->createdAt()) ||
                  $this->hasPivotColumn($this->updatedAt()));//确定给定的列是否被定义为一个主列(获取“更新at”列的名称)

        // To create the attachment records, we will simply spin through the IDs given
        // and create a new record to insert for each ID. Each ID may actually be a
        // key in the array, with extra attributes to be placed in other columns.
        //
        // 创建附件记录,我们将简单地旋转通过给出的ID和为每个ID
        // 创建新记录插入数组中的每个ID实际上可能是一个关键,提供额外的属性被放置在其他列
        //
        foreach ($ids as $key => $value) {
            //创建一个完整的附件记录有效负载
            $records[] = $this->formatAttachRecord(
                $key, $value, $attributes, $hasTimestamps
            );
        }

        return $records;
    }

    /**
     * Create a full attachment record payload.
     *
     * 创建一个完整的附件记录有效负载
     *
     * @param  int    $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @param  bool   $hasTimestamps
     * @return array
     */
    protected function formatAttachRecord($key, $value, $attributes, $hasTimestamps)
    {
        //获取附加记录ID和额外属性
        list($id, $attributes) = $this->extractAttachIdAndAttributes($key, $value, $attributes);

        return array_merge(
            //创建一个新的主连接记录
            $this->baseAttachRecord($id, $hasTimestamps), $attributes
        );
    }

    /**
     * Get the attach record ID and extra attributes.
     *
     * 获取附加记录ID和额外属性
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    protected function extractAttachIdAndAttributes($key, $value, array $attributes)
    {
        return is_array($value)
                    ? [$key, array_merge($value, $attributes)]
                    : [$value, $attributes];
    }

    /**
     * Create a new pivot attachment record.
     *
     * 创建一个新的主连接记录
     *
     * @param  int   $id
     * @param  bool  $timed
     * @return array
     */
    protected function baseAttachRecord($id, $timed)
    {
        $record[$this->relatedKey] = $id;

        $record[$this->foreignKey] = $this->parent->getKey();

        // If the record needs to have creation and update timestamps, we will make
        // them by calling the parent model's "freshTimestamp" method which will
        // provide us with a fresh timestamp in this model's preferred format.
        //
        // 如果需要创建和更新时间戳记录,我们将让他们通过调用父模型的“freshTimestamp”方法将为我们提供一个新的时间戳在这个模型的首选格式
        //
        if ($timed) {
            $record = $this->addTimestampsToAttachment($record);//在附加记录上设置创建和更新时间戳
        }

        return $record;
    }

    /**
     * Set the creation and update timestamps on an attach record.
     *
     * 在附加记录上设置创建和更新时间戳
     *
     * @param  array  $record
     * @param  bool   $exists
     * @return array
     */
    protected function addTimestampsToAttachment(array $record, $exists = false)
    {
        $fresh = $this->parent->freshTimestamp();
        //确定给定的列是否被定义为一个主列(获取“创建at”列的名称)
        if (! $exists && $this->hasPivotColumn($this->createdAt())) {
            $record[$this->createdAt()] = $fresh;
        }
        //确定给定的列是否被定义为一个主列(获取“更新at”列的名称)
        if ($this->hasPivotColumn($this->updatedAt())) {
            $record[$this->updatedAt()] = $fresh;
        }

        return $record;
    }

    /**
     * Determine whether the given column is defined as a pivot column.
     *
     * 确定给定的列是否被定义为一个主列
     *
     * @param  string  $column
     * @return bool
     */
    protected function hasPivotColumn($column)
    {
        return in_array($column, $this->pivotColumns);
    }

    /**
     * Detach models from the relationship.
     *
     * 从关系中分离模型
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     */
    public function detach($ids = null, $touch = true)
    {
        //为pivot表创建一个新的查询构建器
        $query = $this->newPivotQuery();

        // If associated IDs were passed to the method we will only delete those
        // associations, otherwise all of the association ties will be broken.
        // We'll return the numbers of affected rows when we do the deletes.
        //
        // 如果关联的id被传递给该方法，我们只会删除这些关联，否则所有的关联关系将被破坏
        // 当我们执行删除操作时，我们将返回受影响的行数
        //
        //                        从给定的混合值中获取所有的id
        if (! is_null($ids = $this->parseIds($ids))) {
            if (count($ids) === 0) {
                return 0;
            }
            //在查询中添加“where in”子句
            $query->whereIn($this->relatedKey, (array) $ids);
        }

        // Once we have all of the conditions set on the statement, we are ready
        // to run the delete on the pivot table. Then, if the touch parameter
        // is true, we will go ahead and touch all related models to sync.
        //
        // 一旦我们有了语句中设置的所有条件，我们就可以在pivot表上运行delete了
        // 然后，如果触摸参数为真，我们将继续触摸所有相关的模型以同步
        //
        //            从数据库中删除记录
        $results = $query->delete();

        if ($touch) {
            $this->touchIfTouching();//如果我们接触到父模型，触摸
        }

        return $results;
    }

    /**
     * Create a new pivot model instance.
     *
     * 创建一个新的轴心模型实例
     *
     * @param  array  $attributes
     * @param  bool   $exists
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(array $attributes = [], $exists = false)
    {
        $pivot = $this->related->newPivot(
            $this->parent, $attributes, $this->table, $exists, $this->using
        );

        return $pivot->setPivotKeys($this->foreignKey, $this->relatedKey);
    }

    /**
     * Create a new existing pivot model instance.
     *
     * 创建一个新的主模型实例实例
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newExistingPivot(array $attributes = [])
    {
        //创建一个新的轴心模型实例
        return $this->newPivot($attributes, true);
    }

    /**
     * Get a new plain query builder for the pivot table.
     *
     * 为pivot表获取一个新的普通查询构建器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newPivotStatement()
    {
        return $this->query->getQuery()->newQuery()->from($this->table);
    }

    /**
     * Get a new pivot statement for a given "other" ID.
     *
     * 为给定的“其他”ID获取一个新的轴心语句
     *
     * @param  mixed  $id
     * @return \Illuminate\Database\Query\Builder
     */
    public function newPivotStatementForId($id)
    {
        //为pivot表创建一个新的查询构建器->将基本WHERE子句添加到查询中
        return $this->newPivotQuery()->where($this->relatedKey, $id);
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * 为pivot表创建一个新的查询构建器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotQuery()
    {
        //为pivot表获取一个新的普通查询构建器
        $query = $this->newPivotStatement();

        foreach ($this->pivotWheres as $arguments) {
            call_user_func_array([$query, 'where'], $arguments);
        }

        foreach ($this->pivotWhereIns as $arguments) {
            call_user_func_array([$query, 'whereIn'], $arguments);
        }
        //将基本WHERE子句添加到查询中
        return $query->where($this->foreignKey, $this->parent->getKey());
    }

    /**
     * Set the columns on the pivot table to retrieve.
     *
     * 将主表上的列设置为检索
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function withPivot($columns)
    {
        $this->pivotColumns = array_merge(
            $this->pivotColumns, is_array($columns) ? $columns : func_get_args()
        );

        return $this;
    }

    /**
     * Get all of the IDs from the given mixed value.
     *
     * 从给定的混合值中获取所有的id
     *
     * @param  mixed  $value
     * @return array
     */
    protected function parseIds($value)
    {
        if ($value instanceof Model) {
            return $value->getKey();//获取模型主键的值
        }

        if ($value instanceof Collection) {
            return $value->modelKeys();//获取主键的数组
        }

        if ($value instanceof BaseCollection) {
            return $value->toArray();//将项目的集合作为一个简单的数组
        }

        return $value;
    }

    /**
     * Cast the given keys to integers if they are numeric and string otherwise.
     *
     * 将给定的键值转换为整数，如果它们是数值或字符串
     *
     * @param  array  $keys
     * @return array
     */
    protected function castKeys(array $keys)
    {
        return (array) array_map(function ($v) {
            return $this->castKey($v);//如果是数字，则将给定的键转换为整数
        }, $keys);
    }

    /**
     * Cast the given key to an integer if it is numeric.
     *
     * 如果是数字，则将给定的键转换为整数
     *
     * @param  mixed  $key
     * @return mixed
     */
    protected function castKey($key)
    {
        return is_numeric($key) ? (int) $key : (string) $key;
    }
}
