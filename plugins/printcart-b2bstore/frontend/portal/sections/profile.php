<?php if ( ! defined( 'ABSPATH' ) ) exit;
/** @var WP_User $user @var int $user_id */

$notices  = array();
$success  = false;

/* ---- Handle form submission ---- */
if ( isset( $_POST['pcs_profile_nonce'] ) && wp_verify_nonce( $_POST['pcs_profile_nonce'], 'pcs_update_profile' ) ) {

    $first_name   = sanitize_text_field( $_POST['first_name']   ?? '' );
    $last_name    = sanitize_text_field( $_POST['last_name']    ?? '' );
    $display_name = sanitize_text_field( $_POST['display_name'] ?? '' );
    $email        = sanitize_email( $_POST['account_email']     ?? '' );
    $phone        = sanitize_text_field( $_POST['phone']        ?? '' );
    $pass1        = $_POST['password_1'] ?? '';
    $pass2        = $_POST['password_2'] ?? '';
    $pass_current = $_POST['password_current'] ?? '';

    /* Validate */
    if ( empty( $first_name ) || empty( $last_name ) ) {
        $notices[] = array( 'error', 'First and last name are required.' );
    } elseif ( ! is_email( $email ) ) {
        $notices[] = array( 'error', 'Invalid email address.' );
    } elseif ( $email !== $user->user_email && email_exists( $email ) ) {
        $notices[] = array( 'error', 'That email is already in use.' );
    } elseif ( ! empty( $pass1 ) && $pass1 !== $pass2 ) {
        $notices[] = array( 'error', 'Passwords do not match.' );
    } elseif ( ! empty( $pass1 ) && ! wp_check_password( $pass_current, $user->user_pass, $user_id ) ) {
        $notices[] = array( 'error', 'Current password is incorrect.' );
    } else {
        /* Update WP user */
        $args = array(
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $display_name ?: "$first_name $last_name",
            'user_email'   => $email,
        );
        if ( ! empty( $pass1 ) ) $args['user_pass'] = $pass1;
        wp_update_user( $args );

        /* Update phone */
        if ( $phone ) update_user_meta( $user_id, 'billing_phone', $phone );

        /* Update shipping address */
        $addr_fields = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );
        foreach ( $addr_fields as $f ) {
            if ( isset( $_POST[ 'shipping_' . $f ] ) ) {
                update_user_meta( $user_id, 'shipping_' . $f, sanitize_text_field( wp_unslash( $_POST[ 'shipping_' . $f ] ) ) );
                update_user_meta( $user_id, 'billing_' . $f,  sanitize_text_field( wp_unslash( $_POST[ 'shipping_' . $f ] ) ) );
            }
        }

        /* Refresh user object */
        $user   = get_userdata( $user_id );
        $success = true;
        $notices[] = array( 'success', 'Profile updated successfully.' );
    }
}

/* ---- Load current data ---- */
$phone    = get_user_meta( $user_id, 'billing_phone', true );
$s_addr1  = get_user_meta( $user_id, 'shipping_address_1', true );
$s_addr2  = get_user_meta( $user_id, 'shipping_address_2', true );
$s_city   = get_user_meta( $user_id, 'shipping_city',      true );
$s_state  = get_user_meta( $user_id, 'shipping_state',     true );
$s_post   = get_user_meta( $user_id, 'shipping_postcode',  true );
$s_country = get_user_meta( $user_id, 'shipping_country',  true ) ?: WC()->countries->get_base_country();
$initials = PCS_Customers::get_initials( $user->display_name );
?>

<!-- Page header -->
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">👤</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo esc_html( get_bloginfo('name') ); ?> · Account</div>
        <h1 class="pcs-ph-title">My Profile</h1>
        <p class="pcs-ph-sub">Update your personal info, contact details, shipping address, and password.</p>
      </div>
    </div>
  </div>
</div>

