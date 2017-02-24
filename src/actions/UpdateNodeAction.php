<?php

namespace voskobovich\tree\manager\actions;

use voskobovich\tree\manager\interfaces\TreeInterface;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * Class UpdateNodeAction
 * @package voskobovich\tree\manager\actions
 */
class UpdateNodeAction extends BaseAction
{
    /**
     * Move a node (model) below the parent and in between left and right
     *
     * @param integer $id the primaryKey of the moved node
     * @return bool
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function run($id)
    {
        /** @var ActiveRecord|TreeInterface $model */
        $model = $this->findModel($id);
        $params = Yii::$app->request->getBodyParams();
        $model->load($params);
        return $model->save(true);
    }
}
