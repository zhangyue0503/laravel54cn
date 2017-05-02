<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
//授权
trait Authorizable
{
    /**
     * Determine if the entity has a given ability.
     *
     * 确定实体是否具有给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        //                     为给定用户获取保护实例   确定是否给予给定的能力
        return app(Gate::class)->forUser($this)->check($ability, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * 确定实体是否不具有给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cant($ability, $arguments = [])
    {
        //             确定实体是否具有给定的能力
        return ! $this->can($ability, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * 确定实体是否不具有给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cannot($ability, $arguments = [])
    {
        //确定实体是否不具有给定的能力
        return $this->cant($ability, $arguments);
    }
}
