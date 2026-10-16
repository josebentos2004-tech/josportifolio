/* ============================================================
   JOSDIGITAL — PORTFÓLIO PROFISSIONAL
   Script: JavaScript puro | Animações | Interatividade
   ============================================================ */

(function () {
    'use strict';

    /* ==================== NAVEGAÇÃO ==================== */
    const navbar = document.getElementById('navbar');
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    const navLinks = document.querySelectorAll('.nav-link');

    // Toggle menu mobile
    if (navToggle) navToggle.addEventListener('click', function () {
        navToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Fechar menu ao clicar num link
    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            navToggle.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });

    

    // Navbar ao scroll
    var lastScroll = 0;
    window.addEventListener('scroll', function () {
        var currentScroll = window.pageYOffset;

        // Adicionar sombra ao scrollar
        if (currentScroll > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        lastScroll = currentScroll;
    });

    /* ==================== ACTIVE LINK NO SCROLL ==================== */
    var sections = document.querySelectorAll('section[id]');

    function updateActiveLink() {
        var scrollPosition = window.pageYOffset + 150;

        sections.forEach(function (section) {
            var sectionTop = section.offsetTop;
            var sectionHeight = section.offsetHeight;
            var sectionId = section.getAttribute('id');

            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                navLinks.forEach(function (link) {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + sectionId) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }

    window.addEventListener('scroll', updateActiveLink);

    /* ==================== ANIMAÇÕES DE ENTRADA ==================== */
    var animatedElements = document.querySelectorAll('[data-animate]');

    function animateOnScroll() {
        animatedElements.forEach(function (element) {
            var elementTop = element.getBoundingClientRect().top;
            var windowHeight = window.innerHeight;
            var delay = parseInt(element.getAttribute('data-delay')) || 0;

            if (elementTop < windowHeight - 80) {
                setTimeout(function () {
                    element.classList.add('animated');
                }, delay);
            }
        });
    }

    window.addEventListener('scroll', animateOnScroll);
    window.addEventListener('load', animateOnScroll);

    /* ==================== CONTADOR DE NÚMEROS ==================== */
    var statNumbers = document.querySelectorAll('.stat-number');
    var statsCounted = false;

    function animateCounters() {
        if (statsCounted) return;

        var statsSection = document.querySelector('.about-stats');
        if (!statsSection) return;

        var sectionTop = statsSection.getBoundingClientRect().top;
        var windowHeight = window.innerHeight;

        if (sectionTop < windowHeight - 100) {
            statsCounted = true;

            statNumbers.forEach(function (counter) {
                var target = parseInt(counter.getAttribute('data-count'));
                var duration = 2000;
                var startTime = null;

                function updateCounter(timestamp) {
                    if (!startTime) startTime = timestamp;
                    var progress = Math.min((timestamp - startTime) / duration, 1);

                    // Easing function (ease-out)
                    var easeOut = 1 - Math.pow(1 - progress, 3);
                    var currentValue = Math.floor(easeOut * target);

                    counter.textContent = currentValue;

                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                }

                requestAnimationFrame(updateCounter);
            });

            // Animar barras de progresso
            var statBars = document.querySelectorAll('.stat-bar-fill');
            statBars.forEach(function (bar, index) {
                setTimeout(function () {
                    var width = [90, 80, 70, 65][index] || 75;
                    bar.style.width = width + '%';
                }, 300);
            });
        }
    }

    window.addEventListener('scroll', animateCounters);
    window.addEventListener('load', animateCounters);

    /* ==================== BARRAS DE TECNOLOGIA ==================== */
    var techLevels = document.querySelectorAll('.tech-level span');
    var techAnimated = false;

    function animateTechLevels() {
        if (techAnimated) return;

        var techSection = document.getElementById('tecnologias');
        if (!techSection) return;

        var sectionTop = techSection.getBoundingClientRect().top;
        var windowHeight = window.innerHeight;

        if (sectionTop < windowHeight - 100) {
            techAnimated = true;

            techLevels.forEach(function (bar) {
                var width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(function () {
                    bar.style.width = width;
                }, 200);
            });
        }
    }

    window.addEventListener('scroll', animateTechLevels);
    window.addEventListener('load', animateTechLevels);

    /* ==================== BOTÃO VOLTAR AO TOPO ==================== */
    var backToTop = document.getElementById('backToTop');

    window.addEventListener('scroll', function () {
        if (window.pageYOffset > 500) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });

    if (backToTop) backToTop.addEventListener('click', function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    /* ==================== PARTÍCULAS NO HERO ==================== */
    function createParticles() {
        var container = document.getElementById('particles');
        if (!container) return;

        var particleCount = window.innerWidth < 768 ? 15 : 30;

        for (var i = 0; i < particleCount; i++) {
            var particle = document.createElement('div');
            particle.classList.add('particle');

            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 6 + 's';
            particle.style.animationDuration = (4 + Math.random() * 4) + 's';

            var size = 2 + Math.random() * 3;
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';

            container.appendChild(particle);
        }
    }

    createParticles();

    /* ==================== FORMULÁRIO DE CONTACTO ==================== */
    var contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var status = document.getElementById('contactStatus');
            var data = new FormData(contactForm);
            // O formulário já contém o campo oculto action=contact.
            fetch('index.php', {method:'POST', body:data})
                .then(function(r){ return r.text().then(function(t){ return {ok:r.ok,text:t}; }); })
                .then(function(result){
                    if(status){ status.textContent=result.text; status.className='portfolio-status ' + (result.ok?'success':'error'); }
                    if(result.ok) contactForm.reset();
                }).catch(function(){ if(status){ status.textContent='Não foi possível enviar a mensagem.'; status.className='portfolio-status error'; }});
        });
    }

    /* ==================== SISTEMA DE NOTIFICAÇÕES ==================== */
    function showNotification(message, type) {
        // Remover notificação existente
        var existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        var notification = document.createElement('div');
        notification.classList.add('notification', 'notification-' + type);
        notification.innerHTML = '<div class="notification-content">' +
            '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i>' +
            '<span>' + message + '</span>' +
            '</div>';

        // Estilos inline para a notificação
        notification.style.cssText = 'position: fixed; top: 100px; right: 24px; z-index: 10000; padding: 16px 24px; border-radius: 12px; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 10px; animation: slideIn 0.4s ease; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.4);';

        if (type === 'success') {
            notification.style.background = 'rgba(34, 197, 94, 0.15)';
            notification.style.border = '1px solid rgba(34, 197, 94, 0.3)';
            notification.style.color = '#4ade80';
        } else {
            notification.style.background = 'rgba(239, 68, 68, 0.15)';
            notification.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            notification.style.color = '#f87171';
        }

        document.body.appendChild(notification);

        // Adicionar animação CSS
        var style = document.createElement('style');
        style.textContent = '@keyframes slideIn { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } } @keyframes slideOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(30px); } }';
        document.head.appendChild(style);

        // Remover após 4 segundos
        setTimeout(function () {
            notification.style.animation = 'slideOut 0.4s ease forwards';
            setTimeout(function () {
                notification.remove();
            }, 400);
        }, 4000);
    }

    /* ==================== EFEITO CURSOR GLOW (Desktop) ==================== */
    if (window.innerWidth > 768) {
        var heroSection = document.querySelector('.hero');
        var glowElement = document.createElement('div');

        glowElement.style.cssText = 'position: absolute; width: 300px; height: 300px; border-radius: 50%; background: radial-gradient(circle, rgba(59, 130, 246, 0.06), transparent 70%); pointer-events: none; z-index: 0; transition: transform 0.3s ease;';

        heroSection.style.position = 'relative';
        heroSection.appendChild(glowElement);

        heroSection.addEventListener('mousemove', function (e) {
            var rect = heroSection.getBoundingClientRect();
            var x = e.clientX - rect.left - 150;
            var y = e.clientY - rect.top - 150;
            glowElement.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
        });
    }

    /* ==================== SMOOTH REVEAL NO SCROLL ==================== */
    function revealOnScroll() {
        var reveals = document.querySelectorAll('.service-card, .project-card, .tech-category, .timeline-item, .contact-info-card');
        reveals.forEach(function (el) {
            var top = el.getBoundingClientRect().top;
            if (top < window.innerHeight - 60) {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }
        });
    }

    // Aplicar estilos iniciais
    document.querySelectorAll('.service-card, .project-card, .tech-category').forEach(function (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });

    window.addEventListener('scroll', revealOnScroll);
    window.addEventListener('load', revealOnScroll);

    /* ==================== KEYBOARD NAVIGATION ==================== */
    document.addEventListener('keydown', function (e) {
        // ESC fecha o menu mobile
        if (e.key === 'Escape') {
            navToggle.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });

    /* ==================== LOG ==================== */
    console.log(
        '%c JosDigital %c Portfólio Profissional ',
        'background: linear-gradient(135deg, #3b82f6, #06b6d4); color: white; padding: 8px 12px; border-radius: 6px 0 0 6px; font-weight: bold;',
        'background: #0a0a0f; color: #a0a0b8; padding: 8px 12px; border-radius: 0 6px 6px 0; border: 1px solid rgba(59,130,246,0.3);'
    );

})();
