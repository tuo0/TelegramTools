<?php
namespace Common\API\telegram;

use danog\MadelineProto\API;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;

class TelegramClient
{
    static $websocket;

    // 日志和session文件路径
    public $path          = '';

    // 日志和session文件名(不含后缀)
    public $filename      = '';

    // session文件名(含后缀)
    public $madeline_path = '';

    public $settings      = [];

    public function __construct($user_id , $phone_number)
    {
        $this->path          = storage_path('public/telegram/'.$user_id.'/');

        if ( !is_dir( $this->path ) ){
            @mkdir( $this->path, '0777');
        }

        $this->filename      = $this->path.'phone_'.$phone_number;

        $this->madeline_path = $this->filename.'.madeline';

        $this->settings      = [
            'connection_settings'   => [
                'all'               => [
                    //'test_mode'     => true,
                    /*
                    'proxy'         => ObfuscatedStream::getName(),
                    'proxy_extra'   => [
                        'address'   => '104.215.250.123',
                        'port'      => '543',
                        'secret'    => 'ee000000000000000000000000000000017777772e6d6963726f736f66742e636f6d'
                    ],
                    */
                    //'ipv6'         => 'ipv4'
                ]
            ],
            'connection' => [
                'main' => [
                    // Main datacenters
                    'ipv4'  => [
                        // ipv4 addresses
                        2   => [
                            // The rest will be fetched using help.getConfig
                            'ip_address'=> '149.154.167.50',
                            'port'      => 443,
                            'media_only'=> false,
                            'tcpo_only' => false,
                        ],
                    ],
                ],
            ],
            /*
            'app_info' => [
                'api_id' => '123',
                'api_hash' => '11sda'
            ],
            */
            'serialization' => [
                'serialization_interval'        => 30,
                'cleanup_before_serialization'  => true
            ],
            'logger' => [
                'logger_param'  => $this->filename . '.log',
            ],
            'db' => [
            ],
            'serialization' =>
                [
                    'serialization_interval'        => 30,
                    'cleanup_before_serialization'  => true
                ]
        ];
    }

    public function getClient()
    {
        $client = new API( $this->madeline_path , $this->settings );
        $client->setEventHandler(\Common\API\telegram\MyEventHandler::class);
        return $client;
    }

    /**
     * 获取异步Madeline客户端
     * @param $socket
     * @param $async
     * @param $settings
     * @return MadeLineAPI
     */
    public function getAsyncClient( $socket , $async )//: \Generator
    {
        $MadelineProto = $this->getClient();

        if( $async ){
            $MadelineProto->async(true);
        }

        if( is_array($MadelineProto->my_get_self()) ){
            self::$websocket[$MadelineProto->API->authorization['user']['phone']] = $socket;

            $socket->emit('telegram_login_status', ['status' => 'success']);

            $MadelineProto->loop(function () use ($MadelineProto,$socket) {
                yield $MadelineProto->start();
                yield $MadelineProto->setEventHandler(\Common\API\telegram\MyEventHandler::class);

                //yield $MadelineProto->messages->sendMessage(['peer' => '@xintiao', 'message' => 'aaa']);
            });

            //$MadelineProto->loop();
        }else{
            $socket->emit('telegram_login_status', ['status' => 'error']);
        }

        return $MadelineProto;
    }
}
