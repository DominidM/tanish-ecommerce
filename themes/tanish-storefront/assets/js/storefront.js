/**
 * TANISH Storefront - Main JS
 * Header scroll, hero slider, videos, WhatsApp dock, scroll reveal
 * Vanilla JS - no dependencies
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function () {

        /* -------------------------------------------
           HEADER SCROLL (shadow only, no shrink)
        ------------------------------------------- */
        var header = document.querySelector('.site-header');
        if (header) {
            var ticking = false;

            function updateHeader() {
                var scrollY = window.scrollY || window.pageYOffset;
                if (scrollY > 20) {
                    header.classList.add('is-scrolled');
                } else {
                    header.classList.remove('is-scrolled');
                }
                ticking = false;
            }

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    window.requestAnimationFrame(updateHeader);
                    ticking = true;
                }
            }, { passive: true });

            updateHeader();
        }

        /* -------------------------------------------
            HERO CAROUSEL
        ------------------------------------------- */
        var slider = document.querySelector('.tanish-slider');
        if (slider) {
            var slides = slider.querySelectorAll('.tanish-slide');
            var dotsWrap = slider.querySelector('.tanish-dots');
            var prevBtn = slider.querySelector('.tanish-arrow--prev');
            var nextBtn = slider.querySelector('.tanish-arrow--next');
            var current = 0;
            var total = slides.length;
            var autoplayTimer = null;
            var isPaused = false;
            var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function goToSlide(index) {
                if (index < 0) index = total - 1;
                if (index >= total) index = 0;

                slides[current].classList.remove('is-active');
                slides[index].classList.add('is-active');
                current = index;

                // Update dots
                if (dotsWrap) {
                    var dots = dotsWrap.querySelectorAll('button');
                    for (var d = 0; d < dots.length; d++) {
                        dots[d].classList.toggle('is-active', d === current);
                    }
                }
            }

            function nextSlide() {
                goToSlide(current + 1);
            }

            function prevSlide() {
                goToSlide(current - 1);
            }

            function startAutoplay() {
                if (prefersReducedMotion || isPaused || total <= 1) return;
                stopAutoplay();
                autoplayTimer = setInterval(nextSlide, 6000);
            }

            function stopAutoplay() {
                if (autoplayTimer) {
                    clearInterval(autoplayTimer);
                    autoplayTimer = null;
                }
            }

            // Build dots
            if (dotsWrap && total > 1) {
                for (var i = 0; i < total; i++) {
                    var dot = document.createElement('button');
                    dot.setAttribute('aria-label', 'Banner ' + (i + 1));
                    if (i === 0) dot.classList.add('is-active');
                    dot.dataset.index = i;
                    dot.addEventListener('click', function () {
                        goToSlide(parseInt(this.dataset.index));
                        startAutoplay();
                    });
                    dotsWrap.appendChild(dot);
                }
            }

            // Nav buttons
            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    prevSlide();
                    startAutoplay();
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    nextSlide();
                    startAutoplay();
                });
            }

            // Pause on hover
            slider.addEventListener('mouseenter', function () {
                isPaused = true;
                stopAutoplay();
            });
            slider.addEventListener('mouseleave', function () {
                isPaused = false;
                startAutoplay();
            });

            // Keyboard
            slider.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowLeft') { prevSlide(); startAutoplay(); }
                if (e.key === 'ArrowRight') { nextSlide(); startAutoplay(); }
            });

            // Touch swipe
            var touchStartX = 0;
            slider.addEventListener('touchstart', function (e) {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });
            slider.addEventListener('touchend', function (e) {
                var diff = touchStartX - e.changedTouches[0].screenX;
                if (Math.abs(diff) > 50) {
                    if (diff > 0) nextSlide();
                    else prevSlide();
                    startAutoplay();
                }
            }, { passive: true });

            // Init
            if (slides.length > 0) {
                slides[0].classList.add('is-active');
                startAutoplay();
            }
        }

        /* -------------------------------------------
           VIDEO SECTION - Lazy embed + sidebar switch
        ------------------------------------------- */
        var videoSection = document.querySelector('.tanish-videos');
        if (videoSection) {
            var mainPlayer = videoSection.querySelector('.video-main-player');
            var mainVideoUrl = videoSection.querySelector('.video-main-player')?.dataset.videoUrl;
            var sidebarItems = videoSection.querySelectorAll('.video-sidebar-item');

            function getEmbedUrl(url) {
                if (!url) return '';
                // YouTube
                var yt = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                if (yt) return 'https://www.youtube.com/embed/' + yt[1] + '?rel=0';
                // Vimeo
                var vm = url.match(/vimeo\.com\/(\d+)/);
                if (vm) return 'https://player.vimeo.com/video/' + vm[1];
                return '';
            }

            function createIframe(url, poster) {
                var embedUrl = getEmbedUrl(url);
                if (!embedUrl) return null;

                var wrapper = document.createElement('div');
                wrapper.className = 'video-embed-wrapper';
                var iframe = document.createElement('iframe');
                iframe.src = embedUrl;
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
                iframe.setAttribute('allowfullscreen', '');
                iframe.setAttribute('loading', 'lazy');
                wrapper.appendChild(iframe);
                return wrapper;
            }

            // Init main player with lazy loading
            if (mainPlayer && mainVideoUrl) {
                var poster = mainPlayer.querySelector('.video-poster');
                if (poster) {
                    poster.addEventListener('click', function () {
                        var embed = createIframe(mainVideoUrl);
                        if (embed) {
                            mainPlayer.innerHTML = '';
                            mainPlayer.appendChild(embed);
                        }
                    });
                }
            }

            // Sidebar items click -> switch main
            for (var s = 0; s < sidebarItems.length; s++) {
                sidebarItems[s].addEventListener('click', function () {
                    var url = this.dataset.videoUrl;
                    var thumb = this.querySelector('.video-sidebar-thumb img');

                    // Update main player
                    if (mainPlayer && url) {
                        var embed = createIframe(url);
                        if (embed) {
                            mainPlayer.innerHTML = '';
                            mainPlayer.appendChild(embed);
                        }
                    }

                    // Highlight active
                    for (var j = 0; j < sidebarItems.length; j++) {
                        sidebarItems[j].classList.remove('is-active');
                    }
                    this.classList.add('is-active');
                });
            }
        }

        /* -------------------------------------------
           SCROLL REVEAL - IntersectionObserver
        ------------------------------------------- */
        var revealElements = document.querySelectorAll('[data-reveal]');

        if (revealElements.length === 0) return;

        if (typeof IntersectionObserver === 'undefined') {
            for (var r = 0; r < revealElements.length; r++) {
                revealElements[r].classList.add('reveal-visible');
            }
            return;
        }

        var revealObserver = new IntersectionObserver(function (entries) {
            for (var j = 0; j < entries.length; j++) {
                if (entries[j].isIntersecting) {
                    entries[j].target.classList.add('reveal-visible');
                    revealObserver.unobserve(entries[j].target);
                }
            }
        }, {
            root: null,
            rootMargin: '0px 0px -40px 0px',
            threshold: 0.12
        });

        for (var k = 0; k < revealElements.length; k++) {
            revealObserver.observe(revealElements[k]);
        }
    });
})();
