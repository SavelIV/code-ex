<?php

namespace app\modules\event;

use yii\base\Module as BaseModule;
use yii\i18n\PhpMessageSource;

class Module extends BaseModule
{
    public $layout = '//nifty/cabinet';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (!isset(\Yii::$app->get('i18n')->translations['event*'])) {
            \Yii::$app->get('i18n')->translations['event*'] = [
                'class'    => PhpMessageSource::class,
                'basePath' => __DIR__ . '/messages'
            ];
        }
    }
}
