<?php if ( ! defined( 'ABSPATH' ) ) { exit;} ?>
<div class="page-toolbar">
    <div class="page-main">
        <ul>
            <li ng-class="$index == 0 ? 'disabled' : ''" ng-click="switchStage($index, 'prev')"><i class="nbd-icon-vista nbd-icon-vista-arrow-upward" title="<?php esc_html_e('Prev Page','web-to-print-online-designer'); ?>"></i></li>
            <li><span style="font-size: 14px;">{{$index + 1}}/{{stages.length}}</span></li>
            <li ng-class="$index == (stages.length - 1) ? 'disabled' : ''" ng-click="switchStage($index, 'next')"><i class="nbd-icon-vista nbd-icon-vista-arrow-upward rotate-180" title="<?php esc_html_e('Next Page','web-to-print-online-designer'); ?>"></i></li>
            <li><i nbd-clear-stage class="nbd-icon-vista nbd-icon-vista-refresh click-reset-design" title="<?php esc_html_e('Clear Design','web-to-print-online-designer'); ?>"></i></li>
        </ul>
    </div>
</div>