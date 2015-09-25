<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auto_pages_uri_ext
{
	public $settings = array();
	public $name = 'Auto Pages URI';
	public $version = '1.0.2';
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
			'hook' => 'publish_form_channel_preferences'
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
			var pagesUri = $("[name=pages__pages_uri]")[0];

			function copyUrlTitle() {
				var urlTitle = $urlTitle.val();
				pagesUri.value = urlTitle ? "/" + urlTitle : "";
			}

			$title.on("keyup blur", function() {
				setTimeout(copyUrlTitle, 50);
			});
		});
		');

		return $row;
	}
}

/* End of file ext.extension.php */
/* Location: ./system/expressionengine/third_party/extension/ext.extension.php */