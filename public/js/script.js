document.addEventListener('DOMContentLoaded', () => {
    const currentYearElement = document.getElementById('current-year');
    if (currentYearElement) {
        currentYearElement.textContent = new Date().getFullYear();
    }

    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.setAttribute('aria-expanded', navLinks.classList.contains('active') ? 'true' : 'false');
        });
    }

    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    const wrapper = document.querySelector('.wrapper');
    const hero = document.querySelector('.hero');
    const mainImage = document.querySelector('.main-image');
    const productsSection = document.querySelector('.products-section');
    const sectionTitle = document.querySelector('.section-title');
    const navbar = document.querySelector('.navbar');

    if (wrapper && hero && mainImage) {
        const tl = gsap.timeline({
            scrollTrigger: {
                trigger: wrapper,
                start: 'top top',
                end: '+=300%',
                pin: true,
                scrub: 1,
            },
        });

        tl.to(mainImage, { scale: 1.8, z: 500, ease: 'power2.inOut' })
            .to(hero, { scale: 1.2, opacity: 0.7, ease: 'power1.out' }, '<30%')
            .to('.gradient-purple', { opacity: 1, duration: 0.5 }, '>')
            .to(mainImage, { y: 200, rotation: 15, ease: 'back.out(1)' }, '>')
            .to('.gradient-blue', { opacity: 1, duration: 0.8 }, '>0.5');
    }

    if (navbar && hero) {
        gsap.to(navbar, {
            y: 0,
            duration: 0.5,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: hero,
                start: 'bottom top+=300',
                toggleActions: 'play none none reverse',
            },
        });
    }

    if (sectionTitle && productsSection) {
        gsap.to(sectionTitle, {
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: productsSection,
                start: 'top center+=100',
                toggleActions: 'play none none none',
            },
        });
    }
});

# backdated-commit: 2025-08-18 00:00:00
