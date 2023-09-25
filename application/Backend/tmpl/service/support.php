<?php
/**
 * @version 6.9.15
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php global $wpdb; ?>

    <div class="aam-feature" id="support-content">
        <div class="row">
            <div class="col-xs-12">
                <p class="aam-info">
                    <?php echo __('When you encounter any questions or concerns, this is the place to be. We will follow-up with you vie email. Submit your support request here and let us help you to find the best possible solution.', AAM_KEY); ?>
                </p>
            </div>
        </div>

        <div class="form-group">
            <label>Your Name (optional)</label>
            <input type="text" id="support-fullname" class="form-control" placeholder="How should we call you?" />
        </div>

        <div class="form-group">
            <label>Email Address<span class="aam-asterix">*</span></label>
            <input type="email" class="form-control" id="support-email" placeholder="Enter email that we can use to follow-up with you" />
        </div>

        <div class="form-group">
            <label>Message<span class="aam-asterix">*</span></label>
            <textarea class="form-control" id="support-message" rows="7" placeholder="Enter your message..."></textarea>
            <small class="text-muted">
                <span id="message-countdown">700</span> characters
            </small>
        </div>

        <p>
            <a href="#" class="btn btn-primary" id="send-message-btn" disabled>
                <?php echo __('Send the Message', AAM_KEY); ?>
            </a>
        </p>
    </div>
<?php }