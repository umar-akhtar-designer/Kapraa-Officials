<!-- ══ LOYALTY FLOATING STAR ══════════════════════════════════════════ -->
<style>
#loyStarBtn {
    position: fixed; bottom: 90px; right: 22px; z-index: 9990;
    width: 58px; height: 58px; border-radius: 50%; border: none; cursor: pointer;
    background: linear-gradient(135deg, #f9ca24, #f0932b);
    box-shadow: 0 4px 20px rgba(249,202,36,.6);
    display: flex; align-items: center; justify-content: center;
    animation: starFloat 3s ease-in-out infinite, starGlow 2s ease-in-out infinite alternate;
    transition: transform .2s;
}
#loyStarBtn:hover { transform: scale(1.15); }
#loyStarBtn i { font-size: 1.5rem; color: #fff; }
@keyframes starFloat {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-8px); }
}
@keyframes starGlow {
    0%   { box-shadow: 0 4px 20px rgba(249,202,36,.5); }
    100% { box-shadow: 0 4px 40px rgba(249,202,36,.95), 0 0 0 8px rgba(249,202,36,.15); }
}
/* Star orbit dots */
#loyStarBtn::before {
    content: '✦'; position: absolute; font-size: .6rem; color: rgba(255,255,255,.8);
    top: 2px; right: 4px; animation: orbit 3s linear infinite;
}
@keyframes orbit {
    0%  { transform: rotate(0deg)   translateX(26px) rotate(0deg); }
    100%{ transform: rotate(360deg) translateX(26px) rotate(-360deg); }
}

