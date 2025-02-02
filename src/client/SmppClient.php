<?php
/**
 * Copyright (c) 2018-2019. Ghiya <ghiya@mikadze.me>
 */


namespace ghiyam\apix\client;


use smpp\{Address, SMPP, Client, transport\Socket};
use yii\base\InvalidConfigException;
use yii\helpers\Html;

/**
 * Class SmppClient
 *
 * @property Client $connector;
 *
 * @package ghiyam\apix\client
 */
class SmppClient extends CurlClient
{


    /**
     * @var bool
     */
    public $isDebug = false;


    /**
     * @var array
     */
    public $smpp = [];


    /**
     * @var int
     */
    public $timeout = 3000;


    /**
     * @var Socket
     */
    protected $transport;


    /**
     * @var bool
     */
    private $_isProcessing = false;


    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (empty($this->smpp)) {
            throw new InvalidConfigException('Property `smpp` must be set.');
        }
        if (empty($this->smpp['username'])) {
            throw new InvalidConfigException('Property `smpp[\'username\']` must be set.');
        }
        if (empty($this->smpp['password'])) {
            throw new InvalidConfigException('Property `smpp[\'password\']` must be set.');
        }
        if ($this->isDebug) {
            Socket::$defaultDebug = true;
        }
        $this->transport = new Socket([$this->host], $this->port);
        $this->transport->setRecvTimeout($this->timeout);
        $this->connector = new Client($this->transport);
        // Activate binary hex-output of server interaction
        if ($this->isDebug) {
            $this->transport->debug = true;
            $this->connector->debug = true;
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function prepareRequest($method = "", $params = [])
    {
        $this->transport->open();
        $this->connector->bindTransmitter($this->smpp['username'], $this->smpp['password']);
        if (!empty($this->smpp['options'])) {
            foreach ($this->smpp['options'] as $option => $value) {
                Client::${$option} = $value;
            }
        }
        return [
            'from' => new Address($method, SMPP::TON_ALPHANUMERIC),
            'to' => new Address($params['to'], SMPP::TON_INTERNATIONAL, SMPP::NPI_E164),
            'text' => mb_convert_encoding(trim(strip_tags(Html::decode($params['text']))), "UCS-2BE"),
        ];
    }


    /**
     * {@inheritdoc}
     */
    protected function prepareResponse($originalResponse)
    {
        $this->connector->close();
        $this->_isProcessing = false;
        return !empty($originalResponse['id']);
    }


    /**
     * {@inheritdoc}
     */
    public function sendRequest($originalRequest)
    {
        // @todo исправить багу
        // исправление баги задваивания отправки СМС
        if (!$this->_isProcessing) {
            $this->_isProcessing = true;
            return
                [
                    'id'     =>
                        $this->connector->sendSMS(
                            $originalRequest['from'],
                            $originalRequest['to'],
                            $originalRequest['text'],
                            isset($this->smpp['tags']) ? $this->smpp['tags'] : null,
                            isset($this->smpp['encoding']) ? $this->smpp['encoding'] : SMPP::DATA_CODING_DEFAULT
                        ),
                    'source' => $originalRequest['to']
                ];
        }
    }

}