<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Project auto-loader
 *
 * @package AAM
 *
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/244
 * @since 6.9.0  https://github.com/aamplugin/advanced-access-manager/issues/221
 * @since 6.0.0  Initial implementation of the class
 *
 * @version 6.9.12
 */
class AAM_Autoloader
{

    /**
     * PRS HTTP Message package
     *
     * @since 6.9.12
     */
    const PSRHM_BASEDIR = __DIR__ . '/vendor/psr-http-message';

    /**
     * Whip package
     *
     * @since 6.9.12
     */
    const WHIP_BASEDIR = __DIR__ . '/vendor/whip';

    /**
     * Class map
     *
     * @var array
     *
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/244
     * @since 6.9.0  https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.0.0  Initial implementation of the property
     *
     * @access protected
     * @version 6.9.12
     */
    protected static $class_map = array(
        'Psr\Http\Message\MessageInterface'                 => self::PSRHM_BASEDIR . '/MessageInterface.php',
        'Psr\Http\Message\RequestInterface'                 => self::PSRHM_BASEDIR . '/RequestInterface.php',
        'Psr\Http\Message\ResponseInterface'                => self::PSRHM_BASEDIR . '/ResponseInterface.php',
        'Psr\Http\Message\ServerRequestInterface'           => self::PSRHM_BASEDIR . '/ServerRequestInterface.php',
        'Psr\Http\Message\StreamInterface'                  => self::PSRHM_BASEDIR . '/StreamInterface.php',
        'Psr\Http\Message\UploadedFileInterface'            => self::PSRHM_BASEDIR . '/UploadedFileInterface.php',
        'Psr\Http\Message\UriInterface'                     => self::PSRHM_BASEDIR . '/UriInterface.php',
        'Vectorface\Whip\IpRange\IpRange'                   => self::WHIP_BASEDIR . '/IpRange/IpRange.php',
        'Vectorface\Whip\IpRange\IpWhitelist'               => self::WHIP_BASEDIR . '/IpRange/IpWhitelist.php',
        'Vectorface\Whip\IpRange\Ipv4Range'                 => self::WHIP_BASEDIR . '/IpRange/Ipv4Range.php',
        'Vectorface\Whip\IpRange\Ipv6Range'                 => self::WHIP_BASEDIR . '/IpRange/Ipv6Range.php',
        'Vectorface\Whip\Request\Psr7RequestAdapter'        => self::WHIP_BASEDIR . '/Request/Psr7RequestAdapter.php',
        'Vectorface\Whip\Request\RequestAdapter'            => self::WHIP_BASEDIR . '/Request/RequestAdapter.php',
        'Vectorface\Whip\Request\SuperglobalRequestAdapter' => self::WHIP_BASEDIR . '/Request/SuperglobalRequestAdapter.php',
        'Vectorface\Whip\Whip'                              => self::WHIP_BASEDIR . '/Whip.php',
    );

    /**
     * Add new index
     *
     * @param string $class_name
     * @param string $file_path
     *
     * @access public
     * @version 6.0.0
     */
    public static function add($class_name, $file_path)
    {
        self::$class_map[$class_name] = $file_path;
    }

    /**
     * Auto-loader for project Advanced Access Manager
     *
     * Try to load a class if prefix is AAM_
     *
     * @param string $class_name
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function load($class_name)
    {
        if (array_key_exists($class_name, self::$class_map)) {
            $filename = self::$class_map[$class_name];
        } else {
            $chunks = explode('_', $class_name);
            $prefix = array_shift($chunks);

            if ($prefix === 'AAM') {
                $base_path = __DIR__ . '/application';
                $filename  = $base_path . '/' . implode('/', $chunks) . '.php';
            }
        }

        if (!empty($filename) && file_exists($filename)) {
            require($filename);
        }
    }

    /**
     * Register auto-loader
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        spl_autoload_register('AAM_Autoloader::load');
    }

}