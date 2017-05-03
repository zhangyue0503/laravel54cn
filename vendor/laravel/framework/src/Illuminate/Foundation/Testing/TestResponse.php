<?php

namespace Illuminate\Foundation\Testing;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\HttpFoundation\Cookie;

class TestResponse
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The reponse to delegate to.
     *
     * 响应的委托
     *
     * @var \Illuminate\Http\Response
     */
    public $baseResponse;

    /**
     * Create a new test response instance.
     *
     * 创建一个新的测试响应实例
     *
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function __construct($response)
    {
        $this->baseResponse = $response;
    }

    /**
     * Create a new TestResponse from another response.
     *
     * 从另一个响应创建一个新的TestResponse
     *
     * @param  \Illuminate\Http\Response  $response
     * @return static
     */
    public static function fromBaseResponse($response)
    {
        return new static($response);
    }

    /**
     * Assert that the response has the given status code.
     *
     * 断言响应具有给定的状态码
     *
     * @param  int  $status
     * @return $this
     */
    public function assertStatus($status)
    {
        $actual = $this->getStatusCode();//检索当前web响应的状态代码
        //断言一个条件是正确的
        PHPUnit::assertTrue(
            $actual === $status,
            "Expected status code {$status} but received {$actual}."
        );

        return $this;
    }

    /**
     * Assert whether the response is redirecting to a given URI.
     *
     * @param  string  $uri
     * @return $this
     */
    public function assertRedirect($uri)
    {
        //断言一个条件是正确的
        PHPUnit::assertTrue(
            $this->isRedirect(), 'Response status code ['.$this->status().'] is not a redirect status code.'
        );
        //       断言两个变量是相等的         生成给定路径的绝对URL
        PHPUnit::assertEquals(app('url')->to($uri), $this->headers->get('Location'));

        return $this;
    }

    /**
     * Asserts that the response contains the given header and equals the optional value.
     *
     * 断言响应包含给定的头，并等于可选的值
     *
     * @param  string  $headerName
     * @param  mixed  $value
     * @return $this
     */
    public function assertHeader($headerName, $value = null)
    {
        //断言一个条件是正确的
        PHPUnit::assertTrue(
            $this->headers->has($headerName), "Header [{$headerName}] not present on response."
        );

        $actual = $this->headers->get($headerName);

        if (! is_null($value)) {
            //       断言两个变量是相等的
            PHPUnit::assertEquals(
                $this->headers->get($headerName), $value,
                "Header [{$headerName}] was found, but value [{$actual}] does not match [{$value}]."
            );
        }

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * 断言响应包含给定的cookie，等于可选的值
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @return $this
     */
    public function assertPlainCookie($cookieName, $value = null)
    {
        //断言响应包含给定的cookie，等于可选的值
        $this->assertCookie($cookieName, $value, false);

        return $this;
    }

    /**
     * Asserts that the response contains the given cookie and equals the optional value.
     *
     * 断言响应包含给定的cookie，等于可选的值
     *
     * @param  string  $cookieName
     * @param  mixed  $value
     * @param  bool  $encrypted
     * @return $this
     */
    public function assertCookie($cookieName, $value = null, $encrypted = true)
    {
        //断言一个变量不是空的
        PHPUnit::assertNotNull(
            //            从响应中获取给定的cookie
            $cookie = $this->getCookie($cookieName),
            "Cookie [{$cookieName}] not present on response."
        );

        if (! $cookie || is_null($value)) {
            return $this;
        }
        //               获取cookie的值
        $cookieValue = $cookie->getValue();

        $actual = $encrypted
            //                解密给定值
            ? app('encrypter')->decrypt($cookieValue) : $cookieValue;
        //断言两个变量是相等的
        PHPUnit::assertEquals(
            $actual, $value,
            "Cookie [{$cookieName}] was found, but value [{$actual}] does not match [{$value}]."
        );

        return $this;
    }

    /**
     * Get the given cookie from the response.
     *
     * 从响应中获取给定的cookie
     *
     * @param  string  $cookieName
     * @return Cookie|null
     */
    protected function getCookie($cookieName)
    {
        //            返回所有的cookie数组
        foreach ($this->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return $cookie;
            }
        }
    }

    /**
     * Assert that the given string is contained within the response.
     *
     * @param  string  $value
     * @return $this
     */
    public function assertSee($value)
    {
        //断言一个草堆包含一根针            获取当前的响应内容
        PHPUnit::assertContains($value, $this->getContent());

        return $this;
    }

    /**
     * Assert that the given string is not contained within the response.
     *
     * 断言给定的字符串不包含在响应中
     *
     * @param  string  $value
     * @return $this
     */
    public function assertDontSee($value)
    {
        //断言一个草堆不包含针              获取当前的响应内容
        PHPUnit::assertNotContains($value, $this->getContent());

        return $this;
    }

    /**
     * Assert that the response is a superset of the given JSON.
     *
     * 断言响应是给定JSON的一组超集
     *
     * @param  array  $data
     * @return $this
     */
    public function assertJson(array $data)
    {
        //断言一个数组有一个指定子集
        PHPUnit::assertArraySubset(
            //         验证并返回解码后的响应JSON              获得assertJson断言的消息
            $data, $this->decodeResponseJson(), false, $this->assertJsonMessage($data)
        );

        return $this;
    }

    /**
     * Get the assertion message for assertJson.
     *
     * 获得assertJson断言的消息
     *
     * @param  array  $data
     * @return string
     */
    protected function assertJsonMessage(array $data)
    {
        $expected = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        //                        验证并返回解码后的响应JSON
        $actual = json_encode($this->decodeResponseJson(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return 'Unable to find JSON: '.PHP_EOL.PHP_EOL.
            "[{$expected}]".PHP_EOL.PHP_EOL.
            'within response JSON:'.PHP_EOL.PHP_EOL.
            "[{$actual}].".PHP_EOL.PHP_EOL;
    }

    /**
     * Assert that the response has the exact given JSON.
     *
     * 断言响应具有完全给定的JSON
     *
     * @param  array  $data
     * @return $this
     */
    public function assertExactJson(array $data)
    {
        //                       递归排序数组的键和值
        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()//验证并返回解码后的响应JSON
        ));
        //断言两个变量是相等的               递归排序数组的键和值
        PHPUnit::assertEquals(json_encode(Arr::sortRecursive($data)), $actual);

        return $this;
    }

    /**
     * Assert that the response contains the given JSON fragment.
     *
     * 断言响应包含给定的JSON片段
     *
     * @param  array  $data
     * @return $this
     */
    public function assertJsonFragment(array $data)
    {
        //                       递归排序数组的键和值
        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()//验证并返回解码后的响应JSON
        ));
        //      递归排序数组的键和值
        foreach (Arr::sortRecursive($data) as $key => $value) {
            $expected = substr(json_encode([$key => $value]), 1, -1);
            //断言一个条件是正确的
            PHPUnit::assertTrue(
                //确定一个给定的字符串包含另一个字符串
                Str::contains($actual, $expected),
                'Unable to find JSON fragment: '.PHP_EOL.PHP_EOL.
                "[{$expected}]".PHP_EOL.PHP_EOL.
                'within'.PHP_EOL.PHP_EOL.
                "[{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Assert that the response has a given JSON structure.
     *
     * 断言响应具有给定的JSON结构
     *
     * @param  array|null  $structure
     * @param  array|null  $responseData
     * @return $this
     */
    public function assertJsonStructure(array $structure = null, $responseData = null)
    {
        if (is_null($structure)) {
            return $this->assertJson();//断言响应是给定JSON的一组超集
        }

        if (is_null($responseData)) {
            $responseData = $this->decodeResponseJson();//验证并返回解码后的响应JSON
        }

        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                PHPUnit::assertInternalType('array', $responseData);//断言变量是给定类型的

                foreach ($responseData as $responseDataItem) {
                    $this->assertJsonStructure($structure['*'], $responseDataItem);//断言响应具有给定的JSON结构
                }
            } elseif (is_array($value)) {
                PHPUnit::assertArrayHasKey($key, $responseData);//断言一个数组有一个指定的键

                $this->assertJsonStructure($structure[$key], $responseData[$key]);//断言响应具有给定的JSON结构
            } else {
                PHPUnit::assertArrayHasKey($value, $responseData);//断言一个数组有一个指定的键
            }
        }

        return $this;
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * 验证并返回解码后的响应JSON
     *
     * @return array
     */
    public function decodeResponseJson()
    {
        //                               获取当前的响应内容
        $decodedResponse = json_decode($this->getContent(), true);

        if (is_null($decodedResponse) || $decodedResponse === false) {
            if ($this->exception) {
                throw $this->exception;
            } else {
                //对给定消息的测试失败
                PHPUnit::fail('Invalid JSON was returned from the route.');
            }
        }

        return $decodedResponse;
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * 验证并返回解码后的响应JSON
     *
     * @return array
     */
    public function json()
    {
        //验证并返回解码后的响应JSON
        return $this->decodeResponseJson();
    }

    /**
     * Assert that the response view has a given piece of bound data.
     *
     * 断言响应视图有一个给定的绑定数据块
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function assertViewHas($key, $value = null)
    {
        if (is_array($key)) {
            //断言响应视图有一个给定的绑定数据列表
            return $this->assertViewHasAll($key);
        }
        //确保响应有一个视图作为其原始内容
        $this->ensureResponseHasView();

        if (is_null($value)) {
            //断言一个数组有一个指定的键
            PHPUnit::assertArrayHasKey($key, $this->original->getData());
        } elseif ($value instanceof Closure) {
            //断言一个条件是正确的
            PHPUnit::assertTrue($value($this->original->$key));
        } else {
            //断言两个变量是相等的
            PHPUnit::assertEquals($value, $this->original->$key);
        }

        return $this;
    }

    /**
     * Assert that the response view has a given list of bound data.
     *
     * 断言响应视图有一个给定的绑定数据列表
     *
     * @param  array  $bindings
     * @return $this
     */
    public function assertViewHasAll(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertViewHas($value);//断言响应视图有一个给定的绑定数据块
            } else {
                $this->assertViewHas($key, $value);//断言响应视图有一个给定的绑定数据块
            }
        }

        return $this;
    }

    /**
     * Assert that the response view is missing a piece of bound data.
     *
     * 断言响应视图丢失了一段绑定数据
     *
     * @param  string  $key
     * @return $this
     */
    public function assertViewMissing($key)
    {
        $this->ensureResponseHasView();//确保响应有一个视图作为其原始内容
        //断言一个数组没有指定的键
        PHPUnit::assertArrayNotHasKey($key, $this->original->getData());

        return $this;
    }

    /**
     * Ensure that the response has a view as its original content.
     *
     * 确保响应有一个视图作为其原始内容
     *
     * @return $this
     */
    protected function ensureResponseHasView()
    {
        if (! isset($this->original) || ! $this->original instanceof View) {
            return PHPUnit::fail('The response is not a view.');//对给定消息的测试失败
        }

        return $this;
    }

    /**
     * Assert that the session has a given value.
     *
     * 断言会话具有给定的值
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function assertSessionHas($key, $value = null)
    {
        if (is_array($key)) {
            //断言会话有一个给定的值列表
            return $this->assertSessionHasAll($key);
        }

        if (is_null($value)) {
            //断言一个条件是正确的
            PHPUnit::assertTrue(
                //获取当前会话存储->检查一个键是否存在并且不是空
                $this->session()->has($key),
                "Session is missing expected key [{$key}]."
            );
        } else {
            //断言两个变量是相等的
            PHPUnit::assertEquals($value, app('session.store')->get($key));
        }

        return $this;
    }

    /**
     * Assert that the session has a given list of values.
     *
     * 断言会话有一个给定的值列表
     *
     * @param  array  $bindings
     * @return $this
     */
    public function assertSessionHasAll(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertSessionHas($value);//断言会话具有给定的值
            } else {
                $this->assertSessionHas($key, $value);//断言会话具有给定的值
            }
        }

        return $this;
    }

    /**
     * Assert that the session has the given errors.
     *
     * 断言会话具有给定的错误
     *
     * @param  string|array  $keys
     * @param  mixed  $format
     * @return $this
     */
    public function assertSessionHasErrors($keys = [], $format = null)
    {
        $this->assertSessionHas('errors');//断言会话具有给定的值

        $keys = (array) $keys;

        $errors = app('session.store')->get('errors');

        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                //断言一个条件是正确的
                PHPUnit::assertTrue($errors->has($value), "Session missing error: $value");
            } else {
                //断言一个草堆包含一根针
                PHPUnit::assertContains($value, $errors->get($key, $format));
            }
        }

        return $this;
    }

    /**
     * Assert that the session does not have a given key.
     *
     * 断言会话没有给定的键
     *
     * @param  string|array  $key
     * @return $this
     */
    public function assertSessionMissing($key)
    {
        if (is_array($key)) {
            foreach ($key as $value) {
                $this->assertSessionMissing($value);//断言会话没有给定的键
            }
        } else {
            //断言一个条件是假的
            PHPUnit::assertFalse(
                //获取当前会话存储   检查一个键是否存在并且不是空
                $this->session()->has($key),
                "Session has unexpected key [{$key}]."
            );
        }

        return $this;
    }

    /**
     * Get the current session store.
     *
     * 获取当前会话存储
     *
     * @return \Illuminate\Session\Store
     */
    protected function session()
    {
        return app('session.store');
    }

    /**
     * Dump the content from the response.
     *
     * 从响应中转储内容
     *
     * @return void
     */
    public function dump()
    {
        $content = $this->getContent();

        $json = json_decode($content);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $json;
        }

        dd($content);
    }

    /**
     * Dynamically access base response parameters.
     *
     * 动态访问基本响应参数
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->baseResponse->{$key};
    }

    /**
     * Proxy isset() checks to the underlying base response.
     *
     * 代理isset()检查底层基础反应
     *
     * @param  string  $key
     * @return mixed
     */
    public function __isset($key)
    {
        return isset($this->baseResponse->{$key});
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the base response.
     *
     * 处理对宏的动态调用，或者将丢失的方法传递给基本响应
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $args)
    {
        //检查宏是否已注册
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $args);//动态调用类的调用
        }

        return $this->baseResponse->{$method}(...$args);
    }
}
