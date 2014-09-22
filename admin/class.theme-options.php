<?php
/**
 * Theme options class
 *
 * Based off of: 
 *  http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/
 *  http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-2/
 *  http://alisothegeek.com/2011/04/wordpress-settings-api-tutorial-follow-up/
 *  http://wp.tutsplus.com/tutorials/creative-coding/how-to-integrate-the-wordpress-media-uploader-in-theme-and-plugin-options/
 *  http://www.justinwhall.com/multiple-upload-inputs-in-a-wordpress-theme-options-page/
 *
 * USAGE:
 * 1. Optional. Define a variable $my_sections as a keyed string array. Each element  
 *  	represents a section tab and the strings are the tab titles. The keys are the slugs  
 *  	to use for the settings. If not used, a default "General Settings" section (with slug 'general') 
 *    and "About this Theme" (with slug 'about') are created
 * 2. Define a variable $my_settings as another keyed array. Each element represents a 
 *		unique setting and is defined as follows:
 *
		$my_settings['setting_name'] = array(
				// Setting type. Can be text, textarea, checkbox, select, radio, checkbox, 
				//    heading, description, password, email, url, upload
			'type'    => 'text', 
				// Section this setting belongs to. It should be one of the keys in the $my_sections array
			'section' => 'general',
				// A title for the setting, not used for heading and description types
			'title'   => 'The setting title' ,
				// A description for the the setting
			'desc'    => 'A descriptive text to show next to the setting' ,
				// A default value for the setting. Use integer 0 or 1 for checboxes
			'std'     => 'My default value for the setting',
				// A css class for the setting field
			'class'   => '',
				// An array of choices to use in select and radio types
			'choices' => array(
				'choice1' => 'Choice 1',
				'choice2' => 'Choice 2',
				'choice3' => 'Choice 3'
			)
		);
 *
 * 3. Instantiate the class
 *		$theme_options = new Theme_Options($my_settings,$my_sections); // Custom sections and settings
 *		$theme_options = new Theme_Options($my_settings); // OK with default sections 'general' and 'about'
 *		$theme_options = new Theme_Options(); // Default or hard coded sections and options 
 * 4. Add the following helper function to your theme's functions.php to access the options
 * 
		function get_theme_option( $option ) {
			$theme_data = wp_get_theme();
			$theme_safename = sanitize_title($theme_data);
			$options = get_option( $theme_safename . '-options' );
			if ( isset( $options[$option] ) )
				return $options[$option];
			else
				return false;
		}
 * 5. Acces the data using get_theme_option( $setting_name );
 *
 * NOTES:
 *  If the $my_sections variables isn't defined or passed, a default "General Settings" 
 *  section (slug of 'general') and a default "About this Theme" section (slug = 'about') are
 * 	created. The settings can still be passed and will be placed under these headings.
 *  If no variables are passed, the default sections are created and a dummy setting is also 
 *  created, but there is no real context to them. In such cases, you can hard code the sections
 * 	and settings into this file.
 *
 * @since 2.5
 */
 

class Theme_Options {
	
	private $sections;
	private $checkboxes;
	private $settings;
	private $theme_name; // Theme name
	private $theme_safename; // Theme safe name
	private $css_subfolder = '/css/';
	private $alt_css = array();
	/**
	 * Construct
	 *
	 * @since 1.0
	 */
	public function __construct($my_settings=NULL,$my_sections=NULL) {
		global $pagenow;
		
		// This will keep track of the checkbox options for the validate_settings function.
		$this->checkboxes = array();
		
		$this->get_alt_stylesheets();
		
		// Create the sections
		if(isset($my_sections)){
			// Sections passed on class instantiation 
			$this->sections = $my_sections;
		}else{
			// Default sections 
			$this->sections['general']      = __( 'General Settings' );
			$this->sections['about']      = __( 'About this Theme' );
		}
		
		// Get the settings
		$this->get_settings($my_settings);
		
		// Get theme name to save the options 
		$theme_data = wp_get_theme();
		$this->theme_name = $theme_data;
		$this->theme_safename = sanitize_title($theme_data);
		if(''==$this->theme_safename){
			$this->theme_name = 'My Theme';
			$this->theme_safename = sanitize_title('My Theme');
		}
		add_action( 'admin_menu', array( &$this, 'add_pages' ) );
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
		
		if ( ! get_option( $this->theme_safename .'-options' ) )
			$this->initialize_settings();
		
		
		if ( 'media-upload.php' == $pagenow || 'async-upload.php' == $pagenow ) {  
        // Now we'll replace the 'Insert into Post Button' inside Thickbox  
        add_filter( 'gettext', array(&$this, 'replace_thickbox_text'), 1, 3 ); 
    } 
		
	}
	
