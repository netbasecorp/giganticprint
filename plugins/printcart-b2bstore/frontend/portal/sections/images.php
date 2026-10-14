<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Portal section: Image Zone (private image library of the company).
 * Variables: $user_id, $user, $tier, $tiers, $is_b2b, $store
 */
$iz_store_ids = PCS_Company_Images::user_store_ids( $user_id );
if ( ! $iz_store_ids ) {
    echo '<div style="padding:24px;"><p>The Image Zone is available to members of an approved company store.</p></div>';
    return;
}
$iz_store_id = $iz_store_ids[0];
if ( isset( $_GET['store_id'] ) && in_array( absint( $_GET['store_id'] ), $iz_store_ids, true ) ) {
    $iz_store_id = absint( $_GET['store_id'] );
}
$iz_store = PCS_DB::get_store_by_id( $iz_store_id );
?>

<!-- ===== PAGE HEADER ===== -->
<div class="pcs-ph">
  <div class="pcs-ph-inner">
    <div class="pcs-ph-top">
      <div class="pcs-ph-logo">🖼️</div>
      <div class="pcs-ph-info">
        <div class="pcs-ph-eyebrow"><?php echo esc_html( $iz_store ? $iz_store->company_name : '' ); ?> · Image Zone</div>
        <h1 class="pcs-ph-title">Image Zone</h1>
        <p class="pcs-ph-sub">Your company's private image library. Only you, your team and our staff can see these files. Use them in the designer from the Photos tab.</p>
      </div>
      <div class="pcs-ph-actions">
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pcs-pbtn pcs-pbtn-primary">Use in the designer</a>
      </div>
    </div>
  </div>
</div>

