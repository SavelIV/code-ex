<?php

namespace app\modules\event\models;

use app\behaviors\CachedBehavior;
use app\behaviors\SportBehavior;
use app\components\upload\UploadImageBehavior;
use app\constants\ActivityFormat;
use app\constants\ActivityLevel;
use app\constants\AdminStatuses;
use app\constants\ApplicationStatuses;
use app\constants\CacheKeys;
use app\constants\ActivityTypes;
use app\constants\Gender;
use app\constants\Owner;
use app\constants\SportTypes;
use app\helpers\ApplicationHelper;
use app\models\Tag;
use app\modules\event\models\queries\EventQuery;
use app\modules\hdbk\models\HdbkDocument;
use app\modules\hdbk\models\HdbkSeason;
use app\modules\hdbk\models\HdbkSportKind;
use app\modules\league\models\League;
use app\modules\organization\models\Organization;
use app\modules\club\models\Club;
use app\modules\player\models\Player;
use app\modules\team\models\Team;
use app\modules\user\models\User;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * Database fields:
 * @property integer $event_id
 * @property string  $sport_kind
 * @property string  $sport_type
 * @property integer $organization_id
 * @property integer $club_id
 * @property integer $league_id
 * @property integer $season_id
 * @property string  $full_name
 * @property string  $short_name
 * @property string  $description
 * @property string  $logo
 * @property string  $gender
 * @property integer $age_from
 * @property integer $age_to
 * @property string  $level
 * @property string  $format
 * @property string  $type
 * @property string  $start_dt
 * @property string  $end_dt
 * @property string  $app_strict
 * @property integer $is_privacy
 * @property integer $is_open
 * @property integer $is_public
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $is_convert
 *
 * Defined relations:
 * @property Tag               $tag
 * @property Organization      $organization
 * @property Club              $club
 * @property League            $league
 * @property EventAdmin[]      $eventAdmins
 * @property User[]            $admins
 * @property EventForm         $form
 * @property HdbkSeason        $season
 * @property HdbkSportKind     $sportKind
 * @property EventTeam[]       $teamApplications
 * @property EventTeamPlayer[] $playerApplications
 * @property Team[]            $teams
 * @property Team[]            $approvedTeams
 * @property HdbkDocument[]    $documents
 *
 * Defined properties:
 * @property integer  $tagId
 * @property boolean  $appIsActive
 * @property boolean  $isTeam
 * @property boolean  $isPersonal
 * @property string   $sportKindLabel
 * @property string   $fullName
 * @property string   $shortName
 * @property string[] $organizators
 * @property integer  $organizatorsCount
 * @property string   $organizatorsLabel
 * @property integer  $teamsCount
 * @property integer  $approvedTeamsCount
 *
 * Defined methods:
 * @method getThumbUploadUrl($attribute, $size = 'thumb')
 */
class Event extends ActiveRecord
{
    const SCENARIO_API     = 'api';
    const SCENARIO_CONVERT = 'convert';

    /* @var string */
    public $logo_b64;

