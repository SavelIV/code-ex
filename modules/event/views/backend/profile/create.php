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

$this->title = implode(' ', array_filter([
    Yii::t('event', 'Создание мероприятия'),
    $event->organization_id ? Yii::t('event', 'организации "{0}"', [$event->organization->shortName]) : null,
    $event->league_id       ? Yii::t('event', 'лиги "{0}"',        [$event->league->shortName])       : null,
    $event->club_id         ? Yii::t('event', 'клуба "{0}"',       [$event->club->shortName])         : null
]));
$this->params['breadcrumbs'][] = ['label' => Yii::t('event', 'Мероприятия'), 'url' => ['/event/backend/search/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $this->title; ?></h3>
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
