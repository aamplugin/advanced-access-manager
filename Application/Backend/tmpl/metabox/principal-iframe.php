<?php /** @version 6.0.0 */ ?>

<?php if (defined('AAM_KEY')) { ?>
    <?php echo $this->loadTemplate(__DIR__ . '/iframe-header.php', $params); ?>

    <?php echo $this->loadTemplate(dirname(__DIR__) . '/page/subject-panel.php', $params); ?>

    <!-- Additional attributes -->
    <input type="hidden" id="aam-policy-id" value="<?php echo $params->policyId; ?>" />

    <?php echo $this->loadTemplate(__DIR__ . '/iframe-footer.php', $params); ?>
<?php }