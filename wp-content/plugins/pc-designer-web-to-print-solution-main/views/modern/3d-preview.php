<div class="preview-3d-wrap" id="preview-3d-wrapper">
    <div class="preview-3d-wrap-inner">
        <div class="preview-3d-wrap-loading">
            <div class="loader">
                <svg class="circular" viewBox="25 25 50 50">
                    <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"/>
                </svg>
            </div>
        </div>
        <div id="preview-3d-drag-handle" class="preview-3d-drag-handle"><?php esc_html_e('3D preview','web-to-print-online-designer'); ?></div>
        <i class="icon-nbd icon-nbd-clear close-preview-3d-wrap" ng-click="close3DPreview()"></i>
        <i class="icon-nbd icon-nbd-clear close-preview-3d-wrap" ng-click="close3DPreview()" title="<?php esc_html_e('Close 3D preview','web-to-print-online-designer'); ?>"></i>
        <i class="icon-nbd refresh-preview-3d" ng-click="show3DPreview()"  ng-show="threeDimensionPreview.modelLoaded" title="<?php esc_html_e('Refresh 3D preview','web-to-print-online-designer'); ?>" >
            <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M7.52 21.48C4.25 19.94 1.91 16.76 1.55 13H.05C.56 19.16 5.71 24 12 24l.66-.03-3.81-3.81-1.33 1.32zm.89-6.52c-.19 0-.37-.03-.52-.08-.16-.06-.29-.13-.4-.24-.11-.1-.2-.22-.26-.37-.06-.14-.09-.3-.09-.47h-1.3c0 .36.07.68.21.95.14.27.33.5.56.69.24.18.51.32.82.41.3.1.62.15.96.15.37 0 .72-.05 1.03-.15.32-.1.6-.25.83-.44s.42-.43.55-.72c.13-.29.2-.61.2-.97 0-.19-.02-.38-.07-.56-.05-.18-.12-.35-.23-.51-.1-.16-.24-.3-.4-.43-.17-.13-.37-.23-.61-.31.2-.09.37-.2.52-.33.15-.13.27-.27.37-.42.1-.15.17-.3.22-.46.05-.16.07-.32.07-.48 0-.36-.06-.68-.18-.96-.12-.28-.29-.51-.51-.69-.2-.19-.47-.33-.77-.43C9.1 8.05 8.76 8 8.39 8c-.36 0-.69.05-1 .16-.3.11-.57.26-.79.45-.21.19-.38.41-.51.67-.12.26-.18.54-.18.85h1.3c0-.17.03-.32.09-.45s.14-.25.25-.34c.11-.09.23-.17.38-.22.15-.05.3-.08.48-.08.4 0 .7.1.89.31.19.2.29.49.29.86 0 .18-.03.34-.08.49-.05.15-.14.27-.25.37-.11.1-.25.18-.41.24-.16.06-.36.09-.58.09H7.5v1.03h.77c.22 0 .42.02.6.07s.33.13.45.23c.12.11.22.24.29.4.07.16.1.35.1.57 0 .41-.12.72-.35.93-.23.23-.55.33-.95.33zm8.55-5.92c-.32-.33-.7-.59-1.14-.77-.43-.18-.92-.27-1.46-.27H12v8h2.3c.55 0 1.06-.09 1.51-.27.45-.18.84-.43 1.16-.76.32-.33.57-.73.74-1.19.17-.47.26-.99.26-1.57v-.4c0-.58-.09-1.1-.26-1.57-.18-.47-.43-.87-.75-1.2zm-.39 3.16c0 .42-.05.79-.14 1.13-.1.33-.24.62-.43.85-.19.23-.43.41-.71.53-.29.12-.62.18-.99.18h-.91V9.12h.97c.72 0 1.27.23 1.64.69.38.46.57 1.12.57 1.99v.4zM12 0l-.66.03 3.81 3.81 1.33-1.33c3.27 1.55 5.61 4.72 5.96 8.48h1.5C23.44 4.84 18.29 0 12 0z"/></svg>
        </i>
        <i class="icon-nbd minimize-preview-3d preview-3d-size" ng-show="threeDimensionPreview.modelLoaded && threeDimensionPreview.size == 'maximize'" ng-click="resize3DPreview('minimize')" title="<?php esc_html_e('Restore down','web-to-print-online-designer'); ?>">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 512 512" >
                <path d="M386.612,114.939H10.449C4.679,114.939,0,119.617,0,125.388v376.163C0,507.321,4.679,512,10.449,512h376.163 c5.77,0,10.449-4.679,10.449-10.449V125.388C397.061,119.617,392.383,114.939,386.612,114.939z M376.163,491.102H20.898V135.837 h355.265V491.102z"/>
                <path d="M502.633,0H126.469c-5.77,0-11.531,3.978-11.531,9.747V82.89c0,5.772,4.679,10.449,10.449,10.449 s10.449-4.677,10.449-10.449V20.898h355.265v355.265h-51.163c-5.77,0-10.449,4.679-10.449,10.449s4.679,10.449,10.449,10.449 h62.694c5.77,0,9.367-5.379,9.367-11.151V9.747C512,3.978,508.403,0,502.633,0z"/>
            </svg>
        </i>
        <i class="icon-nbd maximize-preview-3d preview-3d-size" ng-show="threeDimensionPreview.modelLoaded && threeDimensionPreview.size == 'minimize'" ng-click="resize3DPreview('maximize')" title="<?php esc_html_e('Maximize','web-to-print-online-designer'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
        </i>
        <div class="preview-3d-resize-handle" id="preview-3d-resize-handle">
            <svg height="512" viewBox="0 0 551.131 551.131" width="512" xmlns="http://www.w3.org/2000/svg">
                <path fill="#888" d="m-66.206 658.97h160.412v34.442h-160.412z" transform="matrix(.707 -.707 .707 .707 -5.799 14)"/>
                <path fill="#888" d="m-215.346 514.072h452.691v34.442h-452.691z" transform="matrix(.707 -.707 .707 .707 -4.556 11)"/>
                <path fill="#888" d="m-364.486 369.173h744.973v34.442h-744.973z" transform="matrix(.707 -.707 .707 .707 -3.314 8)"/>
            </svg>
        </div>
        <div class="preview-3d-resize-overlay" ></div>
        <i class="icon-nbd icon-nbd-camera-alt take-3d-preview-screenshot" ng-click="take3DPreviewScreenshot()" ng-show="threeDimensionPreview.modelLoaded" title="<?php esc_html_e('Take screenshot','web-to-print-online-designer'); ?>"></i>
    </div>
</div>