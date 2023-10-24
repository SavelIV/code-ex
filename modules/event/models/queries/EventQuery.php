<?php

namespace app\modules\event\models\queries;

use app\constants\AdminStatuses;
use app\models\queries\CustomActiveQuery;
use app\modules\event\models\Event;
use app\modules\user\models\User;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * Defined methods:
 * @method Event|null one($db = null)
 * @method Event[]    all($db = null)
 */
class EventQuery extends CustomActiveQuery
{
    /**
     * @param $admin User
     * @param $withAdminsRequests boolean
     * @param $status string|string[]
     * @return $this
     */
    public function byAdmin(User $admin, $withAdminsRequests = true, $status = AdminStatuses::STATUS_ACCEPTED)
    {
        if ($withAdminsRequests) {
            $this->includeAdminRequests();
        }

        $this->joinWith([
            'eventAdmins as ea' => function (ActiveQuery $query) use ($status) {
                $query->onCondition(['ea.status' => $status]);
            }
        ]);

        $where = ['or', ['ea.user_id' => $admin->id]];
        if ($admin->isClubAdmin) {
            $where[] = ['event.club_id' => $admin->adminClubIds];
        } else if ($admin->isTeamAdmin) {
            $where[] = ['event.club_id' => ArrayHelper::getColumn($admin->adminTeams, 'club_id')];
        }
        if ($admin->isLeagueAdmin) {
            $where[] = ['event.league_id' => $admin->adminLeagueIds];
        }
        if ($admin->isOrganizationAdmin) {
            $where[] = ['event.organization_id' => $admin->adminOrganizationIds];
        }

        return $this->andWhere($where)->groupBy('event.event_id');
    }

    /**
     * @return $this
     */
    public function includeAdminRequests()
    {
        return $this->select(['event.*', 'COUNT(DISTINCT ear.user_id) AS admin_requests_count'])->joinWith([
            'eventAdmins as ear' => function (ActiveQuery $query) {
                $query->onCondition(['ear.status' => AdminStatuses::STATUS_WAITING]);
            }
        ]);
    }

    /**
     * @return $this
     */
    public function published()
    {
        return $this->andWhere(["{$this->alias}.is_public" => 1]);
    }

    /**
     * @param $alias string
     * @return $this
     */
    public function sort()
    {
        return $this->orderBy([
            "{$this->alias}.start_dt"   => SORT_DESC,
            "{$this->alias}.full_name"  => SORT_ASC,
            "{$this->alias}.short_name" => SORT_ASC
        ]);
    }
}
