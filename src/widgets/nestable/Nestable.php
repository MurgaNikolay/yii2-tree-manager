<?php

namespace voskobovich\tree\manager\widgets\nestable;

use voskobovich\tree\manager\interfaces\TreeInterface;
use voskobovich\tree\manager\lib\MultiTreeTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\bootstrap\ActiveForm;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\Pjax;

/**
 * Class Nestable
 * @package voskobovich\tree\manager\widgets
 */
class Nestable extends Widget
{

    use MultiTreeTrait;
    /**
     * @var string
     */
    public $id;

    /**
     * @var array
     */
    public $modelClass;

    /**
     * @var array
     */
    public $nameAttribute = 'name';


    /**
     * Behavior key in list all behaviors on model
     * @var string
     */
    public $behaviorName = 'nestedSetsBehavior';

    /**
     * @var array.
     */
    public $pluginOptions = [];

    /**
     * Url to MoveNodeAction
     * @var string
     */
    public $moveUrl;

    /**
     * Url to CreateNodeAction
     * @var string
     */
    public $createUrl;

    /**
     * Url to UpdateNodeAction
     * @var string
     */
    public $updateUrl;

    /**
     * Url to page additional update model
     * @var string
     */
    public $advancedUpdateRoute;

    /**
     * Url to DeleteNodeAction
     * @var string
     */
    public $deleteUrl;

    /**
     * Handler for render form fields on create new node
     * @var callable
     */
    public $formFieldsCallable;

    /**
     * Handler for render form fields on create new node
     * @var callable
     */
    public $shortFormFieldsCallable;

    /**
     * Структура меню в php array формате
     * @var array
     */
    private $_items = [];

    /**
     * Инициализация плагина
     */
    public function init()
    {
        parent::init();

        if (empty($this->id)) {
            $this->id = $this->getId();
        }

        if ($this->modelClass == null) {
            throw new InvalidConfigException('Param "modelClass" must be contain model name');
        }

        if (null == $this->behaviorName) {
            throw new InvalidConfigException("No 'behaviorName' supplied on action initialization.");
        }

        if ($this->formFieldsCallable == null) {
            $this->formFieldsCallable = function ($form, $model) {
                /** @var ActiveForm $form */
                echo $form->field($model, $this->nameAttribute);
            };
        }
        if ($this->shortFormFieldsCallable === null) {
            $this->shortFormFieldsCallable = $this->formFieldsCallable;
        }
    }


    /**
     * @param null $name
     * @return array
     */
    private function getPluginOptions($name = null)
    {
        $options = ArrayHelper::merge($this->getDefaultPluginOptions(), $this->pluginOptions);

        if (isset($options[$name])) {
            return $options[$name];
        }

        return $options;
    }

    /**
     * Работаем!
     */
    public function run()
    {
        $this->registerActionButtonsAssets();
        $this->actionButtons();

        Pjax::begin([
            'id' => $this->id . '-pjax'
        ]);
        $this->registerPluginAssets();
        $this->renderTree();
        $this->renderForm();
        Pjax::end();

        $this->actionButtons();
    }

