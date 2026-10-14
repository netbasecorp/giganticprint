<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Portal section: Templates (private design templates of the company).
 * Variables: $user_id, $user, $tier, $tiers, $is_b2b, $store
 */
$tp_store_ids = PCS_Company_Templates::user_store_ids( $user_id );
if ( ! $tp_store_ids ) {
    echo '<div style="padding:24px;"><p>Company templates are available to members of an approved company store.</p></div>';
    return;
}
$tp_store_id = $tp_store_ids[0];
if ( isset( $_GET['store_id'] ) && in_array( absint( $_GET['store_id'] ), $tp_store_ids, true ) ) {
    $tp_store_id = absint( $_GET['store_id'] );
}
$tp_store = PCS_DB::get_store_by_id( $tp_store_id );
?>

<!-- ===== PAGE HEADER ===== -->
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">📐</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo esc_html( $tp_store ? $tp_store->company_name : '' ); ?> · Templates</div>
        <h1 class="pcs-ph-title">Templates</h1>
        <p class="pcs-ph-sub">Designs your team saved as company templates. Only you, your team and our staff can see them. Save one from the designer with "Save as company template".</p>
      </div>
    </div>
  </div>
</div>

<style>
.pcs-tp { padding: 20px 24px; }
.pcs-tp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 16px; }
.pcs-tp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 1px 3px rgba(0,0,0,.05); min-width: 0; }
.pcs-tp-thumb { background: #f3f4f6; height: 170px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 12px; }
.pcs-tp-thumb img { max-width: 100%; max-height: 100%; display: block; }
.pcs-tp-body { padding: 12px 14px; font-size: 12px; color: #4b5563; flex: 1; }
.pcs-tp-name { font-size: 14px; font-weight: 600; color: #111827; word-break: break-word; margin-bottom: 4px; }
.pcs-tp-body div { margin-top: 2px; word-break: break-word; }
.pcs-tp-foot { padding: 10px 14px; border-top: 1px solid #f3f4f6; display: flex; flex-wrap: wrap; gap: 6px; }
.pcs-tp-btn { display: inline-block; border: 1px solid #d1d5db; background: #fff; color: #111827; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; line-height: 1.3; text-decoration: none; }
.pcs-tp-btn:hover { background: #f3f4f6; }
.pcs-tp-btn[disabled] { opacity: .5; cursor: default; }
.pcs-tp-btn-go { background: #1e3a8a; border-color: #1e3a8a; color: #fff; }
.pcs-tp-btn-go:hover { background: #1e40af; color: #fff; }
.pcs-tp-btn-del { color: #b91c1c; border-color: #fca5a5; }
.pcs-tp-btn-del:hover { background: #fee2e2; }
.pcs-tp-empty, .pcs-tp-msg { font-size: 14px; color: #6b7280; padding: 24px 0; }
.pcs-tp-msg.err { color: #b91c1c; }
@media (max-width: 600px) {
  .pcs-tp { padding: 14px 12px; }
  .pcs-tp-grid { grid-template-columns: 1fr; }
  .pcs-tp-thumb { height: 200px; }
  .pcs-tp-foot .pcs-tp-btn { flex: 1 1 40%; text-align: center; }
}
</style>

<div class="pcs-tp" id="pcs-tp" data-store="<?php echo (int) $tp_store_id; ?>">
  <div id="pcs-tp-msg" class="pcs-tp-msg">Loading your templates…</div>
  <div class="pcs-tp-grid" id="pcs-tp-grid"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () { // PCS_FE is localized in the footer, after this inline script
  var root = document.getElementById('pcs-tp');
  if (!root || typeof PCS_FE === 'undefined') { return; }
  var storeId = root.getAttribute('data-store');
  var grid = document.getElementById('pcs-tp-grid');
  var msg = document.getElementById('pcs-tp-msg');

  function el(tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) { e.className = cls; }
    if (text !== undefined) { e.textContent = text; }
    return e;
  }

  function post(action, fields, cb) {
    var fd = new FormData();
    fd.append('action', action);
    fd.append('_nonce', PCS_FE.nonce);
    fd.append('store_id', storeId);
    for (var k in fields) { if (fields.hasOwnProperty(k)) { fd.append(k, fields[k]); } }
    var x = new XMLHttpRequest();
    x.open('POST', PCS_FE.ajax_url);
    x.withCredentials = true;
    x.onload = function () {
      var j = null;
      try { j = JSON.parse(x.responseText); } catch (e) {}
      if (j && j.success) { cb(null, j.data); }
      else { cb((j && j.data && j.data.message) || 'Request failed (' + x.status + ').'); }
    };
    x.onerror = function () { cb('Network error.'); };
    x.send(fd);
  }

  function action(label, cls, fn) {
    var b = el('button', 'pcs-tp-btn' + (cls ? ' ' + cls : ''), label);
    b.type = 'button';
    b.onclick = function () { fn(b); };
    return b;
  }

  function card(it) {
    var c = el('div', 'pcs-tp-card');
    c.setAttribute('data-tpl-id', it.id);
    var t = el('div', 'pcs-tp-thumb');
    if (it.thumb) {
      var img = document.createElement('img');
      img.alt = it.name;
      img.src = it.thumb;
      t.appendChild(img);
    } else {
      t.textContent = 'No preview';
    }
    c.appendChild(t);
    var b = el('div', 'pcs-tp-body');
    b.appendChild(el('div', 'pcs-tp-name', it.name));
    b.appendChild(el('div', '', it.product_name || 'Product #' + it.product_id));
    b.appendChild(el('div', '', 'Updated ' + it.updated));
    if (it.author) { b.appendChild(el('div', '', 'By ' + it.author)); }
    c.appendChild(b);

    var f = el('div', 'pcs-tp-foot');
    var go = el('a', 'pcs-tp-btn pcs-tp-btn-go', 'Start order');
    go.href = it.start_url;
    f.appendChild(go);
    f.appendChild(action('Rename', '', function (btn) {
      var n = window.prompt('New name for this template:', it.name);
      if (n === null) { return; }
      n = n.replace(/^\s+|\s+$/g, '');
      if (!n) { return; }
      btn.disabled = true;
      post('gp_tpl_rename', { id: it.id, name: n }, function (err) {
        btn.disabled = false;
        if (err) { window.alert(err); return; }
        load();
      });
    }));
    f.appendChild(action('Clone', '', function (btn) {
      btn.disabled = true;
      post('gp_tpl_clone', { id: it.id }, function (err) {
        btn.disabled = false;
        if (err) { window.alert(err); return; }
        load();
      });
    }));
    if (it.can_delete) {
      f.appendChild(action('Delete', 'pcs-tp-btn-del', function (btn) {
        if (!window.confirm('Delete the template "' + it.name + '"? This cannot be undone.')) { return; }
        btn.disabled = true;
        post('gp_tpl_delete', { id: it.id }, function (err) {
          if (err) { btn.disabled = false; window.alert(err); return; }
          load();
        });
      }));
    }
    c.appendChild(f);
    return c;
  }

  function load() {
    post('gp_tpl_list', {}, function (err, data) {
      grid.innerHTML = '';
      if (err) { msg.className = 'pcs-tp-msg err'; msg.textContent = err; return; }
      var items = data.items || [];
      if (!items.length) {
        msg.className = 'pcs-tp-empty';
        msg.textContent = 'No templates yet. Open a product in the designer, create your design and choose "Save as company template".';
        return;
      }
      msg.className = 'pcs-tp-msg';
      msg.textContent = items.length + ' template' + (items.length === 1 ? '' : 's');
      for (var i = 0; i < items.length; i++) { grid.appendChild(card(items[i])); }
    });
  }

  load();
});
</script>
