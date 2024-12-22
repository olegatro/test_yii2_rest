<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "request".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $status
 * @property string $message
 * @property string|null $comment
 * @property string $created_at
 * @property string $updated_at
 */
class Request extends ActiveRecord
{
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_RESOLVED = 'Resolved';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            [['name', 'email', 'message', 'status'], 'required'],
            [['status', 'message', 'comment'], 'string'],
            [['name', 'email'], 'string', 'max' => 255],
            ['email', 'email'],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_RESOLVED]],
            ['comment', 'required', 'when' => function ($model) {
                return $model->status === self::STATUS_RESOLVED;
            }]
        ];
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        // пропускаем если это новая запись
        if ($insert === true) {
            return;
        }

        // пропускаем если не изменился статус
        if (!isset($changedAttributes['status'])
            || $changedAttributes['status'] !== self::STATUS_ACTIVE
            || $this->status !== self::STATUS_RESOLVED
        ) {
            return;
        }

        // отправляем письмо
        \Yii::$app->mailer->compose()
            ->setFrom(\Yii::$app->params['senderEmail'])
            ->setTo($this->email)
            ->setSubject('Ответ на заявку')
            ->setTextBody($this->comment)
            ->send();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'status' => 'Status',
            'message' => 'Message',
            'comment' => 'Comment',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
