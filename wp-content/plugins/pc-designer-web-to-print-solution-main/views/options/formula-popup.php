<?php if (!defined('ABSPATH')) exit; ?>
<div class="nbo-formula-popup-wrap" ng-class="formula.active ? 'active' : ''" ng-click="_cancelFormulaPrice($event)">
    <div class="nbo-formula-popup">
        <div class="nbo-formula-popup-header">
            <?php esc_html_e('Formula price builder', 'web-to-print-online-designer') ?>
        </div>
        <div class="nbo-formula-popup-body">
            <textarea id="nbo-formula-price" ng-model="formula.price" rows="5"></textarea>
            <div style="font-weight: bold; margin: 15px 0;"><?php esc_html_e('Select operator:', 'web-to-print-online-designer') ?></div>
            <div>
                <a class="button button-primary" ng-click="addFormulaVariable('+')">+</a>
                <a class="button button-primary" ng-click="addFormulaVariable('-')">-</a>
                <a class="button button-primary" ng-click="addFormulaVariable('*')">*</a>
                <a class="button button-primary" ng-click="addFormulaVariable('/')">/</a>
            </div>
            <div style="font-weight: bold; margin: 15px 0;"><?php esc_html_e('Select variable:', 'web-to-print-online-designer') ?></div>
            <div>
                <a class="button button-primary" ng-click="addFormulaVariable('{quantity}')"><?php esc_html_e('Product quantity', 'web-to-print-online-designer') ?></a>
                <a class="button button-primary" ng-click="addFormulaVariable('{price}')"><?php esc_html_e('Product base price', 'web-to-print-online-designer') ?></a>
                <a class="button button-primary" ng-click="addFormulaVariable('{area}')"><?php esc_html_e('Product area', 'web-to-print-online-designer') ?></a>
            </div>
            <div>
                <a class="button button-primary" 
                    ng-if="options.fields[formula.fieldIndex].general.data_type.value == 'i' && ( options.fields[formula.fieldIndex].general.input_type.value == 'n' || options.fields[formula.fieldIndex].general.input_type.value == 'r' )" 
                    ng-click="addFormulaVariable('{this.value}')"><?php esc_html_e('The value of field', 'web-to-print-online-designer') ?></a>
                <a class="button button-primary" 
                    ng-if="options.fields[formula.fieldIndex].general.data_type.value == 'i' && ( options.fields[formula.fieldIndex].general.input_type.value == 't' || options.fields[formula.fieldIndex].general.input_type.value == 'a' )" 
                    ng-click="addFormulaVariable('{this.value_length}')"><?php esc_html_e('The value length of field', 'web-to-print-online-designer') ?></a>
            </div>
            <div class="nbo-formula-link-field">
                <select ng-model="formula.currentLinkField">
                    <option ng-if="fieldIndex != formula.fieldIndex" ng-repeat="(fieldIndex, field) in options.fields" value="{{fieldIndex}}">{{field.general.title.value}}</option>
                </select>
                <div class="nbo-formula-link-field-variable">
                    <a class="button button-primary" ng-click="addFormulaVariable('{field.' + options.fields[formula.currentLinkField].id + '.price}')"><?php esc_html_e('Price', 'web-to-print-online-designer') ?></a>
                    <a class="button button-primary" 
                        ng-if="options.fields[formula.currentLinkField].general.data_type.value == 'i' && ( options.fields[formula.currentLinkField].general.input_type.value == 'n' || options.fields[formula.fieldIndex].general.input_type.value == 'r' )" 
                        ng-click="addFormulaVariable('{field.' + options.fields[formula.currentLinkField].id + '.value}')"><?php esc_html_e('The value of field', 'web-to-print-online-designer') ?></a>
                    <a class="button button-primary" 
                        ng-if="options.fields[formula.currentLinkField].general.data_type.value == 'i' && ( options.fields[formula.currentLinkField].general.input_type.value == 't' || options.fields[formula.fieldIndex].general.input_type.value == 'a' )" 
                        ng-click="addFormulaVariable('{field.' + options.fields[formula.currentLinkField].id + '.value_length}')"><?php esc_html_e('The value length of field', 'web-to-print-online-designer') ?></a>
                    <a class="button button-primary" 
                        ng-if="options.fields[formula.currentLinkField].general.data_type.value == 'm'" 
                        ng-click="addFormulaVariable('{field.' + options.fields[formula.currentLinkField].id + '.implicit_value}')"><?php esc_html_e('The implicit value of selected attribute of field', 'web-to-print-online-designer') ?></a>
                    <a class="button button-primary" 
                        ng-if="options.fields[formula.currentLinkField].general.data_type.value == 'm'" 
                        ng-click="addFormulaVariable('{field.' + options.fields[formula.currentLinkField].id + '.sub_implicit_value}')"><?php esc_html_e('The implicit value of selected sub-attribute of field', 'web-to-print-online-designer') ?></a>
                </div>
            </div>
        </div>
        <div class="nbo-formula-popup-footer">
            <a class="button button-primary" ng-click="saveFormulaPrice()"><?php esc_html_e('Save', 'web-to-print-online-designer') ?></a>
            <a class="button" style="background: rgba(170, 0, 0, 0.75);color: #fff;border-color: rgba(170, 0, 0, 0.75);" ng-click="cancelFormulaPrice()"><?php esc_html_e('Cancel', 'web-to-print-online-designer') ?></a>
        </div>
    </div>
</div>