	/**
	 * Add options page
	 *
	 * @since 1.0
	 */
	public function add_pages() {
		
		$admin_page = add_theme_page( __( 'Theme Options' ), ucwords($this->theme_name) . __( ' Theme Options' ), 'manage_options', $this->theme_safename . '-options', array( &$this, 'display_page' ) );
		
		add_action( 'admin_print_scripts-' . $admin_page, array( &$this, 'scripts' ) );
		add_action( 'admin_print_styles-' . $admin_page, array( &$this, 'styles' ) );
		
	}
	
	/**
	 * Create settings field
	 *
	 * @since 1.0
	 */
	public function create_setting( $args = array() ) {
		
		$defaults = array(
			'id'      => 'default_field',
			'title'   => __( 'Default Field' ),
			'desc'    => __( 'This is a default description.' ),
			'std'     => '',
			'type'    => 'text',
			'section' => 'general',
			'choices' => array(),
			'class'   => ''
		);
			
		extract( wp_parse_args( $args, $defaults ) );
		
		$field_args = array(
			'type'      => $type,
			'id'        => $id,
			'desc'      => $desc,
			'std'       => $std,
			'choices'   => $choices,
			'label_for' => $id,
			'class'     => $class
		);
		
		if ( $type == 'checkbox' )
			$this->checkboxes[] = $id;
		
		add_settings_field( $id, $title, array( $this, 'display_setting' ), $this->theme_safename . '-options', $section, $field_args );
	}
	
	/**
	 * Display options page
	 *
	 * @since 1.0
	 */
	public function display_page() {
		$name = $this->theme_safename;
		
		echo '<div class="wrap">
	<div class="icon32" id="icon-options-general"></div>
	<h2>' . ucwords($this->theme_name).  __( ' Theme Options' ) . '</h2>';
	
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true )
			echo '<div class="updated fade"><p>' . __( 'Theme options updated.' ) . '</p></div>';
		
		echo '<form action="options.php" method="post">';
	
		settings_fields( $name . '-options' );
		echo '<div class="ui-tabs">
			<ul class="ui-tabs-nav">';
		
		foreach ( $this->sections as $section_slug => $section )
			echo '<li><a href="#' . $section_slug . '">' . $section . '</a></li>';
		
		echo '</ul>';
		do_settings_sections( $_GET['page'] );
		
		echo '</div>
		
		<p class="submit">
			<input name="reset" type="submit" class="reset-button button-secondary" value="'. __( 'Restore Defaults' ) .'" onclick="return confirm(\'Click OK to reset. Any theme settings will be lost!\');" />
			<input name="submit" type="submit" class="button-primary" value="' . __( 'Save Changes' ) . '" />
		</p>
		
	</form>';
	
