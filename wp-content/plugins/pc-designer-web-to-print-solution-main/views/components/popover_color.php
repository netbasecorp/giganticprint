<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="pop-bg-color shadow">
    <h2><b>{{(langs['BACKGROUND']) ? langs['BACKGROUND'] : "Background"}}</b></h2>
    <div class="background-options">
        <div class="pre-color">
            <span ng-repeat="color in backgroundPalette" ng-click="addBackground( color )" class="nbd-color-item hover-shadow" ng-style="{'background': color}"></span>
        </div>
        <div class="nbd-bg-colr-picker-wrap" style="display: none">
            <?php  if($enableColor == 'yes'): ?>
            <spectrum-colorpicker
                ng-model="colorBackground" 
                ng-change="changeBackgroundColor(colorBackground)" 
                options="{
                    showPaletteOnly: false, 
                    togglePaletteOnly: false, 
                    showPalette: false, 
                    showInput: true}">
            </spectrum-colorpicker>
             <?php else: ?>
            <spectrum-colorpicker
                ng-model="colorBackground" 
                ng-change="changeBackgroundColor(colorBackground)" 
                options="{
                    showPaletteOnly: true, 
                    togglePaletteOnly: false, 
                    hideAfterPaletteSelect:true,
                    showInput: true,
                    palette: colorPalette}">
            </spectrum-colorpicker>
            <?php endif; ?>   
            <span class="nbd-color-item hover-shadow fa fa-ban nbd-remove-stage-bg" ng-click="removeBackgroundStage()"></span>
        </div>
    </div>
</div>  