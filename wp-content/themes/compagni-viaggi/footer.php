<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <?php
                $footer_logo_id = get_theme_mod('cdv_footer_logo', '');
                if ($footer_logo_id) {
                    $footer_logo_url = wp_get_attachment_url($footer_logo_id);
                    if ($footer_logo_url) {
                        echo '<img src="' . esc_url($footer_logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="footer-logo-img" />';
                    }
                } else {
                    echo '<h3>' . esc_html(get_bloginfo('name')) . '</h3>';
                }
                ?>
                <p><?php bloginfo('description'); ?></p>
                <p>Find travel companions and organize adventures together.</p>
            </div>

            <?php if (is_active_sidebar('footer-1')) : ?>
                <div class="footer-section">
                    <?php dynamic_sidebar('footer-1'); ?>
                </div>
            <?php endif; ?>

            <?php if (is_active_sidebar('footer-2')) : ?>
                <div class="footer-section">
                    <?php dynamic_sidebar('footer-2'); ?>
                </div>
            <?php endif; ?>

            <?php if (is_active_sidebar('footer-3')) : ?>
                <div class="footer-section">
                    <?php dynamic_sidebar('footer-3'); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-bottom">
            <p><?php echo wp_kses_post(get_theme_mod('cdv_footer_copyright', '© ' . date('Y') . ' Journey Buddies. All rights reserved.')); ?></p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
