<?php
/**
 * Copyright (c) 2018. Ghiya <ghiya@mikadze.me>
 */

namespace ghiyam\apix\query;


use yii\helpers\ArrayHelper;
use yii\helpers\UnsetArrayValue;

class QueueBuilder
{


    /**
     * @var \SplQueue
     */
    protected static $queue;


    /**
     * @param array $queryParams
     *
     * @return \SplQueue
     */
    public static function build($queryParams = [])
    {
        self::$queue = new \SplQueue();
        self::$queue->setIteratorMode(\SplDoublyLinkedList::IT_MODE_DELETE);
        self::buildQueue($queryParams);
        return self::$queue;
    }


    /**
     * @param array $queryParams
     */
    protected static function buildQueue($queryParams = [])
    {
        if (empty($queryParams['join'])) {
            self::addQuery($queryParams);
        }
        else {
            self::addQuery($queryParams);
            self::join($queryParams['join']);
        }
    }


    /**
     * @param array $queryParams
     */
    protected static function addQuery($queryParams = [])
    {
        self::$queue->enqueue(new Query($queryParams));
    }


    /**
     * @param array $joinParams
     */
    protected static function join($joinParams = [])
    {
        if (ArrayHelper::isIndexed($joinParams)) {
            foreach ($joinParams as $queryParams) {
                self::buildQueue($queryParams);
            }
        }
        else {
            self::addQuery($joinParams);
        }
    }

}