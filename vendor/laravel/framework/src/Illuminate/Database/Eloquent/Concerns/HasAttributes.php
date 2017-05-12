<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Carbon\Carbon;
use LogicException;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\JsonEncodingException;

trait HasAttributes
{
    /**
     * The model's attributes.
     *
     * 模型属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The model attribute's original state.
     *
     * 模型属性的原始状态
     *
     * @var array
     */
    protected $original = [];

    /**
     * The attributes that should be cast to native types.
     *
     * 应该将这些属性转换为原生类型
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * 应该对日期进行突变的属性
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The storage format of the model's date columns.
     *
     * 模型的日期列的存储格式
     *
     * @var string
     */
    protected $dateFormat;

    /**
     * The accessors to append to the model's array form.
     *
     * 访问模型的数组形式的访问器
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Indicates whether attributes are snake cased on arrays.
     *
     * 表示属性是否为数组的蛇套
     *
     * @var bool
     */
    public static $snakeAttributes = true;

    /**
     * The cache of the mutated attributes for each class.
     *
     * 每个类的突变属性的缓存
     *
     * @var array
     */
    protected static $mutatorCache = [];

    /**
     * Convert the model's attributes to an array.
     *
     * 将模型的属性转换为数组
     *
     * @return array
     */
    public function attributesToArray()
    {
        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
        //
        // 如果一个属性是一个日期，我们将把它转换为一个字符串，然后将它转换为一个DateTime/Carbon实例
        // 这是我们会得到一些一致的格式在访问属性和裁剪/ json模型
        //
        //                 将日期属性添加到属性数组中
        $attributes = $this->addDateAttributesToArray(
            //获取所有可arrayable属性的属性数组
            $attributes = $this->getArrayableAttributes()
        );
        //将突变属性添加到属性数组中
        $attributes = $this->addMutatedAttributesToArray(
            //                                   获取给定实例的突变属性
            $attributes, $mutatedAttributes = $this->getMutatedAttributes()
        );

        // Next we will handle any casts that have been setup for this model and cast
        // the values to their appropriate type. If the attribute has a mutator we
        // will not perform the cast on those attributes to avoid any confusion.
        //
        // 接下来，我们将处理为该模型设置的任何类型的转换，并将这些值转换为相应的类型
        // 如果属性有一个mutator，我们就不会在这些属性上执行转换，以避免任何混淆
        //
        //                 将款待属性添加到属性数组中
        $attributes = $this->addCastAttributesToArray(
            $attributes, $mutatedAttributes
        );

        // Here we will grab all of the appended, calculated attributes to this model
        // as these attributes are not really in the attributes array, but are run
        // when we need to array or JSON the model for convenience to the coder.
        //
        // 这里我们将抓住所有的附加,这个模型计算属性,因为这些并不是真正的属性数组,但是当我们运行需要数组或JSON编码器模型为了方便
        //
        //             获取所有可折的值，这些值都是可数组的
        foreach ($this->getArrayableAppends() as $key) {
            //                      使用用于数组转换的mutator方法获取属性的值
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

    /**
     * Add the date attributes to the attributes array.
     *
     * 将日期属性添加到属性数组中
     *
     * @param  array  $attributes
     * @return array
     */
    protected function addDateAttributesToArray(array $attributes)
    {
        //获取应该转换为日期的属性
        foreach ($this->getDates() as $key) {
            if (! isset($attributes[$key])) {
                continue;
            }
            //                   为数组/JSON序列化准备一个日期
            $attributes[$key] = $this->serializeDate(
                //返回时间戳作为DateTime对象
                $this->asDateTime($attributes[$key])
            );
        }

        return $attributes;
    }

    /**
     * Add the mutated attributes to the attributes array.
     *
     * 将突变属性添加到属性数组中
     *
     * @param  array  $attributes
     * @param  array  $mutatedAttributes
     * @return array
     */
    protected function addMutatedAttributesToArray(array $attributes, array $mutatedAttributes)
    {
        foreach ($mutatedAttributes as $key) {
            // We want to spin through all the mutated attributes for this model and call
            // the mutator for the attribute. We cache off every mutated attributes so
            // we don't have to constantly check on attributes that actually change.
            //
            // 我们想要为这个模型旋转所有的突变属性，并为这个属性调用mutator
            // 我们缓存了所有的突变属性，这样我们就不必不断地检查那些真正变化的属性
            //
            if (! array_key_exists($key, $attributes)) {
                continue;
            }

            // Next, we will call the mutator for this attribute so that we can get these
            // mutated attribute's actual values. After we finish mutating each of the
            // attributes we will return this final array of the mutated attributes.
            //
            // 接下来，我们将为这个属性调用mutator，这样我们就可以得到这些突变属性的实际值
            // 在我们完成对每个属性的修改之后，我们将返回最后一个突变属性的数组
            //
            //                     使用用于数组转换的mutator方法获取属性的值
            $attributes[$key] = $this->mutateAttributeForArray(
                $key, $attributes[$key]
            );
        }

        return $attributes;
    }

    /**
     * Add the casted attributes to the attributes array.
     *
     * 将款待属性添加到属性数组中
     *
     * @param  array  $attributes
     * @param  array  $mutatedAttributes
     * @return array
     */
    protected function addCastAttributesToArray(array $attributes, array $mutatedAttributes)
    {
        //得到强制转换数组
        foreach ($this->getCasts() as $key => $value) {
            if (! array_key_exists($key, $attributes) || in_array($key, $mutatedAttributes)) {
                continue;
            }

            // Here we will cast the attribute. Then, if the cast is a date or datetime cast
            // then we will serialize the date for the array. This will convert the dates
            // to strings based on the date format specified for these Eloquent models.
            //
            // 在这里我们将转换属性
            // 然后，如果cast是一个日期或日期时间序列，那么我们将序列化该数组的日期
            // 这将根据这些Eloquent的模型指定的日期格式将日期转换为字符串
            //
            //                        将一个属性转换为一个本地PHP类型
            $attributes[$key] = $this->castAttribute(
                $key, $attributes[$key]
            );

            // If the attribute cast was a date or a datetime, we will serialize the date as
            // a string. This allows the developers to customize hwo dates are serialized
            // into an array without affecting how they are persisted into the storage.
            //
            // 如果属性转换为日期或日期时间，我们将把日期序列化为字符串
            // 这允许开发人员定制hwo日期，并将其序列化为一个数组，而不会影响如何将它们持久化到存储中
            //
            if ($attributes[$key] &&
                ($value === 'date' || $value === 'datetime')) {
                //                  为数组/JSON序列化准备一个日期
                $attributes[$key] = $this->serializeDate($attributes[$key]);
            }
        }

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable attributes.
     *
     * 获取所有可arrayable属性的属性数组
     *
     * @return array
     */
    protected function getArrayableAttributes()
    {
        //获取所有可数组值的属性数组
        return $this->getArrayableItems($this->attributes);
    }

    /**
     * Get all of the appendable values that are arrayable.
     *
     * 获取所有可折的值，这些值都是可数组的
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (! count($this->appends)) {
            return [];
        }
        //             获取所有可数组值的属性数组
        return $this->getArrayableItems(
            array_combine($this->appends, $this->appends)
        );
    }

    /**
     * Get the model's relationships in array form.
     *
     * 以数组形式获取模型的关系
     *
     * @return array
     */
    public function relationsToArray()
    {
        $attributes = [];
        //获取所有可数组关系的属性数组
        foreach ($this->getArrayableRelations() as $key => $value) {
            // If the values implements the Arrayable interface we can just call this
            // toArray method on the instances which will convert both models and
            // collections to their proper array form and we'll set the values.
            //
            // 如果值实现了Arrayable接口，我们可以在实例上调用toArray方法，该方法将把模型和集合转换为正确的数组形式，我们将设置值
            //
            if ($value instanceof Arrayable) {
                $relation = $value->toArray();//获取数组实例
            }

            // If the value is null, we'll still go ahead and set it in this list of
            // attributes since null is used to represent empty relationships if
            // if it a has one or belongs to type relationships on the models.
            //
            // 如果该值为null,我们仍然会继续,在这个属性列表自零用于表示如果如果它有一个空的关系或属于类型关系的模型
            //
            elseif (is_null($value)) {
                $relation = $value;
            }

            // If the relationships snake-casing is enabled, we will snake case this
            // key so that the relation attribute is snake cased in this returned
            // array to the developers, making this consistent with attributes.
            //
            // 如果启用了snake-casing的关系,我们将蛇情况下关键属性是蛇的关系下套管在这个数组返回到开发人员,这与属性一致
            //
            if (static::$snakeAttributes) {
                $key = Str::snake($key);//将字符串转换为蛇形命名
            }

            // If the relation value has been set, we will set it on this attributes
            // list for returning. If it was not arrayable or null, we'll not set
            // the value on the array because it is some type of invalid value.
            //
            // 如果已经设置了关系值，我们将在这个属性列表中设置返回值
            // 如果它不是数组或null，我们就不会在数组上设置值，因为它是某种无效值
            //
            if (isset($relation) || is_null($value)) {
                $attributes[$key] = $relation;
            }

            unset($relation);
        }

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable relations.
     *
     * 获取所有可数组关系的属性数组
     *
     * @return array
     */
    protected function getArrayableRelations()
    {
        //获取所有可数组值的属性数组
        return $this->getArrayableItems($this->relations);
    }

    /**
     * Get an attribute array of all arrayable values.
     *
     * 获取所有可数组值的属性数组
     *
     * @param  array  $values
     * @return array
     */
    protected function getArrayableItems(array $values)
    {
        //获取模型的可见属性
        if (count($this->getVisible()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisible()));
        }
        //获取模型的隐藏属性
        if (count($this->getHidden()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHidden()));
        }

        return $values;
    }

    /**
     * Get an attribute from the model.
     *
     * 从模型中获取属性
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (! $key) {
            return;
        }

        // If the attribute exists in the attribute array or has a "get" mutator we will
        // get the attribute's value. Otherwise, we will proceed as if the developers
        // are asking for a relationship's value. This covers both types of values.
        //
        // 如果属性存在于属性数组或有“获得”突变,我们将获取属性的值
        // 否则，我们将继续进行，就好像开发人员要求关系的价值一样。这涵盖了两种类型的值
        //
        if (array_key_exists($key, $this->attributes) ||
            //确定是否得到一个属性赋值的存在
            $this->hasGetMutator($key)) {
            //获得一个简单的属性(不是关系)
            return $this->getAttributeValue($key);
        }

        // Here we will determine if the model base class itself contains this given key
        // since we do not want to treat any of those methods are relationships since
        // they are all intended as helper methods and none of these are relations.
        //
        // 这里我们将决定如果模型基类本身就含有这给定的关键,因为我们不想把这些方法的关系,因为他们都是作为辅助方法,这些都没有关系
        //
        if (method_exists(self::class, $key)) {
            return;
        }
        //获取关系
        return $this->getRelationValue($key);
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * 获得一个简单的属性(不是关系)
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        //从$attributes数组获得一个属性
        $value = $this->getAttributeFromArray($key);

        // If the attribute has a get mutator, we will call that then return what
        // it returns as the value, which is useful for transforming values on
        // retrieval from the model to a form that is more useful for usage.
        //
        // 如果属性有一个突变,我们将称之为然后返回它所返回的值,它是用于转换值的检索模型,一种是使用更有用
        //
        //         确定是否得到一个属性赋值的存在
        if ($this->hasGetMutator($key)) {
            //使用它的mutator获取属性的值
            return $this->mutateAttribute($key, $value);
        }

        // If the attribute exists within the cast array, we will convert it to
        // an appropriate native PHP type dependant upon the associated value
        // given with the key in the pair. Dayle made this comment line up.
        //
        // 如果这个属性存在于cast数组中，那么我们将把它转换为一个适当的本机PHP类型，它依赖于与这一对中的键所关联的值
        // Dayle发表了这一评论
        //
        //          确定属性是否应被转换为本机类型
        if ($this->hasCast($key)) {
            //        将一个属性转换为一个本地PHP类型
            return $this->castAttribute($key, $value);
        }

        // If the attribute is listed as a date, we will convert it to a DateTime
        // instance on retrieval, which makes it quite convenient to work with
        // date fields without having to create a mutator for each property.
        //
        // 如果属性被列为一个日期,我们将它转换为一个DateTime实例检索,这使得它非常方便使用日期字段,而不必为每个属性创建一个突变
        //
        //                  获取应该转换为日期的属性
        if (in_array($key, $this->getDates()) &&
            ! is_null($value)) {
            //返回时间戳作为DateTime对象
            return $this->asDateTime($value);
        }

        return $value;
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * 从$attributes数组获得一个属性
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
    }

    /**
     * Get a relationship.
     *
     * 获取关系
     *
     * @param  string  $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        //
        // 如果键已经存在于数组的关系,这就意味着已经加载的关系,所以我们就返回它出去,因为没有需要在关系查询两次
        //
        //         确定给定的关系是否已加载
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        //
        // 如果“属性”的存在是一个方法的模型,我们将假设它是一个关系,将负载和返回结果的查询和水合物的关系对“关系”的值数组
        //
        if (method_exists($this, $key)) {
            //          从方法中获得一个关系值
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * Get a relationship value from a method.
     *
     * 从方法中获得一个关系值
     *
     * @param  string  $method
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod($method)
    {
        $relation = $this->$method();

        if (! $relation instanceof Relation) {
            throw new LogicException('Relationship method must return an object of type '
                .'Illuminate\Database\Eloquent\Relations\Relation');
        }
        //用给定的值调用给定的闭包，然后返回值   得到关系的结果
        return tap($relation->getResults(), function ($results) use ($method) {
            //在模型中设置特定的关系
            $this->setRelation($method, $results);
        });
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * 确定是否得到一个属性赋值的存在
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        //                               将值转换为大驼峰
        return method_exists($this, 'get'.Str::studly($key).'Attribute');
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * 使用它的mutator获取属性的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        //                     将值转换为大驼峰
        return $this->{'get'.Str::studly($key).'Attribute'}($value);
    }

    /**
     * Get the value of an attribute using its mutator for array conversion.
     *
     * 使用用于数组转换的mutator方法获取属性的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttributeForArray($key, $value)
    {
        //使用它的mutator获取属性的值
        $value = $this->mutateAttribute($key, $value);
        //                                    获取数组实例
        return $value instanceof Arrayable ? $value->toArray() : $value;
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * 将一个属性转换为一个本地PHP类型
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }
        //获取模型属性的类型
        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                //将给定的JSON解码回数组或对象中
                return $this->fromJson($value, true);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            case 'collection':
                return new BaseCollection($this->fromJson($value));
            case 'date':
                //返回一个时间戳作为就是DateTime对象随时间设置
                return $this->asDate($value);
            case 'datetime':
                //返回时间戳作为DateTime对象
                return $this->asDateTime($value);
            case 'timestamp':
                //返回时间戳作为unix时间戳
                return $this->asTimestamp($value);
            default:
                return $value;
        }
    }

    /**
     * Get the type of cast for a model attribute.
     *
     * 获取模型属性的类型
     *
     * @param  string  $key
     * @return string
     */
    protected function getCastType($key)
    {
        //                        得到强制转换数组
        return trim(strtolower($this->getCasts()[$key]));
    }

    /**
     * Set a given attribute on the model.
     *
     * 在模型上设置给定属性
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        //
        // 首先,我们将检查是否存在突变为一组操作的简单让开发人员调整属性上设置的模式,比如“json_encode”一个清单的数据存储
        //
        //    确定一个属性是否存在一个属性
        if ($this->hasSetMutator($key)) {
            //                  将值转换为大驼峰
            $method = 'set'.Str::studly($key).'Attribute';

            return $this->{$method}($value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        //
        // 如果一个属性被列为“日期”,我们将从一个DateTime实例转换成一种适合存储在数据库表上使用连接语法的日期格式
        // 我们将自动设置这些值
        //
        //                     确定给定的属性是否为日期或日期
        elseif ($value && $this->isDateAttribute($key)) {
            //            DateTime转换为存储字符串
            $value = $this->fromDateTime($value);
        }
        //确定一个值是否是用于入站操作的JSON
        if ($this->isJsonCastable($key) && ! is_null($value)) {
            //             将给定的属性转换为JSON
            $value = $this->castAttributeAsJson($key, $value);
        }

        // If this attribute contains a JSON ->, we'll set the proper value in the
        // attribute's underlying array. This takes care of properly nesting an
        // attribute in the array's value in the case of deeply nested items.
        //
        // 如果这个属性包含一个JSON，那么我们将在属性的底层数组中设置适当的值
        // 这样就可以在嵌套的项目中正确地嵌套一个属性值
        //
        // 确定一个给定的字符串包含另一个字符串
        if (Str::contains($key, '->')) {
            //              在模型上设置一个给定的JSON属性
            return $this->fillJsonAttribute($key, $value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Determine if a set mutator exists for an attribute.
     *
     * 确定一个属性是否存在一个属性
     *
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        //                               将值转换为大驼峰
        return method_exists($this, 'set'.Str::studly($key).'Attribute');
    }

    /**
     * Determine if the given attribute is a date or date castable.
     *
     * 确定给定的属性是否为日期或日期
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateAttribute($key)
    {
        //                      获取应该转换为日期的属性
        return in_array($key, $this->getDates()) ||
                                    $this->isDateCastable($key);//确定一个值是日期/ DateTime浆料用于入站操作
    }

    /**
     * Set a given JSON attribute on the model.
     *
     * 在模型上设置一个给定的JSON属性
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function fillJsonAttribute($key, $value)
    {
        list($key, $path) = explode('->', $key, 2);
        //                       将给定值编码为JSON(使用给定的键和值集获得一个数组属性)
        $this->attributes[$key] = $this->asJson($this->getArrayAttributeWithValue(
            $path, $key, $value
        ));

        return $this;
    }

    /**
     * Get an array attribute with the given key and value set.
     *
     * 使用给定的键和值集获得一个数组属性
     *
     * @param  string  $path
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    protected function getArrayAttributeWithValue($path, $key, $value)
    {
        //用给定的值调用给定的闭包，然后返回值(如果没有设置一个数组属性，或者返回一个空数组
        return tap($this->getArrayAttributeByKey($key), function (&$array) use ($path, $value) {
            //如果没有给定key的方法，整个数组将被替换
            Arr::set($array, str_replace('->', '.', $path), $value);
        });
    }

    /**
     * Get an array attribute or return an empty array if it is not set.
     *
     * 如果没有设置一个数组属性，或者返回一个空数组
     *
     * @param  string  $key
     * @return array
     */
    protected function getArrayAttributeByKey($key)
    {
        return isset($this->attributes[$key]) ?
            //将给定的JSON解码回数组或对象中
                    $this->fromJson($this->attributes[$key]) : [];
    }

    /**
     * Cast the given attribute to JSON.
     *
     * 将给定的属性转换为JSON
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function castAttributeAsJson($key, $value)
    {
        //            将给定值编码为JSON
        $value = $this->asJson($value);

        if ($value === false) {
            //                      为属性创建一个新的JSON编码异常
            throw JsonEncodingException::forAttribute(
                $this, $key, json_last_error_msg()
            );
        }

        return $value;
    }

    /**
     * Encode the given value as JSON.
     *
     * 将给定值编码为JSON
     *
     * @param  mixed  $value
     * @return string
     */
    protected function asJson($value)
    {
        return json_encode($value);
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * 将给定的JSON解码回数组或对象中
     *
     * @param  string  $value
     * @param  bool  $asObject
     * @return mixed
     */
    public function fromJson($value, $asObject = false)
    {
        return json_decode($value, ! $asObject);
    }

    /**
     * Return a timestamp as DateTime object with time set to 00:00:00.
     *
     * 返回一个时间戳作为就是DateTime对象随时间设置
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon
     */
    protected function asDate($value)
    {
        //返回时间戳作为DateTime对象->重置时间
        return $this->asDateTime($value)->startOfDay();
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * 返回时间戳作为DateTime对象
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon
     */
    protected function asDateTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        //
        // 如果这个值已经是一个碳的实例，我们将会返回它。这就防止了我们必须重新实例化一个碳实例，当我们知道它已经是一个时，DateTime检查就无法实现它
        //
        if ($value instanceof Carbon) {
            return $value;
        }

         // If the value is already a DateTime instance, we will just skip the rest of
         // these checks since they will be a waste of time, and hinder performance
         // when checking the field. We will just return the DateTime right away.
        //
        // 如果该值已经是一个DateTime实例，那么我们将跳过这些检查的其余部分，因为它们将是浪费时间，并且在检查字段时阻碍性能
        // 我们将立即返回DateTime
        //
        if ($value instanceof DateTimeInterface) {
            return new Carbon(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        //
        // 如果这个值是一个整数，我们将假设它是一个UNIX时间戳的值，并从这个时间戳中格式化一个碳对象
        // 这在定义日期字段时允许灵活性，因为这里可能是UNIX时间戳
        //
        if (is_numeric($value)) {
            //从时间戳中创建一个Carbon实例
            return Carbon::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        //
        // 如果值仅仅是一年、月、日的格式，我们将从该格式实例化碳实例
        // 同样，这为数据库提供了简单的日期字段，同时仍然支持碳化转换
        //
        //          确定给定值是否为标准日期格式
        if ($this->isStandardDateFormat($value)) {
            //从特定的格式创建一个碳实例->重置时间
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        //
        // 最后,我们将假设这个日期是在默认情况下使用的格式数据库连接和使用这种格式创建碳对象返回到开发人员后,我们把它在这里
        //
        //从特定的格式创建一个碳实例
        return Carbon::createFromFormat(
            //获取数据库存储日期的格式
            $this->getDateFormat(), $value
        );
    }

    /**
     * Determine if the given value is a standard date format.
     *
     * 确定给定值是否为标准日期格式
     *
     * @param  string  $value
     * @return bool
     */
    protected function isStandardDateFormat($value)
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * DateTime转换为存储字符串
     *
     * @param  \DateTime|int  $value
     * @return string
     */
    public function fromDateTime($value)
    {
        //返回时间戳作为DateTime对象
        return $this->asDateTime($value)->format(
            //获取数据库存储日期的格式
            $this->getDateFormat()
        );
    }

    /**
     * Return a timestamp as unix timestamp.
     *
     * 返回时间戳作为unix时间戳
     *
     * @param  mixed  $value
     * @return int
     */
    protected function asTimestamp($value)
    {
        //返回时间戳作为DateTime对象
        return $this->asDateTime($value)->getTimestamp();
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * 为数组/JSON序列化准备一个日期
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        //                        获取数据库存储日期的格式
        return $date->format($this->getDateFormat());
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * 获取应该转换为日期的属性
     *
     * @return array
     */
    public function getDates()
    {
        $defaults = [static::CREATED_AT, static::UPDATED_AT];
        //确定模型是否使用时间戳
        return $this->usesTimestamps() ? array_merge($this->dates, $defaults) : $this->dates;
    }

    /**
     * Get the format for database stored dates.
     *
     * 获取数据库存储日期的格式
     *
     * @return string
     */
    protected function getDateFormat()
    {
        //                              获取模型的数据库连接
        return $this->dateFormat ?: $this->getConnection()->getQueryGrammar()->getDateFormat();
    }

    /**
     * Set the date format used by the model.
     *
     * 设置模型使用的日期格式
     *
     * @param  string  $format
     * @return $this
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Determine whether an attribute should be cast to a native type.
     *
     * 确定属性是否应被转换为本机类型
     *
     * @param  string  $key
     * @param  array|string|null  $types
     * @return bool
     */
    public function hasCast($key, $types = null)
    {
        //                            得到强制转换数组
        if (array_key_exists($key, $this->getCasts())) {
            //                           获取模型属性的类型
            return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
        }

        return false;
    }

    /**
     * Get the casts array.
     *
     * 得到强制转换数组
     *
     * @return array
     */
    public function getCasts()
    {
        //得到的值指示标识递增
        if ($this->getIncrementing()) {
            //                    从模型中获取主键                实现自动递增键类型
            return array_merge([$this->getKeyName() => $this->getKeyType()], $this->casts);
        }

        return $this->casts;
    }

    /**
     * Determine whether a value is Date / DateTime castable for inbound manipulation.
     *
     * 确定一个值是日期/ DateTime浆料用于入站操作
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateCastable($key)
    {
        //确定属性是否应被转换为本机类型
        return $this->hasCast($key, ['date', 'datetime']);
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     *
     * 确定一个值是否是用于入站操作的JSON
     *
     * @param  string  $key
     * @return bool
     */
    protected function isJsonCastable($key)
    {
        //确定属性是否应被转换为本机类型
        return $this->hasCast($key, ['array', 'json', 'object', 'collection']);
    }

    /**
     * Get all of the current attributes on the model.
     *
     * 获取模型上的所有当前属性
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the array of model attributes. No checking is done.
	 *
	 * 设置模型属性的数组
	 * 没有检查完成
	 * * 通过数组设置模型类实例的attributes属性
     *
     * @param  array  $attributes
     * @param  bool  $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if ($sync) {
            //当前模型同步原始属性
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * Get the model's original attribute values.
     *
     * 获取模型的原始属性值
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|array
     */
    public function getOriginal($key = null, $default = null)
    {
        //使用“点”符号从数组中获取一个项
        return Arr::get($this->original, $key, $default);
    }

    /**
     * Sync the original attributes with the current.
     *
     * 当前模型同步原始属性
     *
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * Sync a single original attribute with its current value.
     *
     * 将单个原始属性与当前值同步
     *
     * @param  string  $attribute
     * @return $this
     */
    public function syncOriginalAttribute($attribute)
    {
        $this->original[$attribute] = $this->attributes[$attribute];

        return $this;
    }

    /**
     * Determine if the model or given attribute(s) have been modified.
     *
     * 确定模型或特定的属性是否已经修改
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        //        获取上次同步后已更改的属性
        $dirty = $this->getDirty();

        // If no specific attributes were provided, we will just see if the dirty array
        // already contains any attributes. If it does we will just return that this
        // count is greater than zero. Else, we need to check specific attributes.
        //
        // 如果没有提供特定的属性，我们将只看这个脏数组是否已经包含了任何属性
        // 如果是这样的话，我们就会返回，这个计数大于零
        // 否则，我们需要检查特定的属性
        //
        if (is_null($attributes)) {
            return count($dirty) > 0;
        }

        $attributes = is_array($attributes)
                            ? $attributes : func_get_args();

        // Here we will spin through every attribute and see if this is in the array of
        // dirty attributes. If it is, we will return true and if we make it through
        // all of the attributes for the entire array we will return false at end.
        //
        // 在这里，我们将对每个属性进行旋转，并查看是否存在于脏属性的数组中
        // 如果是，我们将返回true，如果我们通过整个数组的所有属性，我们将返回false
        //
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the model or given attribute(s) have remained the same.
     *
     * 确定模型或给定属性是否保持不变
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isClean($attributes = null)
    {
        //确定模型或特定的属性是否已经修改
        return ! $this->isDirty(...func_get_args());
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * 获取上次同步后已更改的属性
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key] &&
                //               确定给定键的新值和旧值是否在数值上相等
                    ! $this->originalIsNumericallyEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Determine if the new and old values for a given key are numerically equivalent.
     *
     * 确定给定键的新值和旧值是否在数值上相等
     *
     * @param  string  $key
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];

        $original = $this->original[$key];

        // This method checks if the two values are numerically equivalent even if they
        // are different types. This is in case the two values are not the same type
        // we can do a fair comparison of the two values to know if this is dirty.
        //
        // 这个方法检查两个值是否相等，即使它们是不同的类型
        // 如果这两个值不是相同的类型，我们可以对这两个值进行公平比较，以确定这是否是脏的
        //
        return is_numeric($current) && is_numeric($original)
            && strcmp((string) $current, (string) $original) === 0;
    }

    /**
     * Append attributes to query when building a query.
     *
     * 在构建查询时附加属性来查询
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function append($attributes)
    {
        $this->appends = array_unique(
            array_merge($this->appends, is_string($attributes) ? func_get_args() : $attributes)
        );

        return $this;
    }

    /**
     * Set the accessors to append to model arrays.
     *
     * 将访问器添加到模型数组中
     *
     * @param  array  $appends
     * @return $this
     */
    public function setAppends(array $appends)
    {
        $this->appends = $appends;

        return $this;
    }

    /**
     * Get the mutated attributes for a given instance.
     *
     * 获取给定实例的突变属性
     *
     * @return array
     */
    public function getMutatedAttributes()
    {
        $class = static::class;

        if (! isset(static::$mutatorCache[$class])) {
            //提取并缓存类的所有突变属性
            static::cacheMutatedAttributes($class);
        }

        return static::$mutatorCache[$class];
    }

    /**
     * Extract and cache all the mutated attributes of a class.
     *
     * 提取并缓存类的所有突变属性
     *
     * @param  string  $class
     * @return void
     */
    public static function cacheMutatedAttributes($class)
    {
        //                                          获取所有的属性mutator方法         在每个项目上运行map
        static::$mutatorCache[$class] = collect(static::getMutatorMethods($class))->map(function ($match) {
            //                                           将字符串转换为蛇形命名
            return lcfirst(static::$snakeAttributes ? Str::snake($match) : $match);
        })->all();//获取集合中的所有项目
    }

    /**
     * Get all of the attribute mutator methods.
     *
     * 获取所有的属性mutator方法
     *
     * @param  mixed  $class
     * @return array
     */
    protected static function getMutatorMethods($class)
    {
        preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches);

        return $matches[1];
    }
}
