<?php if (!defined('ABSPATH')) exit; ?>
<?php echo '<script type="text/ng-template" id="nbd.actions">'; ?>
    <div class="nbd-field-info">
        <div class="nbd-field-info-1">
            <div><b><?php esc_html_e('Artwork actions', 'web-to-print-online-designer'); ?></b></div>
        </div>
        <div class="nbd-field-info-2">
            <div class="nbd-table-wrap">
                <table class="nbd-table" style="text-align: center;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Option', 'web-to-print-online-designer'); ?></th>
                            <th><?php esc_html_e('Mapping action', 'web-to-print-online-designer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="(opIndex, op) in field.general.attributes.options">
                            <td>{{op.name}}</td>
                            <td>
                                <select ng-model="op.action" name="options[fields][{{fieldIndex}}][general][attributes][options][{{opIndex}}][action]">
                                    <option value="n"><?php esc_html_e('No action', 'web-to-print-online-designer'); ?></option>
                                    <option value="u"><?php esc_html_e('Upload design', 'web-to-print-online-designer'); ?></option>
                                    <option value="c"><?php esc_html_e('Create design online', 'web-to-print-online-designer'); ?></option>
                                    <option value="h"><?php esc_html_e('Hire designer', 'web-to-print-online-designer'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php echo '</script>';