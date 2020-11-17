<?php
/**
 * Supporting of latest SFWD version
 *
 * @since 1.0.1
 */

class LD_Certificate_Plus_Pdf_Modifications {

	/**
	 * Modifications initialization
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_filter( 'learndash_certificate_content', array( $this, 'update_cert_content' ), 10, 2 );
		add_filter( 'learndash_courseinfo', array( $this, 'add_show_attributes' ), 10, 2 );
		add_action( 'learndash_certification_after', array( $this, 'add_background_pages' ), 10, 2 );

	}

	/**
	 * Clear old content
	 *
	 * @since 1.0.1
	 *
	 * @param $cert_content
	 * @param $cert_id
	 *
	 * @return string
	 */
	public function update_cert_content( $cert_content, $cert_id ) {

		$cert_content = '';

		return $cert_content;
	}

	public function add_background_pages( $pdf, $cert_id ) {

		$pdf->setPrintHeader( false );
		$bMargin         = $pdf->getBreakMargin();
		$auto_page_break = $pdf->getAutoPageBreak();
		$pdf->SetAutoPageBreak( false, 0 );
		$pageH = $pdf->getPageHeight();
		$pageW = $pdf->getPageWidth();

		if ( class_exists( 'ACF' ) ) {
			$certificate_page_1 = get_field( 'certificate_page_1', $cert_id );
			$certificate_page_2 = get_field( 'certificate_page_2', $cert_id );
			$certificate_page_3 = get_field( 'certificate_page_3', $cert_id );
			$cert_content       = $this->get_cert_content( $cert_id );

			if ( ! empty( $certificate_page_1 ) ) {
				$pdf->Image( $certificate_page_1, '0', '0', $pageW, $pageH );
				$pdf->writeHTML( isset( $cert_content[0] ) ? $cert_content[0] : '', true, false, true, false, '' );
			}
			if ( ! empty( $certificate_page_2 ) ) {
				$pdf->AddPage();
				$pdf->Image( $certificate_page_2, '0', '0', $pageW, $pageH );
				$pdf->writeHTML( isset( $cert_content[1] ) ? $cert_content[1] : '', true, false, true, false, '' );
			}
			if ( ! empty( $certificate_page_3 ) ) {
				$pdf->AddPage();
				$pdf->Image( $certificate_page_3, '0', '0', $pageW, $pageH );
				$pdf->writeHTML( isset( $cert_content[2] ) ? $cert_content[2] : '', true, false, true, false, '' );
			}
		}

		if ( ( ! isset( $certificate_page_1 ) || empty( $certificate_page_1 ) )
		     && ( ! isset( $certificate_page_2 ) || empty( $certificate_page_2 ) )
		     && ( ! isset( $certificate_page_3 ) || empty( $certificate_page_3 ) ) ) {
			$img_file = learndash_get_thumb_path( $cert_id );

			if ( $img_file != '' ) {
				//Print the Background
				$pdf->Image( $img_file, $x = '0', $y = '0', $w = $pageW, $h = $pageH, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = array() );

				// restore auto-page-break status
				$pdf->SetAutoPageBreak( $auto_page_break, $bMargin );

				// set the starting point for the page content
				$pdf->setPageMark();
			}

			// Print post
			$pdf->writeHTMLCell( $w = 0, $h = 0, $x = '', $y = '', $formatted_post, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true );
		}

		// Set background
		$pdf->SetFillColor( 255, 255, 127 );
		$pdf->setCellPaddings( 0, 0, 0, 0 );
		// Print signature

	}

	/**
	 * Apply shortcodes and styles for description
	 *
	 * @since 1.0.1
	 *
	 * @param $cert_id
	 *
	 * @return array
	 */
	private function get_cert_content( $cert_id ) {

		$post = get_post( $cert_id );
		if ( ! $post ) {

			return '';
		}

		$cert_content = $post->post_content;

		// Delete shortcode for POST2PDF Converter
		$cert_content = preg_replace( '|\[pdf[^\]]*?\].*?\[/pdf\]|i', '', $cert_content );
		$cert_content = do_shortcode( $cert_content );

		// Convert relative image path to absolute image path
		$cert_content = preg_replace( "/<img([^>]*?)src=['\"]((?!(http:\/\/|https:\/\/|\/))[^'\"]+?)['\"]([^>]*?)>/i", '<img$1src="' . site_url() . '/$2"$4>', $cert_content );

		// Set image align to center
		$cert_content = preg_replace_callback( "/(<img[^>]*?class=['\"][^'\"]*?aligncenter[^'\"]*?['\"][^>]*?>)/i", 'learndash_post2pdf_conv_image_align_center', $cert_content );

		// Add width and height into image tag
		$cert_content = preg_replace_callback( "/(<img[^>]*?src=['\"]((http:\/\/|https:\/\/|\/)[^'\"]*?(jpg|jpeg|gif|png))['\"])([^>]*?>)/i", 'learndash_post2pdf_conv_img_size', $cert_content );

		if ( ( ! defined( 'LEARNDASH_TCPDF_LEGACY_LD322' ) ) || ( true !== LEARNDASH_TCPDF_LEGACY_LD322 ) ) {
			$cert_content = wpautop( $cert_content );
		}

		// For other sourcecode
		$cert_content = preg_replace( '/<pre[^>]*?><code[^>]*?>(.*?)<\/code><\/pre>/is', '<pre style="word-wrap:break-word; color: #406040; background-color: #F1F1F1; border: 1px solid #9F9F9F;">$1</pre>', $cert_content );

		// For blockquote
		$cert_content = preg_replace( '/<blockquote[^>]*?>(.*?)<\/blockquote>/is', '<blockquote style="color: #406040;">$1</blockquote>', $cert_content );

		$cert_content = '<br/><br/>' . $cert_content;

		/**
		 * If the $font variable is not empty we use it to replace all font
		 * definitions. This only affects inline styles within the structure
		 * of the certificate content HTML elements.
		 */
		if ( ! empty( $font ) ) {
			$cert_content = preg_replace( '/(<[^>]*?font-family[^:]*?:)([^;]*?;[^>]*?>)/is', '$1' . $font . ',$2', $cert_content );
		}

		if ( ( defined( 'LEARNDASH_TCPDF_LEGACY_LD322' ) ) && ( true === LEARNDASH_TCPDF_LEGACY_LD322 ) ) {
			$cert_content = preg_replace( '/\n/', '<br/>', $cert_content ); //"\n" should be treated as a next line
		}

		if ( function_exists( 'LearnDash_Settings_Section' ) ) {
			if ( apply_filters( 'learndash_certificate_styles', true, $cert_args['cert_id'] ) ) {
				$certificate_styles = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Certificates_Styles', 'styles' );
				$certificate_styles = preg_replace( '/<style[^>]*?>(.*?)<\/style>/is', '$1', $certificate_styles );
				if ( ! empty( $certificate_styles ) ) {
					$cert_content = '<style>' . $certificate_styles . '</style>' . $cert_content;
				}
			}
		}

		return explode( '--next_page--', $cert_content );
	}

	/**
	 * Extend default show attributes
	 *
	 * @since 1.0.2
	 *
	 * @param $output
	 * @param $shortcode_atts
	 *
	 * @return string
	 */
	public function add_show_attributes( $output, $shortcode_atts ) {

		$fields = array( 'steps_completed', 'steps_total', 'user_course_hours', 'course_id' );
		if ( ! in_array( $shortcode_atts['show'], $fields ) ) {
			return $output;
		}

		$activity_query_args             = array(
			'post_types'     => learndash_get_post_type_slug( 'course' ),
			'activity_types' => 'course',
			'per_page'       => 1,
			'page'           => 1,
		);
		$activity_query_args['user_ids'] = $shortcode_atts['user_id'];
		$activity_query_args['post_ids'] = $shortcode_atts['course_id'];

		$user_courses_reports = learndash_reports_get_activity( $activity_query_args );
		if ( ! empty( $user_courses_reports['results'] ) ) {
			foreach ( $user_courses_reports['results'] as $course_activity ) {

				switch ( $shortcode_atts['show'] ) {

					case 'steps_completed':

						return isset( $course_activity->activity_meta['steps_completed'] ) ? $course_activity->activity_meta['steps_completed'] : 0;
						break;

					case 'steps_total':

						return isset( $course_activity->activity_meta['steps_total'] ) ? $course_activity->activity_meta['steps_total'] : 0;
						break;

					case 'user_course_hours':

						if ( ( property_exists( $course_activity, 'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
							$activity_started = $course_activity->activity_started;
						}
						if ( ( property_exists( $course_activity, 'activity_completed' ) ) && ( ! empty( $course_activity->activity_completed ) ) ) {
							$activity_completed = $course_activity->activity_completed;
						} elseif ( ( property_exists( $course_activity, 'activity_updated' ) ) && ( ! empty( $course_activity->activity_updated ) ) ) {
							$activity_completed = $course_activity->activity_updated;
						}

						if ( ( ! empty( $activity_started ) ) && ( ! empty( $activity_completed ) ) ) {
							$seconds = absint( $activity_completed ) - absint( $activity_started );

							return intval( $seconds / 60 );
						}

						break;

					case 'course_id':

						return isset( $course_activity->activity_course_id ) ? $course_activity->activity_course_id : '';
						break;

				}

			}
		}

		return $output;
	}

}

function ld_centificate_plus_pdf_modifications_runner() {

	$modifications = new LD_Certificate_Plus_Pdf_Modifications;
	$modifications->init();

	return;
}

ld_centificate_plus_pdf_modifications_runner();