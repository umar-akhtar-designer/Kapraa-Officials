
<!-- JavaScript to handle quick view functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset modal elements to initial state
        function resetModalElements() {
            // Reset main image
            const mainImageElement = document.getElementById('quickview-main-image');
            mainImageElement.src = '<?php echo $url_domain;?>/images/waiting images.jpeg';
            mainImageElement.alt = 'Product Image';
            
            // Clear thumbnails
            const thumbnailContainer = document.getElementById('quickview-thumbnail-container');
            thumbnailContainer.innerHTML = '';
            thumbnailContainer.style.display = 'flex';
            
            // Reset other elements
            document.getElementById('quickview-product-name').textContent = 'Product Quick View';
            document.getElementById('quickview-sku').textContent = 'N/A';
            document.getElementById('quickview-description').textContent = 'Product description will be loaded here...';
            document.getElementById('quickview-old-price').textContent = 'RS. 0.00';
            document.getElementById('quickview-price').textContent = 'RS. 0.00';
            document.getElementById('ComparePrice-product-template').style.display = 'none';
            document.getElementById('quickview-stock-status').style.display = 'inline';
            document.getElementById('quickview-outstock').style.display = 'none';
            document.getElementById('quickview-size-options').innerHTML = '';
            document.getElementById('form-product-id').value = '';
            document.getElementById('Quantity').value = 1;
        }
        
        // Function to attach event listeners
        function attachEventListeners() {
            // Attach click event to all quick view buttons
            document.querySelectorAll('.quick-view').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Get all product data from data attributes
                    const productId = this.getAttribute('data-product-id');
                    const productName = this.getAttribute('data-product-name');
                    const productPrice = this.getAttribute('data-product-price');
                    const productDiscountPrice = this.getAttribute('data-product-discount-price');
                    const productSku = this.getAttribute('data-product-sku');
                    const productStock = this.getAttribute('data-product-stock');
                    const productDescription = this.getAttribute('data-product-description');
                    const productSizes = this.getAttribute('data-product-sizes');
                    const productImages = JSON.parse(this.getAttribute('data-product-images') || '[]');
                    
                    // Update product information in the modal
                    document.getElementById('form-product-id').value = productId;
                    document.getElementById('quickview-product-name').textContent = productName;
                    document.getElementById('quickview-sku').textContent = productSku || 'N/A';
                    document.getElementById('quickview-description').textContent = productDescription || 'No description available';
                    
                    // Update prices
                    if (productDiscountPrice && parseFloat(productDiscountPrice) < parseFloat(productPrice)) {
                        document.getElementById('quickview-old-price').textContent = 'RS. ' + parseFloat(productPrice).toFixed(2);
                        document.getElementById('quickview-price').textContent = 'RS. ' + parseFloat(productDiscountPrice).toFixed(2);
                        document.getElementById('ComparePrice-product-template').style.display = 'inline';
                    } else {
                        document.getElementById('quickview-price').textContent = 'RS. ' + parseFloat(productPrice).toFixed(2);
                        document.getElementById('ComparePrice-product-template').style.display = 'none';
                    }
                    
                    // Update stock status
                    const stockStatus = document.getElementById('quickview-stock-status');
                    const outStockStatus = document.getElementById('quickview-outstock');
                    
                    if (productStock > 0) {
                        stockStatus.style.display = 'inline';
                        outStockStatus.style.display = 'none';
                    } else {
                        stockStatus.style.display = 'none';
                        outStockStatus.style.display = 'inline';
                    }
                    
                    // Handle product sizes
                    const sizeContainer = document.getElementById('quickview-size-options');
                    sizeContainer.innerHTML = ''; // Clear existing sizes
                    
                    if (productSizes) {
                        const sizes = productSizes.split(',');
                        if (sizes.length > 0) {
                            sizes.forEach(size => {
                                const sizeBadge = document.createElement('span');
                                sizeBadge.className = 'badge bg-secondary me-2 mb-2';
                                sizeBadge.textContent = size.trim();
                                sizeContainer.appendChild(sizeBadge);
                            });
                        } else {
                            sizeContainer.innerHTML = '<span class="text-muted">No sizes available</span>';
                        }
                    } else {
                        sizeContainer.innerHTML = '<span class="text-muted">No sizes available</span>';
                    }
                    
                    // Handle product images
                    const thumbnailContainer = document.getElementById('quickview-thumbnail-container');
                    thumbnailContainer.innerHTML = ''; // Clear existing thumbnails
                    
                    // Set main image first - this is critical
                    const mainImageElement = document.getElementById('quickview-main-image');
                    if (productImages.length > 0) {
                        // Force reload by adding timestamp to prevent caching
                        const timestamp = new Date().getTime();
                        mainImageElement.src = productImages[0] + '?t=' + timestamp;
                        mainImageElement.alt = productName;
                        
                        // Only show thumbnails if there are multiple images
                        if (productImages.length > 1) {
                            productImages.forEach((imageUrl, index) => {
                                const thumbnailItem = document.createElement('div');
                                thumbnailItem.className = 'item';
                                thumbnailItem.innerHTML = `
                                    <img class="blur-up lazyload product-thumbnail" 
                                        src="${imageUrl}?t=${timestamp}" 
                                        alt="${productName} - Image ${index + 1}"
                                        data-image-index="${index}"
                                        style="cursor: pointer;"
                                        onerror="this.src='<?php echo $url_domain; ?>/images/waiting images.jpeg'">
                                `;
                                thumbnailContainer.appendChild(thumbnailItem);
                            });
                            
                            // Add click event to thumbnails
                            thumbnailContainer.querySelectorAll('.product-thumbnail').forEach(thumb => {
                                thumb.addEventListener('click', function() {
                                    const imageIndex = this.getAttribute('data-image-index');
                                    const mainImageElement = document.getElementById('quickview-main-image');
                                    mainImageElement.src = productImages[imageIndex] + '?t=' + timestamp;
                                    mainImageElement.alt = `${productName} - Image ${parseInt(imageIndex) + 1}`;
                                    
                                    // Update active thumbnail class (optional visual feedback)
                                    thumbnailContainer.querySelectorAll('.product-thumbnail').forEach(t => {
                                        t.classList.remove('active-thumbnail');
                                    });
                                    this.classList.add('active-thumbnail');
                                });
                            });
                            
                            // Show thumbnail container
                            thumbnailContainer.style.display = 'flex';
                        } else {
                            // Hide thumbnail container if only one image
                            thumbnailContainer.style.display = 'none';
                        }
                    } else {
                        // No images - set default
                        mainImageElement.src = '<?php echo $url_domain; ?>/images/waiting images.jpeg';
                        mainImageElement.alt = productName;
                        thumbnailContainer.style.display = 'none';
                    }
                    
                    // Log to console for debugging
                    console.log('Quick view opened for Product ID:', productId);
                    console.log('Product Images:', productImages.length);
                    console.log('Main image src set to:', mainImageElement.src);
                });
            });
            
        }
        
        // Initialize event listeners
        attachEventListeners();
        
        // Initialize Magnific Popup
        if (typeof $.magnificPopup !== 'undefined') {
            $('.quick-view-popup').magnificPopup({
                type: 'inline',
                preloader: false,
                focus: '#name',
                removalDelay: 500,
                callbacks: {
                    open: function() {
                        // Reset form when popup opens
                        document.getElementById('Quantity').value = 1;
                        console.log('Modal opened successfully');
                    },
                    close: function() {
                        // Reset modal elements to initial state
                        resetModalElements();
                        console.log('Modal closed and reset');
                    }
                }
            });
        } else {
            // Fallback for if Magnific Popup is not loaded
            console.warn('Magnific Popup not loaded. Using fallback modal behavior.');
            
            // Simple modal open for debugging
            document.querySelectorAll('.quick-view-popup').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const modalId = this.getAttribute('href');
                    const modal = document.querySelector(modalId);
                    if (modal) {
                        modal.classList.remove('mfp-hide');
                        modal.style.display = 'block';
                    }
                });
            });
        }
    });

    // CSS for active thumbnail and size options
    const style = document.createElement('style');
    style.textContent = `
        .product-thumbnail {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .product-thumbnail:hover {
            border-color: #333;
            opacity: 0.8;
        }
        .product-thumbnail.active-thumbnail {
            border-color: #ff6b6b;
            box-shadow: 0 0 5px rgba(255, 107, 107, 0.5);
        }
        .quickview-thumbnail-items {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            justify-content: center;
        }
        .quickview-thumbnail-items .item {
            width: 60px;
            height: 60px;
            overflow: hidden;
            border-radius: 4px;
        }
        .quickview-thumbnail-items .item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .badge.bg-secondary {
            background-color: #6c757d !important;
            font-size: 0.9rem;
            padding: 0.4em 0.8em;
        }
    `;
    document.head.appendChild(style);
</script>