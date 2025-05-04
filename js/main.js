document.addEventListener('DOMContentLoaded', function() {
    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const overlay = document.querySelector('.overlay');
    const closeMenu = document.querySelector('.close-menu');

    if (menuToggle && mobileMenu && overlay && closeMenu) {
        menuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        function closeMenuFunc() {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        closeMenu.addEventListener('click', closeMenuFunc);
        overlay.addEventListener('click', closeMenuFunc);
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const navbarHeight = navbar.offsetHeight;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - navbarHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                if (mobileMenu && mobileMenu.classList.contains('active')) {
                    closeMenuFunc();
                }
            }
        });
    });

    // Testimonial slider
    const testimonialSlider = document.querySelector('.testimonial-slider');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const dots = document.querySelectorAll('.dot');
    let currentSlide = 0;
    
    if (testimonialSlider && prevBtn && nextBtn && dots.length > 0) {
        const slideWidth = testimonialSlider.querySelector('.testimonial-card').offsetWidth + 24; // Card width + gap
        const totalSlides = dots.length;
        
        function goToSlide(slideIndex) {
            if (slideIndex < 0) slideIndex = 0;
            if (slideIndex >= totalSlides) slideIndex = totalSlides - 1;
            
            currentSlide = slideIndex;
            testimonialSlider.style.transform = `translateX(-${slideIndex * slideWidth}px)`;
            
            // Update active dot
            dots.forEach((dot, index) => {
                if (index === slideIndex) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }
        
        prevBtn.addEventListener('click', () => goToSlide(currentSlide - 1));
        nextBtn.addEventListener('click', () => goToSlide(currentSlide + 1));
        
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => goToSlide(index));
        });
        
        // Initialize slider
        goToSlide(0);
        
        // Auto slide every 5 seconds
        setInterval(() => {
            goToSlide((currentSlide + 1) % totalSlides);
        }, 5000);
    }

    // Pricing toggle
    const pricingToggle = document.querySelector('.pricing-toggle input');
    const monthlyPrices = document.querySelectorAll('.amount.monthly');
    const annualPrices = document.querySelectorAll('.amount.annual');
    
    if (pricingToggle && monthlyPrices.length > 0 && annualPrices.length > 0) {
        pricingToggle.addEventListener('change', function() {
            if (this.checked) {
                // Annual pricing
                monthlyPrices.forEach(price => price.style.display = 'none');
                annualPrices.forEach(price => price.style.display = 'block');
            } else {
                // Monthly pricing
                monthlyPrices.forEach(price => price.style.display = 'block');
                annualPrices.forEach(price => price.style.display = 'none');
            }
        });
    }

    // Animate elements when they come into view
    const animateElements = document.querySelectorAll('.animate-on-scroll');
    
    if (animateElements.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        animateElements.forEach(element => {
            observer.observe(element);
        });
    }

    // Language selector in footer
    const languageSelector = document.querySelector('.language-selector select');
    if (languageSelector) {
        languageSelector.addEventListener('change', function() {
            const selectedLanguage = this.value;
            // Here you would implement the language change logic
            console.log(`Language changed to: ${selectedLanguage}`);
        });
    }
});