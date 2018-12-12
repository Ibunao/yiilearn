<?php 
use yii\helpers\Html;

use yii\widgets\ActiveForm;
 ?>
<?php
$form = ActiveForm::begin([
    'id' => 'login-form',
    'options' => ['class' => 'form-horizontal'],
]) ?>
    <?= $form->field($model, 'title') ?>

    <!-- 存放隐藏的锁值 -->
 	<?=Html::activeHiddenInput($model, 'ver'); ?>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('update', ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
<?php ActiveForm::end() ?>