<?php if ( ! defined( 'ABSPATH' ) ) { exit;} ?>
<div ng-if="settings['nbdesigner_enable_text'] == 'yes'" id="tab-text" class="v-tab-content">
    <span class="v-title"><?php esc_html_e('Text', 'web-to-print-online-designer'); ?></span>
    <div class="v-action">
        <span class="v-btn waves-effect" ng-click="addText('Heading','heading')" style="width: calc(100%)"><?php esc_html_e('Add New Text Field', 'web-to-print-online-designer'); ?></span>
    </div>
    <div class="v-content" data-action="yes">
        <div class="tab-scroll">
            <div class="main-scrollbar">
                <div class="text-editor" ng-repeat="layer in stages[currentStage].layers" ng-click="activeLayer(layer.index)" ng-class="{'active' : stages[currentStage].states.isLayer && stages[currentStage].states.itemId == layer.itemId}">
                    <input class="text-field" type="text" ng-if="layer.type == 'text'" ng-change="setLayerAttribute('text', layer.text, layer.index, $index)" ng-model="layer.text" />
                </div>
            </div>
        </div>
    </div>
</div>