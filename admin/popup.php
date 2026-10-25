<?php
require 'include/head.php';
session('WEBSITE-LOGIN');

// ── Ensure table + row exist ──────────────────────────────────
$connection_database->query("CREATE TABLE IF NOT EXISTS popup_settings (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    enabled        TINYINT(1)   NOT NULL DEFAULT 0,
    layout         VARCHAR(10)  NOT NULL DEFAULT 'right',
    badge_text     VARCHAR(80)  NOT NULL DEFAULT 'Kapraa',
    title          VARCHAR(200) NOT NULL DEFAULT '',
    description    TEXT         NOT NULL DEFAULT '',
    end_note       VARCHAR(200) NOT NULL DEFAULT '',
    button_text    VARCHAR(80)  NOT NULL DEFAULT 'Shop Now',
    button_url     VARCHAR(500) NOT NULL DEFAULT '',
    button_color   VARCHAR(20)  NOT NULL DEFAULT '#222222',
    image          VARCHAR(255) NOT NULL DEFAULT '',
    image_position VARCHAR(30)  NOT NULL DEFAULT 'center center',
    delay_seconds  INT          NOT NULL DEFAULT 3,
    reshow_type    VARCHAR(20)  NOT NULL DEFAULT 'session',
    reshow_days    INT          NOT NULL DEFAULT 1,
    updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

@$connection_database->query("ALTER TABLE popup_settings ADD COLUMN IF NOT EXISTS image_position VARCHAR(30) NOT NULL DEFAULT 'center center' AFTER image");
$connection_database->query("INSERT IGNORE INTO popup_settings (id) VALUES (1)");
if (!is_dir('images/popup')) @mkdir('images/popup', 0755, true);

$success = '';
$error   = '';

// ── HANDLE POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Delete image
    if (isset($_POST['delete_image'])) {
        $old = $connection_database->query("SELECT image FROM popup_settings WHERE id=1");
        if ($old && $r = $old->fetch_assoc() && !empty($r['image'])) {
            @unlink('images/popup/' . $r['image']);
        }
        $connection_database->query("UPDATE popup_settings SET image='' WHERE id=1");
        $success = 'Image removed.';
    }

    // Save settings
    if (isset($_POST['save_popup'])) {
        $enabled    = isset($_POST['enabled']) ? 1 : 0;
        $layouts    = ['right','left','top','bottom'];
        $layout     = in_array($_POST['layout'] ?? '', $layouts) ? $_POST['layout'] : 'right';
        $badge      = $connection_database->real_escape_string(trim($_POST['badge_text']  ?? ''));
        $ttl        = $connection_database->real_escape_string(trim($_POST['title']       ?? ''));
        $desc       = $connection_database->real_escape_string(trim($_POST['description'] ?? ''));
        $enote      = $connection_database->real_escape_string(trim($_POST['end_note']    ?? ''));
        $btxt       = $connection_database->real_escape_string(trim($_POST['button_text'] ?? 'Shop Now'));
        $burl       = $connection_database->real_escape_string(trim($_POST['button_url']  ?? ''));
        $bcol       = $connection_database->real_escape_string(trim($_POST['button_color']?? '#222222'));
        $delay      = max(0, (int)($_POST['delay_seconds'] ?? 3));
        $rt_ok      = ['always','session','days','never'];
        $rtype      = in_array($_POST['reshow_type'] ?? '', $rt_ok) ? $_POST['reshow_type'] : 'session';
        $rdays      = max(1, (int)($_POST['reshow_days'] ?? 1));
        $pos_ok     = ['top left','top center','top right','center left','center center','center right','bottom left','bottom center','bottom right'];
        $imgpos     = in_array($_POST['image_position'] ?? '', $pos_ok) ? $_POST['image_position'] : 'center center';
        $imgpos     = $connection_database->real_escape_string($imgpos);

        $imgField = '';
        if (!empty($_FILES['popup_image']['tmp_name']) && $_FILES['popup_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['popup_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $error = 'Only JPG, PNG or WEBP images allowed.';
            } else {
                $fname = 'popup_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['popup_image']['tmp_name'], 'images/popup/' . $fname)) {
                    $old = $connection_database->query("SELECT image FROM popup_settings WHERE id=1");
                    if ($old && $r = $old->fetch_assoc() && !empty($r['image'])) {
                        @unlink('images/popup/' . $r['image']);
                    }
                    $imgField = ", image='" . $connection_database->real_escape_string($fname) . "'";
                } else {
                    $error = 'Image upload failed — check folder permissions.';
                }
            }
        }

        if (!$error) {
            $ok = $connection_database->query("UPDATE popup_settings SET
                enabled=$enabled, layout='$layout',
                badge_text='$badge', title='$ttl',
                description='$desc', end_note='$enote',
                button_text='$btxt', button_url='$burl',
                button_color='$bcol', delay_seconds=$delay,
                reshow_type='$rtype', reshow_days=$rdays,
                image_position='$imgpos'
                $imgField
            WHERE id=1");
            $success = $ok ? 'Popup settings saved successfully!' : 'DB error: ' . $connection_database->error;
        }
    }
}

