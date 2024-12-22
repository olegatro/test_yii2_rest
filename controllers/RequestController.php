<?php

namespace app\controllers;

use app\models\Request;
use yii\base\DynamicModel;
use yii\data\ActiveDataFilter;
use yii\filters\Cors;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;

class RequestController extends ActiveController
{
    public $modelClass = Request::class;

    public function actions()
    {
        $actions = parent::actions();

        // фильтрация по статусу и дате создания
        $actions['index']['dataFilter'] = [
            'class' => ActiveDataFilter::class,
            'searchModel' => (new DynamicModel(['status']))
                ->addRule('status', 'in', ['range' => [Request::STATUS_ACTIVE, Request::STATUS_RESOLVED]])
                ->addRule('created_at', 'datetime', ['timestampAttribute' => 'created_at', 'format' => 'php:Y-m-d H:i:s'])
        ];

        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // разные варианты аутентификации
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'authMethods' => [
                HttpBasicAuth::class,
                HttpBearerAuth::class,
                QueryParamAuth::class
            ],
            'except' => ['create', 'options']
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // cors
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['http://myserver.com']
            ]
        ];

        $behaviors['authenticator'] = $auth;

        return $behaviors;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        // пропускаем если гость, т.к. authenticator и checkAccess о друг друге не знают
        if (\Yii::$app->user->isGuest) {
            return;
        }

        // ограничиваем только для админа (лучше RBAC - но времени не хватило)
        if (\Yii::$app->user->id != '100') {
            throw new ForbiddenHttpException();
        }
    }
}
