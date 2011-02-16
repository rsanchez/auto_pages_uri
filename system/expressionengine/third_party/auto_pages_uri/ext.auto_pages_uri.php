<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auto_pages_uri_ext
{
	public $settings = array();
	public $name = 'Auto Pages URI';
	public $version = '1.0.0';
	public $description = 'Automatically generate the Pages URI when creating a new entry.';
	public $settings_exist = 'n';
	public $docs_url = 'http://github.com/rsanchez/auto_pages_uri';
	
	/**
	 * constructor
	 * 
	 * @access	public
	 * @param	mixed $settings = ''
	 * @return	void
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
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
			'settings' => '',
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
			$this->EE->db->insert('extensions', array_merge($hook_defaults, $hook));
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
		
		$this->EE->db->update('extensions', array('version' => $this->version), array('class' => __CLASS__));
	}
	
	/**
	 * disable_extension
	 * 
	 * @access	public
	 * @return	void
	 */
	public function disable_extension()
	{
		$this->EE->db->delete('extensions', array('class' => __CLASS__));
	}
	
	/**
	 * settings
	 * 
	 * @access	public
	 * @return	void
	 */
	public function settings()
	{
		return array();
	}
	
	public function publish_form_channel_preferences($row)
	{
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$row = $this->EE->extensions->last_call;
		}
		
		//new entries only
		if ($this->EE->input->get_post('entry_id'))
		{
			return $row;
		}
		
		//$site_pages = $this->EE->config->item('site_pages');
		
		$this->EE->load->library('javascript');
		
		$this->EE->javascript->output('
		$("#title").bind("keyup blur", function() {
			var pagesUri = document.getElementById("pages__pages_uri");
			$(this).ee_url_title(pagesUri, true);
			if (pagesUri.value) {
				pagesUri.value = "/"+pagesUri.value;
			}
		});
		');
		
		return $row;
	}
}

/* End of file ext.extension.php */
/* Location: ./system/expressionengine/third_party/extension/ext.extension.php */