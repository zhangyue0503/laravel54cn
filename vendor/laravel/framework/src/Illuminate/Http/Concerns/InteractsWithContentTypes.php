<?php

namespace Illuminate\Http\Concerns;

use Illuminate\Support\Str;
// 与内容类型交互
trait InteractsWithContentTypes
{
    /**
     * Determine if the given content types match.
     *
     * 确定给定的内容是否类型匹配
     *
     * @param  string  $actual
     * @param  string  $type
     * @return bool
     */
    public static function matchesType($actual, $type)
    {
        if ($actual === $type) {
            return true;
        }

        $split = explode('/', $actual);

        return isset($split[1]) && preg_match('#'.preg_quote($split[0], '#').'/.+\+'.preg_quote($split[1], '#').'#', $type);
    }

    /**
     * Determine if the request is sending JSON.
     *
     * 确定请求是否发送JSON
     *
     * @return bool
     */
    public function isJson()
    {
        //确定一个给定的字符串包含另一个字符串  从请求中检索标头
        return Str::contains($this->header('CONTENT_TYPE'), ['/json', '+json']);
    }

    /**
     * Determine if the current request probably expects a JSON response.
     *
     * 确定当前请求是否可能需要JSON响应
     *
     * @return bool
     */
    public function expectsJson()
    {
        //确定请求是否是Ajax调用的结果      确定该请求是否是一个pjax调用的结果     确定当前请求是否返回JSON请求
        return ($this->ajax() && ! $this->pjax()) || $this->wantsJson();
    }

    /**
     * Determine if the current request is asking for JSON in return.
     *
     * 确定当前请求是否返回JSON请求
     *
     * @return bool
     */
    public function wantsJson()
    {
        $acceptable = $this->getAcceptableContentTypes();      // Symfony\Component\HttpFoundation\Request::getAcceptableContentTypes获取客户端浏览器可接受的内容类型列表
        //                             确定一个给定的字符串包含另一个字符串
        return isset($acceptable[0]) && Str::contains($acceptable[0], ['/json', '+json']);
    }

    /**
     * Determines whether the current requests accepts a given content type.
     *
     * 决定是否接受给定的当前请求类型
     *
     * @param  string|array  $contentTypes
     * @return bool
     */
    public function accepts($contentTypes)
    {
        $accepts = $this->getAcceptableContentTypes(); // Symfony\Component\HttpFoundation\Request::getAcceptableContentTypes获取客户端浏览器可接受的内容类型列表

        if (count($accepts) === 0) {
            return true;
        }

        $types = (array) $contentTypes;

        foreach ($accepts as $accept) {
            if ($accept === '*/*' || $accept === '*') {
                return true;
            }

            foreach ($types as $type) {
                //     确定给定的内容是否类型匹配
                if ($this->matchesType($accept, $type) || $accept === strtok($type, '/').'/*') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return the most suitable content type from the given array based on content negotiation.
     *
     * 根据内容协商返回给定数组中最合适的内容类型
     *
     * @param  string|array  $contentTypes
     * @return string|null
     */
    public function prefers($contentTypes)
    {
        $accepts = $this->getAcceptableContentTypes();// Symfony\Component\HttpFoundation\Request::getAcceptableContentTypes获取客户端浏览器可接受的内容类型列表

        $contentTypes = (array) $contentTypes;

        foreach ($accepts as $accept) {
            if (in_array($accept, ['*/*', '*'])) {
                return $contentTypes[0];
            }

            foreach ($contentTypes as $contentType) {
                $type = $contentType;
                //                        Symfony\Component\HttpFoundation\Request::getMimeType获取与格式关联的MIME类型
                if (! is_null($mimeType = $this->getMimeType($contentType))) {
                    $type = $mimeType;
                }
                //     确定给定的内容是否类型匹配
                if ($this->matchesType($type, $accept) || $accept === strtok($type, '/').'/*') {
                    return $contentType;
                }
            }
        }
    }

    /**
     * Determines whether a request accepts JSON.
     *
     * 决定是否接受JSON请求
     *
     * @return bool
     */
    public function acceptsJson()
    {
        //决定是否接受给定的当前请求类型
        return $this->accepts('application/json');
    }

    /**
     * Determines whether a request accepts HTML.
     *
     * 决定是否接受HTML请求
     *
     * @return bool
     */
    public function acceptsHtml()
    {
        //决定是否接受给定的当前请求类型
        return $this->accepts('text/html');
    }

    /**
     * Get the data format expected in the response.
     *
     * 获取响应中预期的数据格式
     *
     * @param  string  $default
     * @return string
     */
    public function format($default = 'html')
    {
        // Symfony\Component\HttpFoundation\Request::getAcceptableContentTypes获取客户端浏览器可接受的内容类型列表
        foreach ($this->getAcceptableContentTypes() as $type) {
            //            Symfony\Component\HttpFoundation\Request::getFormat  获取与MIME类型关联的格式
            if ($format = $this->getFormat($type)) {
                return $format;
            }
        }

        return $default;
    }
}
