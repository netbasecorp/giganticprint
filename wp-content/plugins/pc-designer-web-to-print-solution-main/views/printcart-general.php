<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly  
?>

<div id="printcart-design" class="wp-core-ui" ng-app="printcartApp">
    <div ng-controller="printcartCtrl" keypress ng-cloak>

        <!-- Header -->
        <div>
            <a href="{{printcart_detail.dashboard_url}}" target="_blank">
                <img src="<?php echo esc_attr(NBDESIGNER_PLUGIN_URL . 'assets/images/logo-printcart.svg'); ?>"
                    class="printcart-logo" />
            </a>
        </div>
        <!-- Step Navigation -->
        <ol class="nbd-setup-steps">
            <li ng-class="{'active': current_tab === index}" ng-repeat="(index, tab) in tabs"
                ng-click="changeTab(index)">
                {{tab}}
            </li>
        </ol>
        <!-- Body -->
        <div class="printcart-body">
            <div ng-if="current_tab === 'connect'">
                <div ng-if="isConnected">
                    <div class="printcart-setup-instructions">
                        <h3><?php esc_html_e('Congratulations!', 'web-to-print-online-designer'); ?></h3>
                    </div>
                    <div class="printcart-connected-wrap align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#008000"
                            class="bi bi-check-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                            <path
                                d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z" />
                        </svg>
                        <span class="printcart-connected-text text-success">
                            <b>
                                <?php esc_html_e('Your website has been successfully connected to the Printcart dashboard.', 'web-to-print-online-designer'); ?>
                            </b>
                        </span>
                    </div>
                </div>
                <div ng-if="!isConnected">
                    <div class="printcart-setup-instructions">
                        <h3>
                            <?php esc_html_e('Connect the Printcart Dashboard to your site', 'web-to-print-online-designer'); ?>
                        </h3>
                    </div>
                </div>
                <div class="alert alert-success" ng-class="isConnected ? 'alert-success' : 'alert-warning'"
                    role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="alert-heading text-start">
                            <?php esc_html_e('Account', 'web-to-print-online-designer'); ?>
                        </h5>
                        <div>
                            <div class="d-flex justify-content-center align-items-center">
                                <div ng-if="!isConnected">
                                    <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal"
                                        data-bs-target="#connectDashboardModal">
                                        <?php esc_html_e('Connect to Dashboard', 'web-to-print-online-designer'); ?>
                                    </button>
                                </div>
                                <button type="button" class="btn btn-sm"
                                    ng-class="isConnected ? 'btn-outline-success' : 'btn-outline-warning'"
                                    data-bs-toggle="modal" data-bs-target="#manuallyModal">
                                    <?php esc_html_e('Change API key', 'web-to-print-online-designer'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr class="my-1">
                    <table class="form-table pc-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Name:', 'web-to-print-online-designer'); ?></label>
                                </th>
                                <td>
                                    <div class="printcart-account-info" ng-class="account.name ? '' : 'printcart-nan'">
                                        {{account.name ? account.name :
                                        'N/A'}}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Email:', 'web-to-print-online-designer'); ?></label>
                                </th>
                                <td>
                                    <div class="printcart-account-info" ng-class="account.email ? '' : 'printcart-nan'">
                                        {{account.email ? account.email : 'N/A'}}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Tier:', 'web-to-print-online-designer'); ?></label>
                                </th>
                                <td>
                                    <div ng-if="account.tier && account.tier != 'free'" class="printcart-account-info">
                                        {{account.tier}}
                                    </div>
                                    <div ng-if="!account.tier || account.tier == 'free'" class="printcart-account-info">
                                        <div class="printcart-nan">{{account.tier == 'free' ? account.tier : ''}}</div>
                                        <div style="color: #664d03">
                                            <?php esc_html_e('Please upgrade your account to unlock additional features.', 'web-to-print-online-designer'); ?>
                                            <button ng-click="upgradeAccount()" style="border: none; background: none; padding: 0; margin: 0; color: #0d6efd; text-decoration: underline; cursor: pointer;">
                                                <?php esc_html_e('Upgrade', 'web-to-print-online-designer'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <h5 class="alert-heading text-start"><?php esc_html_e('Store', 'web-to-print-online-designer'); ?>
                    </h5>
                    <hr class="my-1">
                    <table class="form-table pc-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Store name:', 'web-to-print-online-designer'); ?></label>
                                </th>
                                <td>
                                    <div class="printcart-account-info" ng-class="store.name ? '' : 'printcart-nan'">
                                        {{store.name ? store.name :
                                        'N/A'}}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Store url:', 'web-to-print-online-designer'); ?></label>
                                </th>
                                <td>
                                    <div class="printcart-account-info"
                                        ng-class="store.shop_url ? '' : 'printcart-nan'">
                                        {{store.shop_url ? store.shop_url : 'N/A'}}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Sid:', 'web-to-print-online-designer'); ?></label>
                                </th>
                                <td>
                                    <div class="printcart-account-info" ng-class="store.sid ? '' : 'printcart-nan'">
                                        {{store.sid ? store.sid : 'N/A'}}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Secret:', 'web-to-print-online-designer'); ?></label>
                                </th>
                                <td>
                                    <div class="printcart-account-info" ng-class="store.secret ? '' : 'printcart-nan'">
                                        {{store.secret ? store.secret : 'N/A'}}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Unauth token:', 'web-to-print-online-designer'); ?></label>
                                </th>
                                <td>
                                    <div class="printcart-account-info"
                                        ng-class="store.unauth_token ? '' : 'printcart-nan'">
                                        {{store.unauth_token ? store.unauth_token :
                                        'N/A'}}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div ng-show="isLoadingStore"
                        class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-25">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <div ng-if="current_tab === 'import'">
                <div class="printcart-import-product">
                    <div class="d-flex justify-content-between align-items-center p-1">
                        <div class="my-2">
                            <?php esc_html_e('These are sample products that we have pre-configured with printing options and templates to help you easily set up a product.', 'web-to-print-online-designer'); ?>
                        </div>
                    </div>
                </div>
                <div class="border border-gray-300 p-2">
                    <div class="d-flex align-items-center justify-content-between px-2">
                        <input id="pcSelectAll" type="checkbox" ng-model="selectAll"
                            ng-change="toggleAllProducts(selectAll)">
                    </div>
                    <div class="container">
                        <div class="row">
                            <div class="col-sm-6 col-md-4 col-lg-4 g-2" ng-repeat="(key, product) in product_sample">
                                <div
                                    class="d-flex flex-column p-0 mt-0 h-100 w-100 align-items-center border border-gray-300 p-2 rounded">
                                    <div class="d-flex h-100 w-100 align-items-center">
                                        <input class="me-2" type="checkbox" ng-model="product.checked"
                                            id="pcItem{{key}}" ng-change="selectProduct(key)">
                                        <label for="pcItem{{key}}" class="form-check-label d-flex w-100">
                                            <img style="width: 50px; height: 50px" ng-src="{{product.image}}"
                                                class="me-2" alt="{{product.name}}">
                                            <div style="width: calc(100% - 80px);" class="text-start text-truncate"
                                                title="{{product.name}}">
                                                {{product.name}}
                                                <div>
                                                    <span ng-if="product.templates"
                                                        class="badge bg-info position-relative">
                                                        <?php esc_html_e('Templates', 'web-to-print-online-designer'); ?>
                                                        <span
                                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                            {{product.templates}}
                                                        </span>
                                                    </span>
                                                    <span ng-if="product.print_options"
                                                        class="badge bg-success position-relative">
                                                        <?php esc_html_e('Printing options', 'web-to-print-online-designer'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div ng-if="current_tab === 'general'">
                <div class="my-2">
                    <?php esc_html_e('This section allows you to configure general settings for the Printcart integration.', 'web-to-print-online-designer'); ?>
                </div>
                <form method="post" class="general-step text-start">
                    <div class="py-3">
                        <p>
                            <label for="dimension_unit">
                                <?php
                                printf(wp_kses(
                                    __('<strong>Dimension unit</strong>—used to calculate design area.', 'web-to-print-online-designer'),
                                    array('strong' => array())
                                ));
                                ?>
                            </label>
                        </p>
                        <select id="dimension_unit" ng-model="dimension_unit" name="dimension_unit"
                            ng-change="changeUnit(dimension_unit)" class="wc-enhanced-select w-100">
                            <option value="cm">
                                <?php esc_html_e('cm', 'web-to-print-online-designer'); ?>
                            </option>
                            <option value="in">
                                <?php esc_html_e('in', 'web-to-print-online-designer'); ?>
                            </option>
                            <option value="mm">
                                <?php esc_html_e('mm', 'web-to-print-online-designer'); ?>
                            </option>
                            <option value="ft">
                                <?php esc_html_e('ft', 'web-to-print-online-designer'); ?>
                            </option>
                            <option value="px">
                                <?php esc_html_e('px', 'web-to-print-online-designer'); ?>
                            </option>
                        </select>
                        <p>
                            <label for="font_subset">
                                <?php
                                printf(wp_kses(
                                    __('<strong>Font subset</strong>—choose your language font subset.', 'web-to-print-online-designer'),
                                    array('strong' => array())
                                ));
                                ?>
                            </label>
                        </p>
                        <select id="font_subset" ng-model="default_font_subset" class="wc-enhanced-select w-100"
                            ng-change="changeSubnet(default_font_subset)">
                            <?php foreach (_nbd_font_subsets() as $key => $subset): ?>
                                <option value="<?php echo ($key) ?>">
                                    <?php esc_html_e($subset); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div ng-if="current_tab === 'setting_pages'">
                <?php $pages = nbd_get_pages(); ?>
                <div class="my-2">
                    <?php esc_html_e('Create default Printcart Designer pages', 'web-to-print-online-designer'); ?>
                </div>
                <form method="post" class="text-start">
                    <div>
                        <p><label
                                for="nbdesigner_create_your_own_page_id"><?php echo '<strong>' . esc_html__('Create your own page', 'web-to-print-online-designer') . '</strong>' . esc_html__('—page contain design editor.', 'web-to-print-online-designer'); ?>
                            </label></p>
                        <select ng-model="create_your_own_page_id" class="wc-enhanced-select w-100"
                            ng-model="create_your_own_page_id"
                            ng-change="changePage('create_your_own_page_id' , create_your_own_page_id)">
                            <?php foreach ($pages as $key => $page): ?>
                                <option value="<?php echo ($key); ?>"><?php esc_html_e($page); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p><label
                                for="nbdesigner_designer_page_id"><?php echo '<strong>' . esc_html__('Designer page', 'web-to-print-online-designer') . '</strong>' . esc_html__('—designer page.', 'web-to-print-online-designer'); ?></label>
                        </p>
                        <select id="nbdesigner_designer_page_id" name="nbdesigner_designer_page_id"
                            class="wc-enhanced-select w-100" ng-model="designer_page_id"
                            ng-change="changePage('designer_page_id' , designer_page_id)">
                            <?php foreach ($pages as $key => $page): ?>
                                <option value="<?php echo ($key); ?>"><?php esc_html_e($page); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p><label
                                for="nbdesigner_gallery_page_id"><?php echo '<strong>' . esc_html__('Gallery', 'web-to-print-online-designer') . '</strong>' . esc_html__('—The page show all templates.', 'web-to-print-online-designer'); ?></label>
                        </p>
                        <select id="nbdesigner_gallery_page_id" name="nbdesigner_gallery_page_id"
                            class="wc-enhanced-select w-100" ng-model="gallery_page_id"
                            ng-change="changePage('gallery_page_id' , gallery_page_id)">
                            <?php foreach ($pages as $key => $page): ?>
                                <option value="<?php echo ($key); ?>">
                                    <?php esc_html_e($page); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p><label
                                for="nbdesigner_logged_page_id"><strong><?php esc_html_e('Redirect login', 'web-to-print-online-designer'); ?></strong></label>
                        </p>
                        <select id="nbdesigner_logged_page_id" name="nbdesigner_logged_page_id"
                            class="wc-enhanced-select w-100" ng-model="logged_page_id"
                            ng-change="changePage('logged_page_id' , logged_page_id)">
                            <?php foreach ($pages as $key => $page): ?>
                                <option value="<?php echo ($key); ?>">
                                    <?php esc_html_e($page); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div ng-if="current_tab === 'overview'">
                <div class="my-2">
                    <?php esc_html_e('Go to product detail to setup custom design or upload design.', 'web-to-print-online-designer'); ?>
                </div>
                <div class="text-start">
                    <p>
                        <img class="enable-design w-100"
                            src="<?php echo esc_attr(NBDESIGNER_PLUGIN_URL . 'assets/images/enable_nbdesign.png'); ?>" />
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                <button ng-if="previewTabs[current_tab] !== ''" class="btn btn-outline-primary px-3"
                    ng-click="changeTab(previewTabs[current_tab])">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
                    </svg>
                    {{tabs[previewTabs[current_tab]]}}
                </button>
            </div>
            <div>
                <button ng-if="nextTabs[current_tab] !== ''"
                    class="btn btn-outline-primary px-3 position-relative overflow-hidden"
                    ng-click="changeTab(nextTabs[current_tab], true)" ng-disabled="isLoadingSaveSetting">
                    {{tabs[nextTabs[current_tab]]}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                    </svg>
                    <div ng-show="isLoadingSaveSetting && current_tab !== 'connect' && current_tab !== 'import'"
                        class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-light bg-opacity-75">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=nbdesigner_settings')); ?>"
                    ng-if="nextTabs[current_tab] === ''" class="btn btn-outline-primary">
                    <?php esc_html_e('Settings', 'web-to-print-online-designer'); ?>
                </a>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal" id="connectDashboardModal" tabindex="-1" aria-labelledby="connectDashboardModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="connectDashboardModalLabel">
                            <?php esc_html_e('Create Store Connection', 'web-to-print-online-designer'); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-column justify-content-center">
                            <nav class="nav nav-tabs me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <button class="nav-link active" id="v-pills-register-tab" data-bs-toggle="pill" data-bs-target="#v-pills-register" type="button" role="tab" aria-controls="v-pills-register" aria-selected="true"><?php esc_html_e('Register', 'web-to-print-online-designer'); ?></button>
                                <button class="nav-link" id="v-pills-login-tab" data-bs-toggle="pill" data-bs-target="#v-pills-login" type="button" role="tab" aria-controls="v-pills-login" aria-selected="false"><?php esc_html_e('Login', 'web-to-print-online-designer'); ?></button>
                            </nav>
                            <div class="tab-content" id="v-pills-tabContent">
                                <div class="tab-pane fade show active" id="v-pills-register" role="tabpanel" aria-labelledby="v-pills-register-tab">
                                    <table class="form-table pc-table">
                                        <tbody>
                                            <tr valign="top">
                                                <th class="titledesc">
                                                    <label><?php esc_html_e('Name: ', 'web-to-print-online-designer'); ?><span
                                                            class="printcart-help-tip"></span></label>
                                                </th>
                                                <td>
                                                    <p>
                                                        <input name="userName" ng-model="userName" type="text" style="width: 400px"
                                                            class="">
                                                    </p>
                                                    <label
                                                        class="description"><?php esc_html_e('Enter your name.', 'web-to-print-online-designer'); ?></label>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th class="titledesc">
                                                    <label><?php esc_html_e('Email: ', 'web-to-print-online-designer'); ?><span
                                                            class="printcart-help-tip"></span></label>
                                                </th>
                                                <td>
                                                    <p>
                                                        <input name="email" ng-model="email" type="email" style="width: 400px"
                                                            class="">
                                                    </p>
                                                    <label
                                                        class="description"><?php esc_html_e('Enter your email.', 'web-to-print-online-designer'); ?></label>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th class="titledesc">
                                                    <label><?php esc_html_e('Store Name: ', 'web-to-print-online-designer'); ?><span
                                                            class="printcart-help-tip"></span></label>
                                                </th>
                                                <td>
                                                    <p>
                                                        <input name="storeName" ng-model="storeName" type="text"
                                                            style="width: 400px" class="">
                                                    </p>
                                                    <label
                                                        class="description"><?php esc_html_e('Enter your store name.', 'web-to-print-online-designer'); ?></label>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th class="titledesc">
                                                    <label><?php esc_html_e('Shop URL: ', 'web-to-print-online-designer'); ?><span
                                                            class="printcart-help-tip"></span></label>
                                                </th>
                                                <td>
                                                    <p>
                                                        <input name="shopUrl" ng-model="shopUrl" type="text" style="width: 400px"
                                                            class="">
                                                    </p>
                                                    <label
                                                        class="description"><?php esc_html_e('Enter your shop URL.', 'web-to-print-online-designer'); ?></label>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <td colspan="2" class="text-center">
                                                    <button type="button" ng-click="handleConnect()"
                                                        class="pc-button-dashboard button button-primary px-4">
                                                        <?php esc_html_e('Connect', 'web-to-print-online-designer'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="tab-pane fade" id="v-pills-login" role="tabpanel" aria-labelledby="v-pills-login-tab">
                                    <div ng-show='!account.email'>
                                        <table class="form-table pc-table">
                                            <tbody>
                                                <tr valign="top">
                                                    <th class="titledesc">
                                                        <label><?php esc_html_e('Email', 'web-to-print-online-designer'); ?><span
                                                                class="printcart-help-tip"></span></label>
                                                    </th>
                                                    <td>
                                                        <p>
                                                            <input name="email" ng-model="email" type="email" style="width: 400px"
                                                                class="">
                                                        </p>
                                                        <label
                                                            class="description"><?php esc_html_e('Enter your email.', 'web-to-print-online-designer'); ?></label>
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <th class="titledesc">
                                                        <label><?php esc_html_e('Password: ', 'web-to-print-online-designer'); ?><span
                                                                class="printcart-help-tip"></span></label>
                                                    </th>
                                                    <td>
                                                        <p>
                                                            <input name="password" ng-model="password" type="password" style="width: 400px"
                                                                class="">
                                                        </p>
                                                        <label
                                                            class="description"><?php esc_html_e('Enter your password.', 'web-to-print-online-designer'); ?></label>
                                                    </td>
                                                </tr>
                                                <tr valign="top">
                                                    <td colspan="2" class="text-center">
                                                        <button ng-click="handleLogin()"
                                                            class="pc-button-dashboard button button-primary px-4">
                                                            <?php esc_html_e('Login', 'web-to-print-online-designer'); ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="p-3" ng-show="account.email">
                                        <div ng-show="!showCreateStore">
                                            <div ng-show="stores.length">
                                                <div class="py-3">
                                                    <h6 class="form-label fw-bold"><?php esc_html_e('Select a store', 'web-to-print-online-designer'); ?></h6>
                                                </div>
                                                <div class="d-flex flex-column align-items-center gap-3">
                                                    <button class="w-75 py-2 px-4 btn btn-light border d-flex justify-content-start align-items-center text-start"
                                                        role="alert"
                                                        ng-repeat="store in stores"
                                                        ng-click="selectStore(store)"
                                                        style="cursor: pointer;">
                                                        <div>
                                                            <h6 class="alert-heading">{{store.name}}</h6>
                                                            <small>{{store.shop_url}}</small>
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>
                                            <div ng-show="!stores.length">
                                                <?php esc_html_e('No stores found.', 'web-to-print-online-designer'); ?>
                                            </div>
                                            <div class="mt-3">
                                                <button type="button" ng-click="showCreateStore = true;" class="pc-button-dashboard button button-primary px-4">
                                                    <?php esc_html_e('Create store', 'web-to-print-online-designer'); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <div ng-show="showCreateStore">
                                            <div class="py-3 px-2 d-flex align-items-center gap-4">
                                                <button type="button" class="btn btn-link p-0 me-2 text-dark" ng-click="showCreateStore = false;" title="<?php esc_attr_e('Back', 'pc-photobook'); ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                                                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"></path>
                                                    </svg>
                                                </button>
                                                <h6 class="form-label fw-bold mb-0"><?php esc_html_e('Create store', 'web-to-print-online-designer'); ?></h6>
                                            </div>
                                            <div>
                                                <table class="form-table pc-table">
                                                    <tbody>
                                                        <tr valign="top">
                                                            <th class="titledesc">
                                                                <label><?php esc_html_e('Store Name: ', 'web-to-print-online-designer'); ?><span
                                                                        class="printcart-help-tip"></span></label>
                                                            </th>
                                                            <td>
                                                                <p>
                                                                    <input name="storeName" ng-model="storeName" type="text"
                                                                        style="width: 400px" class="">
                                                                </p>
                                                                <label
                                                                    class="description"><?php esc_html_e('Enter your store name.', 'web-to-print-online-designer'); ?></label>
                                                            </td>
                                                        </tr>
                                                        <tr valign="top">
                                                            <th class="titledesc">
                                                                <label><?php esc_html_e('Shop URL: ', 'web-to-print-online-designer'); ?><span
                                                                        class="printcart-help-tip"></span></label>
                                                            </th>
                                                            <td>
                                                                <p>
                                                                    <input name="shopUrl" ng-model="shopUrl" type="text" style="width: 400px"
                                                                        class="">
                                                                </p>
                                                                <label
                                                                    class="description"><?php esc_html_e('Enter your shop URL.', 'web-to-print-online-designer'); ?></label>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <div class="text-center">
                                                    <button ng-click="handleCreateStore()"
                                                        class="pc-button-dashboard button button-primary px-4">
                                                        <?php esc_html_e('Create Store', 'web-to-print-online-designer'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div ng-show="isLoadingConnect"
                        class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-25">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" id="manuallyModal" tabindex="-1" aria-labelledby="manuallyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="manuallyModalLabel">
                            <?php esc_html_e('Manually enter an API key', 'web-to-print-online-designer'); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-start">
                            To connect your store to the Printcart dashboard, you need to enter your API key. Go to <a href="{{printcart_detail.dashboard_url + '/settings/security'}}"
                                target="_blank">store settings</a> to get your API key.
                        </p>
                        <table class="form-table pc-table">
                            <tbody>
                                <tr valign="top">
                                    <th class="titledesc">
                                        <label><?php esc_html_e('Sid: ', 'web-to-print-online-designer'); ?><span
                                                class="printcart-help-tip"></span></label>
                                    </th>
                                    <td>
                                        <p>
                                            <input name="printcart_sid" ng-model="printcart_detail.sid" type="text"
                                                style="width: 400px" class="">
                                        </p>
                                        <label
                                            class="description"><?php esc_html_e('This is your unique Store ID provided by Printcart. It identifies your account and connects your store to the Printcart system.', 'web-to-print-online-designer'); ?></label>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th class="titledesc">
                                        <label><?php esc_html_e('Secret: ', 'web-to-print-online-designer'); ?><span
                                                class="printcart-help-tip"></span></label>
                                    </th>
                                    <td>
                                        <p>
                                            <input name="printcart_secret" ng-model="printcart_detail.secret"
                                                type="text" style="width: 400px" class="">
                                        </p>
                                        <label
                                            class="description"><?php esc_html_e('The Secret Key is used to authenticate your store and secure communication between your website and the Printcart cloud services.', 'web-to-print-online-designer'); ?></label>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th class="titledesc">
                                        <label><?php esc_html_e('Unauth token: ', 'web-to-print-online-designer'); ?><span
                                                class="printcart-help-tip"></span></label>
                                    </th>
                                    <td>
                                        <p>
                                            <input name="printcart_unauth_token" ng-model="printcart_detail.token"
                                                type="text" style="width: 400px" class="">
                                        </p>
                                        <label
                                            class="description"><?php esc_html_e('This token allows limited access for certain features that do not require full authentication. It`s optional but can enhance functionality.', 'web-to-print-online-designer'); ?></label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button ng-click="saveApiKey()" type="button" name="save" class="btn btn-primary me-2"
                            type="submit" value="<?php esc_attr_e('Save changes', 'web-to-print-online-designer'); ?>">
                            <?php esc_html_e('Save changes', 'web-to-print-online-designer'); ?>
                        </button>
                        <button ng-click="testApiKey()" type="button" class="btn btn-outline-primary"><?php esc_html_e("Test
                                        connect", "web-to-print-online-designer"); ?></button>
                    </div>
                    <div ng-show="isLoadingChange"
                        class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-25">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" id="importProductModal" tabindex="-1" aria-labelledby="importProductModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importProductModalLabel">
                            <?php esc_html_e('Import products', 'web-to-print-online-designer'); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-start mb-2 h6">
                            <?php esc_html_e('Do not turn off the page while importing products. It may take a few minutes.', 'web-to-print-online-designer'); ?>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Animated striped example"
                            aria-valuenow="{{100 * productImportedCount/productImportedTotal}}" aria-valuemin="0"
                            aria-valuemax="100">
                            <div class="progress-bar progress-bar bg-success progress-bar-striped progress-bar-animated"
                                style="width: {{100 * productImportedCount/productImportedTotal}}%">
                                {{ (100 * productImportedCount / productImportedTotal).toFixed(0) }}%
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div ng-if="isImportingProducts" class="spinner-grow spinner-grow-sm me-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="text-start my-1">
                                Name: {{product_sample_checked[productImportedCount].name}}
                            </div>
                        </div>
                    </div>
                    <div ng-show="isLoadingChange"
                        class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-25">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" id="confirmImportProductModal" tabindex="-1" aria-labelledby="confirmImportProductModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importProductModalLabel">
                            <?php esc_html_e('Import products', 'web-to-print-online-designer'); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-start mb-2 h6">
                            <?php esc_html_e('Some products have already been imported and may be duplicated. Are you sure you want to proceed with the import?', 'web-to-print-online-designer'); ?>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-end">
                        <button type="button"
                            ng-click="current_tab = 'general'; hiddenModal('confirmImportProductModal')"
                            class="btn btn-outline-primary" type="submit">
                            <?php esc_html_e('Skip', 'web-to-print-online-designer'); ?>
                        </button>
                        <button type="button" ng-click="importProducts()" class="btn btn-primary" type="submit">
                            <?php esc_html_e('Import product', 'web-to-print-online-designer'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" id="activeLicenseModal" tabindex="-1" aria-labelledby="activeLicenseModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="importProductModalLabel">
                            <?php esc_html_e('Upgrade account', 'web-to-print-online-designer'); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 text-start">
                                <img style="width: auto; height: 50px; margin-bottom: 15px;"
                                    src="<?php echo esc_attr(NBDESIGNER_PLUGIN_URL . 'assets/images/logo-printcart.svg'); ?>"
                                    class="img-fluid" alt="Printcart Logo">
                                <div class="p-2 text-start">
                                    <h6 class="mb-3"><?php esc_html_e('You are using the FREE version', 'web-to-print-online-designer'); ?></h6>
                                    <ul class="list-unstyled mb-0" style="font-size: 14px;">
                                        <li class="mb-2">
                                            <span class="me-2 text-success">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                                                </svg>
                                            </span>
                                            <span class="text-muted" style="font-weight: 500;">
                                                <?php esc_html_e('Download limit 10 design files', 'web-to-print-online-designer'); ?>
                                            </span>
                                        </li>
                                        <li class="mb-2">
                                            <span class="me-2 text-success">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                                                </svg>
                                            </span>
                                            <span class="text-muted" style="font-weight: 500;">
                                                <?php esc_html_e('Cannot download PDF and CMYK files', 'web-to-print-online-designer'); ?>
                                            </span>
                                        </li>
                                        <li class="mb-0">
                                            <span class="me-2 text-success">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                                                </svg>
                                            </span>
                                            <span class="text-muted" style="font-weight: 500;">
                                                <?php esc_html_e('Limit 10 products only', 'web-to-print-online-designer'); ?>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex flex-column justify-content-between align-items-center">
                                <h6><?php esc_html_e('Upgrade to PREMIUM', 'web-to-print-online-designer'); ?></h6>
                                <div class="text-start text-muted" style="font-weight: 500;">
                                    <p>
                                        <?php esc_html_e('Unlock all features and get more benefits with the PREMIUM version.', 'web-to-print-online-designer'); ?>
                                    </p>
                                    <p>
                                        <?php esc_html_e('To upgrade your account and unlock all features, please visit our premium page.', 'web-to-print-online-designer'); ?>
                                    </p>
                                </div>
                                <div class="p-2">
                                    <a type="button" href="https://solution.printcart.com/project-setup.html" class="btn btn-primary text-uppercase" target="_blank" style="border-radius: 50px;">
                                        <?php esc_html_e('Get PREMIUM vesion now', 'web-to-print-online-designer'); ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-right-fill" viewBox="0 0 16 16">
                                            <path d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 999999;">
            <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true"
                data-bs-delay="3000">
                <div class="alert mb-0 p-0"
                    ng-class="toastConfig.status == 'success' ? 'alert-success' : 'alert-danger'">
                    <div class="alert-heading d-flex align-items-center justify-content-between py-2 px-3 text-white"
                        ng-class="toastConfig.status == 'success' ? 'bg-success' : 'bg-danger'">
                        <strong class="me-auto">{{toastConfig.title}}</strong>
                        <button type="button" class="btn-close text-white" data-bs-dismiss="toast"
                            aria-label="Close"></button>
                    </div>
                    <div class="py-2 px-3 text-start fw-medium">
                        <p class="mb-0">{{toastConfig.message}}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>