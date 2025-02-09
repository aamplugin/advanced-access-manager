<?php
/**
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/327
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 * @since 6.0.0  Initial implementation of the template
 *
 * @version 6.9.34
 * */
?>

<?php if (defined('AAM_KEY')) { ?>
    <?php global $wpdb; ?>

    <div class="aam-feature" id="welcome-content">
        <div class="row">
            <div class="col-xs-12">
                <span class="aam-common-use-cases aam-mt-2"><?php echo __('Introduction', AAM_KEY); ?></span>

                <div class="panel-group" id="intro-block" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="video-1-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#video-1" aria-controls="video-1">
                                    <?php echo __('Navigating the AAM UI: A Quick Guide', AAM_KEY); ?> <small class="aam-menu-capability">2 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="video-1" class="panel-collapse collapse" role="tabpanel" aria-labelledby="video-1-heading">
                            <div class="panel-body">
                                <iframe
                                    width="100%"
                                    height="315"
                                    src="https://www.youtube.com/embed/qVJDolt31pY?si=xlbnrgPRYyjmxEs1"
                                    title="Navigating the AAM UI: A Quick Guide"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>

                                <a href="https://www.youtube.com/watch?v=qVJDolt31pY&list=PLged38T3QQC0lAMQ2Ov3w1KVah96233eE" target="_blank" class="btn btn-danger btn-block aam-mt-1">Watch on YouTube →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="video-2-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#video-2" aria-controls="video-2">
                                    <?php echo __('Fine-Tuning AAM for Optimal Performance', AAM_KEY); ?> <small class="aam-menu-capability">3 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="video-2" class="panel-collapse collapse" role="tabpanel" aria-labelledby="video-2-heading">
                            <div class="panel-body">
                                <iframe
                                    width="100%"
                                    height="315"
                                    src="https://www.youtube.com/embed/JVbevBWdJjo?si=lCP4VLNTyyMh5OoS"
                                    title="Fine-Tuning AAM for Optimal Performance"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>

                                <a href="https://www.youtube.com/watch?v=JVbevBWdJjo&list=PLged38T3QQC0lAMQ2Ov3w1KVah96233eE&index=2" target="_blank" class="btn btn-danger btn-block aam-mt-1">Watch on YouTube →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="video-3-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#video-3" aria-controls="video-3">
                                    <?php echo __('Understanding Access Levels in AAM', AAM_KEY); ?> <small class="aam-menu-capability">2 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="video-3" class="panel-collapse collapse" role="tabpanel" aria-labelledby="video-3-heading">
                            <div class="panel-body">
                                <iframe
                                    width="100%"
                                    height="315"
                                    src="https://www.youtube.com/embed/mKQ1TTICtE4?si=53byc76WzhH9Lm9K"
                                    title="Understanding Access Levels in AAM"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>

                                <a href="https://www.youtube.com/watch?v=mKQ1TTICtE4&list=PLged38T3QQC0lAMQ2Ov3w1KVah96233eE&index=3" target="_blank" class="btn btn-danger btn-block aam-mt-1">Watch on YouTube →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="video-4-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#video-4" aria-controls="video-4">
                                    <?php echo __('Understanding Access Controls & Preferences Inheritance', AAM_KEY); ?> <small class="aam-menu-capability">3 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="video-4" class="panel-collapse collapse" role="tabpanel" aria-labelledby="video-4-heading">
                            <div class="panel-body">
                                <iframe
                                    width="100%"
                                    height="315"
                                    src="https://www.youtube.com/embed/RrZcK9HkZHI?si=UBu0XqAViTfKBpOZ"
                                    title="Understanding Access Controls & Preferences Inheritance"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>

                                <a href="https://www.youtube.com/watch?v=RrZcK9HkZHI&list=PLged38T3QQC0lAMQ2Ov3w1KVah96233eE&index=4" target="_blank" class="btn btn-danger btn-block aam-mt-1">Watch on YouTube →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="video-5-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#video-5" aria-controls="video-5">
                                    <?php echo __('Master WordPress Roles & Capabilities With AAM', AAM_KEY); ?> <small class="aam-menu-capability">4 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="video-5" class="panel-collapse collapse" role="tabpanel" aria-labelledby="video-5-heading">
                            <div class="panel-body">
                                <iframe
                                    width="100%"
                                    height="315"
                                    src="https://www.youtube.com/embed/bqBLSqUjHV0?si=d6nkVrlFh_gwlCZi"
                                    title="Master WordPress Roles & Capabilities With AAM"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>

                                <a href="https://www.youtube.com/watch?v=bqBLSqUjHV0&list=PLged38T3QQC0lAMQ2Ov3w1KVah96233eE&index=5" target="_blank" class="btn btn-danger btn-block aam-mt-1">Watch on YouTube →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="video-6-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#video-6" aria-controls="video-6">
                                    <?php echo __('Manage User Accounts with AAM | WordPress Access Control', AAM_KEY); ?> <small class="aam-menu-capability">3 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="video-6" class="panel-collapse collapse" role="tabpanel" aria-labelledby="video-6-heading">
                            <div class="panel-body">
                                <iframe
                                    width="100%"
                                    height="315"
                                    src="https://www.youtube.com/embed/QUj6BY_ELZU?si=AKv7M04cV_k48VKs"
                                    title="Manage User Accounts with AAM | WordPress Access Control"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>

                                <a href="https://www.youtube.com/watch?v=QUj6BY_ELZU&list=PLged38T3QQC0lAMQ2Ov3w1KVah96233eE&index=6" target="_blank" class="btn btn-danger btn-block aam-mt-1">Watch on YouTube →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="video-7-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#video-7" aria-controls="video-7">
                                    <?php echo __('Mastering Access Control in WordPress: Back-End Menu, Admin Toolbar, Metaboxes & Widgets', AAM_KEY); ?> <small class="aam-menu-capability">9 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="video-7" class="panel-collapse collapse" role="tabpanel" aria-labelledby="video-7-heading">
                            <div class="panel-body">
                                <iframe
                                    width="100%"
                                    height="315"
                                    src="https://www.youtube.com/embed/oF79nPvc-7Q?si=ioEyyh84_nxhYyFL"
                                    title="Mastering Access Control in WordPress: Back-end Menu, Admin Toolbar, Metaboxes & Widgets"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>

                                <a href="https://www.youtube.com/watch?v=oF79nPvc-7Q&list=PLged38T3QQC0lAMQ2Ov3w1KVah96233eE&index=7" target="_blank" class="btn btn-danger btn-block aam-mt-1">Watch on YouTube →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="video-8-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#video-8" aria-controls="video-8">
                                    <?php echo __('Mastering WordPress Content Access with Posts and Terms in AAM', AAM_KEY); ?> <small class="aam-menu-capability">9 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="video-8" class="panel-collapse collapse" role="tabpanel" aria-labelledby="video-8-heading">
                            <div class="panel-body">
                                <iframe
                                    width="100%"
                                    height="315"
                                    src="https://www.youtube.com/embed/iN_X1h6vmSo?si=VCyg2x_r5YF2ON2c"
                                    title="Mastering WordPress Content Access with Posts and Terms in AAM"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>

                                <a href="https://www.youtube.com/watch?v=iN_X1h6vmSo&list=PLged38T3QQC0lAMQ2Ov3w1KVah96233eE&index=8" target="_blank" class="btn btn-danger btn-block aam-mt-1">Watch on YouTube →</a>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="video-9-heading">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#intro-block" href="#video-9" aria-controls="video-9">
                                    <?php echo __('Understanding AAM Redirects: Access Denied, Login, Logout & 404 Redirects', AAM_KEY); ?> <small class="aam-menu-capability">5 mins</small>
                                </a>
                            </h4>
                        </div>

                        <div id="video-9" class="panel-collapse collapse" role="tabpanel" aria-labelledby="video-9-heading">
                            <div class="panel-body">
                                <iframe
                                    width="100%"
                                    height="315"
                                    src="https://www.youtube.com/embed/rg6-nt6-o7U?si=UrbA8asRLT0vr4GC"
                                    title="Understanding AAM Redirects: Access Denied, Login, Logout & 404 Redirects"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen
                                ></iframe>

                                <a href="https://www.youtube.com/watch?v=rg6-nt6-o7U&list=PLged38T3QQC0lAMQ2Ov3w1KVah96233eE&index=9" target="_blank" class="btn btn-danger btn-block aam-mt-1">Watch on YouTube →</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }