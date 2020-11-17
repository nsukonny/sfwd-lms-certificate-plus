<?php
/**
 * Plugin Name: LearnDash LMS Certificate plus
 * Plugin URI: https://nsukonny.ru
 * Description: Add support custom fields for certificate
 * Version: 1.0.2
 * Author: NSukonny
 * Author URI: https://nsukonny.ru
 * Text Domain: sfwd-lms-certificate-plus
 * Domain Path: /languages
 * License: GPLv2 or later
 * Requires at least: 4.6
 * Tested up to: 5.4
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LD_Certificate_Plus' ) ) {

	include_once dirname( __FILE__ ) . '/libraries/ld-certificate-plus.php';

}

/**
 * The main function for returning LD_Certificate_Plus instance
 *
 * @since 1.0.0
 *
 * @return object The one and only true LD_Certificate_Plus instance.
 */
function ld_certificate_plus_runner() {

	return LD_Certificate_Plus::instance();
}

ld_certificate_plus_runner();