<div style="padding:20px 24px;display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start;">

  <!-- LEFT: Avatar -->
  <div>
    <div class="pcs-pcard" style="text-align:center;padding:28px 20px;">
      <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#2b3a8a,#1a1547);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#fff;margin:0 auto 12px;">
        <?php echo esc_html( $initials ); ?>
      </div>
      <div style="font-weight:700;font-size:15px;color:#111827;"><?php echo esc_html( $user->display_name ); ?></div>
      <div style="font-size:12px;color:#9ca3af;margin-top:4px;"><?php echo esc_html( $user->user_email ); ?></div>
      <?php
      $tier      = PCS_DB::get_customer_tier( $user_id );
      $tiers     = PCS_DB::get_tiers_from_rules();
      $tier_def  = $tiers[ $tier ] ?? array( 'label' => 'B2C', 'discount' => 0 );
      $tier_style = PCS_DB::get_tier_style( $tier );
      ?>
      <div style="margin-top:10px;">
        <?php echo PCS_DB::tier_pill_html( $tier, $tier_def['label'] ); ?>
      </div>
    </div>
  </div>

  <!-- RIGHT: Form -->
  <div>
    <?php foreach ( $notices as $n ) : ?>
    <div style="background:<?php echo $n[0] === 'success' ? '#dcfce7' : '#fee2e2'; ?>;color:<?php echo $n[0] === 'success' ? '#065f46' : '#991b1b'; ?>;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:16px;">
      <?php echo $n[0] === 'success' ? '✅' : '⚠️'; ?> <?php echo esc_html( $n[1] ); ?>
    </div>
    <?php endforeach; ?>

    <form method="post">
      <?php wp_nonce_field( 'pcs_update_profile', 'pcs_profile_nonce' ); ?>

      <!-- Personal info -->
      <div class="pcs-pcard" style="margin-bottom:16px;">
        <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">Personal info</h3></div>
        <div class="pcs-pcard-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div class="pcs-pfield">
              <label class="pcs-pfield-label">First name <span style="color:#ef4444;">*</span></label>
              <input type="text" name="first_name" class="pcs-pinput" required value="<?php echo esc_attr( $user->first_name ); ?>">
            </div>
            <div class="pcs-pfield">
              <label class="pcs-pfield-label">Last name <span style="color:#ef4444;">*</span></label>
              <input type="text" name="last_name" class="pcs-pinput" required value="<?php echo esc_attr( $user->last_name ); ?>">
            </div>
          </div>
          <div class="pcs-pfield" style="margin-bottom:14px;">
            <label class="pcs-pfield-label">Email <span style="color:#ef4444;">*</span></label>
            <input type="email" name="account_email" class="pcs-pinput" required value="<?php echo esc_attr( $user->user_email ); ?>">
          </div>
          <div class="pcs-pfield">
            <label class="pcs-pfield-label">Phone</label>
            <input type="tel" name="phone" class="pcs-pinput" placeholder="(555) 123-4567" value="<?php echo esc_attr( $phone ); ?>">
          </div>
        </div>
      </div>

      <!-- Shipping address -->
      <div class="pcs-pcard" style="margin-bottom:16px;">
        <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">📍 Shipping address</h3></div>
        <div class="pcs-pcard-body">
          <div class="pcs-pfield" style="margin-bottom:14px;">
            <label class="pcs-pfield-label">Street address</label>
            <input type="text" name="shipping_address_1" class="pcs-pinput" placeholder="847 Marketing Way" value="<?php echo esc_attr( $s_addr1 ); ?>">
          </div>
          <div class="pcs-pfield" style="margin-bottom:14px;">
            <label class="pcs-pfield-label">Apartment / Suite (optional)</label>
            <input type="text" name="shipping_address_2" class="pcs-pinput" value="<?php echo esc_attr( $s_addr2 ); ?>">
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
            <div class="pcs-pfield">
              <label class="pcs-pfield-label">City</label>
              <input type="text" name="shipping_city" class="pcs-pinput" value="<?php echo esc_attr( $s_city ); ?>">
            </div>
            <div class="pcs-pfield">
              <label class="pcs-pfield-label">State / Province</label>
              <input type="text" name="shipping_state" class="pcs-pinput" value="<?php echo esc_attr( $s_state ); ?>">
            </div>
            <div class="pcs-pfield">
              <label class="pcs-pfield-label">Postcode</label>
              <input type="text" name="shipping_postcode" class="pcs-pinput" value="<?php echo esc_attr( $s_post ); ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Security -->
      <div class="pcs-pcard" style="margin-bottom:20px;">
        <div class="pcs-pcard-head"><h3 class="pcs-pcard-title">🔒 Security</h3></div>
        <div class="pcs-pcard-body">
          <p style="font-size:12px;color:#9ca3af;margin:0 0 14px;">Leave blank to keep current password.</p>
          <div class="pcs-pfield" style="margin-bottom:14px;">
            <label class="pcs-pfield-label">Current password</label>
            <input type="password" name="password_current" class="pcs-pinput" autocomplete="off">
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="pcs-pfield">
              <label class="pcs-pfield-label">New password</label>
              <input type="password" name="password_1" class="pcs-pinput" autocomplete="new-password">
            </div>
            <div class="pcs-pfield">
              <label class="pcs-pfield-label">Confirm new password</label>
              <input type="password" name="password_2" class="pcs-pinput" autocomplete="new-password">
            </div>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="pcs-pbtn pcs-pbtn-primary" style="flex:2;justify-content:center;">
          💾 Save changes
        </button>
        <a href="<?php echo esc_url( PCS_B2B_Portal::url('overview') ); ?>" class="pcs-pbtn pcs-pbtn-ghost" style="flex:1;justify-content:center;">
          Cancel
        </a>
      </div>
    </form>
  </div>

</div>
