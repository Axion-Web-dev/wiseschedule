document.addEventListener('DOMContentLoaded', () => {
    console.log('Landing page JavaScript loaded');
    
    // Force cursor pointer on all interactive elements
    const interactiveElements = document.querySelectorAll('button, .btn, [onclick], [role="button"], a, input[type="button"], input[type="submit"]');
    
    interactiveElements.forEach(element => {
        element.style.cursor = 'pointer';
        
        // Force cursor on hover
        element.addEventListener('mouseenter', () => {
            element.style.cursor = 'pointer';
        });
        
        element.addEventListener('mouseover', () => {
            element.style.cursor = 'pointer';
        });
    });
    
    // Also force cursor on any element with cursor-pointer class
    const cursorElements = document.querySelectorAll('.cursor-pointer');
    cursorElements.forEach(element => {
        element.style.cursor = 'pointer';
    });
    
    // Smooth scroll for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});

function handleGetStarted() {
    console.log('handleGetStarted called');
    try {
        window.location.href = '/start-trial';
    } catch (error) {
        console.error('Error in handleGetStarted:', error);
        // Fallback: try different route formats
        window.location.href = '/start-trial';
    }
}

function handleLogin() {
    console.log('handleLogin called');
    try {
        window.location.href = '/admin/login';
    } catch (error) {
        console.error('Error in handleLogin:', error);
        window.location.href = '/admin/login';
    }
}

function handleViewDemo() {
    console.log('handleViewDemo called');
    try {
        // Scroll to demo section or open modal
        const demoSection = document.querySelector('#demo');
        if (demoSection) {
            demoSection.scrollIntoView({ behavior: 'smooth' });
        } else {
            // Create demo modal
            showDemoModal();
        }
    } catch (error) {
        console.error('Error in handleViewDemo:', error);
    }
}

window.handleGetStarted = handleGetStarted;
window.handleLogin = handleLogin;
window.handleViewDemo = handleViewDemo;

function handleContactSales() {
    // Open contact form or email
    window.location.href = 'mailto:sales@schedulewise.ai?subject=Enterprise Inquiry';
}

function handleBookCall() {
    // Open calendar booking
    window.open('https://calendly.com/schedulewise-demo', '_blank');
}

function showDemoModal() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm';
    modal.innerHTML = `
        <div class="bg-surface-container rounded-2xl p-8 max-w-2xl mx-4 border border-outline-variant/20 animate-bounce-in">
            <h3 class="text-2xl font-bold mb-4">See ScheduleWise AI in Action</h3>
            <div class="aspect-video bg-surface-container-lowest rounded-xl mb-6 flex items-center justify-center">
                <span class="material-symbols-outlined text-6xl text-primary/40">play_circle</span>
            </div>
            <p class="text-on-surface-variant mb-6">Watch how our AI transforms your content workflow in just 2 minutes.</p>
            <div class="flex gap-4">
                <button onclick="this.closest('.fixed').remove()" class="bg-primary-container text-white px-6 py-3 rounded-xl font-bold">
                    Close
                </button>
                <button onclick="window.location.href='/admin/register'" class="bg-surface-container-highest border border-outline-variant/20 px-6 py-3 rounded-xl font-bold">
                    Start Free Trial
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function showModal(type) {
    const content = {
        privacy: {
            title: 'Privacy Policy',
            content: 'We take your privacy seriously. All data is encrypted and never shared with third parties without your consent.'
        },
        terms: {
            title: 'Terms of Service',
            content: 'By using ScheduleWise AI, you agree to our terms of service including acceptable use and payment policies.'
        },
        security: {
            title: 'Security',
            content: 'We use industry-standard encryption and security measures to protect your data and content.'
        }
    };

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm';
    modal.innerHTML = `
        <div class="bg-surface-container rounded-2xl p-8 max-w-2xl mx-4 border border-outline-variant/20 animate-bounce-in">
            <h3 class="text-2xl font-bold mb-4">${content[type].title}</h3>
            <p class="text-on-surface-variant mb-6">${content[type].content}</p>
            <div class="flex gap-4">
                <button onclick="this.closest('.fixed').remove()" class="bg-primary-container text-white px-6 py-3 rounded-xl font-bold">
                    Close
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function addLoadingState(button, originalText) {
    button.classList.add('loading');
    button.disabled = true;
    button.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span> Loading...';
    
    setTimeout(() => {
        button.classList.remove('loading');
        button.disabled = false;
        button.textContent = originalText;
    }, 2000);
}

const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-fade-in-up');
        }
    });
}, observerOptions);

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('section').forEach(section => {
        observer.observe(section);
    });
});

function createParticles() {
    // Create particles container limited to header area
    const particlesContainer = document.createElement('div');
    particlesContainer.className = 'fixed top-0 left-0 right-0 h-32 pointer-events-none z-0';
    particlesContainer.style.background = 'radial-gradient(circle at 20% 50%, rgba(79, 70, 229, 0.1) 0%, transparent 50%), radial-gradient(circle at 80% 80%, rgba(79, 70, 229, 0.05) 0%, transparent 50%)';
    document.body.insertBefore(particlesContainer, document.body.firstChild);
    
    // Create floating particles limited to header area
    for (let i = 0; i < 8; i++) {
        createFloatingParticle();
    }
}

function createFloatingParticle() {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.animationDelay = Math.random() * 10 + 's';
    particle.style.fontSize = Math.random() * 20 + 10 + 'px';
    particle.innerHTML = ['✨', '⭐', '💫', '🌟', '✦'][Math.floor(Math.random() * 5)];
    
    // Find the header particles container or append to body if not found
    const headerContainer = document.querySelector('.fixed.top-0.left-0.right-0.h-32');
    if (headerContainer) {
        headerContainer.appendChild(particle);
    } else {
        document.body.appendChild(particle);
    }
}

createParticles();

function toggleAccordion(button) {
    const content = button.nextElementSibling;
    const icon = button.querySelector('.material-symbols-outlined');
    const isExpanded = !content.classList.contains('hidden');
    
    // Close all other accordions (optional - remove if you want multiple open)
    document.querySelectorAll('.bg-surface-container.border.border-outline-variant\\/10 .px-6.py-4.text-on-surface-variant').forEach(item => {
        if (item !== content) {
            item.classList.add('hidden');
            const otherIcon = item.previousElementSibling.querySelector('.material-symbols-outlined');
            if (otherIcon) {
                otherIcon.style.transform = 'rotate(0deg)';
            }
        }
    });
    
    // Toggle current accordion
    if (isExpanded) {
        content.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    } else {
        content.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    }
}

window.toggleAccordion = toggleAccordion;

function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    const timer = setInterval(() => {
        start += increment;
        if (start >= target) {
            start = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(start).toLocaleString() + (element.textContent.includes('+') ? '+' : '');
    }, 16);
}

const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
            const text = entry.target.textContent;
            const number = parseInt(text.replace(/[^0-9]/g, ''));
            animateCounter(entry.target, number);
            entry.target.classList.add('counted');
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.text-3xl.font-black').forEach(el => {
        if (/\\d+/.test(el.textContent)) {
            counterObserver.observe(el);
        }
    });
});

window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const parallaxElements = document.querySelectorAll('.parallax-slow, .parallax-medium, .parallax-fast');
    
    parallaxElements.forEach(element => {
        const speed = element.classList.contains('parallax-slow') ? 0.5 : 
                      element.classList.contains('parallax-medium') ? 0.3 : 0.1;
        const yPos = -(scrolled * speed);
        element.style.transform = `translateY(${yPos}px)`;
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});