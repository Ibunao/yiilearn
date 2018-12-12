<?php

namespace app\models;

use Yii;
use app\behaviors\OptimisticLockBehavior;
/**
 * This is the model class for table "clocking".
 *
 * @property integer $id
 * @property string $title
 * @property string $var
 */
class Clocking extends \yii\db\ActiveRecord
{

    // 返回乐观锁字段
    public function optimisticLock()
    {
        return 'ver';
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'clocking';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['ver'], 'integer'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'ver' => Yii::t('app', 'Ver'),
        ];
    }
}
