document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const mobileMenuBtn = document.createElement('button');
    mobileMenuBtn.className = 'mobile-menu-btn btn btn-primary d-lg-none';
    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    mobileMenuBtn.style.position = 'fixed';
    mobileMenuBtn.style.bottom = '20px';
    mobileMenuBtn.style.right = '20px';
    mobileMenuBtn.style.zIndex = '1000';
    mobileMenuBtn.style.width = '50px';
    mobileMenuBtn.style.height = '50px';
    mobileMenuBtn.style.borderRadius = '50%';
    mobileMenuBtn.style.display = 'flex';
    mobileMenuBtn.style.alignItems = 'center';
    mobileMenuBtn.style.justifyContent = 'center';
    mobileMenuBtn.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.2)';

    document.body.appendChild(mobileMenuBtn);

    mobileMenuBtn.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });

    // Animate stats cards on scroll
    const statCards = document.querySelectorAll('.stat-card');

    statCards.forEach((card, index) => {
        gsap.from(card, {
            opacity: 0,
            y: 50,
            duration: 0.5,
            delay: index * 0.1,
            scrollTrigger: {
                trigger: card,
                start: "top 80%",
                toggleActions: "play none none none"
            }
        });
    });

    // Animate activity items
    const activityItems = document.querySelectorAll('.activity-list li');

    activityItems.forEach((item, index) => {
        gsap.from(item, {
            opacity: 0,
            x: -30,
            duration: 0.3,
            delay: index * 0.1,
            scrollTrigger: {
                trigger: item,
                start: "top 90%",
                toggleActions: "play none none none"
            }
        });
    });

    // Set current year in footer
    document.getElementById('current-year').textContent = new Date().getFullYear();

    // Add hover effects to product recommendations
    const productRecs = document.querySelectorAll('.product-recommendation');

    productRecs.forEach(product => {
        product.addEventListener('mouseenter', function() {
            gsap.to(this, {
                y: -5,
                duration: 0.3,
                ease: "power2.out"
            });
        });

        product.addEventListener('mouseleave', function() {
            gsap.to(this, {
                y: 0,
                duration: 0.3,
                ease: "power2.out"
            });
        });
    });

    // Notification badge animation
    const notificationBadge = document.querySelector('.notifications .badge');

    if (notificationBadge) {
        gsap.to(notificationBadge, {
            scale: 1.2,
            duration: 0.5,
            yoyo: true,
            repeat: -1,
            ease: "power1.inOut"
        });
    }
    // Fonctionnalité de recherche
    const searchInput = document.querySelector('.search-bar input');

    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase();
            const products = document.querySelectorAll('.product-recommendation');

            products.forEach(product => {
                const title = product.querySelector('h4').textContent.toLowerCase();
                if (title.includes(query)) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        });
    }


});