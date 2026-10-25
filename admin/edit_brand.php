<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	
    
    $brandId = cleanStr($_GET['id']);
    
    // Fetch current brand data
    $brandQuery = selectQuery("SELECT * FROM brand WHERE id ='$brandId' ORDER BY id DESC");
    $brandData = $brandQuery['data'][0];

    $name           = $brandData['name'];
    $image          = $brandData['image'];
    $slug           = $brandData['slug'];
    $brand_main     = $brandData['brand_main'];
    $tag_line       = $brandData['tag_line'];
    $brand_banner   = $brandData['brand_banner'];
    $image_position = $brandData['image_position'] ?? 'center';
    $discount_text  = $brandData['discount_text'] ?? '';
    
    if(isset($_POST['update'])) {
        
        $name          = cleanStr($_POST['name']);
        $brand_main    = cleanStr($_POST['brand_main']);
        $slug          = cleanStr($_POST['slug']);
        $tag_line      = cleanStr($_POST['tag_line']);
        $discount_text = cleanStr($_POST['discount_text'] ?? '');
        $allowedExts = array('png', 'jpg', 'jpeg','webp');
        $error = [];

        // Check if slug already exists (excluding current brand)
        $originalSlug = $slug;
        $counter = 1;
        
        while(true) {
            $checkSlug = selectQuery("SELECT id FROM brand WHERE slug = '$slug' AND id != '$brandId'");
            if($checkSlug['status'] == true && !empty($checkSlug['data'])) {
                // Slug exists for another brand, append counter and check again
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            } else {
                // Slug is unique or belongs to current brand, break the loop
                break;
            }
        }

        // Handle image upload
        $imageName = $image; // default to existing image
        if(!empty($_FILES['image']['name'])) {
            $imgName   = $_FILES['image']['name'];
            $imgTmp    = $_FILES['image']['tmp_name'];
            $imgExt    = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
            
            // Validate file type
            if (!in_array($imgExt, $allowedExts)) {
                $error[] = "File type not allowed for image. Only PNG, JPG, webp, and JPEG are allowed.";
            } else {
                $newName   = uniqid() . '.' . $imgExt;
                $targetDir = "images/brand/";
                $uploadPath = $targetDir . $newName;

                // Move the file
                if(move_uploaded_file($imgTmp, $uploadPath)) {
                    $imageName = $newName;
                    // Delete old image
                    if(file_exists($targetDir . $image)) {
                        unlink($targetDir . $image);
                    }
                } else {
                    $error[] = "Image upload failed";
                }
            }
        }

        // Handle banner upload
        $bannerName = $brand_banner; // default to existing banner
        if(!empty($_FILES['brand_banner']['name'])) {
            $bannerImgName   = $_FILES['brand_banner']['name'];
            $bannerTmp    = $_FILES['brand_banner']['tmp_name'];
            $bannerExt    = strtolower(pathinfo($bannerImgName, PATHINFO_EXTENSION));
            
            // Validate file type
            if (!in_array($bannerExt, $allowedExts)) {
                $error[] = "File type not allowed for banner. Only PNG, JPG, webp, and JPEG are allowed.";
            } else {
                $newBannerName   = uniqid() . '.' . $bannerExt;
                $bannerDir = "images/brand_banner/";
                $bannerUploadPath = $bannerDir . $newBannerName;
                
                // Create directory if it doesn't exist
                if (!file_exists($bannerDir)) {
                    mkdir($bannerDir, 0777, true);
                }

                // Move the file
                if(move_uploaded_file($bannerTmp, $bannerUploadPath)) {
                    $bannerName = $newBannerName;
                    // Delete old banner
                    if(!empty($brand_banner) && file_exists($bannerDir . $brand_banner)) {
                        unlink($bannerDir . $brand_banner);
                    }
                } else {
                    $error[] = "Banner upload failed";
                }
            }
        }

        $image_position = in_array($_POST['image_position'] ?? '', ['top','center','bottom'])
            ? $_POST['image_position'] : 'center';

        // Update query if no errors
        if(empty($error)) {
            if(update_data_query('brand', array(
                'name'           => $name,
                'brand_main'     => $brand_main,
                'tag_line'       => $tag_line,
                'slug'           => $slug,
                'image'          => $imageName,
                'brand_banner'   => $bannerName,
                'image_position' => $image_position,
                'discount_text'  => $discount_text
            ), "id = '$brandId'") == true) {
                location('add_brand.php');
            } else {
                $error[] = "Something went wrong";
            }
        }
    }
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($error as $err): ?>
                                <p><?php echo $err; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="" class="font-weight-bold">Brand Name</label>
                                <input type="text" class="form-control" name="name" id="name" value="<?php echo $name?>" placeholder="Enter Name">
                            </div>
                            <div class="col-md-4">
                                <label for="" class="font-weight-bold">Slug</label>
                                <input type="text" name="slug" id="slug" class="form-control" value="<?php echo $slug ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="" class="font-weight-bold">Brand Main Page</label>
                                <select name="brand_main" class="form-control" id="">
                                    <option value="">-- Please Select --</option>
                                    <option value="New Arrivals" <?php echo ($brand_main == 'New Arrivals') ? 'selected' : ''; ?>>New Arrivals</option>
                                    <!-- <option value="Best Sellers" <?php echo ($brand_main == 'Best Sellers') ? 'selected' : ''; ?>>Best Sellers</option> -->
                                    <option value="Festive Wear" <?php echo ($brand_main == 'Festive Wear') ? 'selected' : ''; ?>>Festive Wear</option>
                                    <option value="Unstitched Dresses" <?php echo ($brand_main == 'Unstitched Dresses') ? 'selected' : ''; ?>>Unstitched Dresses</option>
                                    <option value="Mens Wear" <?php echo ($brand_main == "Mens Wear") ? 'selected' : ''; ?>>Mens Wear</option>
                                    <option value="Kids Wear" <?php echo ($brand_main == "Kids Wear") ? 'selected' : ''; ?>>Kids Wear</option>
                                    <!-- <option value="Accessories and Footwear" <?php echo ($brand_main == 'Accessories and Footwear') ? 'selected' : ''; ?>>Accessories and Footwear</option> -->
                                </select>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="" class="font-weight-bold">Brand Tag Line</label>
                                <input type="text" name="tag_line" class="form-control" value="<?php echo htmlspecialchars($tag_line) ?>">
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="" class="font-weight-bold">Discount Text <small class="text-muted">(circle flip)</small></label>
                                <input type="text" name="discount_text" class="form-control" value="<?php echo htmlspecialchars($discount_text) ?>" placeholder="e.g. UPTO 40% OFF or FLAT 20% OFF">
                                <small class="text-muted">Leave blank to show image only (no flip).</small>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="" class="font-weight-bold">Brand Circle Image</label>
                                <input type="file" id="imgInp" name="image" class="form-control p-2 mb-2 bg-white">
                                <label class="small fw-bold mt-1">Circle Image Focus</label>
                                <select name="image_position" class="form-control form-select mb-2">
                                    <option value="top"    <?= ($image_position=='top')    ? 'selected':'' ?>>Top — show face/head from top</option>
                                    <option value="center" <?= ($image_position=='center') ? 'selected':'' ?>>Center (default)</option>
                                    <option value="bottom" <?= ($image_position=='bottom') ? 'selected':'' ?>>Bottom</option>
                                </select>
                                <div class="mt-1">
                                    <label class="small text-muted">Current Image:</label><br>
                                    <img id="blah" src="images/brand/<?php echo $image; ?>"
                                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;object-position:<?= $image_position ?> center;border:3px solid #0d6efd;">
                                </div>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="" class="font-weight-bold">Brand Banner</label>
                                <input type="file" id="bannerInp" name="brand_banner" class="form-control bg-white">
                                <div class="mt-2">
                                    <label class="small text-muted">Current Banner:</label>
                                    <?php if (!empty($brand_banner)): ?>
                                        <img id="currentBanner" src="images/brand_banner/<?php echo $brand_banner; ?>" width="100" class="border rounded">
                                    <?php else: ?>
                                        <p class="text-muted">No banner uploaded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                                            <div class="col-md-12 text-end">
                                <button class="btn btn-primary mt-3" name="update">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ VOLUME CIRCLES MANAGER ══════════════════════════════ -->
    <div class="col-md-12 mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" font-size="10" fill="currentColor" stroke="none">V</text></svg>
                    Brand Volumes / Collections
                </h4>
                <button class="btn btn-success btn-sm" id="showAddVolForm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Volume
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Each volume is shown as a clickable circle on the brand page. Customers click a circle to see products in that volume/collection.</p>

                <!-- Add Volume Form -->
                <div id="addVolForm" style="display:none;background:#f8f9fa;border-radius:10px;padding:20px;margin-bottom:20px;border:1px solid #dee2e6;">
                    <h6 class="mb-3">Add New Volume</h6>
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Volume No.</label>
                            <input type="number" id="volNumber" class="form-control" value="1" min="1" max="99">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Volume Name</label>
                            <input type="text" id="volName" class="form-control" placeholder="e.g. Vol-1, Summer 2025">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Circle Image</label>
                            <input type="file" id="volImage" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Image Crop/Fit</label>
                            <select id="volPosition" class="form-control form-select">
                                <option value="center">Center (default)</option>
                                <option value="top">Top — show from top</option>
                                <option value="bottom">Bottom — show from bottom</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end gap-2">
                            <button class="btn btn-primary w-100" id="saveVolBtn">Save</button>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-secondary w-100" id="cancelVolBtn">Cancel</button>
                        </div>
                    </div>
                    <div id="volImgPreview" style="margin-top:10px;display:none;">
                        <p class="small text-muted mb-1">Preview (shows how it will look in the circle):</p>
                        <img id="volPreviewImg" src="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;object-position:center;border:3px solid #0d6efd;">
                    </div>
                </div>

                <!-- Volumes List -->
                <div id="volumesList">
                    <div class="text-center text-muted py-3" id="volLoadingMsg">Loading volumes…</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Preview brand image
    const imgInp = document.getElementById('imgInp');
    const blah = document.getElementById('blah');

    imgInp.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            blah.src = URL.createObjectURL(file);
        }
    });

    // Live preview of object-position
    document.querySelector('select[name="image_position"]').addEventListener('change', function() {
        blah.style.objectPosition = this.value + ' center';
    });

    // Preview banner image
    const bannerInp = document.getElementById('bannerInp');
    const bannerPreview = document.getElementById('currentBanner');

    bannerInp.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            // Create a new image element for preview if it doesn't exist
            if (!document.getElementById('newBannerPreview')) {
                const newImg = document.createElement('img');
                newImg.id = 'newBannerPreview';
                newImg.style.width = '100px';
                newImg.style.height = 'auto';
                newImg.className = 'border rounded ms-2';
                bannerPreview.parentNode.appendChild(newImg);
            }
            
            // Set the preview image
            document.getElementById('newBannerPreview').src = URL.createObjectURL(file);
        }
    });

    function generateSlug(value) {
        // Replace spaces with hyphens and convert to lowercase
        return value.trim().toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
    }

    document.getElementById('name').addEventListener('input', function () {
        const slugField = document.getElementById('slug');
        slugField.value = generateSlug(this.value);
    });

    // ── Volume Manager ───────────────────────────────────────
    const BRAND_ID = <?= (int)$brandId ?>;
    const VOL_AJAX = 'include/brand_volume_ajax.php';

    function loadVolumes() {
        fetch(`${VOL_AJAX}?action=list&brand_id=${BRAND_ID}`)
            .then(r => r.json()).then(data => {
                const el = document.getElementById('volumesList');
                if (!data.volumes || data.volumes.length === 0) {
                    el.innerHTML = '<p class="text-muted text-center py-3">No volumes yet. Click "Add Volume" to create the first one.</p>';
                    return;
                }
                let html = '<div class="row g-3">';
                data.volumes.forEach(v => {
                    const imgSrc = v.volume_image
                        ? `images/brand_volumes/${v.volume_image}`
                        : '';
                    const pos = v.image_position || 'center';
                    const posLabel = pos === 'top' ? 'Top' : pos === 'bottom' ? 'Bottom' : 'Center';
                    html += `
                    <div class="col-6 col-md-3 col-lg-2" id="volCard${v.id}">
                        <div style="background:#fff;border:2px solid #dee2e6;border-radius:12px;padding:14px;text-align:center;position:relative;">
                            <button onclick="deleteVolume(${v.id})" style="position:absolute;top:-8px;right:-8px;width:22px;height:22px;border-radius:50%;background:#dc3545;border:none;color:#fff;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                            ${imgSrc
                                ? `<img src="${imgSrc}" style="width:80px;height:80px;border-radius:50%;object-fit:cover;object-position:${pos} center;border:3px solid #0d6efd;margin-bottom:8px;" onerror="this.style.display='none'">`
                                : `<div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.1rem;margin:0 auto 8px;">V${v.volume_number}</div>`
                            }
                            <div style="font-weight:700;color:#333;font-size:13px;">Vol ${v.volume_number}</div>
                            <div style="font-size:11px;color:#666;margin-top:2px;">${v.volume_name || ''}</div>
                            ${imgSrc ? `
                            <div style="margin-top:8px;">
                                <label style="font-size:10px;color:#888;display:block;margin-bottom:3px;">Image Crop</label>
                                <select onchange="updatePosition(${v.id}, this.value, this)" style="font-size:11px;padding:2px 4px;border-radius:6px;border:1px solid #ccc;width:100%;">
                                    <option value="center" ${pos==='center'?'selected':''}>Center</option>
                                    <option value="top" ${pos==='top'?'selected':''}>Top</option>
                                    <option value="bottom" ${pos==='bottom'?'selected':''}>Bottom</option>
                                </select>
                            </div>` : ''}
                        </div>
                    </div>`;
                });
                html += '</div>';
                el.innerHTML = html;
            });
    }

    function updatePosition(volId, pos, selectEl) {
        selectEl.disabled = true;
        // Also update the preview image inside the same card
        const card = document.getElementById('volCard' + volId);
        if (card) {
            const img = card.querySelector('img');
            if (img) img.style.objectPosition = pos + ' center';
        }
        const fd = new FormData();
        fd.append('action',         'update_position');
        fd.append('brand_id',       BRAND_ID);
        fd.append('vol_id',         volId);
        fd.append('image_position', pos);
        fetch(VOL_AJAX, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(() => { selectEl.disabled = false; })
            .catch(() => { selectEl.disabled = false; });
    }

    function deleteVolume(volId) {
        if (!confirm('Delete this volume? Products assigned to it will be unlinked (not deleted).')) return;
        const fd = new FormData();
        fd.append('action',   'delete');
        fd.append('brand_id', BRAND_ID);
        fd.append('vol_id',   volId);
        fetch(VOL_AJAX, { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d.success) {
                    document.getElementById('volCard' + volId)?.remove();
                    if (!document.querySelector('#volumesList [id^="volCard"]')) {
                        document.getElementById('volumesList').innerHTML = '<p class="text-muted text-center py-3">No volumes yet. Click "Add Volume" to create the first one.</p>';
                    }
                }
            });
    }

    // Show/hide add form
    document.getElementById('showAddVolForm').addEventListener('click', function() {
        document.getElementById('addVolForm').style.display = 'block';
        document.getElementById('volNumber').focus();
    });
    document.getElementById('cancelVolBtn').addEventListener('click', function() {
        document.getElementById('addVolForm').style.display = 'none';
    });

    // Image preview — update live with object-position when position changes
    document.getElementById('volImage').addEventListener('change', function() {
        const f = this.files[0];
        if (f) {
            const pos = document.getElementById('volPosition').value;
            const img = document.getElementById('volPreviewImg');
            img.src = URL.createObjectURL(f);
            img.style.objectPosition = pos + ' center';
            document.getElementById('volImgPreview').style.display = 'block';
        }
    });
    document.getElementById('volPosition').addEventListener('change', function() {
        const img = document.getElementById('volPreviewImg');
        img.style.objectPosition = this.value + ' center';
    });

    // Save volume
    document.getElementById('saveVolBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true; btn.textContent = 'Saving…';

        const fd = new FormData();
        fd.append('action',        'add');
        fd.append('brand_id',      BRAND_ID);
        fd.append('volume_number', document.getElementById('volNumber').value);
        fd.append('volume_name',   document.getElementById('volName').value);
        fd.append('sort_order',    document.getElementById('volNumber').value);
        fd.append('image_position', document.getElementById('volPosition').value);
        const imgFile = document.getElementById('volImage').files[0];
        if (imgFile) fd.append('volume_image', imgFile);

        fetch(VOL_AJAX, { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d.success) {
                    // Reset form
                    document.getElementById('volNumber').value = parseInt(document.getElementById('volNumber').value) + 1;
                    document.getElementById('volName').value   = '';
                    document.getElementById('volImage').value  = '';
                    document.getElementById('volImgPreview').style.display = 'none';
                    document.getElementById('addVolForm').style.display = 'none';
                    loadVolumes();
                }
                btn.disabled = false; btn.textContent = 'Save Volume';
            }).catch(() => { btn.disabled = false; btn.textContent = 'Save Volume'; });
    });

    // Load on page ready
    loadVolumes();
</script>
<?php
  include 'include/footer.php';
?>