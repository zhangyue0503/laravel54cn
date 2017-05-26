<?php

namespace Illuminate\Redis\Connectors;

use Predis\Client;
use Illuminate\Support\Arr;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;

class PredisConnector
{
    /**
     * Create a new clustered Predis connection.
     *
     * 创建一个新的集群Predis连接
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\PredisConnection
     */
    public function connect(array $config, array $options)
    {
        //创建一个新的Predis连接
        return new PredisConnection(new Client($config, array_merge(
            //                             从数组中获取值，并将其移除
            ['timeout' => 10.0], $options, Arr::pull($config, 'options', [])
        )));
    }

    /**
     * Create a new clustered Predis connection.
     *
     * 创建一个新的集群Predis连接
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\PredisClusterConnection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        //                         从数组中获取值，并将其移除
        $clusterSpecificOptions = Arr::pull($config, 'options', []);

        return new PredisClusterConnection(new Client(array_values($config), array_merge(
            $options, $clusterOptions, $clusterSpecificOptions
        )));
    }
}
