<?php

namespace app\models;

use Exception;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "invoice".
 *
 * @property int $id
 * @property string $no
 * @property string $date
 * @property int|null $created_at
 * @property int|null $created_by
 * @property int|null $updated_at
 * @property int|null $updated_by
 */
class Invoice extends \yii\db\ActiveRecord
{
    const ERROR_CODE_MYSQL_DUPLICATE_ENTRY = '23000';
    const PREFIX_NO = 'IN';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['no'], 'string', 'max' => 16],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'no' => Yii::t('app', 'No'),
            'date' => Yii::t('app', 'Date'),
            'created_at' => Yii::t('app', 'Created At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'updated_by' => Yii::t('app', 'Updated By'),
        ];
    }

    public function gernerateNo()
    {
        if(Yii::$app->controller->action->id == 'create')
        {
            $autoIncrement = '00001';
            $generatedPrefixNo = self::PREFIX_NO.date('Ym');
            $invoice = Invoice::find()
                ->where(['LIKE', 'no', $generatedPrefixNo])
                ->limit(1)
                ->addOrderBy(['no' => SORT_DESC])
                ->one();
            
            if($invoice) 
            {
                $lastNo = (int)str_replace($generatedPrefixNo, '', $invoice->no);
                $autoIncrement = str_pad($lastNo + 1, 5, 0, STR_PAD_LEFT);
            }
            
            $this->no = $generatedPrefixNo.$autoIncrement;
        }
    }

    public function saveModel()
    {
        $transaaction = Yii::$app->db->beginTransaction();
        
        try
        {
            $this->gernerateNo();
            if($this->save())
            {
                $transaaction->commit();
                return true;
            }
        }
        catch(Exception $ex)
        {
            if($ex->getCode() == self::ERROR_CODE_MYSQL_DUPLICATE_ENTRY)
            {
                $this->saveModel();
            }

            $transaaction->rollBack();
            throw new Exception($ex->getMessage());
        }

        return false;
    }
}
