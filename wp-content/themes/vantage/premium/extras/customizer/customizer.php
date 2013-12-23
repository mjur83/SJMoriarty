<?php

if ( class_exists( 'WP_Customize_Control' ) && !class_exists('SiteOrigin_Customize_Fonts_Control') ) :
/**
 * A customizer control for choosing web fonts
 */
class SiteOrigin_Customize_Fonts_Control extends WP_Customize_Control {
	function __construct( $manager, $id, $args = array() ) {
		// Let other themes and plugins process the web fonts array
		$google_web_fonts = include ( dirname(__FILE__) . '/google-fonts.php' );

		// Add the default fonts
		$choices = array(
			'Helvetica Neue' => 'Helvetica Neue',
			'Lucida Grande' => 'Lucida Grande',
			'Georgia' => 'Georgia',
			'Courier New' => 'Courier New',
		);

		foreach ( $google_web_fonts as $font => $variants ) {
			foreach ( $variants as $variant ) {
				if ( $variant == 'regular' || $variant == 400 ) {
					$choices[ $font ] = $font;
				}
				else {
					$choices[ $font . ':' . $variant ] = $font . ' (' . $variant . ')';
				}
			}
		}

		$args = wp_parse_args( $args, array(
			'type' => 'select',
			'choices' => $choices,
		) );
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Render the control. Renders the control wrapper, then calls $this->render_content().
	 */
	protected function render() {
		$id = 'customize-control-' . str_replace( '[', '-', str_replace( ']', '', $this->id ) );
		$class = 'customize-control customize-control-' . $this->type . ' customize-control-font';

		?>
		<li id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class ); ?>">
			<?php $this->render_content(); ?>
		</li>
	<?php
	}
}
endif;

if(!class_exists('SiteOrigin_Customizer_CSS_Builder ')) :
/**
 * This is used for building custom CSS.
 */
class SiteOrigin_Customizer_CSS_Builder {
	private $css;
	private $fonts;
	private $defaults;

	// These are web safe fonts
	static $web_safe = array(
		'Helvetica Neue' => 'Arial, Helvetica, Geneva, sans-serif',
		'Lucida Grande' => 'Lucida, Verdana, sans-serif',
		'Georgia' => '"Times New Roman", Times, serif',
		'Courier New' => 'Courier, mono',
	);

	function __construct($defaults) {
		$this->css = array();
		$this->google_fonts = array();
		$this->defaults = $defaults;
	}

	/**
	 * Add a raw CSS value
	 *
	 * @param $selector
	 * @param $property
	 * @param $value
	 */
	function add_css( $selector, $property, $value ) {
		if ( empty( $value ) ) return;

		$selector = preg_replace( '/\s+/m', ' ', $selector );

		if ( $property == 'font' ) {
			if ( strpos( $value, ':' ) !== false ) list( $family, $variant ) = explode( ':', $value, 2 );
			else {
				$family = $value;
				$variant = 400;
			}

			if ( !empty( self::$web_safe[ $family ] ) ) $family = '"' . $family . '", ' . self::$web_safe[ $family ];
			else {
				$this->google_fonts[ ] = array( $family, $variant );
				$family = '"' . $family . '"';
			}

			$this->add_css( $selector, 'font-family', $family );
			if ( $variant != 400 ) $this->add_css( $selector, 'font-weight', $variant );

			return;
		}

		if ( empty( $this->css[ $selector ] ) ) $this->css[ $selector ] = array();

		$this->css[ $selector ][ ] = $property . ': ' . $value;
	}

	/**
	 * Echo all the CSS
	 */
	function css() {
		// Start by importing Google web fonts
		$return = '<style type="text/css" id="customizer-css">';

		$import = array();
		foreach ( $this->google_fonts as $font ) {
			$import[ ] = urlencode( $font[ 0 ] ) . ':' . $font[ 1 ];
		}
		$import = array_unique( $import );
		if ( !empty( $import ) ) {
			$return .= '@import url(http://fonts.googleapis.com/css?family=' . implode( '|', $import ) . '); ';
		}

		foreach ( $this->css as $selector => $rules ) {
			$return .= $selector . ' { ' . implode( '; ', $rules ) . ' } ';
		}
		$return .= '</style>';
		return $return;
	}

	/**
	 * Adds a Google web font or web safe font
	 *
	 * @param string $selector The selector we want to use the web font with.
	 * @param string $mod The theme mod where the font is stored.
	 */
	function add_web_font( $selector, $mod ) {
		$font = get_theme_mod($mod);
		if(empty($font) || empty($this->defaults[$mod]) || $font == $this->defaults[$mod]) return;

		if ( empty( $this->css[ $selector ] ) ) $this->css[ $selector ] = array();

		if ( strpos( $font, ':' ) !== false ) list( $family, $variant ) = explode( ':', $font, 2 );
		else {
			$variant = 400;
			$family = $font;
		}

		if ( !empty( self::$web_safe[ $family ] ) ) $family = '"' . $family . '", ' . self::$web_safe[ $family ];
		else {
			$this->google_fonts[ ] = array( $family, $variant );
			$family = '"' . $family . '"';
		}

		$this->add_css( $selector, 'font-family', $family );
		if ( $variant != 400 ) $this->add_css( $selector, 'font-weight', $variant );

		$this->fonts[ ] = $font;

		if ( !empty( $variant ) ) {
			if ( $variant == 'regular' ) $variant = '400';
			$this->css[ $selector ][ ] = 'font-weight: ' . $variant;
		}
	}

