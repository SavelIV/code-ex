<?php
use yii\helpers\Url;

/* @var $this  yii\web\View */
/* @var $event app\modules\event\models\Event */
/* @var $tab   string */
?>

<ul class="nav nav-tabs">
    <li <?php if ($tab == 'update') echo 'class="active"'; ?>>
        <a href="<?php echo Url::to(['/event/backend/profile/update', 'id' => $event->event_id]); ?>">
            <?php echo Yii::t('event', 'Основные настройки'); ?>
        </a>
    </li>
    <li <?php if ($tab == 'form') echo 'class="active"'; ?>>
        <a href="<?php echo Url::to(['/event/backend/form/index', 'id' => $event->event_id]); ?>">
            <?php echo Yii::t('event', 'Форма заявки'); ?>
        </a>
    </li>
    <li <?php if ($tab == 'tag') echo 'class="active"'; ?>>
        <a href="<?php echo Url::to(['/event/backend/profile/tag', 'id' => $event->event_id]); ?>">
            <?php echo Yii::t('event', 'Тег'); ?>
        </a>
    </li>
</ul>