// ── FETCH CURRENT SETTINGS ────────────────────────────────────
$rs  = $connection_database->query("SELECT * FROM popup_settings WHERE id=1");
$cfg = ($rs && $rs->num_rows > 0) ? $rs->fetch_assoc() : [];
$cfg = array_merge([
    'enabled'        => 0,
    'layout'         => 'right',
    'badge_text'     => 'Kapraa',
    'title'          => '',
    'description'    => '',
    'end_note'       => '',
    'button_text'    => 'Shop Now',
    'button_url'     => '',
    'button_color'   => '#222222',
    'image'          => '',
    'image_position' => 'center center',
    'delay_seconds'  => 3,
    'reshow_type'    => 'session',
    'reshow_days'    => 1,
], $cfg);
?>

<div class="content-body">
<div class="container-fluid">

  <div class="row page-titles mx-0 mb-3">
    <div class="col p-md-0">
      <h4 class="mb-1">
        <i class="fa fa-bullhorn text-primary me-2"></i>Promotional Popup Manager
      </h4>
      <p class="text-muted mb-0">Control the popup shown to all website visitors</p>
    </div>
  </div>

  <?php if ($success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fa fa-check-circle me-2"></i><strong><?= htmlspecialchars($success) ?></strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fa fa-times-circle me-2"></i><strong><?= htmlspecialchars($error) ?></strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="row">

    <!-- ── LEFT COLUMN: FORM ──────────────────────────────── -->
    <div class="col-xl-7 col-lg-12">
      <div class="card">
        <div class="card-header border-0 pb-0">
          <h4 class="card-title">
            <i class="fa fa-cog text-primary me-2"></i>Popup Settings
          </h4>
        </div>
        <div class="card-body pt-3">
          <form method="POST" enctype="multipart/form-data" id="popupForm">

            <!-- STATUS -->
            <div class="mb-4 p-3 rounded" style="background:#f4f6fc;border:1px solid #e0e4f0;">
              <div class="d-flex align-items-center gap-3">
                <i class="fa fa-power-off fa-lg text-muted"></i>
                <span class="fw-bold fs-6">Popup Status:</span>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="popupEnabled" name="enabled"
                         style="width:50px;height:26px;cursor:pointer;" <?= $cfg['enabled'] ? 'checked' : '' ?>>
                  <label class="form-check-label fw-bold ms-2 fs-6" for="popupEnabled">
                    <?php if ($cfg['enabled']): ?>
                      <span class="badge bg-success px-3 py-2">
                        <i class="fa fa-circle me-1" style="font-size:8px;"></i>LIVE
                      </span>
                    <?php else: ?>
                      <span class="badge bg-secondary px-3 py-2">OFF</span>
                    <?php endif; ?>
                  </label>
                </div>
              </div>
              <small class="text-muted d-block mt-1">
                <i class="fa fa-info-circle me-1"></i>Turn ON to show popup to all website visitors
              </small>
            </div>

            <!-- LAYOUT -->
            <div class="mb-4">
              <label class="form-label fw-bold">
                <i class="fa fa-th-large me-1 text-primary"></i>Layout
              </label>
              <div class="d-flex flex-wrap gap-2">
                <?php
                $layouts = [
                  'right'  => ['icon' => 'fa-arrow-right',  'label' => 'Image Right'],
                  'left'   => ['icon' => 'fa-arrow-left',   'label' => 'Image Left'],
                  'top'    => ['icon' => 'fa-arrow-up',     'label' => 'Image Top'],
                  'bottom' => ['icon' => 'fa-arrow-down',   'label' => 'Image Bottom'],
                ];
                foreach ($layouts as $v => $info):
                  $active = $cfg['layout'] === $v;
                ?>
                <label class="layout-opt border rounded px-3 py-2 d-flex align-items-center gap-2"
                       style="cursor:pointer;<?= $active ? 'border-color:#6610f2!important;background:#f0ecff;' : '' ?>">
                  <input type="radio" name="layout" value="<?= $v ?>" class="d-none" <?= $active ? 'checked' : '' ?>>
                  <i class="fa <?= $info['icon'] ?> <?= $active ? 'text-primary' : 'text-muted' ?>"></i>
                  <span style="font-size:13px;font-weight:600;"><?= $info['label'] ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- TEXT FIELDS -->
            <div class="row g-3 mb-3">
              <div class="col-12">
                <label class="form-label fw-semibold">
                  <i class="fa fa-tag me-1 text-primary"></i>Badge / Brand Text
                </label>
                <input type="text" name="badge_text" class="form-control"
                       value="<?= htmlspecialchars($cfg['badge_text']) ?>"
                       placeholder="e.g. Kapraa  or  Summer Sale">
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold">
                  <i class="fa fa-header me-1 text-primary"></i>Headline
                </label>
                <input type="text" name="title" class="form-control"
                       value="<?= htmlspecialchars($cfg['title']) ?>"
                       placeholder="e.g. Up to 50% Off — New Arrivals!">
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold">
                  <i class="fa fa-align-left me-1 text-primary"></i>Description
                </label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Describe the offer..."><?= htmlspecialchars($cfg['description']) ?></textarea>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold">
                  <i class="fa fa-clock-o me-1 text-primary"></i>End Note
                  <small class="text-muted fw-normal">(optional)</small>
                </label>
                <input type="text" name="end_note" class="form-control"
                       value="<?= htmlspecialchars($cfg['end_note']) ?>"
                       placeholder="e.g. Offer ends Sunday at midnight.">
              </div>
              <div class="col-sm-5">
                <label class="form-label fw-semibold">
                  <i class="fa fa-mouse-pointer me-1 text-primary"></i>Button Text
                </label>
                <input type="text" name="button_text" class="form-control"
                       value="<?= htmlspecialchars($cfg['button_text']) ?>" placeholder="Shop Now">
              </div>
              <div class="col-sm-5">
                <label class="form-label fw-semibold">
                  <i class="fa fa-link me-1 text-primary"></i>Button Link
                </label>
                <input type="text" name="button_url" class="form-control"
                       value="<?= htmlspecialchars($cfg['button_url']) ?>" placeholder="https://... or /products">
              </div>
              <div class="col-sm-2">
                <label class="form-label fw-semibold">
                  <i class="fa fa-paint-brush me-1 text-primary"></i>Color
                </label>
                <input type="color" name="button_color" id="btnColor"
                       class="form-control form-control-color w-100"
                       value="<?= htmlspecialchars($cfg['button_color']) ?>"
                       style="height:42px;padding:3px;cursor:pointer;">
              </div>
            </div>

            <!-- IMAGE -->
            <hr>
            <div class="mb-3">
              <label class="form-label fw-bold">
                <i class="fa fa-image me-1 text-primary"></i>Popup Image
              </label>
              <?php if (!empty($cfg['image'])): ?>
              <div class="d-flex align-items-center gap-3 p-2 border rounded mb-2">
                <img src="images/popup/<?= htmlspecialchars($cfg['image']) ?>"
                     alt="popup" style="height:80px;width:60px;object-fit:cover;border-radius:6px;">
                <div>
                  <div class="text-success fw-bold mb-1">
                    <i class="fa fa-check-circle me-1"></i>Image is set
                  </div>
                  <button type="submit" name="delete_image" value="1"
                          class="btn btn-sm btn-outline-danger"
                          onclick="return confirm('Remove this image?')">
                    <i class="fa fa-trash me-1"></i>Remove
                  </button>
                </div>
              </div>
              <?php endif; ?>
              <input type="file" name="popup_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
              <small class="text-muted">
                <i class="fa fa-info-circle me-1"></i>JPG, PNG or WEBP. Use portrait (600×900) for Left/Right layout, landscape (900×400) for Top/Bottom.
              </small>
            </div>

            <!-- IMAGE FOCUS POINT -->
            <div class="mb-3">
              <label class="form-label fw-bold">
                <i class="fa fa-crosshairs me-1 text-primary"></i>Image Focus Point
              </label>
              <div class="d-flex align-items-center gap-4 flex-wrap">
                <div>
                  <?php
                  // Arrow icons for the 3×3 focus grid
                  $posIcons = [
                    'top left'      => ['fa-arrow-up',    'rotate(-45deg)'],
                    'top center'    => ['fa-arrow-up',    'none'],
                    'top right'     => ['fa-arrow-up',    'rotate(45deg)'],
                    'center left'   => ['fa-arrow-left',  'none'],
                    'center center' => ['fa-circle',      'none'],
                    'center right'  => ['fa-arrow-right', 'none'],
                    'bottom left'   => ['fa-arrow-down',  'rotate(45deg)'],
                    'bottom center' => ['fa-arrow-down',  'none'],
                    'bottom right'  => ['fa-arrow-down',  'rotate(-45deg)'],
                  ];
                  ?>
                  <div style="display:grid;grid-template-columns:repeat(3,40px);gap:4px;">
                    <?php foreach ($posIcons as $pos => [$icon, $rotate]):
                      $isActive = $cfg['image_position'] === $pos;
                      $rotateStyle = $rotate !== 'none' ? "transform:{$rotate};display:inline-block;" : '';
                    ?>
                    <button type="button"
                            class="pos-btn btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?>"
                            data-pos="<?= $pos ?>"
                            style="width:40px;height:40px;padding:0;"
                            title="<?= ucwords($pos) ?>">
                      <i class="fa <?= $icon ?>" style="font-size:13px;<?= $rotateStyle ?>"></i>
                    </button>
                    <?php endforeach; ?>
                  </div>
                  <input type="hidden" name="image_position" id="imgPosInput"
                         value="<?= htmlspecialchars($cfg['image_position']) ?>">
                  <small class="text-muted d-block mt-1">
                    Selected: <strong id="posLabel"><?= ucwords($cfg['image_position']) ?></strong>
                  </small>
                </div>
                <?php if (!empty($cfg['image'])): ?>
                <div style="width:110px;height:80px;border-radius:8px;overflow:hidden;border:1px solid #ddd;">
                  <img id="posThumb" src="images/popup/<?= htmlspecialchars($cfg['image']) ?>"
                       style="width:100%;height:100%;object-fit:cover;object-position:<?= htmlspecialchars($cfg['image_position']) ?>;">
                </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- TIMING -->
            <hr>
            <label class="form-label fw-bold">
              <i class="fa fa-clock-o me-1 text-primary"></i>Display Timing
            </label>
            <div class="row g-3 mb-4">
              <div class="col-sm-4">
                <label class="form-label fw-semibold">
                  <i class="fa fa-hourglass-start me-1 text-muted"></i>Show After (seconds)
                </label>
                <input type="number" name="delay_seconds" class="form-control"
                       min="0" max="300" value="<?= (int)$cfg['delay_seconds'] ?>">
                <small class="text-muted">0 = instant</small>
              </div>
              <div class="col-sm-4">
                <label class="form-label fw-semibold">
                  <i class="fa fa-refresh me-1 text-muted"></i>Re-show Rule
                </label>
                <select name="reshow_type" class="form-select" id="reshowType">
                  <?php
                  $reshowOptions = [
                    'always'  => ['icon' => 'fa-repeat',       'label' => 'Every visit'],
                    'session' => ['icon' => 'fa-clock-o',      'label' => 'Once per session'],
                    'days'    => ['icon' => 'fa-calendar',     'label' => 'After X days'],
                    'never'   => ['icon' => 'fa-ban',          'label' => 'Never again'],
                  ];
                  foreach ($reshowOptions as $v => $opt): ?>
                  <option value="<?= $v ?>" <?= $cfg['reshow_type']===$v ? 'selected' : '' ?>>
                    <?= $opt['label'] ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-4" id="daysWrap" style="<?= $cfg['reshow_type']==='days' ? '' : 'display:none' ?>">
                <label class="form-label fw-semibold">
                  <i class="fa fa-calendar-o me-1 text-muted"></i>Days to wait
                </label>
                <input type="number" name="reshow_days" class="form-control"
                       min="1" max="365" value="<?= (int)$cfg['reshow_days'] ?>">
              </div>
            </div>

            <button type="submit" name="save_popup" class="btn btn-primary btn-lg px-5">
              <i class="fa fa-save me-2"></i>Save Popup Settings
            </button>

          </form>
        </div>
      </div>
    </div>

    <!-- ── RIGHT COLUMN: PREVIEW ──────────────────────────── -->
    <div class="col-xl-5 col-lg-12">
      <div class="card">
        <div class="card-header border-0 pb-0">
          <h4 class="card-title">
            <i class="fa fa-eye text-primary me-2"></i>Live Preview
          </h4>
        </div>
        <div class="card-body pt-3">
          <div id="previewBox"
               style="border:1px solid #ddd;border-radius:14px;overflow:hidden;
                      box-shadow:0 4px 20px rgba(0,0,0,.1);background:#fff;min-height:160px;">
          </div>
          <div class="d-flex justify-content-center align-items-center gap-3 mt-3 p-2 rounded"
               style="background:#f8f9fa;font-size:12px;color:#555;">
            <?php if ($cfg['enabled']): ?>
              <span class="badge bg-success"><i class="fa fa-circle me-1" style="font-size:8px;"></i>LIVE</span>
            <?php else: ?>
              <span class="badge bg-secondary"><i class="fa fa-circle me-1" style="font-size:8px;"></i>OFF</span>
            <?php endif; ?>
            <span><i class="fa fa-clock-o me-1"></i>Shows after <strong><?= (int)$cfg['delay_seconds'] ?>s</strong></span>
            <span>|</span>
            <span><i class="fa fa-refresh me-1"></i>Re-show: <strong><?= ['always'=>'Every visit','session'=>'Once/session','days'=>(int)$cfg['reshow_days'].' day(s)','never'=>'Never'][$cfg['reshow_type']] ?? 'Once/session' ?></strong></span>
          </div>
        </div>
      </div>

    </div>

  </div><!-- /row -->
</div>
</div>

<script>
(function () {
  /* ── Initial state from PHP ───────────────────────── */
  var S = {
    layout  : '<?= addslashes($cfg['layout']) ?>',
    img     : '<?= !empty($cfg['image']) ? addslashes('images/popup/'.$cfg['image']) : '' ?>',
    imgPos  : '<?= addslashes($cfg['image_position']) ?>',
    badge   : '<?= addslashes(htmlspecialchars_decode($cfg['badge_text'] ?: 'Kapraa')) ?>',
    title   : '<?= addslashes(htmlspecialchars_decode($cfg['title'] ?: 'Your Headline Here')) ?>',
    desc    : '<?= addslashes(htmlspecialchars_decode($cfg['description'] ?: 'Your offer description goes here.')) ?>',
    endnote : '<?= addslashes(htmlspecialchars_decode($cfg['end_note'])) ?>',
    btnText : '<?= addslashes(htmlspecialchars_decode($cfg['button_text'] ?: 'Shop Now')) ?>',
    btnColor: '<?= addslashes($cfg['button_color']) ?>',
  };

  /* ── Build preview HTML ───────────────────────────── */
  function buildPreview() {
    var imgTag = S.img
      ? '<img src="'+S.img+'" style="width:100%;height:100%;object-fit:cover;object-position:'+S.imgPos+';display:block;">'
      : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;opacity:.2;background:#f5f5f5;"><svg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#888\' stroke-width=\'1.5\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><polyline points=\'21 15 16 10 5 21\'/></svg></div>';

    var content = '<div style="display:flex;flex-direction:column;justify-content:center;gap:7px;padding:24px 20px;flex:1;min-width:0;">'
      + '<div style="font-size:.82rem;font-weight:900;color:#555;letter-spacing:.5px;">'+esc(S.badge)+'</div>'
      + '<div style="font-size:.92rem;font-weight:800;color:#111;line-height:1.3;">'+esc(S.title)+'</div>'
      + '<div style="font-size:11.5px;color:#555;line-height:1.65;">'+esc(S.desc)+'</div>'
      + (S.endnote ? '<div style="font-size:10px;color:#aaa;font-style:italic;">'+esc(S.endnote)+'</div>' : '')
      + '<a href="#" style="display:inline-block;margin-top:6px;padding:9px 22px;border-radius:6px;color:#fff;font-weight:700;font-size:12px;text-decoration:none;background:'+S.btnColor+';">'+esc(S.btnText)+'</a>'
      + '</div>';

    if (S.layout === 'right' || S.layout === 'left') {
      var imgDiv = '<div style="flex:0 0 42%;overflow:hidden;height:220px;">'+imgTag+'</div>';
      var cDiv   = '<div style="flex:0 0 58%;height:220px;display:flex;">'+content+'</div>';
      var row    = S.layout === 'right' ? cDiv + imgDiv : imgDiv + cDiv;
      return '<div style="display:flex;height:220px;overflow:hidden;border-radius:14px;">'+row+'</div>';
    }
    if (S.layout === 'top') {
      return '<div style="height:130px;overflow:hidden;">'+imgTag+'</div>'
           + '<div style="display:flex;">'+content+'</div>';
    }
    return '<div style="display:flex;">'+content+'</div>'
         + '<div style="height:130px;overflow:hidden;">'+imgTag+'</div>';
  }

  function esc(s){ var d=document.createElement('div'); d.textContent=String(s); return d.innerHTML; }
  function render(){ document.getElementById('previewBox').innerHTML = buildPreview(); }
  render();

  /* ── Layout selection ─────────────────────────────── */
  document.querySelectorAll('.layout-opt input').forEach(function(r){
    r.addEventListener('change', function(){
      S.layout = this.value;
      document.querySelectorAll('.layout-opt').forEach(function(l){ l.style.borderColor=''; l.style.background=''; });
      this.closest('.layout-opt').style.borderColor='#6610f2';
      this.closest('.layout-opt').style.background='#f0ecff';
      render();
    });
  });

  /* ── Text fields ──────────────────────────────────── */
  var fieldMap = [['badge_text','badge'],['title','title'],['description','desc'],['end_note','endnote'],['button_text','btnText']];
  fieldMap.forEach(function(p){
    var el = document.querySelector('[name="'+p[0]+'"]');
    if(el) el.addEventListener('input', function(){ S[p[1]] = this.value; render(); });
  });

  /* ── Button color ─────────────────────────────────── */
  var colorEl = document.getElementById('btnColor');
  if(colorEl) colorEl.addEventListener('input', function(){ S.btnColor = this.value; render(); });

  /* ── Image file preview ───────────────────────────── */
  var fileEl = document.querySelector('input[name="popup_image"]');
  if(fileEl){
    fileEl.addEventListener('change', function(){
      var f = this.files[0]; if(!f) return;
      var fr = new FileReader();
      fr.onload = function(e){ S.img = e.target.result; render(); };
      fr.readAsDataURL(f);
    });
  }

  /* ── Image focus buttons ──────────────────────────── */
  document.querySelectorAll('.pos-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.pos-btn').forEach(function(b){
        b.classList.remove('btn-primary'); b.classList.add('btn-outline-secondary');
      });
      this.classList.remove('btn-outline-secondary'); this.classList.add('btn-primary');
      S.imgPos = this.dataset.pos;
      document.getElementById('imgPosInput').value = S.imgPos;
      document.getElementById('posLabel').textContent = S.imgPos.replace(/\b\w/g, function(c){ return c.toUpperCase(); });
      var th = document.getElementById('posThumb');
      if(th) th.style.objectPosition = S.imgPos;
      render();
    });
  });

  /* ── Re-show days toggle ──────────────────────────── */
  var rtEl = document.getElementById('reshowType'), dw = document.getElementById('daysWrap');
  if(rtEl && dw) rtEl.addEventListener('change', function(){ dw.style.display = this.value==='days'?'':'none'; });

})();
</script>

<?php include 'include/footer.php'; ?>
