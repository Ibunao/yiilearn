<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%admin_users}}".
 *
 * @property string $user_id
 * @property string $status
 * @property string $name
 * @property string $lastlogin
 * @property string $truename
 * @property string $config
 * @property string $favorite
 * @property string $super
 * @property string $lastip
 * @property string $logincount
 * @property string $disabled
 * @property string $op_no
 * @property string $password
 * @property string $memo
 * @property string $role
 */
class AdminUsers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin_users}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'lastlogin', 'logincount'], 'integer'],
            [['status', 'config', 'favorite', 'super', 'disabled', 'memo'], 'string'],
            [['name', 'truename'], 'string', 'max' => 30],
            [['lastip', 'role'], 'string', 'max' => 20],
            [['op_no'], 'string', 'max' => 50],
            [['password'], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => Yii::t('app', 'User ID'),
            'status' => Yii::t('app', 'Status'),
            'name' => Yii::t('app', 'Name'),
            'lastlogin' => Yii::t('app', 'Lastlogin'),
            'truename' => Yii::t('app', 'Truename'),
            'config' => Yii::t('app', 'Config'),
            'favorite' => Yii::t('app', 'Favorite'),
            'super' => Yii::t('app', 'Super'),
            'lastip' => Yii::t('app', 'Lastip'),
            'logincount' => Yii::t('app', 'Logincount'),
            'disabled' => Yii::t('app', 'Disabled'),
            'op_no' => Yii::t('app', 'Op No'),
            'password' => Yii::t('app', 'Password'),
            'memo' => Yii::t('app', 'Memo'),
            'role' => Yii::t('app', 'Role'),
        ];
    }
}
