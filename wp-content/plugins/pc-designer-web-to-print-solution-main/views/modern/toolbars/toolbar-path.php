<ul class="nbd-main-menu menu-center tool-path"  ng-show="( stages[currentStage].states.isPath || stages[currentStage].states.isNativeGroup ) && !stages[currentStage].states.isMask">
    <li ng-click="unGroupLayers()" class="menu-item menu-group" ng-show="stages[currentStage].states.isNativeGroup">
        <i class="icon-nbd icon-nbd-ungroup nbd-tooltip-hover tooltipstered" title="<?php esc_html_e('Ungroup','web-to-print-online-designer'); ?>"></i>
    </li>
    <li class="menu-item item-color-fill nbd-show-color-palette" ng-click="stages[currentStage].states.svg.currentPath = $index" ng-repeat="path in stages[currentStage].states.svg.groupPath" end-repeat-color-picker>
        <span ng-style="{'background': path.color}" class="nbd-tooltip-hover color-fill nbd-color-picker-preview" title="<?php esc_html_e('Color','web-to-print-online-designer'); ?>" ></span>
    </li>
    <li class="menu-item menu-item-qrcode" ng-show="stages[currentStage].states.isQrcode">
        <i class="icon-nbd icon-nbd-qrcode" ></i> 
        <input ng-model="stages[currentStage].states.qrContent" ng-change="updateQrCode()"/>
    </li>
    <li class="menu-item menu-item-barcode" ng-show="stages[currentStage].states.isBarcode">
        <i>
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                <path fill="#404762" d="M0 3h3v15h-3zM4.5 3h1.5v15h-1.5zM7.5 3h1.5v15h-1.5zM12 3h1.5v15h-1.5zM18 3h1.5v15h-1.5zM22.5 3h1.5v15h-1.5zM15 3h0.75v15h-0.75zM10.5 3h0.75v15h-0.75zM20.25 3h0.75v15h-0.75zM0 19.5h1.5v1.5h-1.5zM4.5 19.5h1.5v1.5h-1.5zM7.5 19.5h1.5v1.5h-1.5zM15 19.5h1.5v1.5h-1.5zM22.5 19.5h1.5v1.5h-1.5zM18 19.5h3v1.5h-3zM10.5 19.5h3v1.5h-3z"></path>
            </svg>
        </i>
        <input ng-model="stages[currentStage].states.barCodeContent" ng-change="updateBarCode()"/>
    </li>
</ul>
<ul class="nbd-main-menu geo-object-options menu-center tool-path" ng-show="stages[currentStage].states.isShape && !stages[currentStage].states.isMask">
    <li class="menu-item menu-item-shape-fill">
        <span><?php esc_html_e('Fill','web-to-print-online-designer'); ?></span> <span cattr="geoObject.fill" color="{{stages[currentStage].states.geoObject.fill}}" class="nbd-color-picker-preview nbd-color-picker" ng-style="{'background': stages[currentStage].states.geoObject.fill}"></span>
    </li>
    <li class="menu-item menu-item-shape-fill">
        <span><?php esc_html_e('Stroke','web-to-print-online-designer'); ?></span> <span cattr="geoObject.stroke" color="{{stages[currentStage].states.geoObject.stroke}}" class="nbd-color-picker-preview nbd-color-picker" ng-style="{'background': stages[currentStage].states.geoObject.stroke}"></span> <span title="<?php esc_html_e('None','web-to-print-online-designer'); ?>" class="non-stroke" ng-click="setGeometricalObjectAttr( 'stroke', false )"></span>
    </li>
    <li class="menu-item menu-item-shape-fill">
        <span><?php esc_html_e('Stroke Width','web-to-print-online-designer'); ?></span> <input class="geo-object-stroke" ng-model="stages[currentStage].states.geoObject.strokeWidth" ng-change="setGeometricalObjectAttr('strokeWidth', stages[currentStage].states.geoObject.strokeWidth)"/>
    </li>
    <li class="menu-item menu-item-shape-fill">
        <span><?php esc_html_e('Stroke Dash','web-to-print-online-designer'); ?></span> <input class="geo-object-stroke" ng-model="stages[currentStage].states.geoObject.strokeDashArray[0]" ng-change="setGeometricalObjectAttr('strokeDashArray', [stages[currentStage].states.geoObject.strokeDashArray[0], stages[currentStage].states.geoObject.strokeDashArray[1]])"/> <input class="geo-object-stroke" ng-model="stages[currentStage].states.geoObject.strokeDashArray[1]" ng-change="setGeometricalObjectAttr('strokeDashArray', [stages[currentStage].states.geoObject.strokeDashArray[0], stages[currentStage].states.geoObject.strokeDashArray[1]])"/>
    </li>
    <li class="menu-item menu-item-shape-fill">
        <span><?php esc_html_e('Stroke Uniform','web-to-print-online-designer'); ?></span> <input type="checkbox" ng-model="stages[currentStage].states.geoObject.strokeUniform" ng-change="setGeometricalObjectAttr( 'strokeUniform', stages[currentStage].states.geoObject.strokeUniform )"/>
    </li>
</ul>
<ul class="nbd-main-menu menu-center tool-path" ng-show="stages[currentStage].states.isMask">
    <li class="menu-item" ng-click="exitMaskMode()">
        <i class="icon-nbd icon-nbd-fomat-done nbd-tooltip-hover" title="<?php esc_html_e('Apply mask','web-to-print-online-designer'); ?>"></i>
    </li>
</ul>