/* Popup */
#loyPopup {
    position: fixed; bottom: 160px; right: 22px; z-index: 9991;
    width: 300px; background: #fff; border-radius: 16px;
    box-shadow: 0 12px 48px rgba(0,0,0,.18); overflow: hidden;
    display: none; animation: popIn .25s ease;
}
@keyframes popIn { from{opacity:0;transform:scale(.85) translateY(20px)} to{opacity:1;transform:scale(1) translateY(0)} }
.loy-pop-head {
    background: linear-gradient(135deg, #1a1a2e, #0f3460);
    padding: 16px 18px; color: #fff;
}
.loy-pop-head h6 { margin: 0; font-size: .95rem; font-weight: 800; }
.loy-pop-head p  { margin: 2px 0 0; font-size: .75rem; color: rgba(255,255,255,.6); }
.loy-pop-body { padding: 14px 16px; }
.loy-pop-leader { margin-bottom: 12px; }
.loy-pop-row {
    display: flex; align-items: center; gap: 10px;
    padding: 7px 0; border-bottom: 1px solid #f5f5f5;
    font-size: .82rem;
}
.loy-pop-row:last-child { border-bottom: none; }
.loy-pop-av {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
.loy-pop-name { flex: 1; font-weight: 600; color: #222; }
.loy-pop-pts  { font-weight: 800; color: #f0932b; font-size: .85rem; }
.loy-pop-footer { padding: 0 16px 16px; display: flex; flex-direction: column; gap: 8px; }
.loy-pop-btn {
    display: block; text-align: center; padding: 9px 14px; border-radius: 10px;
    font-size: .82rem; font-weight: 700; text-decoration: none; transition: opacity .15s;
}
.loy-pop-btn:hover { opacity: .85; }
.loy-pop-btn.primary { background: #f9ca24; color: #1a1a1a; }
.loy-pop-btn.dark    { background: #1a1a2e; color: #fff; }
</style>

<button id="loyStarBtn" onclick="toggleLoyPop()" title="Loyalty Score">
    <i class="fa fa-star"></i>
</button>

<div id="loyPopup">
    <div class="loy-pop-head">
        <h6><i class="fa fa-trophy me-1" style="color:#f9ca24;"></i>Score Leaderboard</h6>
        <p>Buy more, earn points, win a FREE dress!</p>
    </div>
    <div class="loy-pop-body">
        <div class="loy-pop-leader" id="loyPopLeader">
            <div style="text-align:center;padding:10px;color:#bbb;font-size:.8rem;">Loading...</div>
        </div>
    </div>
    <div class="loy-pop-footer">
        <?php if (isset($_SESSION['WEBSITE-LOGINSS'])): ?>
        <a href="<?= $url_domain ?>/loyalty" class="loy-pop-btn primary"><i class="fa fa-user me-1"></i>My Score</a>
        <?php else: ?>
        <a href="<?= $url_domain ?>/login?redirect=loyalty" class="loy-pop-btn primary"><i class="fa fa-sign-in me-1"></i>Login to See Your Score</a>
        <?php endif; ?>
        <a href="<?= $url_domain ?>/leaderboard" class="loy-pop-btn dark"><i class="fa fa-list-ol me-1"></i>Full Leaderboard</a>
    </div>
</div>

<script>
(function(){
    var popOpen = false;
    window.toggleLoyPop = function() {
        popOpen = !popOpen;
        var pop = document.getElementById('loyPopup');
        pop.style.display = popOpen ? 'block' : 'none';
        if (popOpen && !pop.dataset.loaded) {
            pop.dataset.loaded = '1';
            fetch('<?= $url_domain ?>/loyalty_mini.php')
                .then(r=>r.json())
                .then(data=>{
                    var html = '';
                    var icons = ['<i class="fa fa-diamond" style="color:#0dcaf0;"></i>','<i class="fa fa-star" style="color:#ffd700;"></i>','<i class="fa fa-star" style="color:#c0c0c0;"></i>','<i class="fa fa-star" style="color:#cd7f32;"></i>','<i class="fa fa-star" style="color:#888;"></i>'];
                    var colors = ['#e74c3c','#3498db','#9b59b6','#e67e22','#1abc9c'];
                    (data.players||[]).forEach(function(p,i){
                        var av = (p.name||'?').charAt(0).toUpperCase();
                        html += '<div class="loy-pop-row">'
                            + '<span class="loy-pop-av" style="background:'+colors[i%colors.length]+'">'+av+'</span>'
                            + '<span class="loy-pop-name">'+p.name+'</span>'
                            + '<span style="font-size:.8rem;margin-right:6px;">'+icons[i]+'</span>'
                            + '<span class="loy-pop-pts">'+p.score+' pts</span>'
                            + '</div>';
                    });
                    if (!html) html = '<div style="text-align:center;padding:10px;color:#bbb;font-size:.8rem;">No players yet — be the first!</div>';
                    document.getElementById('loyPopLeader').innerHTML = html;
                })
                .catch(function(){ document.getElementById('loyPopLeader').innerHTML = '<div style="text-align:center;padding:10px;color:#bbb;font-size:.8rem;">Could not load data</div>'; });
        }
        // Close on outside click
        if (popOpen) {
            setTimeout(function(){
                document.addEventListener('click', function closeLoy(e){
                    if (!document.getElementById('loyPopup').contains(e.target) && e.target.id !== 'loyStarBtn') {
                        popOpen = false;
                        document.getElementById('loyPopup').style.display = 'none';
                        document.removeEventListener('click', closeLoy);
                    }
                });
            }, 50);
        }
    };
})();
</script>
<!-- ══ END LOYALTY FLOATING STAR ════════════════════════════════════ -->

<!-- Footer -->
<footer id="footer">
    <div class="site-footer">
        <div class="footer-top">
            <div class="container">
                <!-- Footer Links -->
                <div class="row">
                    <div class="col-12 col-sm-12 col-md-3 col-lg-3 footer-links">
                        <a href="<?php echo $url_domain;?>/index" class="d-flex align-items-center text-decoration-none">
                            <img src="<?php echo $url_domain;?>/assets/images/Kapraa-logo.png" alt="" class="mt-3" height="60px">
                            <h2 class="m-3 mb-0">KAPRAA</h2>
                        </a>
                        <p class="mt-3">
                            Kapraa offers a premium collection of branded suits made with high-quality fabrics and modern designs.
                        </p>
                    </div>
                    <div class="col-12 col-sm-12 col-md-3 col-lg-3 footer-links">
                        <h4 class="h4">Help</h4>
                        <ul>
                            <li><a href="<?php echo $url_domain;?>/about">About Us</a></li>
                            <li><a href="<?php echo $url_domain;?>/blog">Our Blogs</a></li>
                            <li><a href="<?php echo $url_domain;?>/term-condition">Terms and Conditions</a></li>
                            <li><a href="<?php echo $url_domain;?>/privacy-policy">Privacy Policy</a></li>
                            <li><a href="<?php echo $url_domain;?>/faqs">Faq's</a></li>
                            <li><a href="<?php echo $url_domain;?>/contact">Contact Us</a></li>
                            <li><a href="<?php echo $url_domain;?>/returns-exchange">Exchange Policy</a></li>
                            <li><a href="<?php echo $url_domain;?>/wholesale">Wholesale Orders</a></li>
                        </ul>
                    </div>
                    <div class="col-12 col-sm-12 col-md-3 col-lg-3 contact-box">
                        <h4 class="h4">Contact Us</h4>
                        <ul class="addressFooter">
                            <li>
                                <i class="icon an an-map-marker"></i> 
                                <p>Faisalabad, Pakistan</p>
                            </li>
                            <li class="phone">
                                <i class="icon an an-phone-volume"></i>
                                <p><a href="tel:+923209815213">+92 320 9815213</a></p>
                            </li>
                            <li class="email">
                                <i class="icon an an-envelope"></i>
                                <p><a href="mailto:kapraaofficial@gmail.com">kapraaofficial@gmail.com</a></p>
                            </li>
                        </ul>
                    </div>
                    <div class="col-12 col-sm-12 col-md-3 col-lg-3 newsletter">
                        <div class="display-table">
                            <div class="display-table-cell footer-newsletter">
                                <form action="#" method="post">
                                    <label class="h4">Newsletter</label>
                                    <p>sign up for newsletter to know our latest news and offers.</p>
                                    <div class="input-group">
                                        <input type="email" class="input-group__field newsletter__input"
                                            name="EMAIL" value="" placeholder="Email address" required />
                                        <span class="input-group__btn">
                                            <button type="submit" class="btn newsletter__submit" name="commit"
                                                id="Subscribe"><span class="newsletter__submit-text--large">Sign
                                                    Up</span></button>
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Footer Links -->
        </div>

        <div class="footer-middle">
            <div class="container">
                <div class="row">
                    <div
                        class="col-12 col-sm-12 col-md-6 col-lg-6 d-flex justify-content-Start align-items-center">
                        <div class="footer-social w-100 text-start d-flex align-items-center">
                            <h3 class="h4">Stay Connected</h3>
                            <ul class="list--inline site-footer__social-icons social-icons">
                                <li>
                                    <a class="social-icons__link d-inline-block" href="https://www.facebook.com/kapraaofficial" target="_blank"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Facebook"><i
                                            class="icon an an-facebook"></i>
                                    </a>
                                </li>
                                <li>
                                    <a class="social-icons__link d-inline-block" href="https://www.instagram.com/kapraaofficial" target="_blank"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Instagram"><i
                                            class="icon an an-instagram"></i> <span
                                            class="icon__fallback-text">Instagram</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="social-icons__link d-inline-block" href="https://www.tiktok.com/@kapraaofficial" target="_blank"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Tik Tok">
                                        <svg class="mt-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a class="social-icons__link d-inline-block" href="https://wa.me/923209815213?text=Hello%2C%20I%20visited%20Kapraa%20and%20I%27m%20interested%20in%20buying.%20Please%20share%20more%20details." target="_blank"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="WhatsApp">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                        </svg>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!--Footer Payment Icon-->
                    <div class="col-12 col-sm-12 col-md-6 col-lg-6 d-flex justify-content-end align-items-center">
                        <ul class="payment-icons list--inline w-100 justify-content-end text-end">
                            <li><i class="icon an an-cc-visa" aria-hidden="true"></i></li>
                            <li><i class="icon an an-cc-mastercard" aria-hidden="true"></i></li>
                        </ul>
                    </div>
                    <!-- End Footer Payment Icon-->
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                <div class="row">
                    <!-- Footer Copyright -->
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 copyright text-center">
                        <span>Copyright 2026 © Kapraa | Developed by <a href='https://www.weblypro.site' target='_blank' rel='noopener noreferrer' style='color:inherit;text-decoration:underline;'>Weblypro</a></span></div>
                    <!-- End Footer Copyright -->
                </div>
            </div>
        </div>
    </div>

</footer>

<!-- WHATSAPP FLOATING BUTTON — outside footer so position:fixed is relative to viewport -->
<style>
.whatsapp-float {
    position: fixed;
    width: 56px;
    height: 56px;
    bottom: 115px;
    left: 22px;
    background-color: #25d366;
    color: #fff;
    border-radius: 50%;
    box-shadow: 2px 2px 10px rgba(0,0,0,.35);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color .25s, transform .25s;
    text-decoration: none;
}
.whatsapp-float:hover { background-color: #128C7E; transform: scale(1.1); }
.whatsapp-float svg   { width: 32px; height: 32px; fill: #fff; }
</style>
<a href="https://wa.me/923209815213?text=Hello%2C%20I%20visited%20Kapraa%20and%20I%27m%20interested%20in%20buying.%20Please%20share%20more%20details." class="whatsapp-float" target="_blank" rel="noopener noreferrer" title="Chat on WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
    </svg>
</a>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Video close functionality
        const videoContainer = document.getElementById('productVideoContainer');
        const closeVideoBtn = document.getElementById('closeVideoBtn');
        const productID = <?php echo isset($productID) ? intval($productID) : 0; ?>;
        
        function setCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }
        
        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }
        
        if (closeVideoBtn) {
            closeVideoBtn.addEventListener('click', function() {
                if (videoContainer) {
                    // Add fade out effect
                    videoContainer.style.opacity = '0';
                    videoContainer.style.transform = 'translateX(100px)';
                    
                    // Remove after animation
                    setTimeout(function() {
                        if (videoContainer.parentNode) {
                            videoContainer.remove();
                        }
                    }, 300);
                    
                    // Set cookie to remember user closed the video (30 days expiry)
                    setCookie('video_closed_' + productID, 'true', 30);
                    
                    // Optional: Store in localStorage too for redundancy
                    localStorage.setItem('video_closed_' + productID, 'true');
                }
            });
        }
        
        // Set Suits button functionality
        const setSuitsBtn = document.getElementById('set-suits-btn');
        const priceContainer = document.getElementById('price-container');
        const priceTypeInput = document.getElementById('price-type');
        const sizesSection = document.getElementById('sizes-section');
        const colorsSection = document.getElementById('colors-section');
        const stitchingSection = document.getElementById('stitching-section');
        const setSuiteMessage = document.getElementById('set-suite-message');
        
        if (setSuitsBtn) {
            // Store original price values
            const originalPrice = parseFloat(setSuitsBtn.dataset.regularPrice);
            const setPrice = parseFloat(setSuitsBtn.dataset.setPrice);
            const originalSetPrice = parseFloat(setSuitsBtn.dataset.originalSetPrice);
            const setDiscount = parseFloat(setSuitsBtn.dataset.setDiscount);
            const setQuantity = setSuitsBtn.dataset.quantity;
            const regularPrice = <?php echo isset($price) ? floatval($price) : 0; ?>;
            let isSetPrice = false;
            
            // Store original HTML content
            const originalPriceHTML = priceContainer.innerHTML;
            
            setSuitsBtn.addEventListener('click', function() {
                isSetPrice = !isSetPrice;
                
                if (isSetPrice) {
                    // Update to set price
                    setSuitsBtn.classList.add('active');
                    setSuitsBtn.textContent = 'Regular Price';
                    
                    // Update price display - check if set discount exists
                    if (setDiscount > 0) {
                        // Show original price with line-through, discount amount, and final price
                        priceContainer.innerHTML = `
                            <span class="visually-hidden">Regular price</span>
                            <s id="ComparePrice-product-template"><span class="money">RS. ${originalSetPrice.toFixed(2)}</span></s>
                            <span class="product-price__price product-price__price-product-template product-price__sale product-price__sale--single">
                                <span id="ProductPrice-product-template" data-base-price="${setPrice}" class="price-transition"><span class="money">RS. ${setPrice.toFixed(2)}</span></span>
                            </span>
                            <span class="discount-badge">
                                <span class="devider">|</span>&nbsp;
                                <span>You Save</span>
                                <span id="SaveAmount-product-template" class="product-single__save-amount">
                                    <span class="money">RS. ${setDiscount.toFixed(2)}</span></span>
                                <span class="off">(<span><?php echo isset($set_suite_discount_percent) ? $set_suite_discount_percent : 0; ?></span>%)</span>
                            </span>
                        `;
                    } else {
                        // Show only set price without comparison or discount
                        priceContainer.innerHTML = `
                            <span class="product-price__price product-price__price-product-template">
                                <span id="ProductPrice-product-template" data-base-price="${setPrice}" class="price-transition"><span class="money">RS. ${setPrice.toFixed(2)}</span></span>
                            </span>
                        `;
                    }
                    
                    // Show set suite message
                    setSuiteMessage.classList.add('show');
                    
                    // Hide only stitching section, keep sizes and colors section visible
                    stitchingSection.classList.add('hidden');
                    
                    // Update hidden input
                    priceTypeInput.value = 'set';
                } else {
                    // Revert to original price
                    setSuitsBtn.classList.remove('active');
                    setSuitsBtn.textContent = 'Set Suits';
                    
                    // Restore original price display
                    priceContainer.innerHTML = originalPriceHTML;
                    
                    // Hide set suite message
                    setSuiteMessage.classList.remove('show');
                    
                    // Show stitching section again, keep sizes and colors section visible
                    stitchingSection.classList.remove('hidden');
                    
                    // Update hidden input
                    priceTypeInput.value = 'regular';
                }
                
                // Update stitching total if checkbox is checked
                updateStitchingTotal();
            });
        }
        
        // Function to update stitching total price
        function updateStitchingTotal() {
            const stitchingCheckbox = document.getElementById('stitching-checkbox');
            const stitchingTotalPrice = document.getElementById('stitching-total-price');
            
            if (stitchingCheckbox && stitchingCheckbox.checked) {
                const productPriceElement = document.getElementById('ProductPrice-product-template');
                const productPrice = parseFloat(productPriceElement.dataset.basePrice);
                const stitchingPrice = parseFloat(stitchingCheckbox.value);
                const totalPrice = productPrice + stitchingPrice;
                
                stitchingTotalPrice.textContent = `Total Price: RS. ${totalPrice.toFixed(2)}`;
                stitchingTotalPrice.style.display = 'block';
            } else if (stitchingTotalPrice) {
                stitchingTotalPrice.style.display = 'none';
            }
        }
        
        // Stitching checkbox event listener
        const stitchingCheckbox = document.getElementById('stitching-checkbox');
        if (stitchingCheckbox) {
            stitchingCheckbox.addEventListener('change', updateStitchingTotal);
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Define URL domain
        const url_domain = '<?php echo $url_domain; ?>';
        
        // Notification function
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            // Add icon based on type
            let icon = '';
            if (type === 'success') {
                icon = '<i class="icon an an-check-circle"></i>';
            } else if (type === 'error') {
                icon = '<i class="icon an an-times-circle"></i>';
            }
            
            notification.innerHTML = `${icon} ${message}`;
            
            const container = document.getElementById('notification-container');
            container.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
        
        // Format money function with NaN check
        function formatMoney(amount) {
            // Check if amount is a valid number
            if (isNaN(amount) || amount === '' || amount === null || amount === undefined) {
                amount = 0;
            }
            return 'RS. ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        // Image gallery functionality
        const productTabLinks = document.querySelectorAll('.product-tab-left a');
        productTabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const mainImage = this.getAttribute('data-image');
                const zoomImage = this.getAttribute('data-zoom-image');
                
                // Update main image
                document.querySelector('.zoompro').setAttribute('src', mainImage);
                document.querySelector('.zoompro').setAttribute('data-zoom-image', zoomImage);
                
                // Update lightbox
                document.querySelector('.prlightbox').setAttribute('href', mainImage);
                
                // Update active class
                productTabLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Size selection mode functionality
        const sizeSelectionModeRadios = document.querySelectorAll('input[name="size-selection-mode"]');
        const sizeTagsContainer = document.querySelector('.size-tags');
        
        sizeSelectionModeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Update the data attribute on the container
                sizeTagsContainer.setAttribute('data-selection-mode', this.value);
                
                // If switching to single mode, deselect all but the first selected size
                if (this.value === 'single') {
                    const selectedTags = document.querySelectorAll('.size-tag.selected');
                    if (selectedTags.length > 1) {
                        // Keep only the first selected size
                        selectedTags.forEach((tag, index) => {
                            if (index > 0) {
                                tag.classList.remove('selected');
                            }
                        });
                        updateSelectedSizes();
                    }
                }
            });
        });
        
        // Size selection functionality
        const sizeTags = document.querySelectorAll('.size-tag');
        
        sizeTags.forEach(tag => {
            tag.addEventListener('click', function() {
                const selectionMode = sizeTagsContainer.getAttribute('data-selection-mode');
                
                if (selectionMode === 'single') {
                    // Single selection mode: remove selected from all tags, then add to clicked one
                    sizeTags.forEach(t => t.classList.remove('selected'));
                    this.classList.add('selected');
                } else {
                    // Multiple selection mode: toggle selected class on clicked tag
                    this.classList.toggle('selected');
                }
                
                // Update the hidden input with selected sizes
                updateSelectedSizes();
            });
        });
        
        // Color selection functionality - Always allow multiple selection
        const colorTags = document.querySelectorAll('.color-tag');
        
        colorTags.forEach(tag => {
            tag.addEventListener('click', function() {
                // Toggle selected class on clicked tag
                this.classList.toggle('selected');
                
                // Update the hidden input with selected colors
                updateSelectedColors();
            });
        });
        
        // Function to update the hidden input with selected sizes
        function updateSelectedSizes() {
            const selectedTags = document.querySelectorAll('.size-tag.selected');
            const selectedSizes = Array.from(selectedTags).map(tag => tag.getAttribute('data-size'));
            document.getElementById('selected-sizes').value = selectedSizes.join(',');
        }
        
        // Function to update the hidden input with selected colors
        function updateSelectedColors() {
            const selectedTags = document.querySelectorAll('.color-tag.selected');
            const selectedColors = Array.from(selectedTags).map(tag => tag.getAttribute('data-color'));
            document.getElementById('selected-colors').value = selectedColors.join(',');
        }
        
        // Stitching option functionality - only if the checkbox exists
        const stitchingCheckbox = document.getElementById('stitching-checkbox');
        if (stitchingCheckbox) {
            const productPriceElement = document.getElementById('ProductPrice-product-template');
            const moneySpan = productPriceElement.querySelector('.money');
            const basePrice = parseFloat(productPriceElement.getAttribute('data-base-price')) || 0;
            const stitchingPrice = parseFloat(stitchingCheckbox.value) || 0;
            const stitchingOptionInput = document.getElementById('stitching-option');
            const stitchingTotalPriceElement = document.getElementById('stitching-total-price');
            
            // Update price based on stitching option
            function updatePrice() {
                let newPrice;
                if (stitchingCheckbox.checked) {
                    newPrice = basePrice + stitchingPrice;
                    stitchingOptionInput.value = "1";
                    // Show and update stitching total price
                    stitchingTotalPriceElement.textContent = 'Total with stitching: ' + formatMoney(newPrice);
                    stitchingTotalPriceElement.style.display = 'block';
                } else {
                    newPrice = basePrice;
                    stitchingOptionInput.value = "0";
                    // Hide stitching total price
                    stitchingTotalPriceElement.style.display = 'none';
                }
                moneySpan.textContent = formatMoney(newPrice);
            }
            
            // Add event listener to stitching checkbox
            stitchingCheckbox.addEventListener('change', updatePrice);
        }
        
        // Add to cart functionality
        const addToCartBtn = document.querySelector('.add-to-cart-btn');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                let quantity = document.getElementById('Quantity').value;
                
                // Validate maximum quantity (5)
                if (parseInt(quantity) > 5) {
                    showNotification('Maximum quantity allowed is 5', 'error');
                    return;
                }
                
                const selectedSizes = document.getElementById('selected-sizes').value;
                const selectedColors = document.getElementById('selected-colors').value;
                const stitchingOption = document.getElementById('stitching-option') ? document.getElementById('stitching-option').value : "0";
                const priceType = document.getElementById('price-type').value;
                
                // Check if at least one size is selected
                if (sizeTags.length > 0 && !selectedSizes) {
                    showNotification('Please select at least one size before adding to cart', 'error');
                    return;
                }
                
                // Check if at least one color is selected
                if (colorTags.length > 0 && !selectedColors) {
                    showNotification('Please select at least one color before adding to cart', 'error');
                    return;
                }
                
                // Show loading state
                this.innerHTML = '<i class="icon an an-spinner fa-spin me-2"></i><span>Adding...</span>';
                this.disabled = true;
                
                // Send AJAX request to add to cart
                fetch(url_domain + '/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        product_id: productId,
                        quantity: quantity,
                        sizes: selectedSizes,
                        colors: selectedColors,
                        stitching: stitchingOption,
                        price_type: priceType
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button state
                    this.innerHTML = '<i class="icon an an-shopping-cart me-2"></i><span>Add to cart</span>';
                    this.disabled = false;
                    
                    if (data.success) {
                        // Update cart count in the navbar
                        document.getElementById('CartCount').textContent = data.cart_count;
                        
                        // Update mini cart
                        updateMiniCart();
                        
                        // Show success message with price type info
                        // const priceTypeText = priceType === 'set' ? 'Set Suit' : 'Single Suit';
                        // showNotification(`${priceTypeText} product added to cart successfully!`);
                        
                        // Open the minicart drawer
                        const minicartDrawer = new bootstrap.Modal(document.getElementById('minicart-drawer'));
                        minicartDrawer.show();
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while adding the product to the cart.', 'error');
                    
                    // Reset button state
                    this.innerHTML = '<i class="icon an an-shopping-cart me-2"></i><span>Add to cart</span>';
                    this.disabled = false;
                });
            });
        }
        
        // ── Buy Now functionality ─────────────────────────────
        const buyNowBtn = document.querySelector('.buy-now-btn');
        if (buyNowBtn) {
            buyNowBtn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                if (!productId) return;

                let quantity = document.getElementById('Quantity').value;

                // Validate max quantity
                if (parseInt(quantity) > 5) {
                    showNotification('Maximum quantity allowed is 5', 'error');
                    return;
                }

                const selectedSizes   = document.getElementById('selected-sizes').value;
                const selectedColors  = document.getElementById('selected-colors').value;
                const stitchingOption = document.getElementById('stitching-option') ? document.getElementById('stitching-option').value : '0';
                const priceType       = document.getElementById('price-type').value;

                // Validate size selection
                if (sizeTags.length > 0 && !selectedSizes) {
                    showNotification('Please select at least one size before proceeding', 'error');
                    return;
                }

                // Validate color selection
                if (colorTags.length > 0 && !selectedColors) {
                    showNotification('Please select at least one color before proceeding', 'error');
                    return;
                }

                // Show loading state
                this.innerHTML = '<i class="icon an an-spinner fa-spin me-2"></i><span>Please wait...</span>';
                this.disabled = true;

                // Add product to cart then redirect to checkout
                fetch(url_domain + '/add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        product_id: productId,
                        quantity:   quantity,
                        sizes:      selectedSizes,
                        colors:     selectedColors,
                        stitching:  stitchingOption,
                        price_type: priceType
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Product added — go straight to checkout
                        window.location.href = url_domain + '/checkout';
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                        this.innerHTML = '<i class="icon an an-flash me-2"></i><span>Buy Now</span>';
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Buy Now error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                    this.innerHTML = '<i class="icon an an-flash me-2"></i><span>Buy Now</span>';
                    this.disabled = false;
                });
            });
        }
        // ─────────────────────────────────────────────────────

        // Function to update mini cart
        function updateMiniCart() {
            fetch(url_domain + '/get_cart.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateMiniCartDisplay(data.cart_items, data.cart_count, data.cart_total, data.cart_subtotal, data.cart_shipping);
                    }
                })
                .catch(error => {
                    console.error('Error updating mini cart:', error);
                });
        }
        
        // Function to update mini cart display
        function updateMiniCartDisplay(cartItems, cartCount, cartTotal, cartSubtotal, cartShipping) {
            const miniCartBody = document.querySelector('.minicart-body');
            const miniCartFooter = document.querySelector('.minicart-footer');
            const cartItemCountElement = document.getElementById('cart-item-count');
            
            // Update cart item count in header
            if (cartItemCountElement) {
                cartItemCountElement.textContent = '(' + cartCount + ')';
            }
            
            if (cartItems.length === 0) {
                // Empty cart
                miniCartBody.innerHTML = '<div class="empty-cart"><p>You have no items in your shopping cart.</p></div>';
                miniCartFooter.style.display = 'none';
            } else {
                // Cart has items
                let itemsHtml = '<div id="drawer-minicart" class="block block-cart"><ul class="mini-products-list">';
                
                cartItems.forEach(item => {
                    const imageParts = item.image.split(',');
                    const firstImage = imageParts[0].trim();
                    
                    // Parse all values as numbers with fallbacks
                    const price = parseFloat(item.price) || 0;
                    const stitchingPrice = parseFloat(item.stitching_price) || 0;
                    const quantity = parseInt(item.quantity) || 1;
                    const itemTotal = (price + stitchingPrice) * quantity;
                    
                    itemsHtml += `
                        <li class="item" data-cart-key="${item.cart_key}">
                            <a class="product-image" href="${url_domain}/product-details.php?slug=${item.slug}">
                                <img src="${url_domain}/admin/images/product/${firstImage}" alt="${item.name}" title="">
                            </a>
                            <div class="product-details">
                                <a href="#" class="remove remove-from-cart" data-bs-toggle="tooltip" data-bs-placement="top" title="Remove" data-cart-key="${item.cart_key}">
                                    <i class="an an-times" aria-hidden="true"></i>
                                </a>
                                <a class="pName" href="${url_domain}/product-details.php?slug=${item.slug}">${item.name}</a>
                                ${item.sizes ? `<div class="variant-cart">Size: ${item.sizes}</div>` : ''}
                                ${item.colors ? `<div class="variant-cart">Color: ${item.colors}</div>` : ''}
                                ${item.stitching == 1 ? '<div class="variant-cart">With Stitching</div>' : ''}
                                ${item.price_type === 'set' ? `<div class="variant-cart badge bg-info">Set Suit (${item.setsuite_quantity} pieces)</div>` : ''}
                                <div class="wrapQtyBtn clearfix">
                                    <span class="label">Qty :</span>
                                    <div class="qtyField clearfix">
                                        <a class="qtyBtn minus update-quantity" href="javascript:void(0);" data-action="minus" data-cart-key="${item.cart_key}">
                                            <i class="fa fa-minus" aria-hidden="true"></i>
                                        </a>
                                        <input type="text" name="quantity" value="${item.quantity}" class="product-form__input qty" readonly>
                                        <a class="qtyBtn plus update-quantity" href="javascript:void(0);" data-action="plus" data-cart-key="${item.cart_key}">
                                            <i class="fa fa-plus" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="priceRow clearfix">
                                    <div class="product-price">
                                        <span class="money">${formatMoney(itemTotal)}</span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    `;
                });
                
                itemsHtml += '</ul></div>';
                miniCartBody.innerHTML = itemsHtml;
                
                // Update footer with subtotal, shipping, and total
                const subtotal = parseFloat(cartSubtotal) || 0;
                const shipping = parseFloat(cartShipping) || 0;
                const total = parseFloat(cartTotal) || 0;
                
                miniCartFooter.innerHTML = `
                    <div class="total-in">
                        <p class="label"><b>Subtotal :</b><span class="item product-price"><span class="money">${formatMoney(subtotal)}</span></span></p>
                        <p class="label"><b>Shipping :</b><span class="item product-price"><span class="money">${formatMoney(shipping)}</span></span></p>
                        <p class="label"><b>Total :</b><span class="item product-price"><span class="totals">${formatMoney(total)}</span></span></p>
                    </div>
                    <div class="buttonSet">
                        <a href="${url_domain}/checkout" class="btn btn-secondary w-100 ">Proceed To Checkout</a>
                    </div>
                `;
                miniCartFooter.style.display = 'block';
                
                // Update cart count in navbar
                document.getElementById('CartCount').textContent = '(' + cartCount + ')';
                
                // Re-attach event listeners
                attachCartEventListeners();
            }
        }
        
        // Function to attach event listeners to cart elements
        function attachCartEventListeners() {
            // Remove from cart
            document.querySelectorAll('.remove-from-cart').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const cartKey = this.getAttribute('data-cart-key');
                    
                    fetch(url_domain + '/update_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'remove',
                            cart_key: cartKey
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateMiniCartDisplay(data.cart_items, data.cart_count, data.cart_total, data.cart_subtotal, data.cart_shipping);
                        } else {
                            showNotification('Error: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while removing the item from the cart.', 'error');
                    });
                });
            });
            
            // Update quantity
            document.querySelectorAll('.update-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const cartKey = this.getAttribute('data-cart-key');
                    const action = this.getAttribute('data-action');
                    const qtyInput = this.parentElement.querySelector('.qty');
                    let newQty = parseInt(qtyInput.value);
                    
                    if (action === 'plus') {
                        // Check if new quantity would exceed the maximum (5)
                        if (newQty >= 5) {
                            showNotification('Maximum quantity allowed is 5', 'error');
                            return;
                        }
                        newQty++;
                    } else if (action === 'minus' && newQty > 1) {
                        newQty--;
                    }
                    
                    fetch(url_domain + '/update_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'update_quantity',
                            cart_key: cartKey,
                            quantity: newQty
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateMiniCartDisplay(data.cart_items, data.cart_count, data.cart_total, data.cart_subtotal, data.cart_shipping);
                        } else {
                            showNotification('Error: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while updating the quantity.', 'error');
                    });
                });
            });
        }
        
        // Initialize mini cart on page load
        updateMiniCart();
        
        // Video modal functionality
        const videoModal = document.getElementById('videoModal');
        if (videoModal) {
            videoModal.addEventListener('shown.bs.modal', function () {
                // Auto-play video when modal opens
                const iframe = document.getElementById('productVideo');
                if (iframe) {
                    let src = iframe.getAttribute('src');
                    iframe.setAttribute('src', src + '&autoplay=1');
                }
            });
            
            videoModal.addEventListener('hidden.bs.modal', function () {
                // Stop video when modal closes
                const iframe = document.getElementById('productVideo');
                if (iframe) {
                    let src = iframe.getAttribute('src');
                    // Remove autoplay parameter
                    src = src.replace('&autoplay=1', '');
                    iframe.setAttribute('src', src);
                }
            });
        }
    });
