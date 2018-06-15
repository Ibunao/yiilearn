<?php

namespace app\controllers;

use yii\web\Controller;
use yii\data\Pagination;
use app\models\Country;

class CountryController extends Controller
{
    public function actionIndex()
    {
        $query = Country::find();
        // 分页对象
        $pagination = new Pagination([
            'defaultPageSize' => 5,
            'totalCount' => $query->count(),// 数据总数
        ]);
        // 分页的数据
        $countries = $query->orderBy('name')
            ->offset($pagination->offset)// 获取当前的偏移量
            ->limit($pagination->limit)// 获取数据的条数
            ->all();

        return $this->render('index', [
            'countries' => $countries,
            'pagination' => $pagination,
        ]);
    }
}