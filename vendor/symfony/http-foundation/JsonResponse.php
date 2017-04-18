<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation;

/**
 * Response represents an HTTP response in JSON format.
 *
 * 响应代表JSON格式的HTTP响应
 *
 * Note that this class does not force the returned JSON content to be an
 * object. It is however recommended that you do return an object as it
 * protects yourself against XSSI and JSON-JavaScript Hijacking.
 *
 * 请注意，此类不强制返回的JSON内容为对象
 * 不过，建议你返回一个对象，因为它保护自己对抗XSSI和JSON-JavaScript 劫持。
 *
 * @see https://www.owasp.org/index.php/OWASP_AJAX_Security_Guidelines#Always_return_JSON_with_an_Object_on_the_outside
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class JsonResponse extends Response
{
    protected $data;
    protected $callback;

    // Encode <, >, ', &, and " characters in the JSON, making it also safe to be embedded into HTML.
    // 编码<, >, ', &, 和 "在JSON中的字符，使它也安全地嵌入到HTML
    // 15 === JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    const DEFAULT_ENCODING_OPTIONS = 15;

    protected $encodingOptions = self::DEFAULT_ENCODING_OPTIONS;

    /**
     * 构造函数
     * @param mixed $data    The response data
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     * @param bool  $json    If the data is already a JSON string
     */
    public function __construct($data = null, $status = 200, $headers = array(), $json = false)
    {
        parent::__construct('', $status, $headers);

        if (null === $data) {
            $data = new \ArrayObject();
        }
        //   设置包含要发送的JSON文档的原始字符串 设置要作为JSON发送的数据
        $json ? $this->setJson($data) : $this->setData($data);
    }

    /**
     * Factory method for chainability.
     *
     * 对于链式调用的工厂方法
     *
     * Example:
     *
     *     return JsonResponse::create($data, 200)
     *         ->setSharedMaxAge(300);
     *
     * @param mixed $data    The json response data
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     *
     * @return static
     */
    public static function create($data = null, $status = 200, $headers = array())
    {
        return new static($data, $status, $headers);
    }

    /**
     * Make easier the creation of JsonResponse from raw json.
     * 更容易创造JsonResponse从原始JSON
     */
    public static function fromJsonString($data = null, $status = 200, $headers = array())
    {
        return new static($data, $status, $headers, true);
    }

    /**
     * Sets the JSONP callback.
     *
     * 设置JSONP回调
     *
     * @param string|null $callback The JSONP callback or null to use none
     *
     * @return $this
     *
     * @throws \InvalidArgumentException When the callback name is not valid
     */
    public function setCallback($callback = null)
    {
        if (null !== $callback) {
            // partially taken from http://www.geekality.net/2011/08/03/valid-javascript-identifier/
            // 部分从http://www.geekality.net/2011/08/03/valid-javascript-identifier/
            // partially taken from https://github.com/willdurand/JsonpCallbackValidator
            // 部分从https://github.com/willdurand/JsonpCallbackValidator
            //      JsonpCallbackValidator is released under the MIT License. See https://github.com/willdurand/JsonpCallbackValidator/blob/v1.1.0/LICENSE for details.
            //      JsonpCallbackValidator是MIT许可下发布
            //      (c) William Durand <william.durand1@gmail.com>
            $pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*(?:\[(?:"(?:\\\.|[^"\\\])*"|\'(?:\\\.|[^\'\\\])*\'|\d+)\])*?$/u';
            $reserved = array(
                'break', 'do', 'instanceof', 'typeof', 'case', 'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue', 'for', 'switch', 'while',
                'debugger', 'function', 'this', 'with', 'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum', 'extends', 'super',  'const', 'export',
                'import', 'implements', 'let', 'private', 'public', 'yield', 'interface', 'package', 'protected', 'static', 'null', 'true', 'false',
            );
            $parts = explode('.', $callback);
            foreach ($parts as $part) {
                if (!preg_match($pattern, $part) || in_array($part, $reserved, true)) {
                    throw new \InvalidArgumentException('The callback name is not valid.');
                }
            }
        }

        $this->callback = $callback;

        return $this->update(); // 根据JSON数据和回调函数更新内容和头文件
    }

    /**
     * Sets a raw string containing a JSON document to be sent.
     *
     * 设置包含要发送的JSON文档的原始字符串
     *
     * @param string $json
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setJson($json)
    {
        $this->data = $json;

        return $this->update(); //根据JSON数据和回调函数更新内容和头文件
    }

    /**
     * Sets the data to be sent as JSON.
     *
     * 设置要作为JSON发送的数据
     *
     * @param mixed $data
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setData($data = array())
    {
        if (defined('HHVM_VERSION')) {
            // HHVM does not trigger any warnings and let exceptions
            // thrown from a JsonSerializable object pass through.
            // If only PHP did the same...
            //
            // hhvm不触发任何警报，让从JsonSerializable对象通过抛出的异常
            // 如果只有PHP做同样的…
            //
            $data = json_encode($data, $this->encodingOptions);
        } else {
            try {
                // PHP 5.4 and up wrap exceptions thrown by JsonSerializable
                // objects in a new exception that needs to be removed.
                // Fortunately, PHP 5.5 and up do not trigger any warning anymore.
                //
                // PHP 5.4和包被在一个新的例外，需要去除JsonSerializable对象抛出的异常
                // 幸运的是，PHP 5.5以上不触发任何警告了
                //
                $data = json_encode($data, $this->encodingOptions);
            } catch (\Exception $e) {
                if ('Exception' === get_class($e) && 0 === strpos($e->getMessage(), 'Failed calling ')) {
                    throw $e->getPrevious() ?: $e;
                }
                throw $e;
            }
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        return $this->setJson($data); // 设置包含要发送的JSON文档的原始字符串
    }

    /**
     * Returns options used while encoding data to JSON.
     *
     * 返回在将数据编码到JSON时使用的选项
     *
     * @return int
     */
    public function getEncodingOptions()
    {
        return $this->encodingOptions;
    }

    /**
     * Sets options used while encoding data to JSON.
     *
     * 设置用于将数据编码到JSON时使用的选项
     *
     * @param int $encodingOptions
     *
     * @return $this
     */
    public function setEncodingOptions($encodingOptions)
    {
        $this->encodingOptions = (int) $encodingOptions;

        return $this->setData(json_decode($this->data)); //设置要作为JSON发送的数据
    }

    /**
     * Updates the content and headers according to the JSON data and callback.
     *
     * 根据JSON数据和回调函数更新内容和头文件
     *
     * @return $this
     */
    protected function update()
    {
        if (null !== $this->callback) {
            // Not using application/javascript for compatibility reasons with older browsers.
            // 不使用application/javascript，兼容性原因与旧浏览器
            $this->headers->set('Content-Type', 'text/javascript');

            return $this->setContent(sprintf('/**/%s(%s);', $this->callback, $this->data)); // 设置响应的内容
        }

        // Only set the header when there is none or when it equals 'text/javascript' (from a previous update with callback)
        // 只有当没有或当它等于“text/javascript”（从以前的更新回调）
        // in order to not overwrite a custom definition.
        // 以不覆盖自定义定义
        if (!$this->headers->has('Content-Type') || 'text/javascript' === $this->headers->get('Content-Type')) {
            $this->headers->set('Content-Type', 'application/json');
        }

        return $this->setContent($this->data); //设置响应的内容
    }
}
