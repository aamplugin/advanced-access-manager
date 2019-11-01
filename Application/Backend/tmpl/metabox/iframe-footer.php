<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
        <script type="text/javascript" src="<?php echo $this->prepareIframeWPAssetsURL('js'); ?>"></script>
        <?php do_action('aam_iframe_footer_action'); ?>
    </body>
</html>
<?php }