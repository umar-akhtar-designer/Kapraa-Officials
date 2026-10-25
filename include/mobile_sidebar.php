<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuButton = document.getElementById('menu-button');
        const sidebar = document.getElementById('sidebarsss');
        const closeSidebar = document.getElementById('close-sidebar');
        const closeMobileSidebar = document.getElementById('close-mobile-sidebar');
        const overlay = document.getElementById('overlay');
        const mobileNavToggle = document.querySelector('.js-mobile-nav-toggle');
        const mobileNavWrapper = document.querySelector('.mobile-nav-wrapper');
        
        // Open desktop sidebar when menu button is clicked
        menuButton.addEventListener('click', function() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        });
        
        // Close desktop sidebar when close button is clicked
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        // Close mobile menu when close button is clicked
        closeMobileSidebar.addEventListener('click', function() {
            mobileNavWrapper.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
        });
        
        // Close sidebar when overlay is clicked
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            mobileNavWrapper.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
        });
        
        // Toggle mobile menu
        if (mobileNavToggle) {
            mobileNavToggle.addEventListener('click', function() {
                mobileNavWrapper.classList.toggle('open');
                document.body.classList.toggle('overflow-hidden');
            });
        }
        
        // Close sidebar when Escape key is pressed
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                mobileNavWrapper.classList.remove('open');
                document.body.classList.remove('overflow-hidden');
            }
        });
        
        // Toggle desktop submenus when clicking the icon
        const desktopSubmenuIcons = document.querySelectorAll('.sidebarsss-menu .has-submenu > a .icon');
        desktopSubmenuIcons.forEach(icon => {
            icon.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const parent = this.closest('.has-submenu');
                const submenu = parent.querySelector('.submenu');
                const parentUl = parent.parentElement;
                
                // Close all other submenus at the same level
                const siblings = Array.from(parentUl.children).filter(child => {
                    return child !== parent && child.classList.contains('has-submenu');
                });
                
                siblings.forEach(sibling => {
                    sibling.classList.remove('active');
                    const siblingSubmenu = sibling.querySelector('.submenu');
                    if(siblingSubmenu) siblingSubmenu.classList.remove('active');
                });
                
                // Toggle current submenu
                parent.classList.toggle('active');
                if(submenu) submenu.classList.toggle('active');
            });
        });
        
        // Toggle mobile submenus
        const mobileSubmenuIcons = document.querySelectorAll('.mobile-nav .has-submenu > a .mobile-icon');
        mobileSubmenuIcons.forEach(icon => {
            icon.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const parent = this.closest('.has-submenu');
                const submenu = parent.querySelector('.mobile-submenu');
                const parentUl = parent.parentElement;
                
                // Toggle current submenu
                parent.classList.toggle('active');
                if(submenu) submenu.classList.toggle('active');
                
                // Toggle icon
                if (parent.classList.contains('active')) {
                    this.classList.remove('an-plus');
                    this.classList.add('an-minus');
                } else {
                    this.classList.remove('an-minus');
                    this.classList.add('an-plus');
                }
            });
        });
        
        // Search functionality for both desktop and mobile
        const searchInputs = document.querySelectorAll('.search-input');
        
        searchInputs.forEach(input => {
            input.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const targetId = this.getAttribute('data-search-target');
                const targetContainer = document.getElementById(targetId);
                
                if (!targetContainer) return;
                
                const items = targetContainer.querySelectorAll('li');
                let hasVisibleItems = false;
                
                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = '';
                        hasVisibleItems = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Show/hide no results message
                let noResultsMsg = targetContainer.querySelector('.no-results');
                if (!hasVisibleItems) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'no-results';
                        noResultsMsg.textContent = 'No results found';
                        targetContainer.appendChild(noResultsMsg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            });
        });
    });
</script>
<script>
    // Function to format search query for URL
    function formatSearchQuery(query) {
        return query
            .toLowerCase()
            .replace(/[^\w\s]/gi, '') // Remove special characters
            .replace(/\s+/g, '-')     // Replace spaces with hyphens
            .replace(/-+/g, '-')      // Replace multiple hyphens with single hyphen
            .trim();
    }

    // Handle search form submission
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.querySelector('.search-bar__form');
        
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                const searchInput = this.querySelector('.search__input');
                const searchQuery = searchInput.value.trim();
                
                if (searchQuery) {
                    // Format the query for URL
                    const formattedQuery = formatSearchQuery(searchQuery);
                    
                    // Build the new URL
                    const newUrl = '<?php echo $url_domain; ?>/product/' + formattedQuery;
                    
                    // Navigate to the new URL
                    window.location.href = newUrl;
                    
                    // Prevent default form submission
                    e.preventDefault();
                    return false;
                }
            });
        }
        
        // Live Search Implementation
        const searchInput = document.querySelector('.search__input');
        const resultsContainer = document.querySelector('.live-search-results');
        let debounceTimer;

        if (searchInput && resultsContainer) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();

                // Clear previous timer
                clearTimeout(debounceTimer);

                // If search term is empty, clear results
                if (searchTerm.length < 2) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.style.display = 'none';
                    return;
                }

                // Set a new timer
                debounceTimer = setTimeout(function() {
                    // Send AJAX request
                    fetch('<?php echo $url_domain; ?>/search_handler.php?term=' + encodeURIComponent(searchTerm))
                        .then(response => response.json())
                        .then(data => {
                            // Clear previous results
                            resultsContainer.innerHTML = '';

                            if (data.length > 0) {
                                // Show results container
                                resultsContainer.style.display = 'block';

                                // Create and append results
                                data.forEach(product => {
                                    const resultItem = document.createElement('div');
                                    resultItem.className = 'live-search-result-item';
                                    resultItem.innerHTML = `
                                        <a href="${product.url}">
                                            <img src="${product.image}" alt="${product.name}" />
                                            <span class="product-name">${product.name}</span>
                                        </a>
                                    `;
                                    resultsContainer.appendChild(resultItem);
                                });
                            } else {
                                resultsContainer.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            resultsContainer.style.display = 'none';
                        });
                }, 300); // 300ms debounce
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(event) {
                if (!searchInput.contains(event.target) && !resultsContainer.contains(event.target)) {
                    resultsContainer.style.display = 'none';
                }
            });
        }
    });
</script>