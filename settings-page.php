<?php
/**
 * View file of the settings page for this plugin
 *
 * @var string $form_message
 * @var string $footer_html
 * @var string $log_html
 * @var string $form_url
 * @var bool $plugin_writeable
 */
?>
<div class="wrap" id="gmail-login-settings">

    <?php if( !empty($form_message) ): ?>
    <div class="message updated" id="message">
        <p><?php echo $form_message ?></p>
    </div>
    <?php endif; ?>

    <div id="icon-options-general" class="icon32"><br></div>
    <h2>Simple Gmail Login</h2>

    <div class="inner-wrap">
        <?php if( $plugin_writeable ): ?>

        <?php if( empty($log_html) ): ?>
            <p>
                <strong>- Log is empty</strong>
            </p>
            <?php else: ?>
            <?php echo $log_html ?>
            <p>
                <a href="<?php echo $form_url ?>&amp;clear-log=1" class="button"
                   onclick="return confirm('Are you sure you want to clear the log?');">
                    Clear log
                </a>
            </p>
            <?php endif; ?>

        <h1>Login footer</h1>
        <p>Here you can add HTML that will be added below the login form.</p>
        <form action="<?php echo $form_url ?>" method="post">
            <p>
                <textarea name="footer_html"><?php echo htmlentities($footer_html) ?></textarea>
            </p>
            <p>
                <input type="submit" name="send" value="Save" class="button-primary" />
            </p>
        </form>

        <?php if( !empty($footer_html) ): ?>
            <p><em>Preview:</em></p>
            <div class="footer-preview">
                <?php echo $footer_html ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
        <p class="error-msg">
            The plugin directory (<?php echo dirname(__FILE__) ?>) has to be writeable
            in order to see this page
        </p>
        <?php endif; ?>

        <div id="donation">
            <p>
                <strong>Do you find this plugin helpful?</strong>
                <br />
                Please click on the button to the right and
                give me a donation<br /> to show your support.
            </p>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="5PME86XEFHB8S">
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
                <img alt="" border="0" src="https://www.paypalobjects.com/sv_SE/i/scr/pixel.gif" width="1" height="1">
            </form>
        </div>
    </div>
</div>