    /* @var integer */
    public $admin_requests_count;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'event';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            BlameableBehavior::class,
            TimestampBehavior::class,
            [
                'class'       => UploadImageBehavior::class,
                'attribute'   => 'logo',
                'scenarios'   => ['default', self::SCENARIO_API],
                'placeholder' => '@app/modules/event/assets/images/event_logo.png',
                'path'        => 'event/{event_id}/logo',
                'url'         => 'event/{event_id}/logo',
                'thumbs'      => [
                    'thumb'   => ['width' => 1920],
                    '300x300' => ['width' => 300, 'height' => 300],
                    '190x190' => ['width' => 190, 'height' => 190]
                ]
            ],
            'sport' => [
                'class' => SportBehavior::class
            ],
            'cache' => [
                'class'     => CachedBehavior::class,
                'cacheKeys' => CacheKeys::MAIN_COUNTERS,
                'condition' => true
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $defaultParams = [
            'sport_kind', 'sport_type', 'season_id',
            'full_name', 'short_name', 'description', 'logo',
            'gender', 'level', 'format', 'type',
            'start_dt', 'end_dt', 'age_from', 'age_to', 'is_privacy', 'is_open', 'is_public'
        ];
        return array_merge(parent::scenarios(), [
            self::SCENARIO_API           => $defaultParams,
            self::SCENARIO_CONVERT       => ['is_convert'],
            Owner::SCENARIO_CLUB         => array_merge($defaultParams, ['club_id']),
            Owner::SCENARIO_LEAGUE       => array_merge($defaultParams, ['league_id']),
            Owner::SCENARIO_ORGANIZATION => array_merge($defaultParams, ['organization_id'])
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sport_kind', 'sport_type', 'full_name', 'short_name', 'season_id', 'start_dt', 'end_dt'], 'required'],
            [['organization_id', 'club_id', 'league_id', 'is_privacy', 'is_open', 'is_public'], 'integer'],
            [['organization_id'], 'required', 'when' => function() {
                return $this->scenario == Owner::SCENARIO_ORGANIZATION || $this->organization_id;
            }],
            [['league_id'], 'required', 'when' => function() {
                return $this->scenario == Owner::SCENARIO_LEAGUE || $this->league_id;
            }],
            [['club_id'], 'required', 'when' => function() {
                return $this->scenario == Owner::SCENARIO_CLUB || $this->club_id;
            }],
            [['is_public'], 'default', 'value' => 0],
            [['is_privacy'], 'default', 'value' => 0],
            [['full_name'], 'string', 'max' => 128],
            [['short_name'], 'string', 'max' => 32],
            [['description', 'sport_kind', 'sport_type'], 'string'],
            [['sport_kind'], 'default', 'value' => null],
            [['sport_type'], 'in', 'range' => array_keys(SportTypes::getAll())],
            //date rules
            ['start_dt', 'date', 'timestampAttribute' => 'start_dt', 'timestampAttributeFormat' => 'php:Y-m-d H:i:s', 'format' => 'php:d.m.Y', 'on' => self::SCENARIO_DEFAULT],
            ['end_dt', 'date', 'timestampAttribute' => 'end_dt', 'timestampAttributeFormat' => 'php:Y-m-d H:i:s', 'format' => 'php:d.m.Y', 'on' => self::SCENARIO_DEFAULT],
            ['start_dt', 'compare', 'compareAttribute' => 'end_dt', 'operator' => '<=', 'when' => function() {
                return !empty($this->end_dt);
            }],
            ['end_dt', 'compare', 'compareAttribute' => 'start_dt', 'operator' => '>=', 'when' => function() {
                return !empty($this->start_dt);
            }],
            //logo rules
            [['logo'], 'image', 'extensions' => 'jpg, jpeg, gif, png'],
            [['logo_b64'], 'safe'],
            // gender, format, level, type rules
            [['gender'], 'string', 'max' => 5],
            [['gender'], 'in', 'range' => array_keys(Gender::getData())],
            [['level', 'format', 'type'], 'string', 'max' => 15],
            [['level'], 'in', 'range' => array_keys(ActivityLevel::getData())],
            [['format'], 'in', 'range' => array_keys(ActivityFormat::getData())],
            [['type'], 'in', 'range' => array_keys(ActivityTypes::getData())],
            [['level', 'format', 'type'], 'default', 'value' => null],
            //app_strict
            ['app_strict', 'integer', 'min' => 0, 'max' => 1],
            ['app_strict', 'default', 'value' => 1],
            //age rules
            [['age_from', 'age_to'], 'integer'],
            [['age_from', 'age_to'], 'default', 'value' => null],
            ['age_from', 'compare', 'compareAttribute' => 'age_to', 'operator' => '<=', 'when' => function() {
                return !empty($this->age_to);
            }],
            [['age_to'], 'compare', 'compareAttribute' => 'age_from', 'operator' => '>=', 'when' => function() {
                return !empty($this->age_from);
            }],
            //is_convert rules
            [['is_convert'], 'integer'],
            [['is_convert'], 'default', 'value' => 0]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'event_id'        => Yii::t('event', 'Event ID'),
            'sport_kind'      => Yii::t('event', 'Вид спорта'),
            'sport_type'      => Yii::t('event', 'Тип спорта'),
            'organization_id' => Yii::t('event', 'Организация'),
            'club_id'         => Yii::t('event', 'Клуб'),
            'league_id'       => Yii::t('event', 'Лига'),
            'season_id'       => Yii::t('event', 'Сезон'),
            'full_name'       => Yii::t('event', 'Полное название'),
            'short_name'      => Yii::t('event', 'Краткое название'),
            'description'     => Yii::t('event', 'Описание'),
            'logo'            => Yii::t('event', 'Логотип'),
            'gender'          => Yii::t('event', 'Пол'),
            'age_from'        => Yii::t('event', 'Возраст от:'),
            'age_to'          => Yii::t('event', 'Возраст до:'),
            'level'           => Yii::t('event', 'Уровень'),
            'format'          => Yii::t('event', 'Формат'),
            'type'            => Yii::t('event', 'Тип'),
            'start_dt'        => Yii::t('event', 'Дата начала'),
            'end_dt'          => Yii::t('event', 'Дата завершения'),
            'app_strict'      => Yii::t('event', 'Заявка спортсмена от двух и более команд одновременно запрещена'),
            'is_privacy'      => Yii::t('event', 'Приватность'),
            'is_open'         => Yii::t('event', 'Открыт прием заявок'),
            'is_public'       => Yii::t('event', 'Опубликовать на сайте'),
            'created_by'      => Yii::t('general', 'Автор записи'),
            'updated_by'      => Yii::t('general', 'Автор изменений'),
            'created_at'      => Yii::t('general', 'Дата и время создания'),
            'updated_at'      => Yii::t('general', 'Дата и время обновления'),
            'is_convert'      => Yii::t('general', 'Конвертировано во "Встречи"')
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            $tag = new Tag();
            $tag->module    = Tag::MODULE_EVENT;
            $tag->item_id   = $this->event_id;
            $tag->full_name = $this->full_name;
            $tag->name      = $this->short_name;
            $tag->save();

            $form = new EventForm();
            $form->event_id = $this->event_id;
            $form->save();
        }
    }

    /**
     * @inheritdoc
     * @throws \Throwable
     * @throws yii\db\StaleObjectException
     */
    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->tag) {
            $this->tag->delete();
        }

