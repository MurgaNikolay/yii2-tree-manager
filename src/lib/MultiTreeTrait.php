<?php
namespace voskobovich\tree\manager\lib;

use voskobovich\tree\manager\interfaces\TreeInterface;
use yii\db\ActiveRecord;

/**
 * Class MultiTreeTrait
 * @package voskobovich\tree\manager\lib
 * @property ActiveRecord|TreeInterface $modelClass
 * @property integer $treeId
 * @property string $treeAttribute
 */
trait MultiTreeTrait
{
    /**
     * @var string
     */
    public $_treeAttribute = null;

    /**
     * @var integer
     */
    public $_treeId = null;

    /**
     * @return null|TreeInterface
     */
    public function getRoot()
    {
        $modelClass = $this->modelClass;
        $rootNodes = $modelClass::find()->roots();

        if ($this->treeAttribute) {
            $rootNodes->andWhere([$this->treeAttribute => $this->treeId]);
        }
        $root = $rootNodes->one();
        /**
         * @var TreeInterface $root
         */
        return $root;
    }

    public function getTreeId()
    {
        if ($this->treeAttribute && !$this->_treeId) {
            $this->_treeId = \Yii::$app->getRequest()->get(
                $this->treeAttribute,
                \Yii::$app->getRequest()->post($this->treeAttribute));
        }
        return $this->_treeId;
    }

    public function setTreeId($value)
    {
        $this->_treeId = $value;
    }

    public function getTreeAttribute()
    {
        if ($this->_treeAttribute === null) {
            /**
             * @var ActiveRecord $model
             */
            $model = new $this->modelClass;
            foreach ($model->behaviors() as $behavior) {
                if (!empty($behavior['treeAttribute'])) {
                    $this->_treeAttribute = $behavior['treeAttribute'];
                    break;
                }
            };
        }
        return $this->_treeAttribute;
    }

    public function setTreeAttribute($value)
    {
        $this->_treeAttribute = $value;
    }
}