    /**
     * Register Asset manager
     */
    private function registerPluginAssets()
    {
        NestableAsset::register($this->getView());

        $view = $this->getView();

        $pluginOptions = $this->getPluginOptions();
        $pluginOptions = Json::encode($pluginOptions);
        $view->registerJs("$('#{$this->id}').nestable({$pluginOptions});");
        $view->registerJs("
			$('#{$this->id}-new-node-form').on('beforeSubmit', function(e){
                $.ajax({
                    url: '{$this->getPluginOptions('createUrl')}',
                    method: 'POST',
                    data: $(this).serialize()
                }).success(function (data, textStatus, jqXHR) {
                    $('#{$this->id}-new-node-modal').modal('hide')
                    window.location.reload();
                    //$.pjax.reload({container: '#{$this->id}-pjax'});
                    window.scrollTo(0, document.body.scrollHeight);
                }).fail(function (jqXHR) {
                    alert(jqXHR.responseText);
                });

                return false;
			});
		");
    }

    /**
     * Register Asset manager
     */
    private function registerActionButtonsAssets()
    {
        $view = $this->getView();
        $view->registerJs("
			$('.{$this->id}-nestable-menu [data-action]').on('click', function(e) {
                e.preventDefault();

				var target = $(e.target),
				    action = target.data('action');

				switch (action) {
					case 'expand-all':
					    $('#{$this->id}').nestable('expandAll');
					    $('.{$this->id}-nestable-menu [data-action=\"expand-all\"]').hide();
					    $('.{$this->id}-nestable-menu [data-action=\"collapse-all\"]').show();

						break;
					case 'collapse-all':
					    $('#{$this->id}').nestable('collapseAll');
					    $('.{$this->id}-nestable-menu [data-action=\"expand-all\"]').show();
					    $('.{$this->id}-nestable-menu [data-action=\"collapse-all\"]').hide();

						break;
				}
			});
		");
    }

    /**
     * Generate default plugin options
     * @return array
     */
    private function getDefaultPluginOptions()
    {
        $options = [
            'namePlaceholder' => $this->getPlaceholderForName(),
            'deleteAlert' => Yii::t('vendor/voskobovich/yii2-tree-manager/widgets/nestable',
                'The nobe will be removed together with the children. Are you sure?'),
            'newNodeTitle' => Yii::t('vendor/voskobovich/yii2-tree-manager/widgets/nestable',
                'Enter the new node name'),
        ];

        $controller = Yii::$app->controller;
        if ($controller) {
            $options['moveUrl'] = Url::to(["{$controller->id}/moveNode"]);
            $options['createUrl'] = Url::to(["{$controller->id}/createNode"]);
            $options['updateUrl'] = Url::to(["{$controller->id}/updateNode"]);
            $options['deleteUrl'] = Url::to(["{$controller->id}/deleteNode"]);
        }

        if ($this->moveUrl) {
            $this->pluginOptions['moveUrl'] = $this->moveUrl;
        }
        if ($this->createUrl) {
            $this->pluginOptions['createUrl'] = $this->createUrl;
        }
        if ($this->updateUrl) {
            $this->pluginOptions['updateUrl'] = $this->updateUrl;
        }
        if ($this->deleteUrl) {
            $this->pluginOptions['deleteUrl'] = $this->deleteUrl;
        }

        return $options;
    }

    /**
     * Get placeholder for Name input
     */
    public function getPlaceholderForName()
    {
        return Yii::t('vendor/voskobovich/yii2-tree-manager/widgets/nestable', 'Node name');
    }

    /**
     * Кнопки действий над виджетом
     */
    public function actionButtons()
    {
        echo Html::beginTag('div', ['class' => "{$this->id}-nestable-menu"]);

        echo Html::beginTag('div', ['class' => 'btn-group']);
        echo Html::button(Yii::t('vendor/voskobovich/yii2-tree-manager/widgets/nestable', 'Add node'), [
            'data-toggle' => 'modal',
            'data-target' => "#{$this->id}-new-node-modal",
            'class' => 'btn btn-success'
        ]);
        echo Html::button(Yii::t('vendor/voskobovich/yii2-tree-manager/widgets/nestable', 'Collapse all'), [
            'data-action' => 'collapse-all',
            'class' => 'btn btn-default'
        ]);
        echo Html::button(Yii::t('vendor/voskobovich/yii2-tree-manager/widgets/nestable', 'Expand all'), [
            'data-action' => 'expand-all',
            'class' => 'btn btn-default',
            'style' => 'display: none'
        ]);
        echo Html::endTag('div');

        echo Html::endTag('div');
    }

    /**
     * Вывод меню
     */
    private function renderTree()
    {
        $root = $this->getRoot();

        echo Html::beginTag('div', ['class' => 'dd-nestable', 'id' => $this->id]);
        if ($root) {
            $this->renderNodes($this->getRoot()->children);
        }
        echo Html::endTag('div');
    }


    /**
     * Render form for new node
     */
    private function renderForm()
    {
        /** @var ActiveRecord $model */
        $model = new $this->modelClass;
        if ($this->treeAttribute) {
            $model->{$this->treeAttribute} = $this->treeId;
        }
        echo <<<HTML
<div class="modal" id="{$this->id}-new-node-modal" tabindex="-1" role="dialog" aria-labelledby="newNodeModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
HTML;
        /** @var ActiveForm $form */
        $form = ActiveForm::begin([
            'id' => $this->id . '-new-node-form',
        ]);

        echo <<<HTML
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="newNodeModalLabel">New node</h4>
      </div>
      <div class="modal-body">
HTML;

        echo call_user_func($this->formFieldsCallable, $form, $model);
        if ($this->treeAttribute) {
            echo $form->field($model, $this->treeAttribute)->hiddenInput()->label(false);
        }

        echo <<<HTML
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Create node</button>
      </div>
HTML;
        $form->end();
        echo <<<HTML
    </div>
  </div>
</div>
HTML;
    }

    /**
     * Распечатка одного уровня
     * @param $level
     */
    private function renderNodes($nodes)
    {
        echo Html::beginTag('ol', ['class' => 'dd-list']);
        foreach ($nodes as $node) {
            $this->renderNode($node);
        }

        echo Html::endTag('ol');
    }

    /**
     * Print one line
     * @param ActiveRecord|TreeInterface $node
     */
    private function renderNode($node)
    {
        $htmlOptions = ['class' => 'dd-item'];
        $htmlOptions['data-id'] = $node->getPrimaryKey();

        echo Html::beginTag('li', $htmlOptions);

        echo Html::tag('div', '', ['class' => 'dd-handle']);
        echo Html::tag('div', $node->{$this->nameAttribute}, ['class' => 'dd-content']);

        echo Html::beginTag('div', ['class' => 'dd-edit-panel']);

        if ($this->shortFormFieldsCallable) {
            $form = ActiveForm::begin([
                'id' => $node->getPrimaryKey() . '-update-node-form',
                'options' => [
                    'class' => 'dd-edit-form'
                ]
            ]);
            echo call_user_func($this->shortFormFieldsCallable, $form, $node);
        }

        echo Html::beginTag('div', ['class' => 'btn-group']);

        if ($this->advancedUpdateRoute) {
            echo Html::a(Yii::t('vendor/voskobovich/yii2-tree-manager/widgets/nestable', 'Advanced editing'),
                Url::to([$this->advancedUpdateRoute, 'id' => $node->getPrimaryKey()]), [
                    'data-action' => 'advanced-editing',
                    'data-pjax' => false,
                    'class' => 'btn btn-default btn-sm',
                    'target' => '_blank'
                ]);
        }

        echo Html::button(Yii::t('vendor/voskobovich/yii2-tree-manager/widgets/nestable', 'Delete'), [
            'data-action' => 'delete',
            'class' => 'btn btn-danger btn-sm'
        ]);

        if ($this->shortFormFieldsCallable && isset($form)) {
            echo Html::button(Yii::t('vendor/voskobovich/yii2-tree-manager/widgets/nestable', 'Save'), [
                'data-action' => 'save',
                'class' => 'btn btn-success btn-sm',
            ]);
            $form->end();
        }

        echo Html::endTag('div');

        echo Html::endTag('div');

        if ($node->children) {
            $this->renderNodes($node->children);
        }

        echo Html::endTag('li');
    }
}
