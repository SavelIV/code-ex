<?php

namespace app\modules\event\models\searchers;

use app\constants\ApplicationStatuses;
use app\modules\user\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use app\modules\event\models\Event;
use yii\db\ActiveQuery;

class EventSearch extends Event
{
    /* @var string */
    public $name;

    /* @var integer */
    public $player_id;

    /* @var integer */
    public $team_id;

    /* @var User */
    public $admin;

    /* @var integer */
    public $adminStatus;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['event_id', 'season_id', 'league_id', 'organization_id', 'club_id', 'team_id', 'player_id', 'is_open', 'is_public'], 'integer'],
            [['sport_kind', 'full_name', 'short_name', 'name', 'type', 'adminStatus'], 'string'],
            [['start_dt', 'end_dt'], 'safe']
        ];
    }

    /**
     * @param $params []
     * @param $withAdminRequests boolean
     * @return ActiveDataProvider
     */
    public function search($params = [], $withAdminRequests = false)
    {
        $query = Event::find();
        if ($this->admin) {
            $query->byAdmin($this->admin, $withAdminRequests);
        } else if ($withAdminRequests) {
            $query->includeAdminRequests();
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'defaultOrder' => [
                    'start_dt' => SORT_DESC
                ]
            ]
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->team_id) {
            $query->innerJoinWith([
                'approvedTeams' => function(ActiveQuery $query) {
                    $query->andWhere(['team.team_id' => $this->team_id]);
                }
            ]);
        }

        if ($this->player_id) {
            $query->innerJoinWith(['playerApplications' => function(ActiveQuery $query) {
                $query->andWhere([
                    'player_id' => $this->player_id,
                    'status'    => [ApplicationStatuses::STATUS_APPROVED, ApplicationStatuses::STATUS_NEW]
                ]);
            }]);
        }

        $query->andFilterWhere(['or', ['league_id' => $this->league_id], ['organization_id' => $this->organization_id], ['club_id' => $this->club_id]]);

        $query->andFilterWhere([
            'event_id'   => $this->event_id,
            'season_id'  => $this->season_id,
            'sport_kind' => $this->sport_kind,
            'is_open'    => $this->is_open,
            'is_public'  => $this->is_public,
            'type'       => $this->type
        ]);

        $query->andFilterWhere([
            'DATE_FORMAT(start_dt, "%d.%m.%Y")' => $this->start_dt,
            'DATE_FORMAT(end_dt, "%d.%m.%Y")'   => $this->end_dt
        ]);

        $query->andFilterWhere(['and',
            ['like', 'full_name', $this->full_name],
            ['like', 'short_name', $this->short_name],
            ['or',
                ['like', 'full_name', $this->name],
                ['like', 'short_name', $this->name]
            ]
        ]);

        if ($this->adminStatus) {
            $query->innerJoinWith(['eventAdmins eau' => function(ActiveQuery $query) {
                $where = ['eau.status' => $this->adminStatus];

                /* @var $user User */
                $user = Yii::$app->user->identity;
                if (!$user->isAdmin && !$user->checkRole(User::ROLE_ALL_TOURNAMENT_ADMIN)) {
                    $where['eau.user_id'] = $user->id;
                }

                $query->onCondition($where);
            }]);
        }

        $query->groupBy('event.event_id');

        return $dataProvider;
    }
}
