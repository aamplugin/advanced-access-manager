<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Importer
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Importer {

    /**
     *
     * @var type 
     */
    protected $input = null;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $blog = null;

    /**
     * 
     * @param type $input
     */
    public function __construct($input = null, $blog = null) {
        if (!is_null($input)) {
            $this->input = json_decode($input);
        }
        
        $this->setBlog(is_null($blog) ? get_current_blog_id() : $blog);
    }
    
    /**
     * 
     * @param type $blog_id
     * @return type
     */
    public function setBlog($blog_id) {
            if ( is_numeric( $blog_id ) ) {
                $blog_id = (int) $blog_id;
            } else {
                $blog = 'http://' . preg_replace( '#^https?://#', '', $blog_id );
                if ( ( !$parsed = parse_url( $blog ) ) || empty( $parsed['host'] ) ) {
                        fwrite( STDERR, "Error: can not determine blog_id from $blog_id\n" );
                        exit();
                }
                if ( empty( $parsed['path'] ) ) {
                        $parsed['path'] = '/';
                }
                $blogs = get_sites( array( 'domain' => $parsed['host'], 'number' => 1, 'path' => $parsed['path'] ) );
                if ( ! $blogs ) {
                        fwrite( STDERR, "Error: Could not find blog\n" );
                        exit();
                }
                $blog = array_shift( $blogs );
                $blog_id = (int) $blog->blog_id;
            }

            if ( function_exists( 'is_multisite' ) ) {
                    if ( is_multisite() )
                            switch_to_blog( $blog_id );
            }

            return $blog_id;
    }

    /**
     * 
     */
    public function dispatch() {
        $this->header();

        switch(AAM_Core_Request::get('step', 0)) {
            case 0:
                $this->greet();
                break;
            
            case 1:
                check_admin_referer('import-upload');
                
                if ($this->handleUpload()) {
                    $this->renderConfirmationStep();
                }
                break;
                
            case 2:
                check_admin_referer( 'import-wordpress' );
                
                $this->id = intval(AAM_Core_Request::post('import_id'));
                $filepath = get_attached_file($this->id);
                $this->import_start( $filepath );
                $this->run();
                $this->import_end();
                break;
        }

        $this->footer();
    }

    // Display import page title
    protected function header() {
        echo '<div class="wrap">';
        echo '<h2>' . __('Import AAM Settings', AAM_KEY) . '</h2>';
    }

    // Close div.wrap
    protected function footer() {
        echo '</div>';
    }

    /**
     * Display introductory text and file upload form
     */
    protected function greet() {
        echo '<div class="narrow">';
        echo '<p>' . __('Howdy! Upload your AAM JSON file and we&#8217;ll import the access settings into this site.', AAM_KEY) . '</p>';
        echo '<p>' . __('Choose a JSON (.json) file to upload, then click Upload file and import.', AAM_KEY) . '</p>';
        wp_import_upload_form('admin.php?import=aam&amp;step=1');
        echo '</div>';
    }
    
    /**
     * 
     * @return boolean
     */
    protected function handleUpload() {
        $result = true;
        $file   = wp_import_handle_upload();

        if ( isset( $file['error'] ) ) {
            echo '<p><strong>' . __( 'Sorry, there has been an error.', AAM_KEY ) . '</strong><br />';
            echo esc_html( $file['error'] ) . '</p>';
            $result = false;
        } else if ( ! file_exists( $file['file'] ) ) {
            echo '<p><strong>' . __( 'Sorry, there has been an error.', AAM_KEY ) . '</strong><br />';
            printf( __( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', AAM_KEY ), esc_html( $file['file'] ) );
            echo '</p>';
            $result = false;
        } else {
            $this->file = $file;
        }
        
        return $result;
    }
    
    /**
     * 
     */
    protected function renderConfirmationStep() {
?>
<form action="<?php echo admin_url('admin.php?import=aam&amp;step=2' ); ?>" method="post">
    <?php wp_nonce_field('import-wordpress' ); ?>
    <input type="hidden" name="import_id" value="<?php echo $this->file['id']; ?>" />

    <p>Please confirm the AAM access settings import. Note! All imported access settings will override existing.</p>

    <p class="submit"><input type="submit" class="button" value="<?php esc_attr_e( 'Submit', AAM_KEY ); ?>" /></p>
</form>
<?php
    }
    
    /**
    * Parses the WXR file and prepares us for the task of processing parsed data
    *
    * @param string $file Path to the WXR file for importing
    */
   protected function import_start( $file ) {
       if ( ! is_file($file) ) {
           echo '<p><strong>' . __( 'Sorry, there has been an error.', AAM_KEY ) . '</strong><br />';
           echo __( 'The file does not exist, please try again.', AAM_KEY ) . '</p>';
           $this->footer();
           die();
       }

       $this->input = json_decode(file_get_contents($file));

       if ( empty( $this->input ) ) {
           echo '<p><strong>' . __( 'Sorry, there has been an error. File content is invalid', AAM_KEY ) . '</strong></p>';
           $this->footer();
           die();
       }
   }

	/**
	 * Performs post-import cleanup of files and the cache
	 */
	function import_end() {
            wp_import_cleanup( $this->id );

            wp_cache_flush();

            echo '<p>' . __( 'All done.', 'wordpress-importer' ) . ' <a href="' . admin_url() . '">' . __( 'Have fun!', 'wordpress-importer' ) . '</a>' . '</p>';
	}

    /**
     * 
     * @return type
     */
    public function run() {
        $response = array('status' => 'success');

        if (version_compare($this->input->version, AAM_Core_API::version()) === 0) {
            foreach ($this->input->dataset as $table => $data) {
                if ($table === '_options') {
                    $this->insertOptions($data);
                } elseif ($table === '_postmeta') {
                    $this->insertPostmeta($data);
                } elseif ($table === '_usermeta') {
                    $this->insertUsermeta($data);
                } else {
                    do_action('aam-import-action', $table, $data);
                }
            }
        } else {
            $response = array(
                'status' => 'failure',
                'reason' => __('Version of exported settings do not match current AAM version', AAM_KEY)
            );
        }
        
        return $response;
    }

    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    protected function insertOptions($data) {
        global $wpdb;
        
        foreach ($data as $key => $value) {
            AAM_Core_API::updateOption(
                preg_replace('/^_/', $wpdb->get_blog_prefix(), $key), 
                $this->prepareValue($value)
            );
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    protected function insertUsermeta($data) {
        global $wpdb;

        foreach ($data as $id => $set) {
            foreach ($set as $key => $value) {
                update_user_meta(
                    $id, 
                    preg_replace('/^_/', $wpdb->get_blog_prefix(), $key), 
                    $this->prepareValue($value)
                );
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    protected function insertPostmeta($data) {
        global $wpdb;
        
        foreach ($data as $id => $set) {
            foreach ($set as $key => $value) {
                update_post_meta(
                    $id, 
                    preg_replace('/^_/', $wpdb->prefix, $key), 
                    $this->prepareValue($value)
                );
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $value
     * @return void
     */
    protected function prepareValue($value) {
        return json_decode(base64_decode($value), true);
    }

}