</script>
<!-- End Footer -->

<!-- Scoll Top -->
<!-- <div id="site-scroll"><i class="icon an an-angle-up"></i></div> -->

<!-- End Scoll Top -->
<script src="<?php echo $url_domain;?>/assets/js/plugins.js"></script>

<!-- Main JS -->
<script src="<?php echo $url_domain;?>/assets/js/main.js"></script>
<!-- Animations -->
<script src="<?php echo $url_domain;?>/assets/js/th-animations.js"></script>
</div>
<!-- AI Widget -->
<script src="<?php echo $url_domain; ?>/th_widget.js?v=20250706"></script>

<!-- ══ PROMO POPUP (Admin-Controlled, 4 Layouts) ════════════════════ -->
<?php
$_popupCfg = null;
try {
    $_pr = @$connection_database->query("SELECT * FROM popup_settings WHERE id=1 AND enabled=1 LIMIT 1");
    if ($_pr && $_pr->num_rows > 0) {
        $_popupCfg = $_pr->fetch_assoc();
    }
} catch (Exception $_e) {}

if ($_popupCfg):
    $_layout     = $_popupCfg['layout'] ?? 'right';
    $_delayMs    = max(0, (int)$_popupCfg['delay_seconds']) * 1000;
    $_reshowType = $_popupCfg['reshow_type'] ?? 'session';
    $_reshowDays = max(1, (int)($_popupCfg['reshow_days'] ?? 1));
    // Bump popup storage key when settings are updated,
    // so users can see new popup campaigns even if they previously hid old ones.
    $_popupVersion = !empty($_popupCfg['updated_at']) ? strtotime($_popupCfg['updated_at']) : 0;
    $_storageKey = 'th_popup_v3_' . (string)$_popupVersion;
    $_imgUrl     = !empty($_popupCfg['image'])
                   ? $url_domain . '/admin/images/popup/' . $_popupCfg['image']
                   : '';
    $_imgPos     = $_popupCfg['image_position'] ?? 'center center';
    $_btnUrl     = $_popupCfg['button_url'] ?? '';
    if ($_btnUrl && !preg_match('/^https?:\/\//i', $_btnUrl) && ($_btnUrl[0] ?? '') !== '/') {
        $_btnUrl = $url_domain . '/' . ltrim($_btnUrl, '/');
    }
    $_btnColor = htmlspecialchars($_popupCfg['button_color'] ?? '#222222');
    // Is layout side-by-side or stacked?
    $_isSide   = in_array($_layout, ['right', 'left']);
?>
<style>
/* ── Promo Popup ─────────────────────────────── */
#promoBg {
  position:fixed; inset:0; z-index:100000;
  background:rgba(0,0,0,.58);
  display:flex; align-items:center; justify-content:center;
  opacity:0; pointer-events:none;
  transition:opacity .35s;
  padding:12px;
}
#promoBg.show { opacity:1; pointer-events:all; }

#promoBox {
  background:#fff; border-radius:16px; overflow:hidden;
  position:relative; width:100%;
  box-shadow:0 24px 60px rgba(0,0,0,.32);
  transform:translateY(28px) scale(.97);
  transition:transform .4s cubic-bezier(.22,.9,.36,1);
  /* Side layouts */
  <?php if ($_isSide): ?>
  display:flex; flex-direction:row;
  max-width:620px; height:420px; max-height:90vh;
  <?php else: ?>
  display:flex; flex-direction:column; max-width:480px; max-height:92vh;
  overflow-y:auto;
  <?php endif; ?>
}
#promoBg.show #promoBox { transform:translateY(0) scale(1); }

/* Close */
#promoClose {
  position:absolute; top:10px; right:12px; z-index:20;
  width:32px; height:32px; border-radius:50%;
  background:rgba(0,0,0,.22); border:none; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  color:#fff; transition:background .2s;
  box-shadow:0 1px 4px rgba(0,0,0,.15);
}
#promoClose:hover { background:rgba(0,0,0,.45); }
#promoClose svg { stroke:#fff; }

