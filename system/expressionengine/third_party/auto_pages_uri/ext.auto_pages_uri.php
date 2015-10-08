<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auto_pages_uri_ext
{
	public $settings = array();
	public $name = 'Auto Pages URI';
	public $version = '1.0.5';
	public $description = 'Automatically generate the Pages URI when creating a new entry.';
	public $settings_exist = 'y';
	public $docs_url = 'https://github.com/rsanchez/auto_pages_uri';

	/**
	 * constructor
	 *
	 * @access	public
	 * @param	mixed $settings = ''
	 * @return	void
	 */
	public function __construct($settings = '')
	{
		$this->settings = $settings;
	}

	/**
	 * activate_extension
	 *
	 * @access	public
	 * @return	void
	 */
	public function activate_extension()
	{
		$hook_defaults = array(
			'class' => __CLASS__,
			'settings' => serialize(array('channels' => array())),
			'version' => $this->version,
			'enabled' => 'y',
			'priority' => 10
		);

		$hooks[] = array(
			'method' => 'publish_form_channel_preferences',
			'hook' => 'publish_form_channel_preferences',
		);

		$hooks[] = array(
			'method' => 'entry_submission_ready',
			'hook' => 'entry_submission_ready',
		);

		foreach ($hooks as $hook)
		{
			ee()->db->insert('extensions', array_merge($hook_defaults, $hook));
		}
	}

	/**
	 * update_extension
	 *
	 * @access	public
	 * @param	mixed $current = ''
	 * @return	void
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		if (version_compare($current, '1.0.5', '<'))
		{
			ee()->db->insert('extensions', array(
				'class' => __CLASS__,
				'settings' => serialize(array('channels' => array())),
				'version' => $this->version,
				'enabled' => 'y',
				'priority' => 10,
				'method' => 'entry_submission_ready',
				'hook' => 'entry_submission_ready',
			));
		}

		ee()->db->update('extensions', array('version' => $this->version), array('class' => __CLASS__));
	}

	/**
	 * disable_extension
	 *
	 * @access	public
	 * @return	void
	 */
	public function disable_extension()
	{
		ee()->db->delete('extensions', array('class' => __CLASS__));
	}

	/**
	 * settings
	 *
	 * @access	public
	 * @return	void
	 */
	public function settings()
	{
		$query = ee()->db->get('channels');

		$channels = array();

		foreach ($query->result() as $row)
		{
			$channels[$row->channel_id] = $row->channel_title;
		}

		$query->free_result();

		return array(
			'channels' => array('c', $channels, array()),
		);
	}

	public function publish_form_channel_preferences($row)
	{
		if (ee()->extensions->last_call !== FALSE)
		{
			$row = ee()->extensions->last_call;
		}

		//new entries only
		if (ee()->input->get_post('entry_id'))
		{
			return $row;
		}

		$channel_id = ee()->input->get_post('channel_id');

		$valid_channels = isset($this->settings['channels']) ? $this->settings['channels'] : array();

		if ( ! $channel_id || ! in_array($channel_id, $valid_channels))
		{
			return $row;
		}

		ee()->load->library('javascript');

		ee()->javascript->output('
		(function() {
			var $title = $("[name=title]");
			var $urlTitle = $("[name=url_title]");
			var $parentPage = $(".auto-pages-uri-parent-page").first();
			var pagesUri = $("[name=pages__pages_uri]")[0];

			function copyUrlTitle() {
				var urlTitle = $urlTitle.val();
				var prefix = $parentPage.length > 0 ? $parentPage.val() : "";
				pagesUri.value = urlTitle ? prefix + "/" + urlTitle : "";
			}

			$title.on("keyup blur", function() {
				setTimeout(copyUrlTitle, 50);
			});

			$parentPage.change(function() {
				$title.trigger("keyup");
			});
		})();
		');

		return $row;
	}

	public function entry_submission_ready($meta, $data, $autosave)
	{
		if ($autosave || empty($data['entry_id']) || empty($data['revision_post']['pages__pages_uri']))
		{
			return;
		}

		if ( ! isset(ee()->config->config['site_pages']))
		{
			return;
		}

		$site_id = ee()->config->item('site_id');

		$site_pages =& ee()->config->config['site_pages'];

		if ( ! isset($site_pages[$site_id]['uris'][$data['entry_id']]))
		{
			return;
		}

		$old_page_uri = $site_pages[$site_id]['uris'][$data['entry_id']];
		$new_page_uri = $data['revision_post']['pages__pages_uri'];

		if ($old_page_uri === $new_page_uri)
		{
			return;
		}

		// find all fields with this FT
		$query = ee()->db->select('field_id')
			->where('field_type', 'auto_pages_uri')
			->get('channel_fields');

		$fields = $query->result();

		$query->free_result();

		$table = ee()->db->dbprefix('channel_data');

		$escaped_old_page_uri = ee()->db->escape($old_page_uri);

		$regex = '/^'.preg_quote($old_page_uri, '/').'/';

		foreach ($fields as $field)
		{
			$field_name = sprintf('field_id_'.$field->field_id);

			$query = ee()->db->select($field_name)
				->distinct()
				->like($field_name, $old_page_uri, 'after')
				->get('channel_data');

			foreach ($query->result() as $row)
			{
				$old_child_page_uri = $row->{$field_name};
				$new_child_page_uri = preg_replace($regex, $new_page_uri, $old_child_page_uri);

				ee()->db->update(
					'channel_data',
					array(
						$field_name => $new_child_page_uri,
					),
					array(
						$field_name => $old_child_page_uri,
					)
				);
			}

			$query->free_result();
		}

		foreach ($site_pages[$site_id]['uris'] as $entry_id => $page_uri)
		{
			$site_pages[$site_id]['uris'][$entry_id] = preg_replace($regex, $new_page_uri, $page_uri);
		}
	}
}

/* End of file ext.extension.php */
/* Location: ./system/expressionengine/third_party/extension/ext.extension.php */