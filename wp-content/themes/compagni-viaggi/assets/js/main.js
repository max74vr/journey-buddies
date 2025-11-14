/**
 * Main JavaScript file for Compagni di Viaggi theme
 */

(function($) {
    'use strict';

    /**
     * Mobile menu toggle
     */
    function initMobileMenu() {
        const button = document.querySelector('.mobile-menu-toggle');
        const mobileNav = document.querySelector('.mobile-nav');

        if (button && mobileNav) {
            button.addEventListener('click', function() {
                mobileNav.classList.toggle('active');
                this.classList.toggle('active');
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!button.contains(event.target) && !mobileNav.contains(event.target)) {
                    mobileNav.classList.remove('active');
                    button.classList.remove('active');
                }
            });
        }
    }

    /**
     * Smooth scroll for anchor links
     */
    function initSmoothScroll() {
        $('a[href*="#"]:not([href="#"])').on('click', function() {
            if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
                var target = $(this.hash);
                target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 80
                    }, 500);
                    return false;
                }
            }
        });
    }

    /**
     * Add loading state to forms
     */
    function initFormLoading() {
        $('form').on('submit', function() {
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true);
            submitBtn.addClass('loading');

            // Re-enable after 5 seconds as fallback
            setTimeout(function() {
                submitBtn.prop('disabled', false);
                submitBtn.removeClass('loading');
            }, 5000);
        });
    }

    /**
     * Initialize card hover effects
     */
    function initCardEffects() {
        $('.card').hover(
            function() {
                $(this).addClass('hover');
            },
            function() {
                $(this).removeClass('hover');
            }
        );
    }

    /**
     * Lazy load images
     */
    function initLazyLoad() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img.lazy').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Search form enhancements
     */
    function initSearchEnhancements() {
        const searchInputs = document.querySelectorAll('input[type="search"], input[name="s"]');
        searchInputs.forEach(input => {
            // Add clear button
            if (input.value) {
                addClearButton(input);
            }

            input.addEventListener('input', function() {
                if (this.value) {
                    addClearButton(this);
                } else {
                    removeClearButton(this);
                }
            });
        });

        function addClearButton(input) {
            if (input.nextElementSibling && input.nextElementSibling.classList.contains('clear-search')) {
                return;
            }

            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'clear-search';
            clearBtn.innerHTML = '×';
            clearBtn.addEventListener('click', function() {
                input.value = '';
                input.focus();
                removeClearButton(input);
            });

            input.parentNode.style.position = 'relative';
            input.parentNode.appendChild(clearBtn);
        }

        function removeClearButton(input) {
            const clearBtn = input.nextElementSibling;
            if (clearBtn && clearBtn.classList.contains('clear-search')) {
                clearBtn.remove();
            }
        }
    }

    /**
     * Add animation on scroll
     */
    function initScrollAnimations() {
        const elements = document.querySelectorAll('.section');

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, {
                threshold: 0.1
            });

            elements.forEach(el => {
                observer.observe(el);
            });
        }
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            $(this).on('mouseenter', function() {
                const text = $(this).data('tooltip');
                const tooltip = $('<div class="tooltip">' + text + '</div>');

                $('body').append(tooltip);

                const pos = $(this).offset();
                tooltip.css({
                    top: pos.top - tooltip.outerHeight() - 10,
                    left: pos.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
                });

                setTimeout(() => tooltip.addClass('show'), 10);
            });

            $(this).on('mouseleave', function() {
                $('.tooltip').remove();
            });
        });
    }

    /**
     * Format numbers
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initMobileMenu();
        initSmoothScroll();
        initFormLoading();
        initCardEffects();
        initLazyLoad();
        initSearchEnhancements();
        initScrollAnimations();
        initTooltips();

        // Add loaded class to body
        $('body').addClass('loaded');
    });

    /**
     * Window load event
     */
    $(window).on('load', function() {
        // Remove loading screen if exists
        $('.loading-screen').fadeOut(300);
    });

    /**
     * Window scroll event
     */
    let lastScroll = 0;
    $(window).on('scroll', function() {
        const currentScroll = $(this).scrollTop();

        // Shrink header on scroll
        if (currentScroll > 100) {
            $('.site-header').addClass('scrolled');
        } else {
            $('.site-header').removeClass('scrolled');
        }

        // Hide/show header on scroll direction
        if (currentScroll > lastScroll && currentScroll > 500) {
            $('.site-header').addClass('header-hidden');
        } else {
            $('.site-header').removeClass('header-hidden');
        }

        lastScroll = currentScroll;
    });

})(jQuery);