	/**
	 * Adds a color property.
	 *
	 * @param $selector
	 * @param $property
	 * @param $mod
	 */
	function add_color($selector, $property, $mod) {
		$color = get_theme_mod($mod);
		if(empty($color) || empty($this->defaults[$mod]) || $color == $this->defaults[$mod]) return;

		$this->add_css($selector, $property, $color);
	}

	/**
	 * Add an image URL
	 *
	 * @param $selector
	 * @param $property
	 * @param $mod
	 */
	function add_image($selector, $property, $mod) {
		$image = get_theme_mod($mod);
		if(!empty($image)) {
			$this->add_css($selector, $property, 'url("'.$image.'")');
		}
	}

	/**
	 * Adds a measurement property
	 *
	 * @param $selector
	 * @param $property
	 * @param $mod
	 * @param string $units
	 */
	function add_measurement($selector, $property, $mod, $units = 'px') {
		$measurement = floatval(get_theme_mod($mod));
		if(empty($measurement) || empty($this->defaults[$mod]) || $measurement == $this->defaults[$mod]) return;

		$this->add_css($selector, $property, $measurement.$units);
	}


}
endif;

if(!class_exists('SiteOrigin_Customizer_Helper')) :
class SiteOrigin_Customizer_Helper {
	private $theme;
	private $settings;
	private $sections;
	private $defaults;

	function __construct($settings = array(), $sections = array(), $theme){
		// Give child themes a chance to filter this.
		$settings = apply_filters($theme.'_siteorigin_theme_customizer_settings', $settings);
		$sections = apply_filters($theme.'_siteorigin_theme_customizer_sections', $sections);

		$this->theme = $theme;
		$this->sections = $sections;

		$this->defaults = array();
		$this->settings = array();

		foreach($settings as $section_id => $section_settings) {
			foreach($section_settings as $id => $setting) {
				$setting['section'] = $section_id;
				$this->settings[$section_id.'_'.$id] = $setting;

				if(!empty($setting['default'])) {
					$this->defaults[$section_id.'_'.$id] = $setting['default'];
				}
			}
		}

		add_action( 'customize_preview_init', array( $this, 'enqueue' ) );
	}

	/**
	 * @param WP_Customize_Manager $wp_customize
	 */
	function customize_register($wp_customize){
		// Start by registering all the sections
		foreach($this->sections as $id => $section) {
			$wp_customize->add_section($id, $section);
		}

		static $priority = 0;

		// Now add all the settings
		foreach($this->settings as $id => $setting) {
			$wp_customize->add_setting( $id, array(
				'default' => !empty($setting['default']) ? $setting['default'] : '',
			) );

			// Now lets add a control for this setting
			switch($setting['type']) {
				case 'font' :
					$wp_customize->add_control( new SiteOrigin_Customize_Fonts_Control( $wp_customize, $this->theme.'_'.$id, array(
						'label' => $setting['title'],
						'section' => $setting['section'],
						'settings' => $id,
						'priority' => $priority++,
					) ) );
					break;

				case 'color' :
					$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $this->theme.'_'.$id, array(
						'label' => $setting['title'],
						'section' => $setting['section'],
						'settings' => $id,
						'priority' => $priority++,
					) ) );
					if(empty($setting['no_live'])) $wp_customize->get_setting( $id )->transport = 'postMessage';
					break;

				case 'measurement' :
					$wp_customize->add_control( $id, array(
						'label' => $setting['title'],
						'section' => $setting['section'],
						'type'    => 'text',
						'priority' => $priority++,
					) );
					if(empty($setting['no_live'])) $wp_customize->get_setting( $id )->transport = 'postMessage';
					break;

				case 'image' :
					$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $this->theme.'_'.$id, array(
						'label' => $setting['title'],
						'section' => $setting['section'],
						'settings' => $id,
						'priority' => $priority++,
					) ) );
					break;
			}
		}
	}

	/**
	 * @return SiteOrigin_CSS_Builder
	 */
	function create_css_builder(){
		$builder = new SiteOrigin_Customizer_CSS_Builder($this->defaults);

		foreach($this->settings as $id => $setting) {
			if(empty($setting['selector'])) continue;

			foreach((array) $setting['selector'] as $selector) {
				switch($setting['type']) {
					case 'font' :
						$builder->add_web_font($selector, $id);
						break;

					case 'color' :
						foreach((array) $setting['property'] as $property) {
							$builder->add_color($selector, $property, $id);
						}
						break;

					case 'measurement' :
						foreach((array) $setting['property'] as $property) {
							$builder->add_measurement($selector, $property, $id, $setting['unit']);
						}
						break;

					case 'image' :
						foreach((array) $setting['property'] as $property) {
							$builder->add_image($selector, $property, $id);
						}
						break;

				}
			}
		}

		return $builder;
	}

	/**
	 * Enqueue the customizer scripts
	 */
	function enqueue(){
		wp_enqueue_script('siteorigin-customizer-preview',get_template_directory_uri() . '/premium/extras/customizer/js/live-customizer.min.js',array( 'jquery','customize-preview' ),'',true);
		wp_localize_script('siteorigin-customizer-preview', 'customizeSettings', $this->settings);
	}
}
endif;