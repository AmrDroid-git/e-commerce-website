
// Register GSAP ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

// Get DOM elements
const wrapper = document.querySelector('.wrapper');
const hero = document.querySelector('.hero');
const mainImage = document.querySelector('.main-image');
const scrollAppearDiv = document.querySelector('.scroll-appear-div');
const productsSection = document.querySelector('.products-section');
const sectionTitle = document.querySelector('.section-title');
const navbar = document.querySelector('.navbar');
const highlights = document.querySelectorAll('.highlight');
const currentYearElement = document.getElementById('current-year');

// Set current year in footer
currentYearElement.textContent = new Date().getFullYear();

// Cart functionality
let cartCount = 0;
const cartCountElement = document.querySelector('.cart-count');

function addToCart() {
    cartCount++;
    cartCountElement.textContent = cartCount;

    gsap.to(".navbar-cart", {
        scale: 1.2,
        duration: 0.2,
        yoyo: true,
        repeat: 1,
        ease: "power1.inOut"
    });
}

// Initialize GSAP animations
function initAnimations() {
    const tl = gsap.timeline({
        scrollTrigger: {
            trigger: wrapper,
            start: "top top",
            end: "+=300%",
            pin: true,
            scrub: 1
        }
    });

    tl.to(mainImage, {
        scale: 1.8,
        z: 500,
        ease: "power2.inOut"
    })
        .to(hero, {
            scale: 1.2,
            opacity: 0.7,
            ease: "power1.out"
        }, "<30%")
        .to(".gradient-purple", {
            opacity: 1,
            duration: 0.5
        }, ">")
        .to(mainImage, {
            y: 200,
            rotation: 15,
            ease: "back.out(1)"
        }, ">")
        .to(".gradient-blue", {
            opacity: 1,
            duration: 0.8
        }, ">0.5");

    gsap.to(".scroll-reveal-text", {
        opacity: 1,
        visibility: "visible",
        y: -50,
        duration: 1,
        ease: "power2.out",
        scrollTrigger: {
            trigger: ".hero",
            start: "center center",
            end: "bottom top",
            toggleActions: "play none reverse none",
            markers: false
        }
    });

    gsap.to(navbar, {
        y: 0,
        duration: 0.5,
        ease: "power2.out",
        scrollTrigger: {
            trigger: hero,
            start: "bottom top+=300",
            toggleActions: "play none none reverse"
        }
    });

    gsap.to(sectionTitle, {
        opacity: 1,
        y: 0,
        duration: 0.8,
        ease: "power2.out",
        scrollTrigger: {
            trigger: productsSection,
            start: "top center+=100",
            toggleActions: "play none none none"
        }
    });

    gsap.to('.product-card', {
        opacity: 1,
        y: 0,
        stagger: 0.15,
        duration: 0.6,
        ease: "power2.out",
        scrollTrigger: {
            trigger: productsSection,
            start: "top center",
            toggleActions: "play none none none"
        }
    });

    gsap.to(".scroll-appear-div", {
        scrollTrigger: {
            trigger: hero,
            start: "bottom top+=300",
            toggleActions: "play none none reverse"
        },
        opacity: 1,
        visibility: "visible",
        y: 0,
        duration: 1,
        ease: "power2.out"
    });

    highlights.forEach(highlight => {
        highlight.addEventListener('mouseenter', () => {
            gsap.to(highlight, {
                y: -3,
                duration: 0.3,
                ease: "power1.out"
            });
        });

        highlight.addEventListener('mouseleave', () => {
            gsap.to(highlight, {
                y: 0,
                duration: 0.3,
                ease: "power1.out"
            });
        });
    });
}

function handleScroll() {
    const scrollY = window.scrollY;
    if (scrollY > 100) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        initAnimations();
    }, 100);

    window.addEventListener('scroll', handleScroll);

    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navbarLinks = document.querySelector('.navbar-links');

    mobileMenuBtn.addEventListener('click', () => {
        navbarLinks.classList.toggle('show');

        if (navbarLinks.classList.contains('show')) {
            gsap.to(navbarLinks, {
                display: 'flex',
                flexDirection: 'column',
                position: 'absolute',
                top: '80px',
                left: 0,
                width: '100%',
                padding: '1rem',
                backgroundColor: 'rgba(30, 27, 75, 0.95)',
                opacity: 1
            });
        } else {
            gsap.to(navbarLinks, {
                opacity: 0,
                onComplete: () => {
                    navbarLinks.style.display = 'none';
                }
            });
        }
    });

    // Add event listeners to your static Add to Cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', addToCart);
    });
});