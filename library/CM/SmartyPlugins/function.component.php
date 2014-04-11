<?php

function smarty_function_component(array $params, Smarty_Internal_Template $template) {
    if (empty($params['name'])) {
        trigger_error('Param `name` missing.');
    }
    $name = $params['name'];
    /** @var CM_Render $render */
    $render = $template->smarty->getTemplateVars('render');
    unset($params['name']);

    $componentParams = $params;
    unset($componentParams['params']);
    if (isset($params['params'])) {
        $componentParams = array_merge($componentParams, $params['params']);
    }
    $component = CM_Component_Abstract::factory($name, $componentParams);

    if ($component instanceof CM_Page_Abstract) {
        $renderAdapter = new CM_RenderAdapter_Page($render, $component);
    } else {
        $renderAdapter = new CM_RenderAdapter_Component($render, $component);
    }
    return $renderAdapter->fetch($component);
}
