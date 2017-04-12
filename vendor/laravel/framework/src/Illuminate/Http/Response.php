<?php

namespace Illuminate\Http;

use ArrayObject;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Renderable;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class Response extends BaseResponse
{
    use ResponseTrait;

    /**
     * Set the content on the response.
     *
     * 设置响应内容
     *
     * @param  mixed  $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->original = $content;

        // If the content is "JSONable" we will set the appropriate header and convert
        // the content to JSON. This is useful when returning something like models
        // from routes that will be automatically transformed to their JSON form.
        //
        // 如果内容是“JSONable”我们将设置适当的标题和内容到JSON转换。
        // 当从自动转换到JSON格式的路由返回一些类似模型时，这非常有用。
        //
        if ($this->shouldBeJson($content)) {
            $this->header('Content-Type', 'application/json');

            $content = $this->morphToJson($content); //将给定的内容转换成JSON
        }

        // If this content implements the "Renderable" interface then we will call the
        // render method on the object so we will avoid any "__toString" exceptions
        // that might be thrown and have their errors obscured by PHP's handling.
        //
        // 如果这个内容实现了“渲染”界面，然后我们将调用Render方法的对象，我们将避免“__tostring”异常可能被抛出，有错误的处理了PHP。
        //
        elseif ($content instanceof Renderable) {
            $content = $content->render();
        }

        return parent::setContent($content); // 响应集的内容 Symfony\Component\HttpFoundation\Response::setContent()
    }

    /**
     * Determine if the given content should be turned into JSON.
     *
     * 确定给定的内容是否应该变成JSON
     *
     * @param  mixed  $content
     * @return bool
     */
    protected function shouldBeJson($content)
    {
        return $content instanceof Jsonable ||
               $content instanceof ArrayObject ||
               $content instanceof JsonSerializable ||
               is_array($content);
    }

    /**
     * Morph the given content into JSON.
     *
     * 将给定的内容转换成JSON
     *
     * @param  mixed   $content
     * @return string
     */
    protected function morphToJson($content)
    {
        if ($content instanceof Jsonable) {
            return $content->toJson();
        }

        return json_encode($content);
    }
}