#promoDontShow {
  position:absolute; bottom:14px; right:14px; z-index:20;
  background:none; border:none; cursor:pointer;
  font-size:11px; color:#aaa; padding:4px 6px;
  text-decoration:underline; transition:color .2s;
}
#promoDontShow:hover { color:#555; }

/* Content block */
.promo-content {
  <?php if ($_isSide): ?>
  padding:36px 40px 36px 38px;
  flex:0 0 55%; width:55%; min-width:0; height:100%;
  <?php else: ?>
  padding:28px 32px;
  flex:none;
  <?php endif; ?>
  display:flex; flex-direction:column; justify-content:center; align-items:flex-start; gap:10px;
}
.promo-brand   { font-size:1.1rem; font-weight:900; color:#222; margin:0; line-height:1.2; }
.promo-title   { font-size:1.25rem; font-weight:800; color:#111; line-height:1.25; margin:0; }
.promo-desc    { font-size:13px; color:#555; line-height:1.65; margin:0; }
.promo-endnote { font-size:11.5px; color:#999; font-style:italic; margin:0; }
.promo-title:empty, .promo-desc:empty, .promo-endnote:empty { display:none; }
.promo-cta {
  display:inline-block; padding:12px 28px; border-radius:5px; margin-top:4px;
  color:#fff !important; font-weight:700; font-size:14px;
  text-decoration:none; align-self:flex-start;
  transition:opacity .2s, transform .15s;
}
.promo-cta:hover { opacity:.85; transform:translateY(-2px); }

/* Image block */
.promo-img {
  background:#f0f0f0; overflow:hidden; flex-shrink:0;
  <?php if ($_isSide): ?>
  flex:0 0 45%; width:45%; height:100%;
  <?php else: ?>
  width:100%; height:220px;
  <?php endif; ?>
}
.promo-img img { width:100%; height:100%; object-fit:cover; display:block; }
.promo-img-ph  { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:40px; opacity:.2; }

/* Mobile: always stack — compact card */
@media (max-width:580px) {
  #promoBg       { padding:16px 12px; align-items:center; }
  #promoBox      {
    flex-direction:column !important; height:auto !important;
    max-height:82vh; max-width:calc(100vw - 24px);
    overflow-y:auto; border-radius:18px;
  }
  .promo-img     { width:100% !important; flex:none !important; height:150px; }
  .promo-img img { object-position:top center; }
  .promo-content {
    padding:14px 16px 50px !important;
    height:auto !important; gap:7px !important;
  }
  .promo-brand   { font-size:.95rem; }
  .promo-title   { font-size:1rem; }
  .promo-desc    { font-size:12px; line-height:1.5; }
  .promo-endnote { font-size:11px; }
  .promo-cta     { padding:10px 22px; font-size:13px; margin-top:2px; }
  #promoClose    { top:8px; right:8px; width:28px; height:28px; }
  #promoDontShow { font-size:11px; bottom:10px; right:12px; }
}
</style>

<div id="promoBg">
  <div id="promoBox">

    <button id="promoClose" aria-label="Close">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
        <path d="M1 1l10 10M11 1L1 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>
    <button id="promoDontShow" title="Don't show again">Don't show again</button>

    <?php
    // Build image block
    $_imgBlock = '<div class="promo-img">'
        . ($_imgUrl
            ? '<img src="' . htmlspecialchars($_imgUrl) . '" alt="Offer" style="object-position:' . htmlspecialchars($_imgPos) . ';">'
            : '<div class="promo-img-ph">🛍️</div>')
        . '</div>';

    // Build content block
    $_contentBlock = '<div class="promo-content">'
        . '<div class="promo-brand">'   . htmlspecialchars($_popupCfg['badge_text']) . '</div>'
        . '<h2 class="promo-title">'    . htmlspecialchars($_popupCfg['title'])      . '</h2>'
        . (!empty($_popupCfg['description'])
            ? '<p class="promo-desc">'    . nl2br(htmlspecialchars($_popupCfg['description'])) . '</p>' : '')
        . (!empty($_popupCfg['end_note'])
            ? '<p class="promo-endnote">' . htmlspecialchars($_popupCfg['end_note'])  . '</p>' : '')
        . '<a href="' . htmlspecialchars($_btnUrl) . '" class="promo-cta" style="background:' . $_btnColor . ';">'
        .   htmlspecialchars($_popupCfg['button_text'])
        . '</a></div>';

    // Render based on layout
    if ($_layout === 'right') {
        echo $_contentBlock . $_imgBlock;      // text | image
    } elseif ($_layout === 'left') {
        echo $_imgBlock . $_contentBlock;      // image | text
    } elseif ($_layout === 'top') {
        echo $_imgBlock . $_contentBlock;      // image above, text below
    } else { // bottom
        echo $_contentBlock . $_imgBlock;      // text above, image below
    }
    ?>

  </div>
</div>

<script>
(function(){
  var KEY     = '<?= $_storageKey ?>';
  var DELAY   = <?= $_delayMs ?>;
  var RESHOW  = '<?= $_reshowType ?>';
  var DAYS    = <?= $_reshowDays ?>;
  function lsGet(k){ try { return localStorage.getItem(k); } catch(e){ return null; } }
  function lsSet(k,v){ try { localStorage.setItem(k,v); } catch(e){} }
  function ssGet(k){ try { return sessionStorage.getItem(k); } catch(e){ return null; } }
  function ssSet(k,v){ try { sessionStorage.setItem(k,v); } catch(e){} }

  function shouldShow() {
    // Always respect session-close first (clicking X hides for this page session)
    if (ssGet(KEY + '_closed')) return false;
    // Permanent "don't show again"
    if (lsGet(KEY + '_never')) return false;
    if (RESHOW === 'always')  return true;
    if (RESHOW === 'never')   return !lsGet(KEY + '_closed');
    if (RESHOW === 'session') return !ssGet(KEY);
    if (RESHOW === 'days') {
      var last = lsGet(KEY + '_ts');
      if (!last) return true;
      return (Date.now() - parseInt(last)) / 86400000 >= DAYS;
    }
    return true;
  }

  if (!shouldShow()) return;

  var bg       = document.getElementById('promoBg');
  var cls      = document.getElementById('promoClose');
  var dontShow = document.getElementById('promoDontShow');

  function open()  {
    bg.classList.add('show');
    if (RESHOW === 'session') ssSet(KEY, '1');
    if (RESHOW === 'days')    lsSet(KEY + '_ts', Date.now());
  }
  function close() {
    bg.classList.remove('show');
    // Always hide for rest of this browser session (until page refresh)
    ssSet(KEY + '_closed', '1');
    if (RESHOW === 'never') lsSet(KEY + '_closed', '1');
  }
  function neverShow() {
    bg.classList.remove('show');
    lsSet(KEY + '_never', '1');
    ssSet(KEY + '_closed', '1');
  }

  setTimeout(open, DELAY || 500);
  if (cls)      cls.addEventListener('click', close);
  if (dontShow) dontShow.addEventListener('click', neverShow);
  bg.addEventListener('click', function(e) { if (e.target === bg) close(); });
  document.addEventListener('keydown', function(e) { if (e.key === 'Escape') close(); });
})();
</script>
<?php endif; ?>
<!-- ══ END PROMO POPUP ════════════════════════════════════════════ -->

<!-- AI STYLIST FLOATING BUTTON REMOVED — keeping only AI chatbot (th_widget.js) -->
<?php if(false): // disabled block ?>
<style>
/* Floating button */
#aiFloat{
  position:fixed;bottom:28px;right:28px;z-index:9999;
  width:58px;height:58px;border-radius:50%;
  background:linear-gradient(135deg,#7c3aed,#be185d);
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 4px 20px rgba(124,58,237,.45);
  cursor:pointer;border:none;transition:transform .25s,box-shadow .25s;
  text-decoration:none;
}
#aiFloat:hover{transform:scale(1.1);box-shadow:0 6px 28px rgba(124,58,237,.6);}
#aiFloat svg{width:26px;height:26px;fill:none;stroke:#fff;stroke-width:2;}
#aiFloat .ai-pulse{
  position:absolute;top:-3px;right:-3px;
  width:16px;height:16px;background:#f59e0b;border-radius:50%;
  border:2px solid #fff;font-size:9px;font-weight:800;color:#fff;
  display:flex;align-items:center;justify-content:center;
}

/* Popup */
#aiPopupOverlay{
  position:fixed;inset:0;z-index:10000;
  background:rgba(0,0,0,.45);
  display:flex;align-items:center;justify-content:center;
  opacity:0;pointer-events:none;transition:opacity .3s;
}
#aiPopupOverlay.show{opacity:1;pointer-events:all;}
#aiPopup{
  background:#fff;border-radius:20px;
  padding:32px 28px 24px;max-width:400px;width:90%;
  text-align:center;position:relative;
  box-shadow:0 20px 60px rgba(0,0,0,.25);
  transform:translateY(20px);transition:transform .3s;
}
#aiPopupOverlay.show #aiPopup{transform:translateY(0);}
.pop-icon{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#be185d);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:28px;}
#aiPopup h4{font-size:17px;font-weight:800;color:#1f2937;margin-bottom:8px;}
#aiPopup p{font-size:13px;color:#6b7280;line-height:1.65;margin-bottom:20px;}
.pop-btns{display:flex;gap:10px;justify-content:center;margin-bottom:16px;}
.pop-btn-yes{background:linear-gradient(135deg,#7c3aed,#be185d);color:#fff;border:none;padding:11px 28px;border-radius:50px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;transition:opacity .2s;}
.pop-btn-yes:hover{opacity:.88;color:#fff;}
.pop-btn-no{background:#f3f4f6;color:#6b7280;border:none;padding:11px 22px;border-radius:50px;font-size:14px;font-weight:600;cursor:pointer;transition:background .2s;}
.pop-btn-no:hover{background:#e5e7eb;}
.pop-dontshow{display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;color:#9ca3af;cursor:pointer;}
.pop-dontshow input{cursor:pointer;accent-color:#7c3aed;}
</style>

<!-- Floating AI button -->
<a href="<?php echo $url_domain;?>/recommend" id="aiFloat" title="AI Fashion Stylist">
  <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20A10 10 0 0012 2z" stroke-linecap="round"/><path d="M8 12h8M12 8v8" stroke-linecap="round"/></svg>
  <span class="ai-pulse">AI</span>
</a>

<!-- Popup -->
<div id="aiPopupOverlay">
  <div id="aiPopup">
    <div class="pop-icon">✨</div>
    <h4>Need Outfit Suggestions?</h4>
    <p>Our <strong>AI Fashion Stylist</strong> can recommend the perfect outfit for any occasion — Wedding, Eid, Office &amp; more — or explore cultural dresses from across Pakistan 🇵🇰</p>
    <div class="pop-btns">
      <a href="<?php echo $url_domain;?>/recommend" class="pop-btn-yes">✨ Try AI Stylist</a>
      <button class="pop-btn-no" onclick="closePopup()">Not Now</button>
    </div>
    <label class="pop-dontshow">
      <input type="checkbox" id="dontShowAgain"> Don't show this again
    </label>
  </div>
</div>

<script>
(function(){
  const KEY     = 'aiPopupHidden';
  const DELAY   = 2000;   // show after 2s on page load
  const RETRY   = 20000;  // re-show after 20s if cancelled without "don't show"

  // Don't show on the recommend page itself
  if (window.location.pathname.includes('/recommend')) return;

  // Don't show if user checked "don't show again"
  if (localStorage.getItem(KEY) === '1') return;

  var timer = setTimeout(showPopup, DELAY);

  function showPopup() {
    document.getElementById('aiPopupOverlay').classList.add('show');
  }

  window.closePopup = function() {
    document.getElementById('aiPopupOverlay').classList.remove('show');
    // If "don't show again" checked → save to localStorage
    if (document.getElementById('dontShowAgain').checked) {
      localStorage.setItem(KEY, '1');
    } else {
      // Re-show after 20 seconds
      setTimeout(showPopup, RETRY);
    }
  };

  // Click overlay background to close
  document.getElementById('aiPopupOverlay').addEventListener('click', function(e){
    if (e.target === this) closePopup();
  });
})();
</script>
<?php endif; // end disabled AI stylist block ?>
<!-- ══ END AI STYLIST WIDGET (DISABLED) ═══════════════════════════════ -->

<!-- ══ SOCIAL PROOF TOAST ══════════════════════════════════════════════ -->
<style>
#spToast {
    position: fixed;
    bottom: 28px;
    left: 24px;
    z-index: 99998;
    width: 310px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0,0,0,.18), 0 2px 8px rgba(0,0,0,.08);
    display: flex;
    align-items: center;
    gap: 0;
    overflow: hidden;
    transform: translateX(-360px);
    opacity: 0;
    transition: transform .45s cubic-bezier(.34,1.56,.64,1), opacity .35s ease;
    border: 1px solid rgba(0,0,0,.06);
}
#spToast.sp-show {
    transform: translateX(0);
    opacity: 1;
}
#spToast .sp-img-wrap {
    width: 72px;
    height: 72px;
    flex-shrink: 0;
    background: #f5f5f5;
    overflow: hidden;
}
#spToast .sp-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
#spToast .sp-body {
    flex: 1;
    padding: 10px 12px;
    min-width: 0;
}
#spToast .sp-label {
    font-size: .68rem;
    font-weight: 700;
    color: #27ae60;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    gap: 4px;
}
#spToast .sp-label::before {
    content: '';
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #27ae60;
    display: inline-block;
    animation: spPulse 1.4s ease-in-out infinite;
}
@keyframes spPulse {
    0%,100% { transform: scale(1); opacity: 1; }
    50%      { transform: scale(1.6); opacity: .5; }
}
#spToast .sp-name {
    font-size: .82rem;
    font-weight: 700;
    color: #111;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}
#spToast .sp-meta {
    font-size: .72rem;
    color: #888;
}
#spToast .sp-meta strong {
    color: #555;
}
#spToast .sp-close {
    position: absolute;
    top: 7px;
    right: 9px;
    background: none;
    border: none;
    color: #bbb;
    font-size: .85rem;
    cursor: pointer;
    line-height: 1;
    padding: 0;
}
#spToast .sp-close:hover { color: #555; }
#spToast .sp-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(90deg, #27ae60, #2ecc71);
    border-radius: 0 0 0 16px;
    width: 100%;
    transform-origin: left;
    transition: none;
}
</style>

