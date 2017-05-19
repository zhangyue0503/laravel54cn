<?php

namespace Illuminate\Http;

use JsonSerializable;
use InvalidArgumentException;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;
//json响应
class JsonResponse extends BaseJsonResponse
{
    use ResponseTrait;

    /**
     * Constructor.
     *
     * 构造函数
     *
     * @param  mixed  $data
     * @param  int    $status
     * @param  array  $headers
     * @param  int    $options
     */
    public function __construct($data = null, $status = 200, $headers = [], $options = 0)
    {
        $this->encodingOptions = $options;
        //响应代表JSON格式的HTTP响应
        parent::__construct($data, $status, $headers);
    }

    /**
     * Sets the JSONP callback.
     *
     * 设置JSONP回调
     *
     * @param  string|null  $callback
     * @return $this
     */
    public function withCallback($callback = null)
    {
        //            设置JSONP回调
        return $this->setCallback($callback);
    }

    /**
     * Get the json_decoded data from the response.
     *
     * json_decode数据的响应
     *
     * @param  bool  $assoc
     * @param  int  $depth
     * @return mixed
     */
    public function getData($assoc = false, $depth = 512)
    {
        return json_decode($this->data, $assoc, $depth);
    }

    /**
     * {@inheritdoc}
     * 设置要作为JSON发送的数据
     */
    public function setData($data = [])
    {
        $this->original = $data;

        if ($data instanceof Arrayable) {
            //                          获取数组实例
            $this->data = json_encode($data->toArray(), $this->encodingOptions);
        } elseif ($data instanceof Jsonable) {
            //              将对象转换为JSON表示形式
            $this->data = $data->toJson($this->encodingOptions);
        } elseif ($data instanceof JsonSerializable) {
            $this->data = json_encode($data->jsonSerialize(), $this->encodingOptions);
        } else {
            $this->data = json_encode($data, $this->encodingOptions);
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(json_last_error_msg());
        }
        //根据JSON数据和回调函数更新内容和头文件
        return $this->update();
    }

    /**
     * {@inheritdoc}
     * 设置用于将数据编码到JSON时使用的选项
     */
    public function setEncodingOptions($options)
    {
        $this->encodingOptions = (int) $options;
        //设置要作为JSON发送的数据     json_decode数据的响应
        return $this->setData($this->getData());
    }
}
