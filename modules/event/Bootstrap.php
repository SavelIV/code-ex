<?php

namespace app\modules\event;

use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApplication;
use yii\i18n\PhpMessageSource;

class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        /* @var $module Module */
        if ($app->hasModule('event') && ($module = $app->getModule('event')) instanceof Module) {
            if ($app instanceof ConsoleApplication) {
                $module->controllerNamespace = 'app\modules\event\controllers';
            }
            if (!isset($app->get('i18n')->translations['event*'])) {
                $app->get('i18n')->translations['event*'] = [
                    'class'          => PhpMessageSource::class,
                    'basePath'       => __DIR__ . '/messages',
                    'sourceLanguage' => 'ru'
                ];
            }
        }
    }
}
