<?php if (!defined('ABSPATH')) exit; ?>
<?php echo '<script type="text/ng-template" id="nbd.fold">'; ?>
    <div class="nbd-field-info">
        <div class="nbd-field-info-1">
            <div><b><?php esc_html_e('Folding Styles', 'web-to-print-online-designer'); ?></b></div>
        </div>
        <div class="nbd-field-info-2">
            <table class="nbd-table" style="text-align: center;">
                <tbody>
                    <tr ng-repeat="(opIndex, op) in field.general.attributes.options">
                        <th style="text-align: left;">{{op.name}}</th>
                        <td>
                            <select ng-model="op.fold" name="options[fields][{{fieldIndex}}][general][attributes][options][{{opIndex}}][fold]" >
                                <option value="n"><?php esc_html_e('No fold', 'web-to-print-online-designer'); ?></option>
                                <option value="h"><?php esc_html_e('Half fold', 'web-to-print-online-designer'); ?></option>
                                <option value="t"><?php esc_html_e('Tri fold', 'web-to-print-online-designer'); ?></option>
                                <option value="z"><?php esc_html_e('Z fold', 'web-to-print-online-designer'); ?></option>
                                <option value="s"><?php esc_html_e('Single gate fold', 'web-to-print-online-designer'); ?></option>
                                <option value="d"><?php esc_html_e('Double gate fold', 'web-to-print-online-designer'); ?></option>
                                <option value="dp"><?php esc_html_e('Double parallel fold', 'web-to-print-online-designer'); ?></option>
                                <option value="dr"><?php esc_html_e('Double parallel reverse fold', 'web-to-print-online-designer'); ?></option>
                                <option value="r"><?php esc_html_e('Roll fold ( 4 panels )', 'web-to-print-online-designer'); ?></option>
                                <option value="a"><?php esc_html_e('Accordion fold ( 4 panels )', 'web-to-print-online-designer'); ?></option>
                                <option value="hh"><?php esc_html_e('Half fold then half fold', 'web-to-print-online-designer'); ?></option>
                                <option value="ht"><?php esc_html_e('Half fold then tri fold', 'web-to-print-online-designer'); ?></option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div>
                <img style="margin-top: 15px;" src="<?php echo NBDESIGNER_ASSETS_URL . 'images/folding-style.png' ?>"/>
            </div>
        </div>
    </div>
<?php echo '</script>';