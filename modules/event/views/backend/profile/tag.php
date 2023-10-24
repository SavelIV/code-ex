<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this  yii\web\View */
/* @var $event app\modules\event\models\Event */
/* @var $tag   app\models\Tag */

$this->title = Yii::t('event', 'Редактирование мероприятия: ') . $event->full_name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('event', 'Мероприятия'), 'url' => ['/event/backend/search/index']];
$this->params['breadcrumbs'][] = ['label' => $event->short_name];
$this->params['breadcrumbs'][] = Yii::t('event', 'Редактирование тега');
?>

<div class="panel">
    <div class="panel-heading">
        <div class="panel-control">
            <?php echo $this->render('_tabs', [
                'event' => $event,
                'tab'   => 'tag'
            ]); ?>
        </div>
        <h3 class="panel-title"><?php echo Yii::t('event', 'Редактирование тега'); ?></h3>
    </div>
    <?php $form = ActiveForm::begin([
        'id'                     => 'tag-form',
        'enableAjaxValidation'   => true,
        'enableClientValidation' => false,
        'validateOnBlur'         => false,
    ]); ?>
    <?php echo $this->render('@app/modules/redaction/views/backend/tag/_form-data', [
        'form' => $form,
        'tag'  => $tag
    ]); ?>
    <div class="panel-footer text-right">
        <?php echo Html::a(Yii::t('general', 'Отменить'), Yii::$app->request->referrer, ['class' => 'btn btn-default']); ?>
        <?php echo Html::submitButton(Yii::t('general', 'Сохранить'), ['class' => 'btn btn-primary']); ?>
        <br>
    </div>
    <?php ActiveForm::end(); ?>
</div>
