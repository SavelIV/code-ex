<?php
use yii\bootstrap\Nav;

/* @var $this       yii\web\View */
/* @var $event      app\modules\event\models\Event */
/* @var $content    string */
/* @var $controller app\modules\event\controllers\backend\DefaultController*/

$controller = $this->context;
$event = $controller->event;
?>
<?php $this->beginContent('@app/views/layouts/nifty/cabinet.php'); ?>
<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title pull-left"><?php echo Yii::t('event', 'Мероприятие'); ?>: <?php echo $event->full_name; ?></h3>
    </div>
    <?php echo Nav::widget([
        'options' => ['class' => 'nav-pills nav-justified js-nav-pills-menu'],
        'items'   => [
            [
                'label'  => Yii::t('event', 'Редактировать'),
                'url'    => ['/event/backend/profile/update', 'id' => $event->event_id],
                'active' => in_array($controller->id, ['backend/profile', 'backend/form'])
            ],
            [
                'label'  => Yii::t('event', 'Участники'),
                'url'    => $event->isTeam ?
                    ['/event/backend/application/index', 'id' => $event->event_id] :
                    ['/event/backend/personal/index', 'id' => $event->event_id],
                'active' => in_array($controller->id, ['backend/application', 'backend/personal'])
            ],
            [
                'label'   => Yii::t('event', 'Документы'),
                'url'     => ['/event/backend/documents/index', 'id' => $event->event_id],
                'active'  => $controller->id == 'backend/documents'
            ],
            [
                'label'   => Yii::t('event', 'Админы'),
                'url'     => ['/event/backend/admins/index', 'id' => $event->event_id],
                'active'  => $controller->id == 'backend/admins'
            ]
        ]
    ]); ?>
</div>
<?php echo $content; ?>
<?php $this->endContent(); ?>
