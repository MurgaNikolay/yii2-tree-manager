<?php

namespace voskobovich\tree\manager\actions;

use voskobovich\tree\manager\lib\MultiTreeTrait;
use Yii;
use voskobovich\tree\manager\interfaces\TreeInterface;
use yii\db\ActiveRecord;
use yii\web\HttpException;

/**
 * Class CreateNodeAction
 * @package voskobovich\tree\manager\actions
 */
class CreateNodeAction extends BaseAction
{
    use MultiTreeTrait;
    /**
     * @return null
     * @throws HttpException
     */
    public function run()
    {
        /** @var TreeInterface|ActiveRecord $model */
        $model = Yii::createObject($this->modelClass);

        $params = Yii::$app->getRequest()->getBodyParams();
        $model->load($params);

        if (!$model->validate()) {
            return $model;
        }

        if ($this->treeAttribute) {
            $this->treeId = $model->{$this->treeAttribute};
        }
        $root = $this->getRoot();
        if (!$root){
            // Use current node as prototype
            $root = clone $model;
            $root->makeRoot()->save();
        }

        return $model->appendTo($root)->save();
    }
}
