<div class="<?php if( $active_cliparts ) echo 'active'; ?> tab nbd-onload" ng-if="settings['nbdesigner_enable_clipart'] == 'yes' && settings['printcart_api_enable'] != '1' " id="tab-svg" nbd-scroll="scrollLoadMore(container, type)" data-container="#tab-svg" data-type="clipart" data-offset="20">
    <div class="nbd-search">
        <input type="text" name="search" placeholder="<?php esc_html_e('Search clipart', 'web-to-print-online-designer'); ?>" ng-model="resource.clipart.filter.search"/>
        <i class="icon-nbd icon-nbd-fomat-search"></i>
    </div>
    <div class="cliparts-category" ng-class="resource.clipart.data.cat.length > 0 ? '' : 'nbd-hiden'">
        <div class="nbd-button nbd-dropdown">
            <span>{{resource.clipart.filter.currentCat.name}}</span>
            <i class="icon-nbd icon-nbd-chevron-right rotate90"></i>
            <div class="nbd-sub-dropdown" data-pos="center">
                <ul class="nbd-perfect-scroll">
                    <li ng-click="changeCat('clipart', cat)" ng-repeat="cat in resource.clipart.data.cat"><span>{{cat.name}}</span><span>{{cat.amount}}</span></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="tab-main tab-scroll">
        <div class="nbd-items-dropdown" >
            <div>
                <div class="clipart-wrap">
                    <div class="clipart-item" nbd-drag="art.url" extenal="false" type="svg"  ng-repeat="art in resource.clipart.filteredArts | limitTo: resource.clipart.filter.currentPage * resource.clipart.filter.perPage" repeat-end="onEndRepeat('clipart')">
                        <img  ng-src="{{art.url}}" ng-click="addArt(art, true, true)" alt="{{art.name}}">
                    </div>
                </div>
                <div class="loading-photo" >
                    <svg class="circular" viewBox="25 25 50 50">
                        <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"/>
                    </svg>
                </div>
                <div class="tab-load-more" style="display: none;" ng-show="!resource.clipart.onload && resource.clipart.filteredArts.length && resource.clipart.filter.currentPage * resource.clipart.filter.perPage < resource.clipart.filter.total">
                    <a class="nbd-button" ng-click="scrollLoadMore('#tab-svg', 'clipart')"><?php esc_html_e('Load more','web-to-print-online-designer'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="tab nbd-onload" ng-if="settings['nbdesigner_enable_clipart'] == 'yes' && settings['printcart_api_enable'] == '1'" id="tab-svg" nbd-scroll="scrollLoadMore(container, type)" data-container="#tab-svg" data-type="clipart" data-offset="20">
    <div class="nbd-search">
        <input type="text" name="search" placeholder="Search clipart" ng-model="resource.clipart.filter.search"/>
        <i class="icon-nbd icon-nbd-fomat-search"></i>
    </div>
    <!-- <div class="cliparts-category" ng-class="resource.clipart.data.cat.length > 0 ? '' : 'nbd-hiden'">
        <div class="nbd-button nbd-dropdown">
            <span>{{resource.clipart.filter.currentCat.name}}</span>
            <i class="icon-nbd icon-nbd-chevron-right rotate90"></i>
            <div class="nbd-sub-dropdown" data-pos="center">
                <ul class="nbd-perfect-scroll">
                    <li ng-click="changeCatNew('clipart', cat)" ng-repeat="cat in resource.clipart.data.cat"><span>{{cat.name}}</span></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="tab-main tab-scroll">
        <div class="nbd-items-dropdown" >
            <div>
                <div class="clipart-wrap">
                    <div class="clipart-item" nbd-drag="art.url" extenal="false" type="svg"  ng-repeat="art in resource.clipart.filteredArts | limitTo: resource.clipart.filter.currentPage * resource.clipart.filter.perPage" repeat-end="onEndRepeat('clipart')">
                        <img  ng-src="{{art.url}}" ng-click="addArt(art, true, true)" alt="{{art.name}}">
                    </div>
                </div>
                <div class="loading-photo" >
                    <svg class="circular" viewBox="25 25 50 50">
                        <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"/>
                    </svg>
                </div>
                <div class="tab-load-more" style="display: none;" ng-show="!resource.clipart.onload && resource.clipart.filteredArts.length && (resource.clipart.nextPage[resource.clipart.filter.currentCat.id] || resource.clipart.filter.currentPage * resource.clipart.filter.perPage < resource.clipart.data.cat_data[resource.clipart.filter.currentCat.id].length)">
                    <a class="nbd-button" ng-click="scrollLoadMore('#tab-svg', 'clipart')"><?php esc_html_e('Load more','web-to-print-online-designer'); ?></a>
                </div>
            </div>
        </div>
    </div> -->
    <div class="pc-tab-clipart">
        <div class="toolbar-header-line"></div>
        <div class="nbd-tab-nav pc-tab-nav pc-tab-cliparts">
            <ul class="nbd-tabs">
                <li class="nbd-tab active" data-tab="default-clipart-tab" ng-click="changeCurrentClipart('clipart')">
                    <span class="title">Default cliparts</span>
                </li>
                <li class="nbd-tab" data-tab="custom-clipart-tab" ng-click="changeCurrentClipart('custom-clipart')">
                    <span class="title">Custom cliparts</span>
                </li>
            </ul>
        </div>
        <div class="toolbar-header-line"></div>
    </div>
    <div class="nbd-tab-contents">
        <div class="nbd-tab-content active" id="default-clipart-tab">
            <div class="cliparts-category" ng-class="resource.clipart.data.cat.length > 0 ? '' : 'nbd-hiden'">
                <div class="nbd-button nbd-dropdown">
                    <span>{{resource.clipart.filter.currentCat.name}}</span>
                    <i class="icon-nbd icon-nbd-chevron-right rotate90"></i>
                    <div class="nbd-sub-dropdown" data-pos="center">
                        <ul class="nbd-perfect-scroll">
                            <li ng-click="changeCatNew('clipart', cat)" ng-repeat="cat in resource.clipart.data.cat"><span>{{cat.name}}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="tab-main tab-scroll">
                <div class="nbd-items-dropdown" >
                    <div class="clipart-wrap">
                        <div class="clipart-item" nbd-drag="art.url" extenal="false" type="svg"  ng-repeat="art in resource.clipart.filteredArts | limitTo: resource.clipart.filter.currentPage * resource.clipart.filter.perPage" repeat-end="onEndRepeat('clipart')">
                            <img  ng-src="{{art.url}}" ng-click="addArt(art, true, true)" alt="{{art.name}}">
                        </div>
                    </div>
                    <div class="loading-photo" >
                        <svg class="circular" viewBox="25 25 50 50">
                            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"/>
                        </svg>
                    </div>
                    <div class="tab-load-more" style="display: none;" ng-show="!resource.clipart.onload && resource.clipart.filteredArts.length && (resource.clipart.nextPage[resource.clipart.filter.currentCat.id] || resource.clipart.filter.currentPage * resource.clipart.filter.perPage < resource.clipart.data.cat_data[resource.clipart.filter.currentCat.id].length)">
                        <a class="nbd-button" ng-click="scrollLoadMore('#tab-svg', 'clipart')">Load more</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="nbd-tab-content" id="custom-clipart-tab">
            <div class="cliparts-category" ng-class="resource.myClipart.data.cat.length > 0 ? '' : 'nbd-hiden'">
                <div class="nbd-button nbd-dropdown">
                    <span>{{resource.myClipart.filter.currentCat.name}}</span>
                    <i class="icon-nbd icon-nbd-chevron-right rotate90"></i>
                    <div class="nbd-sub-dropdown" data-pos="center">
                        <ul class="nbd-perfect-scroll">
                            <li ng-click="changeCatNew('myClipart', cat)" ng-repeat="cat in resource.myClipart.data.cat"><span>{{cat.name}}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="tab-main tab-scroll">
                <div class="nbd-items-dropdown" >
                    <div class="clipart-wrap">
                        <div class="clipart-item" nbd-drag="art.url" extenal="false" type="svg"  ng-repeat="art in resource.myClipart.filteredArts | limitTo: resource.myClipart.filter.currentPage * resource.myClipart.filter.perPage" repeat-end="onEndRepeat('myClipart')">
                            <img  ng-src="{{art.url}}" ng-click="addArt(art, true, true)" alt="{{art.name}}">
                        </div>
                    </div>
                    <div class="loading-photo" >
                        <svg class="circular" viewBox="25 25 50 50">
                            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"/>
                        </svg>
                    </div>
                    <div class="tab-load-more" style="display: none;" ng-show="!resource.myClipart.onload && resource.myClipart.filteredArts.length && (resource.myClipart.nextPage[resource.myClipart.filter.currentCat.id] || resource.myClipart.filter.currentPage * resource.myClipart.filter.perPage < resource.myClipart.data.cat_data[resource.myClipart.filter.currentCat.id].length)">
                        <a class="nbd-button" ng-click="scrollLoadMore('#tab-svg', 'myClipart')">Load more</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>