        if ($this->form) {
            $this->form->delete();
        }

        foreach ($this->teamApplications as $application) {
            $application->delete();
        }

        foreach ($this->playerApplications as $application) {
            $application->delete();
        }

        foreach ($this->documents as $document) {
            $document->delete();
        }

        EventAdmin::deleteAll(['event_id' => $this->event_id]);
    }

    /**
     * @return EventQuery
     */
    public static function find()
    {
        return new EventQuery(get_called_class());
    }

    /**
     * @return ActiveQuery
     */
    public function getTag()
    {
        return $this->hasOne(Tag::class, ['item_id' => 'event_id'])->andWhere(['module' => Tag::MODULE_EVENT]);
    }

    /**
     * @return integer|null
     */
    public function getTagId()
    {
        return $this->tag->tag_id ?? null;
    }

    /**
     * @return ActiveQuery
     */
    public function getForm()
    {
        return $this->hasOne(EventForm::class, ['event_id' => 'event_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['organization_id' => 'organization_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getClub()
    {
        return $this->hasOne(Club::class, ['club_id' => 'club_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getLeague()
    {
        return $this->hasOne(League::class, ['league_id' => 'league_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSeason()
    {
        return $this->hasOne(HdbkSeason::class, ['season_id' => 'season_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTeamApplications()
    {
        return $this->hasMany(EventTeam::className(), ['event_id' => 'event_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getPlayerApplications()
    {
        return $this->hasMany(EventTeamPlayer::class, ['event_id' => 'event_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getDocuments()
    {
        return $this->hasMany(HdbkDocument::class, ['item_id' => 'event_id'])
            ->andWhere(['hdbk_document.module' => HdbkDocument::MODULE_EVENT]);
    }

    /**
     * @return boolean
     */
    public function getAppIsActive()
    {
        return (bool)$this->is_open;
    }

    /**
     * @param $status string
     * @param $withStats boolean
     * @param $teamTitle string
     * @return ActiveQuery
     * @throws \Throwable
     */
    public function getEventTeams($status = null, $withStats = false, $teamTitle = null)
    {
        $query = $this->getTeamApplications()
            ->joinWith('profile')
            ->andFilterWhere(['event_team.status' => $status])
            ->andWhere(['team.is_archived' => 0]);
        if ($withStats) {
            $query->select([
                'event_team.*',
                'COUNT(DISTINCT tp.player_id) AS players_count',
                'COUNT(DISTINCT atp.player_id) AS approved_players_count'
            ])
            ->joinWith([
                'teamPlayers as tp' => function (ActiveQuery $query) {
                    $query->onCondition(['tp.status' => [
                        ApplicationStatuses::STATUS_NEW,
                        ApplicationStatuses::STATUS_APPROVED
                    ]]);
                }
            ])
            ->joinWith([
                'teamPlayers as atp' => function (ActiveQuery $query) {
                    $query->onCondition(['atp.status' => ApplicationStatuses::STATUS_APPROVED]);
                }
            ])
            ->andFilterWhere(['or',
                ['like', 'team.full_name', $teamTitle],
                ['like', 'team.short_name', $teamTitle]
            ])
            ->groupBy(['event_team.team_id'])
            ->orderBy(['event_team.name' => SORT_ASC]);
        }

        if (in_array($status, [ApplicationStatuses::STATUS_DRAFT, ApplicationStatuses::STATUS_NEW])) {
            /* @var $user User */
            $user = Yii::$app->user->identity;
            if (!$user->checkRole(User::ROLE_SUPER_ADMIN | User::ROLE_ADMIN | User::ROLE_TOURNAMENT_ADMIN)) {
                $query->joinWith(['team.teamAdmins ta' => function(ActiveQuery $query) use ($user) {
                    $query->andWhere([
                        'ta.user_id' => $user->id,
                        'ta.status'  => AdminStatuses::STATUS_ACCEPTED
                    ]);
                }]);
            }
        }

        return $query;
    }

    /**
     * @param string|null $status
     * @return ActiveQuery
     */
    public function getTeams($status = null)
    {
        return $this->hasMany(Team::class, ['team_id' => 'team_id'])
            ->via('eventTeams', function(ActiveQuery $query) use ($status) {
                $query->andFilterWhere(['event_team.status' => $status]);
            })->orderBy(['team.full_name' => SORT_ASC, 'team.short_name' => SORT_ASC]);
    }

    /**
     * @return integer
     */
    public function getTeamsCount()
    {
        return $this->getTeams()->count();
    }

    /**
     * @return ActiveQuery
     */
    public function getApprovedTeams()
    {
        return $this->getTeams(ApplicationStatuses::STATUS_APPROVED);
    }

    /**
     * @return integer
     */
    public function getApprovedTeamsCount()
    {
        return $this->getApprovedTeams()->count();
    }

    /**
     * @param $status string
     * @param $playerName string
     * @return ActiveQuery
     */
    public function getEventPlayers($status = null, $playerName = null)
    {
        return $this->getPlayerApplications()
            ->joinWith(['profile'])
            ->with(['data' => function(ActiveQuery $query) {
                $query->indexBy('characteristic_id');
            }])
            ->andFilterWhere(['and',
                ['or',
                    ['like', 'concat_ws(" ", first_name, last_name, middle_name)', $playerName],
                    ['like', 'concat_ws(" ", last_name, first_name, middle_name)', $playerName]
                ],
                ['event_team_player.status' => $status]
            ]);
    }

    /**
     * @param $userId integer
     * @param $teamTitle string
     * @return ActiveQuery
     */
    public function getAvailableTeams($userId = null, $teamTitle = null)
    {
        $query = Team::find()
            ->leftJoin('event_team et', 'et.team_id = team.team_id and et.event_id = :event_id')
            ->andWhere(['et.team_id' => null, 'team.is_archived' => 0])
            ->andFilterWhere(['or',
                ['like', 'full_name', $teamTitle],
                ['like', 'short_name', $teamTitle]
            ])->addParams([':event_id' => $this->event_id]);

        if ($userId) {
            $query->innerJoinWith('teamAdmins ta')->andWhere([
                'ta.user_id' => $userId,
                'ta.status'  => AdminStatuses::STATUS_ACCEPTED
            ]);
        }

        return $query;
    }

    /**
     * @param $teamIds integer|integer[]
     * @param $playerName string
     * @return ActiveQuery
     */
    public function getAvailablePlayers($teamIds = [], $playerName = null)
    {
        $query = Player::find()
            ->leftJoin('event_team_player etp', 'etp.player_id = player.player_id and etp.event_id = :event_id')
            ->andWhere(['etp.player_id' => null])
            ->andFilterWhere(['or',
                ['like', 'concat_ws(" ", first_name, last_name, middle_name)', $playerName],
                ['like', 'concat_ws(" ", last_name, first_name, middle_name)', $playerName]
            ])
            ->andFilterWhere(['and',
                ['>=', 'TIMESTAMPDIFF(YEAR, player.birthday, CURDATE())', $this->age_from],
                ['<=', 'TIMESTAMPDIFF(YEAR, player.birthday, CURDATE())', $this->age_to]
            ])
            ->addParams([':event_id' => $this->event_id]);

        if (!empty($teamIds)) {
            $query->innerJoinWith(['teams'])->andFilterWhere(['team.team_id' => $teamIds]);
        }

        return $query;
    }

    /**
     * @param $teamId integer
     * @return EventTeam
     */
    public function getEventTeam($teamId)
    {
        return EventTeam::findOne(['event_id' => $this->event_id, 'team_id' => $teamId]);
    }

    /**
     * @param $playerId integer
     * @return EventTeamPlayer
     */
    public function getEventTeamPlayer($playerId)
    {
        return EventTeamPlayer::findOne(['event_id' => $this->event_id, 'player_id' => $playerId]);
    }

    /**
     * @param $status string
     * @return ActiveQuery
     */
    public function getMembers($status = ApplicationStatuses::STATUS_APPROVED)
    {
        return $this->hasMany($this->isTeam ? EventTeam::class : EventTeamPlayer::class, ['event_id' => 'event_id'])
            ->andFilterWhere(['status' => $status]);
    }

    /**
     * @return integer
     */
    public function getApprovedMembersCount()
    {
        return $this->getMembers()->count();
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->full_name;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->short_name ?: $this->full_name;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $now = date('Y-m-d H:i:s');
        if ($now > $this->start_dt && $now < $this->end_dt && $this->is_open) {
            return Yii::t('event', 'Приём заявок');
        } else if ($now > $this->start_dt && $now < $this->end_dt) {
            return Yii::t('event', 'Идёт');
        } else if ($now > $this->end_dt) {
            return Yii::t('event', 'Завершён');
        } else {
            return Yii::t('event', 'Планируется');
        }
    }

    /**
     * @return string[]
     */
    public function getOrganizators()
    {
        $organizators = array_filter([
            $this->club_id         ? Html::a($this->club->shortName, $this->club->frontendUrl, ['target' => '_blank']) : null,
            $this->league_id       ? Html::a($this->league->shortName, $this->league->frontendUrl, ['target' => '_blank']) : null,
            $this->organization_id ? Html::a($this->organization->shortName, $this->organization->frontendUrl, ['target' => '_blank']) : null
        ]);

        sort($organizators);

        return $organizators;
    }

    /**
     * @return integer
     */
    public function getOrganizatorsCount()
    {
        return count($this->organizators);
    }

    /**
     * @return string
     */
    public function getOrganizatorsLabel()
    {
        return $this->organizators ? implode(', ', $this->organizators) : Yii::t('event', 'Не указан');
    }

    /**
     * @param Player $player
     * @return boolean
     * @throws \Exception
     */
    public function canApplication(Player $player)
    {
        return $player->user &&
            ApplicationHelper::checkPrivacy($this, $player) &&
            ApplicationHelper::checkAge($this, $player);
    }

    /**
     * @param $player Player
     * @param $teamId integer
     * @return boolean
     */
    public function hasApplication(Player $player, $teamId = null)
    {
        return EventTeamPlayer::find()->andFilterWhere([
            'event_id'  => $this->event_id,
            'team_id'   => $teamId,
            'player_id' => $player->player_id
        ])->exists();
    }

    /**
     * @param $status string
     * @return ActiveQuery
     */
    public function getEventAdmins($status = null)
    {
        return $this->hasMany(EventAdmin::class, ['event_id' => 'event_id'])
            ->andFilterWhere(['status' => $status]);
    }

    /**
     * @param $status string
     * @return ActiveQuery
     */
    public function getAdmins($status = null)
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('eventAdmins', function (ActiveQuery $query) use ($status) {
                $query->onCondition(['status' => $status]);
            });
    }

    /**
     * @param $user User
     * @return boolean
     */
    public function isAdmin($user)
    {
        if (
            $user->isAdmin || $user->checkRole(User::ROLE_ALL_TOURNAMENT_ADMIN) ||
            ($user->checkRole(User::ROLE_TOURNAMENT_ADMIN) && in_array($this->event_id, $user->adminEventIds)) ||
            ($this->league_id && $user->isLeagueAdmin && in_array($this->league_id, $user->adminLeagueIds)) ||
            ($this->organization_id && $user->isOrganizationAdmin && in_array($this->organization_id, $user->adminOrganizationIds)) ||
            ($this->club_id && $user->isClubAdmin && in_array($this->club_id, $user->adminClubIds))
        ) {
            return true;
        }

        return EventAdmin::find()->andWhere([
            'event_id' => $this->event_id,
            'user_id'  => $user->id,
            'status'   => AdminStatuses::STATUS_ACCEPTED
        ])->exists();
    }

    /**
     * @return boolean
     */
    public static function canCreate()
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        /* @var $user User */
        $user = Yii::$app->user->identity;
        return $user->isAdmin || $user->checkRole(User::ROLE_ALL_TOURNAMENT_ADMIN) ||
            $user->isOrganizationAdmin || $user->isLeagueAdmin || $user->isClubAdmin;
    }
}
