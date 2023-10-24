<?php
/* @var $this                yii\web\View */
/* @var $user                app\modules\user\models\User */
/* @var $event               app\modules\event\models\Event */
/* @var $organizations       app\modules\organization\models\Organization[] */
/* @var $clubs               app\modules\club\models\Club[] */
/* @var $leagues             app\modules\league\models\League[] */
/* @var $seasons             app\modules\hdbk\models\HdbkSeason[] */
/* @var $sportKinds          app\modules\hdbk\models\HdbkSportKind[] */
/* @var $isOrganizationAdmin boolean */
/* @var $isClubAdmin         boolean */
/* @var $isLeagueAdmin       boolean */

$this->title = Yii::t('event', 'Редактирование мероприятия: ') . $event->full_name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('event', 'Мероприятия'), 'url' => ['/event/backend/search/index']];
$this->params['breadcrumbs'][] = ['label' => $event->short_name];
$this->params['breadcrumbs'][] = Yii::t('event', 'Редактирование');
?>

<div class="panel">
    <div class="panel-heading">
        <div class="panel-control">
            <?php echo $this->render('_tabs', [
                'event' => $event,
                'tab'   => 'update'
            ]); ?>
        </div>
        <h3 class="panel-title"><?php echo Yii::t('event', 'Редактирование мероприятия'); ?></h3>
    </div>
    <?php echo $this->render('_form', [
        'user'                => $user,
        'event'               => $event,
        'organizations'       => $organizations,
        'clubs'               => $clubs,
        'leagues'             => $leagues,
        'seasons'             => $seasons,
        'sportKinds'          => $sportKinds,
        'isOrganizationAdmin' => $isOrganizationAdmin,
        'isClubAdmin'         => $isClubAdmin,
        'isLeagueAdmin'       => $isLeagueAdmin
    ]); ?>
</div>