<div id="spToast" role="alert" aria-live="polite">
    <div class="sp-img-wrap"><img id="spImg" src="" alt="" onerror="this.style.display='none'"></div>
    <div class="sp-body">
        <div class="sp-label">Someone just bought</div>
        <div class="sp-name" id="spName"></div>
        <div class="sp-meta"><strong id="spCity"></strong> &middot; <span id="spTime"></span></div>
    </div>
    <button class="sp-close" onclick="spDismiss()" title="Close">&times;</button>
    <div class="sp-bar" id="spBar"></div>
</div>

<script>
(function(){
    var spData = null, spIndex = 0, spTimer = null, spBarTimer = null, spDismissed = false;

    function loadSP() {
        fetch('<?= $url_domain ?>/social_proof_data.php')
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d.enabled || !d.notifications || d.notifications.length === 0) return;
                spData = d;
                setTimeout(showNext, 3000); // first popup after 3s
            })
            .catch(function(){});
    }

    function showNext() {
        if (!spData || spData.notifications.length === 0 || spDismissed) return;
        var n = spData.notifications[spIndex % spData.notifications.length];
        spIndex++;

        document.getElementById('spName').textContent = n.product_name;
        document.getElementById('spCity').textContent = n.city;
        document.getElementById('spTime').textContent = n.time_label;
        var img = document.getElementById('spImg');
        img.src = n.product_image || '';
        img.style.display = n.product_image ? '' : 'none';

        // Make clickable if slug exists
        var toast = document.getElementById('spToast');
        if (n.slug) {
            toast.style.cursor = 'pointer';
            toast.onclick = function(e){ if (e.target.classList.contains('sp-close')) return; window.location = '<?= $url_domain ?>/product/' + n.slug; };
        } else {
            toast.style.cursor = '';
            toast.onclick = null;
        }

        toast.classList.add('sp-show');

        // Animate countdown bar
        var bar = document.getElementById('spBar');
        bar.style.transition = 'none';
        bar.style.transform = 'scaleX(1)';
        var duration = (spData.duration || 4) * 1000;
        setTimeout(function(){
            bar.style.transition = 'transform ' + duration + 'ms linear';
            bar.style.transform = 'scaleX(0)';
        }, 50);

        // Hide after duration
        spTimer = setTimeout(function(){
            toast.classList.remove('sp-show');
            // Queue next after delay
            setTimeout(showNext, (spData.delay || 5) * 1000);
        }, duration);
    }

    window.spDismiss = function() {
        clearTimeout(spTimer);
        document.getElementById('spToast').classList.remove('sp-show');
        spDismissed = true;
    };

    window.addEventListener('load', loadSP);
})();
</script>
<!-- ══ END SOCIAL PROOF TOAST ══════════════════════════════════════════ -->

</body>
</html>