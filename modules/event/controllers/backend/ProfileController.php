<?php

namespace app\modules\event\controllers\backend;

use app\constants\Owner;
use app\modules\club\models\Club;
use app\modules\event\models\Event;
use app\modules\hdbk\models\HdbkSeason;
use app\modules\hdbk\models\HdbkSportKind;
use app\modules\league\models\League;
use app\modules\organization\models\Organization;
use app\traits\AjaxValidationTrait;
use app\widgets\Imperavi\actions\DocumentUploadAction;
use app\widgets\Imperavi\actions\ImageUploadAction;
use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ProfileController extends DefaultController
{
    use AjaxValidationTrait;

    /* @var string */
    public $layout = 'default';

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'image-upload' => ['class' => ImageUploadAction::class, 'module' => 'event'],
            'file-upload'  => ['class' => DocumentUploadAction::class, 'module' => 'event']
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST']
                ]
            ]
        ];
    }

    /**
     * @param $organization_id integer
     * @param $club_id integer
     * @param $league_id integer
     * @return string|Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws yii\base\ExitException
     * @throws yii\base\InvalidConfigException
     */
    public function actionCreate($organization_id = null, $league_id = null, $club_id = null)
    {
        if (!Event::canCreate()) {
            throw new ForbiddenHttpException();
        }

        $this->layout = '//nifty/cabinet';

        $this->event = new Event();
        $this->setOwnerCreateParams($this->event);

        $this->performAjaxValidation($this->event);
        if ($this->event->load(Yii::$app->request->post())) {
            if ($this->event->save()) {
                Yii::$app->getSession()->setFlash('success', Yii::t('event', 'Мероприятие добавлено!'));
                return $this->redirect(['update', 'id' => $this->event->event_id]);
            } else {
                Yii::$app->getSession()->setFlash('danger', Yii::t('event', 'Произошла ошибка при добавлении мероприятия!'));
            }
        }

        if ($this->isAllTournamentAdmin()) {
            $organizations = Organization::find()->all();
            $leagues       = League::find()->notArchived()->all();
            $clubs         = Club::find()->all();
        } else {
            $organizations = $this->isOrganizationAdmin() ? $this->user->adminOrganizations : [];
            $leagues       = $this->isLeagueAdmin()       ? $this->user->adminLeagues       : [];
            $clubs         = $this->isClubAdmin()         ? $this->user->adminClubs         : [];
        }

        $seasons = HdbkSeason::find()->last()->all();
        $sportKinds = HdbkSportKind::find()->published()->all();

        return $this->render('create', [
            'seasons'             => $seasons,
            'sportKinds'          => $sportKinds,
            'organizations'       => $organizations,
            'leagues'             => $leagues,
            'clubs'               => $clubs,
            'isOrganizationAdmin' => $this->isOrganizationAdmin(),
            'isLeagueAdmin'       => $this->isLeagueAdmin(),
            'isClubAdmin'         => $this->isClubAdmin()
        ]);
    }

    /**
     * @param $id integer
     * @return string|Response
     * @throws yii\base\ExitException
     * @throws yii\base\InvalidConfigException
     */
    public function actionUpdate($id)
    {
        $this->performAjaxValidation($this->event);
        if ($this->event->load(Yii::$app->request->post())) {
            if ($this->event->save()) {
                Yii::$app->getSession()->setFlash('success', Yii::t('event', 'Мероприятие обновлено!'));
                return $this->refresh();
            } else {
                Yii::$app->getSession()->setFlash('danger', Yii::t('event', 'Произошла ошибка при обновлении мероприятия!'));
            }
        }

        $organizations = $this->event->organization_id ? [$this->event->organization] : Organization::find()->all();
        $clubs         = $this->event->club_id         ? [$this->event->club]         : Club::find()->all();
        $leagues       = $this->event->league_id       ? [$this->event->league]       : League::find()->notArchived()->all();

        $seasons = HdbkSeason::find()->last()->all();
        $sportKinds = HdbkSportKind::find()->published()->all();

        return $this->render('update', [
            'organizations'       => $organizations,
            'leagues'             => $leagues,
            'clubs'               => $clubs,
            'seasons'             => $seasons,
            'sportKinds'          => $sportKinds,
            'isLeagueAdmin'       => $this->isLeagueAdmin(),
            'isOrganizationAdmin' => $this->isOrganizationAdmin(),
            'isClubAdmin'         => $this->isClubAdmin()
        ]);
    }

    /**
     * @param $id integer
     * @return Response
     * @throws \Throwable
     * @throws yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        if ($this->event->delete()) {
            Yii::$app->getSession()->setFlash('success', Yii::t('event', 'Мероприятие удалено!'));
        } else {
            Yii::$app->getSession()->setFlash('danger', Yii::t('event', 'Произошла ошибка при удалении мероприятия!'));
        }

        return $this->redirect(['/event/backend/search/index']);
    }

    /**
     * @param $id integer
     * @return string|Response
     * @throws yii\base\ExitException
     * @throws yii\base\InvalidConfigException
     */
    public function actionTag($id)
    {
        $tag = $this->event->tag;

        $this->performAjaxValidation($tag);
        if ($tag->load(Yii::$app->request->post())) {
            if ($tag->save()) {
                Yii::$app->getSession()->setFlash('success', Yii::t('event', 'Тег мероприятия обновлён!'));
            } else {
                Yii::$app->getSession()->setFlash('danger', Yii::t('event', 'Произошла ошибка при обновлении тега мероприятия!'));
            }

            return $this->refresh();
        }

        return $this->render('tag', [
            'tag' => $tag
        ]);
    }
}
