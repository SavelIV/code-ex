<?php
use app\constants\ActivityFormat;
use app\constants\ActivityLevel;
use app\constants\ActivityTypes;
use app\constants\SportTypes;
use app\helpers\ImageHelper;
use app\widgets\Imperavi\Widget as Imperavi;
use dosamigos\datepicker\DatePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this                yii\web\View */
/* @var $event               app\modules\event\models\Event */
/* @var $user                app\modules\user\models\User */
/* @var $organizations       app\modules\organization\models\Organization[] */
/* @var $clubs               app\modules\club\models\Club[] */
/* @var $leagues             app\modules\league\models\League[] */
/* @var $seasons             app\modules\hdbk\models\HdbkSeason[] */
/* @var $sportKinds          app\modules\hdbk\models\HdbkSportKind[] */
/* @var $isOrganizationAdmin boolean */
/* @var $isClubAdmin         boolean */
/* @var $isLeagueAdmin       boolean */

$this->registerCssFile('/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css', ['depends' => 'dosamigos\datepicker\DatePickerAsset']);
\app\assets\Select2Asset::register($this);
$this->registerJs('$("select").select2();');
?>

<?php $form = ActiveForm::begin([
    'id'                     => 'event-form',
    'enableAjaxValidation'   => true,
    'enableClientValidation' => false,
    'validateOnBlur'         => false
]); ?>
<div class="panel-body">
    <div class="fixed-fluid">
        <div class="fixed-sm-200 fixed-md-200 pull-sm-left">
            <div class="row">
                <div class="col-sm-12">
                    <?php echo ImageHelper::getCropper($event, 'logo', [
                        'previewWidth' => 200,
                    ], Yii::t('event', 'Изменить логотип'), '300x300'); ?>
                </div>
            </div>
        </div>
        <div class="fluid">
            <p class="bord-btm text-main text-bold" style="padding: 5px 0;"><?php echo Yii::t('event', 'Основное'); ?></p>
            <div class="row">
                <div class="col-sm-4">
                    <?php echo $form->field($event, 'full_name')->textInput(['maxLength' => true]); ?>
                </div>
                <div class="col-sm-4">
                    <?php echo $form->field($event, 'short_name')->textInput(['maxLength' => true]); ?>
                </div>
            </div>
            <p class="bord-btm text-main text-bold" style="padding: 5px 0;"><?php echo Yii::t('event', 'Принадлежность'); ?></p>
            <div class="row">
                <div class="col-sm-4">
                    <?php echo $form->field($event, 'season_id')->dropDownList(ArrayHelper::map($seasons, 'season_id', 'title'), [
                        'prompt'   => Yii::t('event', 'Выберите сезон'),
                        'style'    => 'width: 100%;',
                        'disabled' => !$event->isNewRecord
                    ]); ?>
                </div>
            </div>
            <div class="row">
                <?php if ($isLeagueAdmin) { ?>
                    <div class="col-sm-4">
                        <?php echo $form->field($event, 'league_id')->dropDownList(ArrayHelper::map($leagues, 'league_id', 'full_name'), [
                            'prompt'   => Yii::t('event', 'Выберите лигу'),
                            'style'    => 'width: 100%;',
                            'disabled' => $event->league_id
                        ]); ?>
                    </div>
                <?php } ?>
                <?php if ($isOrganizationAdmin) { ?>
                    <div class="col-sm-4">
                        <?php echo $form->field($event, 'organization_id')->dropDownList(ArrayHelper::map($organizations, 'organization_id', 'full_name'), [
                            'prompt'   => Yii::t('event', 'Выберите организацию'),
                            'style'    => 'width: 100%;',
                            'disabled' => $event->organization_id
                        ]); ?>
                    </div>
                <?php } ?>
                <?php if ($isClubAdmin) { ?>
                    <div class="col-sm-4">
                        <?php echo $form->field($event, 'club_id')->dropDownList(ArrayHelper::map($clubs, 'club_id', 'full_name'), [
                            'prompt'   => Yii::t('event', 'Выберите клуб'),
                            'style'    => 'width: 100%;',
                            'disabled' => $event->club_id
                        ]); ?>
                    </div>
                <?php } ?>
            </div>
            <p class="bord-btm text-main text-bold" style="padding: 5px 0;"><?php echo Yii::t('event', 'Параметры'); ?></p>
            <div class="row">
                <div class="col-md-3">
                    <?php echo $form->field($event, 'sport_kind')->dropDownList(ArrayHelper::map($sportKinds, 'alias', 'title'), [
                        'prompt'   => Yii::t('event', 'Выберите вид спорта'),
                        'style'    => 'width: 100%;',
                        'disabled' => !$event->isNewRecord
                    ]); ?>
                </div>
                <div class="col-md-3">
                    <?php echo $form->field($event, 'sport_type')->dropDownList(SportTypes::getAll(), [
                        'prompt'   => Yii::t('event', 'Выберите тип спорта'),
                        'style'    => 'width: 100%;',
                        'disabled' => !$event->isNewRecord
                    ]); ?>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <?php echo $form->field($event, 'is_privacy')->dropDownList([0 => 'Открытое для всех', 1 => 'Внутреннее'], [
                            'prompt' => Yii::t('event', 'Выберите приватность'),
                            'style'  => 'width: 100%;'
                        ]); ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <?php echo $form->field($event, 'level')->dropDownList(ActivityLevel::getData(), [
                        'prompt' => Yii::t('event', 'Выберите уровень проведения'),
                        'style'  => 'width: 100%;'
                    ]); ?>
                </div>
                <div class="col-md-3">
                    <?php echo $form->field($event, 'format')->dropDownList(ActivityFormat::getData(), [
                        'prompt' => Yii::t('event', 'Выберите формат проведения'),
                        'style'  => 'width: 100%;'
                    ]); ?>
                </div>
                <div class="col-md-3">
                    <?php echo $form->field($event, 'type')->dropDownList(ActivityTypes::getAll(), [
                        'prompt' => Yii::t('event', 'Выберите тип мероприятия'),
                        'style'  => 'width: 100%;'
                    ]); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <?php echo $form->field($event, 'start_dt')->widget(DatePicker::class, [
                        'options'       => [
                            'placeholder' => Yii::t('event', 'Дата начала'),
                            'value'       => !empty($event->start_dt) ? Yii::$app->formatter->asDate($event->start_dt, 'dd.MM.yyyy') : null,
                        ],
                        'language'      => mb_substr(Yii::$app->language, 0, 2),
                        'clientOptions' => [
                            'autoclose' => true,
                            'format'    => 'dd.mm.yyyy'
                        ]
                    ])->label(Yii::t('event', 'Сроки проведения')); ?>
                </div>
                <div class="col-md-3">
                    <?php echo $form->field($event, 'end_dt')->widget(DatePicker::class, [
                        'options'       => [
                            'placeholder' => Yii::t('event', 'Дата окончания'),
                            'value'       => !empty($event->end_dt) ? Yii::$app->formatter->asDate($event->end_dt, 'dd.MM.yyyy') : null,
                        ],
                        'language'      => mb_substr(Yii::$app->language, 0, 2),
                        'clientOptions' => [
                            'autoclose' => true,
                            'format'    => 'dd.mm.yyyy'
                        ]
                    ])->label('&nbsp;'); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-2">
                    <?php echo $form->field($event, 'age_from')->input('number', ['min' => 0, 'style' => 'width: 100px;'])->label(Yii::t('event', 'Возраст участников от:')); ?>
                </div>
                <div class="col-sm-2">
                    <?php echo $form->field($event, 'age_to')->input('number', ['min' => 0, 'style' => 'width: 100px;'])->label(Yii::t('event', 'до:')); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <?php echo $form->field($event, 'app_strict', [
                        'options'  => ['tag' => false],
                        'template' => "<div class=\"checkbox\">{input} {label}</div>"
                    ])->checkbox(['class' => 'magic-checkbox'], false); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?php echo $form->field($event, 'is_open', [
                        'options'  => ['tag' => false],
                        'template' => '<div class="checkbox">{input} {label} ' . Html::tag('i', null, [
                            'class' => 'fa fa-info-circle',
                            'title' => Yii::t('event', 'Не забудьте отключить эту функцию, когда приём заявок на мероприятие будет закрыт'),
                            'data'  => ['toggle' => 'tooltip']
                        ]) . '</div>',
                    ])->checkbox(['class' => 'magic-checkbox'], false); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?php echo $form->field($event, 'is_public', [
                        'options'  => ['tag' => false],
                        'template' => "<div class=\"checkbox\">{input} {label}</div>"
                    ])->checkbox(['class' => 'magic-checkbox'], false); ?>
                </div>
            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <?php echo $form->field($event, 'description')->widget(Imperavi::class, [
                'settings' => [
                    'imageUpload' => Url::to(['/event/backend/profile/image-upload']),
                    'fileUpload'  => Url::to(['/event/backend/profile/file-upload'])
                ]
            ]); ?>
        </div>
    </div>
</div>
<div class="panel-footer text-right">
    <?php if (!$event->isNewRecord) {
        echo Html::a(Yii::t('general', 'Удалить'), ['/event/backend/profile/delete', 'id' => $event->event_id], [
            'class'        => 'btn btn-danger btn-labeled fa fa-trash pull-left',
            'title'        => Yii::t('yii', 'Delete'),
            'aria-label'   => Yii::t('yii', 'Delete'),
            'data-confirm' => Yii::t('hdbk', 'Вы уверены, что хотите удалить мероприятие?'),
            'data-method'  => 'post'
        ]);
    } ?>
    <?php echo Html::a(Yii::t('general', 'Отменить'), ['index'], ['class' => 'btn btn-default']); ?>
    <?php echo Html::submitButton($event->isNewRecord ? Yii::t('general', 'Создать') : Yii::t('general', 'Сохранить'), ['class' => $event->isNewRecord ? 'btn btn-success' : 'btn btn-primary']); ?>
</div>
<?php ActiveForm::end(); ?>
