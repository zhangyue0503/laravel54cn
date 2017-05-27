<?php

namespace Illuminate\Validation\Concerns;

use DateTime;
use Countable;
use Exception;
use Throwable;
use DateTimeZone;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationData;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait ValidatesAttributes
{
    /**
     * Validate that an attribute was "accepted".
     *
     * 验证属性是否被“接受”
     *
     * This validation rule implies the attribute is "required".
     *
     * 这个验证规则意味着属性是“必需的”
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAccepted($attribute, $value)
    {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];
        //验证所需属性是否存在
        return $this->validateRequired($attribute, $value) && in_array($value, $acceptable, true);
    }

    /**
     * Validate that an attribute is an active URL.
     *
     * 确认属性是一个活动的URL
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateActiveUrl($attribute, $value)
    {
        if (! is_string($value)) {
            return false;
        }

        if ($url = parse_url($value, PHP_URL_HOST)) {
            try {
                return count(dns_get_record($url, DNS_A | DNS_AAAA)) > 0;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * "Break" on first validation fail.
     *
     * 第一次验证失败的“中断”
     *
     * Always returns true, just lets us put "bail" in rules.
     *
     * 总是返回true，让我们在规则中加入“保释”
     *
     * @return bool
     */
    protected function validateBail()
    {
        return true;
    }

    /**
     * Validate the date is before a given date.
     *
     * 验证日期是在给定日期之前
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateBefore($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'before');
        //使用操作符将给定日期与另一个日期进行比较
        return $this->compareDates($attribute, $value, $parameters, '<');
    }

    /**
     * Validate the date is before or equal a given date.
     *
     * 验证日期是在一个给定的日期之前或等于
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateBeforeOrEqual($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'before_or_equal');
        //使用操作符将给定日期与另一个日期进行比较
        return $this->compareDates($attribute, $value, $parameters, '<=');
    }

    /**
     * Validate the date is after a given date.
     *
     * 验证日期是在给定日期之后
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateAfter($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'after');
        //使用操作符将给定日期与另一个日期进行比较
        return $this->compareDates($attribute, $value, $parameters, '>');
    }

    /**
     * Validate the date is equal or after a given date.
     *
     * 验证日期是否等于或在给定日期之后
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateAfterOrEqual($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'after_or_equal');
        //使用操作符将给定日期与另一个日期进行比较
        return $this->compareDates($attribute, $value, $parameters, '>=');
    }

    /**
     * Compare a given date against another using an operator.
     *
     * 使用操作符将给定日期与另一个日期进行比较
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @param  string  $operator
     * @return bool
     */
    protected function compareDates($attribute, $value, $parameters, $operator)
    {
        if (! is_string($value) && ! is_numeric($value) && ! $value instanceof DateTimeInterface) {
            return false;
        }
        //获取属性的日期格式，如果它有一个属性
        if ($format = $this->getDateFormat($attribute)) {
            //给定两个日期/时间字符串，检查一个是否在另一个字符串后面
            return $this->checkDateTimeOrder(
                //                    获取给定属性的值
                $format, $value, $this->getValue($parameters[0]) ?: $parameters[0], $operator
            );
        }
        //                 获取日期时间戳
        if (! $date = $this->getDateTimestamp($parameters[0])) {
            $date = $this->getDateTimestamp($this->getValue($parameters[0]));
        }
        // 确定一个比较是否在给定值之间传递
        return $this->compare($this->getDateTimestamp($value), $date, $operator);
    }

    /**
     * Get the date format for an attribute if it has one.
     *
     * 获取属性的日期格式，如果它有一个属性
     *
     * @param  string  $attribute
     * @return string|null
     */
    protected function getDateFormat($attribute)
    {
        //          获取给定属性的规则及其参数
        if ($result = $this->getRule($attribute, 'DateFormat')) {
            return $result[1][0];
        }
    }

    /**
     * Get the date timestamp.
     *
     * 获取日期时间戳
     *
     * @param  mixed  $value
     * @return int
     */
    protected function getDateTimestamp($value)
    {
        return $value instanceof DateTimeInterface ? $value->getTimestamp() : strtotime($value);
    }

    /**
     * Given two date/time strings, check that one is after the other.
     *
     * 给定两个日期/时间字符串，检查一个是否在另一个字符串后面
     *
     * @param  string  $format
     * @param  string  $first
     * @param  string  $second
     * @param  string  $operator
     * @return bool
     */
    protected function checkDateTimeOrder($format, $first, $second, $operator)
    {
        //          从字符串中获取一个DateTime实例
        $first = $this->getDateTimeWithOptionalFormat($format, $first);

        $second = $this->getDateTimeWithOptionalFormat($format, $second);
        //                           确定一个比较是否在给定值之间传递
        return ($first && $second) && ($this->compare($first, $second, $operator));
    }

    /**
     * Get a DateTime instance from a string.
     *
     * 从字符串中获取一个DateTime实例
     *
     * @param  string  $format
     * @param  string  $value
     * @return \DateTime|null
     */
    protected function getDateTimeWithOptionalFormat($format, $value)
    {
        if ($date = DateTime::createFromFormat($format, $value)) {
            return $date;
        }

        try {
            return new DateTime($value);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Validate that an attribute contains only alphabetic characters.
     *
     * 确认一个属性只包含字母字符
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlpha($attribute, $value)
    {
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters, dashes, and underscores.
     *
     * 验证一个属性只包含字母数字字符、斜杠和下划线
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaDash($attribute, $value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value) > 0;
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters.
     *
     * 验证一个属性只包含字母数字字符
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaNum($attribute, $value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN]+$/u', $value) > 0;
    }

    /**
     * Validate that an attribute is an array.
     *
     * 验证一个属性是一个数组
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateArray($attribute, $value)
    {
        return is_array($value);
    }

    /**
     * Validate the size of an attribute is between a set of values.
     *
     * 验证属性的大小在一组值之间
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateBetween($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(2, $parameters, 'between');
        //          获取属性的大小
        $size = $this->getSize($attribute, $value);

        return $size >= $parameters[0] && $size <= $parameters[1];
    }

    /**
     * Validate that an attribute is a boolean.
     *
     * 验证属性是一个布尔值
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateBoolean($attribute, $value)
    {
        $acceptable = [true, false, 0, 1, '0', '1'];

        return in_array($value, $acceptable, true);
    }

    /**
     * Validate that an attribute has a matching confirmation.
     *
     * 验证属性是否具有匹配的确认
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateConfirmed($attribute, $value)
    {
        //          验证这两个属性匹配
        return $this->validateSame($attribute, $value, [$attribute.'_confirmation']);
    }

    /**
     * Validate that an attribute is a valid date.
     *
     * 确认属性是有效日期
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateDate($attribute, $value)
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if ((! is_string($value) && ! is_numeric($value)) || strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * Validate that an attribute matches a date format.
     *
     * 验证属性是否匹配日期格式
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDateFormat($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'date_format');

        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $date = DateTime::createFromFormat($parameters[0], $value);

        return $date && $date->format($parameters[0]) == $value;
    }

    /**
     * Validate that an attribute is different from another attribute.
     *
     * 验证一个属性与另一个属性不同
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDifferent($attribute, $value, $parameters)
    {
        // 需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'different');
        // 使用“点”符号从数组中获取一个项
        $other = Arr::get($this->data, $parameters[0]);

        return isset($other) && $value !== $other;
    }

    /**
     * Validate that an attribute has a given number of digits.
     *
     * 确认一个属性有一个给定的数字
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDigits($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'digits');

        return ! preg_match('/[^0-9]/', $value)
                    && strlen((string) $value) == $parameters[0];
    }

    /**
     * Validate that an attribute is between a given number of digits.
     *
     * 确认一个属性在给定的数字数字之间
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDigitsBetween($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(2, $parameters, 'digits_between');

        $length = strlen((string) $value);

        return ! preg_match('/[^0-9]/', $value)
                    && $length >= $parameters[0] && $length <= $parameters[1];
    }

    /**
     * Validate the dimensions of an image matches the given values.
     *
     * 验证图像的维数与给定的值匹配
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  array $parameters
     * @return bool
     */
    protected function validateDimensions($attribute, $value, $parameters)
    {
        //检查给定值是否为有效的文件实例
        if (! $this->isValidFileInstance($value) || ! $sizeDetails = @getimagesize($value->getRealPath())) {
            return false;
        }
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'dimensions');

        list($width, $height) = $sizeDetails;
        //将命名参数解析为$key=$value项
        $parameters = $this->parseNamedParameters($parameters);
        //测试如果给定的宽度和高度是否有任何条件
        if ($this->failsBasicDimensionChecks($parameters, $width, $height) ||
            //确定给定的参数是否失败了维度比检查
            $this->failsRatioCheck($parameters, $width, $height)) {
            return false;
        }

        return true;
    }

    /**
     * Test if the given width and height fail any conditions.
     *
     * 测试如果给定的宽度和高度是否有任何条件
     *
     * @param  array  $parameters
     * @param  int  $width
     * @param  int  $height
     * @return bool
     */
    protected function failsBasicDimensionChecks($parameters, $width, $height)
    {
        return (isset($parameters['width']) && $parameters['width'] != $width) ||
               (isset($parameters['min_width']) && $parameters['min_width'] > $width) ||
               (isset($parameters['max_width']) && $parameters['max_width'] < $width) ||
               (isset($parameters['height']) && $parameters['height'] != $height) ||
               (isset($parameters['min_height']) && $parameters['min_height'] > $height) ||
               (isset($parameters['max_height']) && $parameters['max_height'] < $height);
    }

    /**
     * Determine if the given parameters fail a dimension ratio check.
     *
     * 确定给定的参数是否失败了维度比检查
     *
     * @param  array  $parameters
     * @param  int  $width
     * @param  int  $height
     * @return bool
     */
    protected function failsRatioCheck($parameters, $width, $height)
    {
        if (! isset($parameters['ratio'])) {
            return false;
        }

        list($numerator, $denominator) = array_replace(
            [1, 1], array_filter(sscanf($parameters['ratio'], '%f/%d'))
        );

        return abs($numerator / $denominator - $width / $height) > 0.000001;
    }

    /**
     * Validate an attribute is unique among other values.
     *
     * 验证属性在其他值中是惟一的
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDistinct($attribute, $value, $parameters)
    {
        //                       获得主属性名
        $attributeName = $this->getPrimaryAttribute($attribute);
        //                             根据给定的点指定路径提取数据
        $attributeData = ValidationData::extractDataFromPath(
            //            获取属性名的显式部分
            ValidationData::getLeadingExplicitAttributePath($attributeName), $this->data
        );

        $pattern = str_replace('\*', '[^.]+', preg_quote($attributeName, '#'));
        //使用给定的回调筛选数组    用点对多维关联数组进行扁平化
        $data = Arr::where(Arr::dot($attributeData), function ($value, $key) use ($attribute, $pattern) {
            return $key != $attribute && (bool) preg_match('#^'.$pattern.'\z#u', $key);
        });

        return ! in_array($value, array_values($data));
    }

    /**
     * Validate that an attribute is a valid e-mail address.
     *
     * 确认属性是有效的电子邮件地址
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateEmail($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate the existence of an attribute value in a database table.
     *
     * 在数据库表中验证属性值的存在
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateExists($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'exists');
        //                             解析用于唯一/存在规则的连接/表
        list($connection, $table) = $this->parseTable($parameters[0]);

        // The second parameter position holds the name of the column that should be
        // verified as existing. If this parameter is not specified we will guess
        // that the columns being "verified" shares the given attribute's name.
        //
        // 第二个参数位置保留应该被验证为现有的列的名称
        // 如果这个参数没有被指定，我们将猜测被“验证”的列共享给定属性的名称
        //
        //              获取一个存在/唯一查询的列名
        $column = $this->getQueryColumn($parameters, $attribute);

        $expected = (is_array($value)) ? count($value) : 1;
        //       获取存储在存储中的记录的数量
        return $this->getExistCount(
            $connection, $table, $column, $value, $parameters
        ) >= $expected;
    }

    /**
     * Get the number of records that exist in storage.
     *
     * 获取存储在存储中的记录的数量
     *
     * @param  mixed   $connection
     * @param  string  $table
     * @param  string  $column
     * @param  mixed   $value
     * @param  array   $parameters
     * @return int
     */
    protected function getExistCount($connection, $table, $column, $value, $parameters)
    {
        //              获得存在验证器的实现
        $verifier = $this->getPresenceVerifierFor($connection);
        //          获得唯一/存在规则的额外条件
        $extra = $this->getExtraConditions(
            array_values(array_slice($parameters, 2))
        );

        if ($this->currentRule instanceof Exists) {
            //                                          获取规则的自定义查询回调
            $extra = array_merge($extra, $this->currentRule->queryCallbacks());
        }

        return is_array($value)
        //              用给定的值计算一个集合中的对象的数量
                ? $verifier->getMultiCount($table, $column, $value, $extra)
            //计算具有给定值的集合中的对象的数量
                : $verifier->getCount($table, $column, $value, null, null, $extra);
    }

    /**
     * Validate the uniqueness of an attribute value on a given database table.
     *
     * 在给定的数据库表中验证属性值的惟一性
     *
     * If a database column is not specified, the attribute will be used.
     *
     * 如果没有指定数据库列，将使用该属性
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateUnique($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'unique');
        //                            解析用于唯一/存在规则的连接/表
        list($connection, $table) = $this->parseTable($parameters[0]);

        // The second parameter position holds the name of the column that needs to
        // be verified as unique. If this parameter isn't specified we will just
        // assume that this column to be verified shares the attribute's name.
        //
        // 第二个参数位置保存需要被验证为惟一的列的名称
        // 如果这个参数没有指定，我们将假设该列被验证，共享该属性的名称
        //
        //                获取一个存在/唯一查询的列名
        $column = $this->getQueryColumn($parameters, $attribute);

        list($idColumn, $id) = [null, null];

        if (isset($parameters[2])) {
            //                         获取惟一规则的排除ID列和值
            list($idColumn, $id) = $this->getUniqueIds($parameters);
        }

        // The presence verifier is responsible for counting rows within this store
        // mechanism which might be a relational database or any other permanent
        // data store like Redis, etc. We will use it to determine uniqueness.
        //
        // 存在的验证者负责在这个存储机制中计算行，这可能是一个关系数据库，或者像Redis这样的其他永久数据存储，我们将使用它来确定唯一性
        //
        //                 获得存在验证器的实现
        $verifier = $this->getPresenceVerifierFor($connection);
        //          为一个唯一的规则获取额外的条件
        $extra = $this->getUniqueExtra($parameters);

        if ($this->currentRule instanceof Unique) {
            //                                  获取规则的自定义查询回调
            $extra = array_merge($extra, $this->currentRule->queryCallbacks());
        }
        //计算具有给定值的集合中的对象的数量
        return $verifier->getCount(
            $table, $column, $value, $id, $idColumn, $extra
        ) == 0;
    }

    /**
     * Get the excluded ID column and value for the unique rule.
     *
     * 获取惟一规则的排除ID列和值
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getUniqueIds($parameters)
    {
        $idColumn = isset($parameters[3]) ? $parameters[3] : 'id';
        //                    为查询准备给定的ID
        return [$idColumn, $this->prepareUniqueId($parameters[2])];
    }

    /**
     * Prepare the given ID for querying.
     *
     * 为查询准备给定的ID
     *
     * @param  mixed  $id
     * @return int
     */
    protected function prepareUniqueId($id)
    {
        if (preg_match('/\[(.*)\]/', $id, $matches)) {
            //          获取给定属性的值
            $id = $this->getValue($matches[1]);
        }

        if (strtolower($id) == 'null') {
            $id = null;
        }

        if (filter_var($id, FILTER_VALIDATE_INT) !== false) {
            $id = intval($id);
        }

        return $id;
    }

    /**
     * Get the extra conditions for a unique rule.
     *
     * 为一个唯一的规则获取额外的条件
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getUniqueExtra($parameters)
    {
        if (isset($parameters[4])) {
            //获得唯一/存在规则的额外条件
            return $this->getExtraConditions(array_slice($parameters, 4));
        }

        return [];
    }

    /**
     * Parse the connection / table for the unique / exists rules.
     *
     * 解析用于唯一/存在规则的连接/表
     *
     * @param  string  $table
     * @return array
     */
    protected function parseTable($table)
    {
        //确定一个给定的字符串包含另一个字符串
        return Str::contains($table, '.') ? explode('.', $table, 2) : [null, $table];
    }

    /**
     * Get the column name for an exists / unique query.
     *
     * 获取一个存在/唯一查询的列名
     *
     * @param  array  $parameters
     * @param  string  $attribute
     * @return bool
     */
    protected function getQueryColumn($parameters, $attribute)
    {
        return isset($parameters[1]) && $parameters[1] !== 'NULL'
        //                              从给定的属性名称中猜测数据库列
                    ? $parameters[1] : $this->guessColumnForQuery($attribute);
    }

    /**
     * Guess the database column from the given attribute name.
     *
     * 从给定的属性名称中猜测数据库列
     *
     * @param  string  $attribute
     * @return string
     */
    public function guessColumnForQuery($attribute)
    {
        //                       将多维数组折叠为单个数组
        if (in_array($attribute, array_collapse($this->implicitAttributes))
            //                           从数组中获取最后一个元素
                && ! is_numeric($last = last(explode('.', $attribute)))) {
            return $last;
        }

        return $attribute;
    }

    /**
     * Get the extra conditions for a unique / exists rule.
     *
     * 获得唯一/存在规则的额外条件
     *
     * @param  array  $segments
     * @return array
     */
    protected function getExtraConditions(array $segments)
    {
        $extra = [];

        $count = count($segments);

        for ($i = 0; $i < $count; $i += 2) {
            $extra[$segments[$i]] = $segments[$i + 1];
        }

        return $extra;
    }

    /**
     * Validate the given value is a valid file.
     *
     * 验证给定的值是一个有效的文件
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateFile($attribute, $value)
    {
        //检查给定值是否为有效的文件实例
        return $this->isValidFileInstance($value);
    }

    /**
     * Validate the given attribute is filled if it is present.
     *
     * 如果存在，验证给定的属性是填充的
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateFilled($attribute, $value)
    {
        //使用“点”符号检查数组中的项或项是否存在
        if (Arr::has($this->data, $attribute)) {
            //           验证所需属性是否存在
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate the MIME type of a file is an image MIME type.
     *
     * 验证文件的MIME类型是一个图像MIME类型
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateImage($attribute, $value)
    {
        //验证文件上载的猜测扩展名在一组文件扩展中
        return $this->validateMimes($attribute, $value, ['jpeg', 'png', 'gif', 'bmp', 'svg']);
    }

    /**
     * Validate an attribute is contained within a list of values.
     *
     * 验证一个属性包含在一个值列表中
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateIn($attribute, $value, $parameters)
    {
        //                        确定给定属性在给定的集合中是否有规则
        if (is_array($value) && $this->hasRule($attribute, 'Array')) {
            foreach ($value as $element) {
                if (is_array($element)) {
                    return false;
                }
            }

            return count(array_diff($value, $parameters)) == 0;
        }

        return ! is_array($value) && in_array((string) $value, $parameters);
    }

    /**
     * Validate that the values of an attribute is in another attribute.
     *
     * 验证属性的值在另一个属性中
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateInArray($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'in_array');
        //                       获取属性名的显式部分
        $explicitPath = ValidationData::getLeadingExplicitAttributePath($parameters[0]);
        //                        根据给定的点指定路径提取数据
        $attributeData = ValidationData::extractDataFromPath($explicitPath, $this->data);
        //     使用给定的回调筛选数组   用点对多维关联数组进行扁平化
        $otherValues = Arr::where(Arr::dot($attributeData), function ($value, $key) use ($parameters) {
            //确定给定的字符串是否与给定的模式匹配
            return Str::is($parameters[0], $key);
        });

        return in_array($value, $otherValues);
    }

    /**
     * Validate that an attribute is an integer.
     *
     * 确认一个属性是一个整数
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateInteger($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate that an attribute is a valid IP.
     *
     * 确认属性是有效的IP
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateIp($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate that an attribute is a valid IPv4.
     *
     * 确认属性是有效的IPv4
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateIpv4($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate that an attribute is a valid IPv6.
     *
     * 确认属性是有效的IPv6
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateIpv6($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Validate the attribute is a valid JSON string.
     *
     * 确认属性是有效的JSON字符串
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateJson($attribute, $value)
    {
        if (! is_scalar($value) && ! method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate the size of an attribute is less than a maximum value.
     *
     * 确认一个属性的大小小于一个最大值
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateMax($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'max');
        //                                        返回文件是否成功上传
        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }
        //          获取属性的大小
        return $this->getSize($attribute, $value) <= $parameters[0];
    }

    /**
     * Validate the guessed extension of a file upload is in a set of file extensions.
     *
     * 验证文件上载的猜测扩展名在一组文件扩展中
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateMimes($attribute, $value, $parameters)
    {
        //          检查给定值是否为有效的文件实例
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        return $value->getPath() != '' && in_array($value->guessExtension(), $parameters);
    }

    /**
     * Validate the MIME type of a file upload attribute is in a set of MIME types.
     *
     * 验证文件上载属性的MIME类型在一组MIME类型中
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    protected function validateMimetypes($attribute, $value, $parameters)
    {
        //      检查给定值是否为有效的文件实例
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        return $value->getPath() != '' && in_array($value->getMimeType(), $parameters);
    }

    /**
     * Validate the size of an attribute is greater than a minimum value.
     *
     * 验证属性的大小是否大于最小值
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateMin($attribute, $value, $parameters)
    {
        //  需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'min');
        //       获取属性的大小
        return $this->getSize($attribute, $value) >= $parameters[0];
    }

    /**
     * "Indicate" validation should pass if value is null.
     *
     * 如果值为null，则表明验证应该通过
     *
     * Always returns true, just lets us put "nullable" in rules.
     *
     * 总是返回true，让我们在规则中添加“可空”
     *
     * @return bool
     */
    protected function validateNullable()
    {
        return true;
    }

    /**
     * Validate an attribute is not contained within a list of values.
     *
     * 验证一个属性不包含在一个值列表中
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateNotIn($attribute, $value, $parameters)
    {
        //         验证一个属性包含在一个值列表中
        return ! $this->validateIn($attribute, $value, $parameters);
    }

    /**
     * Validate that an attribute is numeric.
     *
     * 确认属性为数值
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateNumeric($attribute, $value)
    {
        return is_numeric($value);
    }

    /**
     * Validate that an attribute exists even if not filled.
     *
     * 验证一个属性是否存在，即使没有填充
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validatePresent($attribute, $value)
    {
        //使用“点”符号检查数组中的项或项是否存在
        return Arr::has($this->data, $attribute);
    }

    /**
     * Validate that an attribute passes a regular expression check.
     *
     * 验证一个属性通过一个正则表达式检查
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateRegex($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'regex');

        return preg_match($parameters[0], $value) > 0;
    }

    /**
     * Validate that a required attribute exists.
     *
     * 验证所需属性是否存在
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateRequired($attribute, $value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        } elseif ($value instanceof File) {
            return (string) $value->getPath() != '';
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute has a given value.
     *
     * 当另一个属性具有给定值时，验证属性是否存在
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredIf($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(2, $parameters, 'required_if');
        //使用“点”符号从数组中获取一个项
        $other = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if (is_bool($other)) {
            //           将给定的值转换为布尔值，如果它们是字符串"true"/"false"
            $values = $this->convertValuesToBoolean($values);
        }

        if (in_array($other, $values)) {
            //      验证所需属性是否存在
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Convert the given values to boolean if they are string "true" / "false".
     *
     * 将给定的值转换为布尔值，如果它们是字符串"true"/"false"
     *
     * @param  array  $values
     * @return array
     */
    protected function convertValuesToBoolean($values)
    {
        return array_map(function ($value) {
            if ($value === 'true') {
                return true;
            } elseif ($value === 'false') {
                return false;
            }

            return $value;
        }, $values);
    }

    /**
     * Validate that an attribute exists when another attribute does not have a given value.
     *
     * 当另一个属性没有给定值时，验证一个属性是否存在
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    protected function validateRequiredUnless($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(2, $parameters, 'required_unless');
        //使用“点”符号从数组中获取一个项
        $data = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if (! in_array($data, $values)) {
            //          验证所需属性是否存在
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when any other attribute exists.
     *
     * 验证当任何其他属性存在时属性是否存在
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredWith($attribute, $value, $parameters)
    {
        //确定是否所有给定的属性都失败了所需的测试
        if (! $this->allFailingRequired($parameters)) {
            //验证所需属性是否存在
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes exists.
     *
     * 当所有其他属性存在时，验证一个属性是否存在
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredWithAll($attribute, $value, $parameters)
    {
        //确定是否有任何给定的属性失败了所需的测试
        if (! $this->anyFailingRequired($parameters)) {
            //验证所需属性是否存在
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute does not.
     *
     * 验证当另一个属性不存在时属性是否存在
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredWithout($attribute, $value, $parameters)
    {
        //确定是否有任何给定的属性失败了所需的测试
        if ($this->anyFailingRequired($parameters)) {
            //验证所需属性是否存在
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes do not.
     *
     * 当所有其他属性都不存在时，验证一个属性是否存在
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredWithoutAll($attribute, $value, $parameters)
    {
        //确定是否有任何给定的属性失败了所需的测试
        if ($this->allFailingRequired($parameters)) {
            //验证所需属性是否存在
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Determine if any of the given attributes fail the required test.
     *
     * 确定是否有任何给定的属性失败了所需的测试
     *
     * @param  array  $attributes
     * @return bool
     */
    protected function anyFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            //验证所需属性是否存在                     获取给定属性的值
            if (! $this->validateRequired($key, $this->getValue($key))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if all of the given attributes fail the required test.
     *
     * 确定是否所有给定的属性都失败了所需的测试
     *
     * @param  array  $attributes
     * @return bool
     */
    protected function allFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            //验证所需属性是否存在                     获取给定属性的值
            if ($this->validateRequired($key, $this->getValue($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that two attributes match.
     *
     * 验证这两个属性匹配
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateSame($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'same');
        //使用“点”符号从数组中获取一个项
        $other = Arr::get($this->data, $parameters[0]);

        return $value === $other;
    }

    /**
     * Validate the size of an attribute.
     *
     * 验证一个属性的大小
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateSize($attribute, $value, $parameters)
    {
        //需要一定数量的参数
        $this->requireParameterCount(1, $parameters, 'size');
        //获取属性的大小
        return $this->getSize($attribute, $value) == $parameters[0];
    }

    /**
     * "Validate" optional attributes.
     *
     * “验证”可选属性
     *
     * Always returns true, just lets us put sometimes in rules.
     *
     * @return bool
     */
    protected function validateSometimes()
    {
        return true;
    }

    /**
     * Validate that an attribute is a string.
     *
     * 确认属性是字符串
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateString($attribute, $value)
    {
        return is_string($value);
    }

    /**
     * Validate that an attribute is a valid timezone.
     *
     * 确认一个属性是一个有效的时区
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateTimezone($attribute, $value)
    {
        try {
            new DateTimeZone($value);
        } catch (Exception $e) {
            return false;
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Validate that an attribute is a valid URL.
     *
     * 确认属性是有效的URL
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateUrl($attribute, $value)
    {
        if (! is_string($value)) {
            return false;
        }

        /*
         * This pattern is derived from Symfony\Component\Validator\Constraints\UrlValidator (2.7.4).
         *
         * 这个模式来自于
         *
         * (c) Fabien Potencier <fabien@symfony.com> http://symfony.com
         */
        $pattern = '~^
            ((aaa|aaas|about|acap|acct|acr|adiumxtra|afp|afs|aim|apt|attachment|aw|barion|beshare|bitcoin|blob|bolo|callto|cap|chrome|chrome-extension|cid|coap|coaps|com-eventbrite-attendee|content|crid|cvs|data|dav|dict|dlna-playcontainer|dlna-playsingle|dns|dntp|dtn|dvb|ed2k|example|facetime|fax|feed|feedready|file|filesystem|finger|fish|ftp|geo|gg|git|gizmoproject|go|gopher|gtalk|h323|ham|hcp|http|https|iax|icap|icon|im|imap|info|iotdisco|ipn|ipp|ipps|irc|irc6|ircs|iris|iris.beep|iris.lwz|iris.xpc|iris.xpcs|itms|jabber|jar|jms|keyparc|lastfm|ldap|ldaps|magnet|mailserver|mailto|maps|market|message|mid|mms|modem|ms-help|ms-settings|ms-settings-airplanemode|ms-settings-bluetooth|ms-settings-camera|ms-settings-cellular|ms-settings-cloudstorage|ms-settings-emailandaccounts|ms-settings-language|ms-settings-location|ms-settings-lock|ms-settings-nfctransactions|ms-settings-notifications|ms-settings-power|ms-settings-privacy|ms-settings-proximity|ms-settings-screenrotation|ms-settings-wifi|ms-settings-workplace|msnim|msrp|msrps|mtqp|mumble|mupdate|mvn|news|nfs|ni|nih|nntp|notes|oid|opaquelocktoken|pack|palm|paparazzi|pkcs11|platform|pop|pres|prospero|proxy|psyc|query|redis|rediss|reload|res|resource|rmi|rsync|rtmfp|rtmp|rtsp|rtsps|rtspu|secondlife|service|session|sftp|sgn|shttp|sieve|sip|sips|skype|smb|sms|smtp|snews|snmp|soap.beep|soap.beeps|soldat|spotify|ssh|steam|stun|stuns|submit|svn|tag|teamspeak|tel|teliaeid|telnet|tftp|things|thismessage|tip|tn3270|turn|turns|tv|udp|unreal|urn|ut2004|vemmi|ventrilo|videotex|view-source|wais|webcal|ws|wss|wtai|wyciwyg|xcon|xcon-userid|xfire|xmlrpc\.beep|xmlrpc.beeps|xmpp|xri|ymsgr|z39\.50|z39\.50r|z39\.50s))://                                 # protocol
            (([\pL\pN-]+:)?([\pL\pN-]+)@)?          # basic auth
            (
                ([\pL\pN\pS-\.])+(\.?([\pL]|xn\-\-[\pL\pN-]+)+\.?) # a domain name
                    |                                              # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                 # an IP address
                    |                                              # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]  # an IPv6 address
            )
            (:[0-9]+)?                              # a port (optional)
            (/?|/\S+|\?\S*|\#\S*)                   # a /, nothing, a / with something, a query or a fragment
        $~ixu';

        return preg_match($pattern, $value) > 0;
    }

    /**
     * Get the size of an attribute.
     *
     * 获取属性的大小
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return mixed
     */
    protected function getSize($attribute, $value)
    {
        $hasNumeric = $this->hasRule($attribute, $this->numericRules);

        // This method will determine if the attribute is a number, string, or file and
        // return the proper size accordingly. If it is a number, then number itself
        // is the size. If it is a file, we take kilobytes, and for a string the
        // entire length of the string will be considered the attribute size.
        //
        // 该方法将确定属性是一个数字、字符串或文件，并相应地返回适当的大小
        // 如果它是一个数字，那么数字本身就是它的大小
        // 如果它是一个文件，我们就用千字节，对于一个字符串，字符串的整个长度将被认为是属性大小
        //
        if (is_numeric($value) && $hasNumeric) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        } elseif ($value instanceof File) {
            return $value->getSize() / 1024;
        }

        return mb_strlen($value);
    }

    /**
     * Check that the given value is a valid file instance.
     *
     * 检查给定值是否为有效的文件实例
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValidFileInstance($value)
    {
        //                                            返回文件是否成功上传
        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }

        return $value instanceof File;
    }

    /**
     * Determine if a comparison passes between the given values.
     *
     * 确定一个比较是否在给定值之间传递
     *
     * @param  mixed  $first
     * @param  mixed  $second
     * @param  string  $operator
     * @return bool
     */
    protected function compare($first, $second, $operator)
    {
        switch ($operator) {
            case '<':
                return $first < $second;
            case '>':
                return $first > $second;
            case '<=':
                return $first <= $second;
            case '>=':
                return $first >= $second;
            default:
                throw new InvalidArgumentException;
        }
    }

    /**
     * Parse named parameters to $key => $value items.
     *
     * 将命名参数解析为$key=$value项
     *
     * @param  array  $parameters
     * @return array
     */
    protected function parseNamedParameters($parameters)
    {
        return array_reduce($parameters, function ($result, $item) {
            list($key, $value) = array_pad(explode('=', $item, 2), 2, null);

            $result[$key] = $value;

            return $result;
        });
    }

    /**
     * Require a certain number of parameters to be present.
     *
     * 需要一定数量的参数
     *
     * @param  int    $count
     * @param  array  $parameters
     * @param  string  $rule
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function requireParameterCount($count, $parameters, $rule)
    {
        if (count($parameters) < $count) {
            throw new InvalidArgumentException("Validation rule $rule requires at least $count parameters.");
        }
    }
}