	echo '<script type="text/javascript">
		jQuery(document).ready(function($) {
			var sections = [];';
			
			foreach ( $this->sections as $section_slug => $section )
				echo "sections['$section'] = '$section_slug';";
			
			echo 'var wrapped = $(".wrap h3").wrap("<div class=\"ui-tabs-panel\">");
			wrapped.each(function() {
				$(this).parent().append($(this).parent().nextUntil("div.ui-tabs-panel"));
			});
			
			$(".ui-tabs-panel").each(function(index) {
				$(this).attr("id", sections[$(this).children("h3").text()]);
				if (index > 0)
					$(this).addClass("ui-tabs-hide");
			});
			
			$(".ui-tabs").tabs({
				fx: { opacity: "toggle", duration: "fast" }
			});
			
			$("input[type=text],  input[type=url], input[type=email], textarea").each(function() {
				if ($(this).val() == $(this).attr("placeholder") || $(this).val() == "")
					$(this).css("color", "#999");
			});
			
			$("input[type=text], input[type=url], input[type=email], textarea").focus(function() {
				if ($(this).val() == $(this).attr("placeholder") || $(this).val() == "") {
					$(this).val("");
					$(this).css("color", "#000");
				}
			}).blur(function() {
				if ($(this).val() == "" || $(this).val() == $(this).attr("placeholder")) {
					$(this).val($(this).attr("placeholder"));
					$(this).css("color", "#999");
				}
			});
			
			$(".wrap h3, .wrap table").show();
			
			// Browser compatibility
			if ($.browser.mozilla) 
			         $("form").attr("autocomplete", "off");
							 
			// Uploader stuff
			$(".upload_button").click(function() {
         targetfield = $(this).prev(".upload-url");
         tb_show("Upload an image", "media-upload.php?referer='. $this->theme_safename . '-options&type=image&TB_iframe=true");
         return false;
			});
			window.send_to_editor = function(html) {
         imgurl = $("img",html).attr("src");
         $(targetfield).val(imgurl);
				 tb_remove();
				 $(targetfield).nextAll("div:first").find(".upload-preview").attr("src",imgurl);
			}
			
		});
	</script>
</div>';
		
	}
	
	/**
	 * Description for section
	 *
	 * @since 1.0
	 */
	public function display_section() {
		// code
	}
	
	/**
	 * Description for About section
	 *
	 * @since 1.0
	 */
	public function display_about_section() {
		$theme_data = wp_get_theme();
		
		echo '<div style="padding:20px;">';
		if(file_exists(STYLESHEETPATH .'/screenshot.jpg'))
			echo '<img src="'.get_stylesheet_directory_uri(). '/screenshot.jpg" /><br/>';
		elseif(file_exists(STYLESHEETPATH .'/screenshot.png'))
			echo '<img src="'.get_stylesheet_directory_uri(). '/screenshot.png" /><br/>';
		
		echo '<h4>'.$theme_data.' Theme'; 
		if ($theme_data->get('Version'))
			echo ', Version ' . $theme_data->get('Version') .'</h4>';
		else
			echo '</h4>';
		if ($theme_data->get('Description'))
			echo '<span class="description">'.$theme_data->get('Description').'</span><br/>';
		if ($theme_data->get('Author'))
			echo '<span class="description">&copy; 2013 '.$theme_data->get('Author').'</span>';
			
		echo '</div>';
	}
	
	/**
	 * HTML output for text field
	 *
	 * @since 1.0
	 */
	public function display_setting( $args = array() ) {
		
		extract( $args );

		$name = $this->theme_safename;

		$options = get_option( $name . '-options' );

		if ( ! isset( $options[$id] ) )
			$options[$id] = $std;
		elseif ( ! isset( $options[$id] ) )
			$options[$id] = 0;

		$field_class = '';
		if ( $class != '' )
			$field_class = ' ' . $class;
		
		switch ( $type ) {
			
			case 'description':
				echo  '<span class="description'. $field_class . '">' . $desc . '</span>' ;
				break;
			
			case 'heading':
				echo '</td></tr><tr valign="top"><td colspan="2"><h4>' . $desc . '</h4>';
				break;
			
			case 'checkbox':
				
				echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="'. $name . '-options[' . $id . ']" value="1" ' . checked( $options[$id], 1, false ) . ' /> <label for="' . $id . '">' . $desc .'</label>';
				
				break;
			
			case 'select':
				echo '<select class="select' . $field_class . '" name="'. $name . '-options[' . $id . ']">';
				
				foreach ( $choices as $value => $label )
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $options[$id], $value, false ) . '>' . $label . '</option>';
				
				echo '</select>';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'radio':
				$i = 0;
				foreach ( $choices as $value => $label ) {
					echo '<input class="radio' . $field_class . '" type="radio" name="'. $name . '-options[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked( $options[$id], $value, false ) . '> <label for="' . $id . $i . '">' . $label . '</label>';
					if ( $i < count( $options ) - 1 )
						echo '<br />';
					$i++;
				}
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'textarea':
				echo '<textarea class="' . $field_class . '" id="' . $id . '" name="'. $name . '-options[' . $id . ']" placeholder="' . $std . '" rows="5" cols="30">' . wp_htmledit_pre( $options[$id] ) . '</textarea>';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'password':
				echo '<input class="regular-text' . $field_class . '" type="password" id="' . $id . '" name="'. $name . '-options[' . $id . ']" value="' . esc_attr( $options[$id] ) . '" />';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'email':
				echo '<input class="regular-text' . $field_class . '" type="email" id="' . $id . '" name="'. $name . '-options[' . $id . ']" value="' . esc_attr( $options[$id] ) . '" />';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'url':
				echo '<input class="regular-text' . $field_class . '" type="url" id="' . $id . '" name="'. $name . '-options[' . $id . ']" value="' . esc_url( $options[$id] ) . '" />';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
				
			case 'upload':
				echo '<input id="' . $id . '" class="upload-url' . $field_class . '" type="text" name="'. $name . '-options[' . $id . ']" value="' . esc_url( $options[$id] ) . '" /> 
				<input id="'. $id . '-upload_button" class="upload_button button-secondary" type="button" name="' . $name . '-upload_button" value="Upload" />';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				echo '<br /><div style="margin:10px;"><img id="' . $id . '-preview" class="upload-preview" style="max-width:100%;" src="' . esc_url( $options[$id] ) . '" /></div>';
				
				
				break;
				
			case 'styles':
				$choices = $this->alt_css;
				echo '<select class="select' . $field_class . '" name="'. $name . '-options[' . $id . ']">';
				
				foreach ( $choices as $value => $label )
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $options[$id], $value, false ) . '>' . $label . '</option>';
				
				echo '</select>';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
			
			case 'text':
			default:
		 		echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="'. $name . '-options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';
		 		
		 		if ( $desc != '' )
		 			echo '<br /><span class="description">' . $desc . '</span>';
		 		
		 		break;
		 	
		}
		
	}
	
	/**
	 * Settings and defaults
	 * 
	 * @since 1.0
	 */
	public function get_settings($my_settings=NULL) {
		if(isset($my_settings)){
			/* The settings are created outside and passed to the class constructor */
			$this->settings = $my_settings;
			return;
		} else {
			/* Hard coded settings  */
			/* General Settings
			===========================================*/
			if(isset($this->settings)) return;
			$this->settings['dummy'] = array(
				'title'   => __( 'Dummy Setting' ),
				'desc'    => __( 'Dummy setting to demo functionality.' ),
				'std'     => '',
				'type'    => 'text',
				'section' => 'general'
			);
			
		}
	}
	
	/**
	 * Initialize settings to their default values
	 * 
	 * @since 1.0
	 */
	public function initialize_settings() {
		
		$default_settings = array();
		foreach ( $this->settings as $id => $setting ) {
			if ( $setting['type'] != 'heading' && $setting['type'] != 'description' )
				$default_settings[$id] = $setting['std'];
		}
		
		update_option( $this->theme_safename . '-options', $default_settings );
		
	}
	
	/**
	* Register settings
	*
	* @since 1.0
	*/
	public function register_settings() {
		
		register_setting( $this->theme_safename . '-options', $this->theme_safename . '-options', array ( &$this, 'validate_settings' ) );
		
		foreach ( $this->sections as $slug => $title ) {
			if ( $slug == 'about' )
				add_settings_section( $slug, $title, array( &$this, 'display_about_section' ), $this->theme_safename . '-options' );
			else
				add_settings_section( $slug, $title, array( &$this, 'display_section' ), $this->theme_safename . '-options' );
		}
		
		$this->get_settings();
		
		foreach ( $this->settings as $id => $setting ) {
			$setting['id'] = $id;
			$this->create_setting( $setting );
		}
		
	}
	
	/**
	* Hook for changing the text in the media uploader button
	*
	* @since 1.0
	*/
	public function replace_thickbox_text($translated_text, $text, $domain) { 
    if ('Insert into Post' == $text) { 
        $referer = strpos( wp_get_referer(), $this->theme_safename . '-options' ); 
        if ( $referer != '' ) { 
            return __('Use this image');  
        }  
    }  
    return $translated_text;  
}  
	
	/**
	* jQuery Tabs
	*
	* @since 1.0
	*/
	public function scripts() {
		
		wp_enqueue_script( 'jquery-ui-tabs' );
		//Media Uploader Scripts
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
	}
	
	/**
	* Styling for the theme options page
	*
	* @since 1.0
	*/
	public function styles() {
		$file=realpath(dirname(__FILE__));
		$templ=realpath(get_stylesheet_directory());
		$subf=trailingslashit(str_replace('\\','/',str_replace($templ,'',$file)));
		$uri=get_stylesheet_directory_uri().$subf;
		
		wp_register_style( $this->theme_safename .'-admin', $uri . $this->theme_safename .'-options.css' );
		wp_enqueue_style( $this->theme_safename .'-admin' );
		wp_enqueue_style('thickbox');
	}
	
	/**
	* Validate settings
	*
	* @since 1.0
	*/
	public function validate_settings( $input ) {
		
		if ( ! isset( $_POST['reset']) ) {
			$options = get_option( $this->theme_safename .'-options' );
			
			foreach ( $this->checkboxes as $id ) {
				if ( isset( $options[$id] ) && ! isset( $input[$id] ) )
					unset( $options[$id] );
			}
			
			return $input;
		}
		return false;
		
	}
	
	/**
	* Generate a dropdown of custom styles
	*
	* @since 2.5
	*/
	public function get_alt_stylesheets(){
		// For maximum compatibility, the CSS filename should not have any spaces
		$css_path = trailingslashit(get_stylesheet_directory() . $this->css_subfolder);
		$css_path_url = trailingslashit(get_stylesheet_directory_uri() . $this->css_subfolder);
		$this->alt_css = array();
		if ( is_dir($css_path) ) {
			if ($css_dir = opendir($css_path) ) { 
				while ( ($css_file = readdir($css_dir)) !== false ) {
					if(stristr($css_file, ".css") !== false) {
						$this->alt_css[$css_path_url.$css_file] = $css_file;
					}
				}		
			}
		}
	}
}



?>