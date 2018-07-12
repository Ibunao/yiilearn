<?php

namespace app\controllers;

use yii\rest\ActiveController;
use yii\data\Pagination;
use app\models\Country;

class CountryController extends ActiveController
{
    public $modelClass = 'app\models\Country';
}