<style>
.pcs-iz { padding: 20px 24px; }
.pcs-iz-drop { border: 2px dashed #93c5fd; background: #eff6ff; border-radius: 12px; padding: 18px; display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 18px; }
.pcs-iz-drop input[type=file] { max-width: 100%; }
.pcs-iz-btn { display: inline-block; border: 1px solid #1e3a8a; background: #1e3a8a; color: #fff; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; line-height: 1.3; }
.pcs-iz-btn:hover { background: #1e40af; }
.pcs-iz-btn[disabled] { opacity: .5; cursor: default; }
.pcs-iz-btn-del { background: #fff; color: #b91c1c; border-color: #fca5a5; padding: 6px 12px; font-size: 12px; }
.pcs-iz-btn-del:hover { background: #fee2e2; }
.pcs-iz-hint { font-size: 12px; color: #6b7280; flex: 1 1 220px; }
.pcs-iz-queue { margin: 0 0 16px; padding: 0; list-style: none; }
.pcs-iz-queue li { font-size: 13px; padding: 6px 0; border-bottom: 1px solid #f3f4f6; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.pcs-iz-queue .bar { flex: 1 1 120px; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; min-width: 80px; }
.pcs-iz-queue .bar i { display: block; height: 100%; width: 0; background: #2563eb; }
.pcs-iz-queue .err { color: #b91c1c; }
.pcs-iz-queue .ok { color: #047857; }
.pcs-iz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
.pcs-iz-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 1px 3px rgba(0,0,0,.05); min-width: 0; }
.pcs-iz-thumb { background: #f3f4f6; height: 170px; display: flex; align-items: center; justify-content: center; }
.pcs-iz-thumb img { max-width: 100%; max-height: 100%; display: block; }
.pcs-iz-body { padding: 12px 14px; font-size: 12px; color: #4b5563; flex: 1; }
.pcs-iz-name { font-size: 13px; font-weight: 600; color: #111827; word-break: break-all; margin-bottom: 4px; }
.pcs-iz-body div { margin-top: 2px; }
.pcs-iz-foot { padding: 10px 14px; border-top: 1px solid #f3f4f6; display: flex; justify-content: flex-end; }
.pcs-iz-empty, .pcs-iz-msg { font-size: 14px; color: #6b7280; padding: 24px 0; }
.pcs-iz-msg.err { color: #b91c1c; }
@media (max-width: 600px) {
  .pcs-iz { padding: 14px 12px; }
  .pcs-iz-grid { grid-template-columns: 1fr; }
  .pcs-iz-thumb { height: 200px; }
}
</style>

<div class="pcs-iz" id="pcs-iz" data-store="<?php echo (int) $iz_store_id; ?>">
  <div class="pcs-iz-drop">
    <input type="file" id="pcs-iz-file" multiple accept=".png,.jpg,.jpeg,.tif,.tiff,.pdf,.ai,.eps">
    <button type="button" class="pcs-iz-btn" id="pcs-iz-go">Upload</button>
    <span class="pcs-iz-hint">PNG, JPG, TIF, PDF, AI or EPS · up to 100 MB per file. Files are private to your company.</span>
  </div>
  <ul class="pcs-iz-queue" id="pcs-iz-queue"></ul>
  <div id="pcs-iz-msg" class="pcs-iz-msg">Loading your images…</div>
  <div class="pcs-iz-grid" id="pcs-iz-grid"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () { // PCS_FE is localized in the footer, after this inline script
  var root = document.getElementById('pcs-iz');
  if (!root || typeof PCS_FE === 'undefined') { return; }
  var storeId = root.getAttribute('data-store');
  var grid = document.getElementById('pcs-iz-grid');
  var msg = document.getElementById('pcs-iz-msg');
  var queue = document.getElementById('pcs-iz-queue');
  var input = document.getElementById('pcs-iz-file');
  var btn = document.getElementById('pcs-iz-go');
  var busy = false;

  function el(tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) { e.className = cls; }
    if (text !== undefined) { e.textContent = text; }
    return e;
  }
  function mb(b) { return (b / 1048576).toFixed(b < 10485760 ? 1 : 0) + ' MB'; }

  function post(action, fields, file, onProgress, cb) {
    var fd = new FormData();
    fd.append('action', action);
    fd.append('_nonce', PCS_FE.nonce);
    fd.append('store_id', storeId);
    for (var k in fields) { if (fields.hasOwnProperty(k)) { fd.append(k, fields[k]); } }
    if (file) { fd.append('file', file, file.name); }
    var x = new XMLHttpRequest();
    x.open('POST', PCS_FE.ajax_url);
    x.withCredentials = true;
    if (onProgress && x.upload) {
      x.upload.onprogress = function (ev) { if (ev.lengthComputable) { onProgress(ev.loaded / ev.total); } };
    }
    x.onload = function () {
      var j = null;
      try { j = JSON.parse(x.responseText); } catch (e) {}
      if (j && j.success) { cb(null, j.data); }
      else { cb((j && j.data && j.data.message) || (x.status === 413 ? 'The file is too large.' : 'Request failed (' + x.status + ').')); }
    };
    x.onerror = function () { cb('Network error.'); };
    x.send(fd);
  }

  function card(it) {
    var c = el('div', 'pcs-iz-card');
    c.setAttribute('data-iz-id', it.id);
    var t = el('div', 'pcs-iz-thumb');
    var img = document.createElement('img');
    img.alt = it.name;
    img.loading = 'lazy';
    img.src = it.thumb;
    t.appendChild(img);
    c.appendChild(t);
    var b = el('div', 'pcs-iz-body');
    b.appendChild(el('div', 'pcs-iz-name', it.name));
    b.appendChild(el('div', '', it.width && it.height ? it.width + ' x ' + it.height + ' px' : 'Size unknown'));
    b.appendChild(el('div', '', it.dpi ? it.dpi + ' DPI' : 'DPI unknown'));
    if (it.print_label) { b.appendChild(el('div', '', it.print_label)); }
    b.appendChild(el('div', '', mb(it.bytes) + ' · ' + it.created));
    if (it.uploader) { b.appendChild(el('div', '', 'By ' + it.uploader)); }
    c.appendChild(b);
    if (it.can_delete) {
      var f = el('div', 'pcs-iz-foot');
      var d = el('button', 'pcs-iz-btn pcs-iz-btn-del', 'Delete');
      d.type = 'button';
      d.onclick = function () {
        if (!window.confirm('Delete "' + it.name + '" from your Image Zone? This cannot be undone.')) { return; }
        d.disabled = true;
        post('gp_iz_delete', { id: it.id }, null, null, function (err) {
          if (err) { d.disabled = false; window.alert(err); return; }
          load();
        });
      };
      f.appendChild(d);
      c.appendChild(f);
    }
    return c;
  }

  function load() {
    post('gp_iz_list', {}, null, null, function (err, data) {
      grid.innerHTML = '';
      if (err) { msg.className = 'pcs-iz-msg err'; msg.textContent = err; return; }
      if (!data.items.length) { msg.className = 'pcs-iz-empty'; msg.textContent = 'No images yet. Upload your logos and artwork above.'; return; }
      msg.className = 'pcs-iz-msg'; msg.textContent = data.items.length + ' image' + (data.items.length === 1 ? '' : 's');
      for (var i = 0; i < data.items.length; i++) { grid.appendChild(card(data.items[i])); }
    });
  }

  function upload() {
    var files = input.files;
    if (busy || !files || !files.length) { return; }
    busy = true; btn.disabled = true;
    queue.innerHTML = '';
    var list = [], i;
    for (i = 0; i < files.length; i++) { list.push(files[i]); }
    var n = 0;
    function next() {
      if (n >= list.length) { busy = false; btn.disabled = false; input.value = ''; load(); return; }
      var f = list[n++];
      var li = el('li'), name = el('span', '', f.name + ' (' + mb(f.size) + ')'), bar = el('span', 'bar'), fill = document.createElement('i'), st = el('span', '', 'Uploading…');
      bar.appendChild(fill); li.appendChild(name); li.appendChild(bar); li.appendChild(st); queue.appendChild(li);
      if (f.size > 104857600) { st.className = 'err'; st.textContent = 'Larger than 100 MB'; next(); return; }
      post('gp_iz_upload', {}, f, function (p) { fill.style.width = Math.round(p * 100) + '%'; }, function (err) {
        if (err) { st.className = 'err'; st.textContent = err; }
        else { fill.style.width = '100%'; st.className = 'ok'; st.textContent = 'Done'; }
        next();
      });
    }
    next();
  }

  btn.onclick = upload;
  input.onchange = upload;
  load();
});
</script>
