/**
 * Social Sharing JavaScript
 */

(function($) {
    'use strict';

    // Share button click tracking
    $('.share-btn').on('click', function(e) {
        const platform = $(this).data('platform');

        // Don't prevent default for email and copy
        if (platform !== 'email' && platform !== 'copy') {
            e.preventDefault();

            const url = $(this).attr('href');
            const width = 600;
            const height = 400;
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;

            window.open(
                url,
                'share-' + platform,
                `width=${width},height=${height},left=${left},top=${top},toolbar=0,location=0,menubar=0`
            );
        }

        // Track share event (if analytics is available)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'share', {
                'method': platform,
                'content_type': 'travel',
                'item_id': window.location.href
            });
        }
    });

    // Copy link functionality
    $('.share-copy').on('click', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const url = $btn.data('url');
        const originalText = $btn.find('span').text();

        // Try modern clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function() {
                showCopiedFeedback($btn, originalText);
            }).catch(function() {
                // Fallback to legacy method
                copyToClipboardLegacy(url, $btn, originalText);
            });
        } else {
            // Fallback to legacy method
            copyToClipboardLegacy(url, $btn, originalText);
        }
    });

    /**
     * Legacy copy to clipboard method
     */
    function copyToClipboardLegacy(text, $btn, originalText) {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();

        try {
            document.execCommand('copy');
            showCopiedFeedback($btn, originalText);
        } catch (err) {
            console.error('Errore durante la copia:', err);
            alert('Errore durante la copia del link');
        }

        $temp.remove();
    }

    /**
     * Show copied feedback
     */
    function showCopiedFeedback($btn, originalText) {
        $btn.addClass('copied');
        $btn.find('span').text('Copiato!');

        // Create tooltip
        const $tooltip = $('<span class="share-copy-tooltip show">Link copiato!</span>');
        $btn.append($tooltip);

        setTimeout(function() {
            $tooltip.removeClass('show');
            setTimeout(function() {
                $tooltip.remove();
            }, 300);
        }, 2000);

        setTimeout(function() {
            $btn.removeClass('copied');
            $btn.find('span').text(originalText);
        }, 2500);
    }

    // Sticky share bar on scroll (mobile only)
    if (window.innerWidth <= 768) {
        const $stickyBar = $('.sticky-share-bar');

        if ($stickyBar.length) {
            let lastScrollTop = 0;
            let scrollTimer = null;

            $(window).on('scroll', function() {
                clearTimeout(scrollTimer);

                scrollTimer = setTimeout(function() {
                    const scrollTop = $(window).scrollTop();
                    const windowHeight = $(window).height();
                    const documentHeight = $(document).height();

                    // Show bar when scrolling down and past 30% of page
                    if (scrollTop > lastScrollTop && scrollTop > documentHeight * 0.3) {
                        $stickyBar.addClass('visible');
                    } else if (scrollTop < lastScrollTop) {
                        // Hide when scrolling up
                        $stickyBar.removeClass('visible');
                    }

                    // Hide when near bottom (to avoid overlap with footer)
                    if (scrollTop + windowHeight >= documentHeight - 100) {
                        $stickyBar.removeClass('visible');
                    }

                    lastScrollTop = scrollTop;
                }, 100);
            });
        }
    }

    // Native share API (if available on mobile)
    if (navigator.share) {
        const $shareButtons = $('.cdv-share-buttons');

        // Add native share button
        const $nativeShareBtn = $(`
            <button class="share-btn share-native" style="background: #007bff; color: white;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/>
                </svg>
                <span>Condividi</span>
            </button>
        `);

        $nativeShareBtn.on('click', function(e) {
            e.preventDefault();

            const url = window.location.href;
            const title = document.title;
            const text = $shareButtons.closest('.travel-share-section').data('description') || '';

            navigator.share({
                title: title,
                text: text,
                url: url
            }).then(function() {
                console.log('Condivisione riuscita');
            }).catch(function(err) {
                console.log('Condivisione annullata:', err);
            });
        });

        // Add native share button as first item (on mobile only)
        if (window.innerWidth <= 768) {
            $shareButtons.prepend($nativeShareBtn);
        }
    }

})(jQuery);
