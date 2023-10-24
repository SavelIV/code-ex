<?php
namespace app\modules\event\controllers\backend;

use app\controllers\backend\DefaultController as BackendDefaultController;
use app\modules\event\models\Event;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class DefaultController extends BackendDefaultController
{
    /* @var Event */
    public $event;

    /**
     * @inheritdoc
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if ($eventId = (int)Yii::$app->request->get('id')) {
            $this->event = Event::findOne($eventId);
            if (!$this->event) {
                throw new NotFoundHttpException();
            }
        }

        if ($this->id == 'backend/profile' && in_array($action->id, ['create', 'image-upload', 'file-upload'])) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function render($view, $params = [])
    {
        return parent::render($view, array_merge($params, [
            'user'              => $this->user,
            'isTournamentAdmin' => $this->isTournamentAdmin(),
            'isAdmin'           => $this->isAdmin(),
            'event'             => $this->event
        ]));
    }
}
