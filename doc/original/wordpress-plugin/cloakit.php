<?php
/*
 * Plugin Name: Cloakit
 * Description: Cloakit extended integration.
 * Version: 2.2
 */

require_once __DIR__ . '/cloakit-api.php';

class Cloakit {
    protected const OPTIONS_GROUP = 'cloakit-settings';
    protected const SETTINGS_PAGE = 'cloakit-settings';
    protected const MANAGE_CLOAKIT_CAPABILITY = 'manage_cloakit';

	protected $api;

	public function __construct()
	{
	    $this->api = new CloakitApi();

		register_activation_hook(__FILE__, [$this, 'activation']);
		register_deactivation_hook(__FILE__, [$this, 'deactivation']);

		add_action('admin_menu', [$this, 'settingsPage']);
		add_action('admin_init', [$this, 'setupSections']);
		add_action('admin_init', [$this, 'setupFields']);

		add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'settingsPageLink']);

		add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

		add_action('template_redirect', [$this, 'checkFilter'], 200);
	}

	public function activation() {
		$roles = get_editable_roles();

		foreach ($GLOBALS['wp_roles']->role_objects as $key => $role) {
			if (isset($roles[$key]) && $role->has_cap('manage_options')) {
				$role->add_cap($this::MANAGE_CLOAKIT_CAPABILITY);
			}
		}
	}

	public function deactivation() {
		$roles = get_editable_roles();

		foreach ($GLOBALS['wp_roles']->role_objects as $key => $role) {
			if (isset($roles[$key]) && $role->has_cap($this::MANAGE_CLOAKIT_CAPABILITY)) {
				$role->remove_cap($this::MANAGE_CLOAKIT_CAPABILITY);
			}
		}
	}

	public function enqueueAssets($hook)
	{
	    if (strpos($hook, $this::SETTINGS_PAGE) !== false) {
		    wp_enqueue_style('cloakit-admin-select2', plugins_url('/assets/select2.min.css', __FILE__));
		    wp_enqueue_style('cloakit-admin', plugins_url('/assets/settings-page.css', __FILE__));

		    wp_enqueue_script('cloakit-admin-select2', plugins_url('/assets/select2.full.min.js', __FILE__), 'jquery');
		    wp_enqueue_script('cloakit-admin', plugins_url('/assets/settings-page.js', __FILE__), 'cloakit-admin-select2');
        }
	}

	public function settingsPageLink($links)
	{
		$ulr = admin_url('admin.php?page=' . $this::SETTINGS_PAGE);
		$links[] = '<a href="' . $ulr . '">' . __('Settings') . '</a>';

		return $links;
	}

	public function settingsPage()
	{
		$pageTitle = 'Cloakit Settings';
		$menuTitle = 'Cloakit';
		$capability = $this::MANAGE_CLOAKIT_CAPABILITY;
		$slug = $this::SETTINGS_PAGE;
		$callback = [$this, 'settingsPageContent'];

		add_menu_page($pageTitle, $menuTitle, $capability, $slug, $callback);
	}

	public function settingsPageContent()
	{
		?>
		<div class="wrap">
			<h2><?php print get_admin_page_title(); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields($this::OPTIONS_GROUP);
				do_settings_sections($this::SETTINGS_PAGE);
				submit_button();
				?>
			</form>
		</div>
        <?php
	}

	public function setupSections()
    {
        $sections = [
            [
	            'id' => 'general',
	            'title' => 'General',
            ],
	        [
		        'id' => 'campaign',
		        'title' => 'Campaign',
	        ],
        ];

        foreach ($sections as $section) {
	        add_settings_section($section['id'], $section['title'], false, $this::SETTINGS_PAGE);
        }
	}

	public function setupFields() {
		$fields = [
            [
                'title' => __('Enable on page'),
                'id' => 'enable_on_page',
                'type' => 'multiple_select_page',
                'css' => 'min-width:300px;',
                'class' => 'enable-on-page-select',
                'section' => 'general',
            ],
            [
                'title' => __('Campaign ID'),
                'id' => 'campaign_id',
                'type' => 'text',
                'css' => 'min-width:300px;',
                'section' => 'campaign',
                'placeholder' => 'XXX'
            ],
            [
                'title' => __('Offer page'),
                'id' => 'offer_page',
                'type' => 'text',
                'css' => 'min-width:300px;',
                'section' => 'campaign',
                'placeholder' => '/offer-page',
            ],
			[
				'title' => __('Type'),
				'id' => 'result_behaviour',
				'type' => 'radio_buttons',
                'options' => [
	                'inline' => __('Show Сontent'),
	                'redirect' => __('Redirect'),
                ],
                'default' => 'redirect',
				'section' => 'campaign',
			],
        ];

		$settings = get_option('cloakit', []);
		foreach ($fields as $field) {
			$fieldId = esc_attr($field['id']);

			$callback = $field['callback'] ?? [$this, 'fieldCallback'];
		    $field['default'] = $settings[$fieldId] ?? $field['default'];

			add_settings_field($field['id'], $field['title'], $callback, $this::SETTINGS_PAGE, $field['section'], $field);
		}

		register_setting($this::OPTIONS_GROUP, 'cloakit');
	}

	public static function fieldCallback($arguments)
    {
        $fieldType = esc_attr($arguments['type'] ?? null);
        $fieldId = esc_attr($arguments['id']);
	    $fieldName = 'cloakit[' . $fieldId . ']';

	    $defaultValue = $arguments['default'] ?? null;

	    switch($fieldType) {
		    case 'text':
		    case 'password':
		    case 'datetime':
		    case 'datetime-local':
		    case 'date':
		    case 'month':
		    case 'time':
		    case 'week':
		    case 'number':
		    case 'email':
		    case 'url':
		    case 'tel':
		        ?>
                <input
                        name="<?php print $fieldName; ?>"
                        id="<?php print $fieldId; ?>"
                        type="<?php print $fieldType; ?>"
                        style="<?php echo esc_attr($arguments['css']); ?>"
                        value="<?php print esc_attr($defaultValue); ?>"
                        placeholder="<?php print esc_attr($arguments['placeholder']); ?>"
                />
                <?php
                break;

            case 'radio_buttons':
                $options = $arguments['options'] ?? [];

	            foreach ($options as $value => $title) {
	                $selected = $defaultValue == $value ? 'checked' : null;
	                $buttonId = $fieldId . '_' . $value;
	                ?>
                    <span style="margin-right: 10px">
                        <label for="<?php print $buttonId; ?>">
                            <input
                                    name="<?php print $fieldName; ?>"
                                    id="<?php print $buttonId; ?>"
                                    type="radio"
                                    value="<?php print esc_attr($value); ?>"
                                    placeholder="<?php print esc_attr($arguments['placeholder']); ?>"
                                <?php print $selected; ?>
                            />
                            <?php print $title; ?>
                        </label>
                    </span>
		            <?php
                }
                break;

		    case 'multiple_select_page':
			    $defaultValue = $defaultValue ?? [];
			    $fieldName .= '[]';

			    $posts = get_posts([
				    'numberposts' => 200,
				    'orderby' => 'date',
				    'order' => 'DESC',
				    'post_type' => ['post', 'page'],
				    'suppress_filters' => true,
			    ]);

			    $options = [];

			    foreach($posts as $post){
				    setup_postdata($post);
				    $options[$post->post_type][$post->ID] = $post->post_title;
			    }
			    wp_reset_postdata();

			    $output = "<select style='" . $arguments['css'] . "' class='" . $arguments['class'] . "' name='$fieldName' id='$fieldId' multiple='multiple''>\n";
			    foreach($options as $groupTitle => $elements) {
			        $addGroup = !empty($groupTitle);
			        $output .= "\t<optgroup label='" . ucfirst($groupTitle) . "'>\n";

			        foreach ($elements as $value => $title) {
			            $selected = in_array($value, $defaultValue) ? 'selected' : null;

				        $output .= "\t<option value='$value' $selected>" . $title . "</option>\n";
			        }

			        $output .= "\t</optgroup>\n";
			    }
			    $output .= "</select>\n";

			    print $output;
			    break;
	    }
	}

	public function checkFilter()
    {
        $settings = get_option('cloakit', []);
        $enableOnPageIds = $settings['enable_on_page'] ?? [];

        $campaignId = $settings['campaign_id'] ?? null;
        if (empty($enableOnPageIds) || !$campaignId || !(is_page($enableOnPageIds) || is_single($enableOnPageIds))) {
            return;
        }

	    $data = [
		    'companyId' => $campaignId,
		    'referrerCF' => $_GET["referrerCF"],
		    'urlCF' => $_GET["urlCF"],
		    'QUERY_STRING' => $_SERVER["QUERY_STRING"],
		    'HTTP_REFERER' => $_SERVER["HTTP_REFERER"],
		    'HTTP_USER_AGENT' => $_SERVER["HTTP_USER_AGENT"],
		    'REMOTE_ADDR' => $_SERVER["REMOTE_ADDR"],
		    'HTTP_CF_CONNECTING_IP' => $_SERVER["HTTP_CF_CONNECTING_IP"],
		    'CF_CONNECTING_IP' => $_SERVER["CF_CONNECTING_IP"],
		    'X_FORWARDED_FOR' => $_SERVER["X_FORWARDED_FOR"],
		    'TRUE_CLIENT_IP' => $_SERVER["TRUE_CLIENT_IP"],
	    ];

	    $response = $this->api->check($data);

	    if ($response->isPassed) {
		    $offerPageUrl = $settings['offer_page'] ?? null;
		    if ($offerPageUrl === esc_url_raw($offerPageUrl)) {
		        $offerUrlQuery = parse_url($offerPageUrl, PHP_URL_QUERY);
		        if (empty($offerUrlQuery)) {
			        $requestQuery = parse_url(add_query_arg(null, null), PHP_URL_QUERY);
			        if (!empty($requestQuery)) {
				        $parsedUrl = parse_url($offerPageUrl);
				        $separator = ($parsedUrl['query'] == null) ? '?' : '&';
				        $offerPageUrl .= $separator . $requestQuery;
			        }
                }

			    $baseTag = '<base href="' . $offerPageUrl . '" />';
			    if(empty(parse_url($offerPageUrl, PHP_URL_HOST))) {
			        $offerPageUrl = home_url($offerPageUrl);
				    $baseTag = null;
			    }

			    if ($settings['result_behaviour'] === 'inline') {
				    $arrContextOptions = [
                        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                        'http' => ['header' => 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT']]
                    ];

				    $content = file_get_contents($offerPageUrl, false, stream_context_create($arrContextOptions));

				    $replace = '<head>' . $baseTag;
				    print str_replace('<head>', $replace, $content);
				    exit();
			    } else {
				    wp_redirect($offerPageUrl);
			    }
		    }
	    }
    }
}

new Cloakit();
