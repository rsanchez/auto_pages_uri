<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license     http://expressionengine.com/user_guide/license.html
 * @link        http://expressionengine.com
 * @since       Version 2.0
 * @filesource
 */

/**
 * Auto Pages URI - Parent Page Fieldtype
 *
 * @package    ExpressionEngine
 * @subpackage Addons
 * @category   Fieldtype
 * @author     Rob Sanchez
 * @link       https://github.com/rsanchez
 */
class Auto_pages_uri_ft extends EE_Fieldtype
{
    public $info = array(
        'name' => 'Auto Pages URI - Parent Page',
        'version' => '1.0.4',
    );

    /**
     * Display field
     *
     * @param   $data the entry data
     * @return  string
     *
     */
    public function display_field($data)
    {
        $site_pages = ee()->config->item('site_pages');

        $site_id = ee()->config->item('site_id');

        $page_uris = isset($site_pages[$site_id]['uris']) ? $site_pages[$site_id]['uris'] : array();

        $home_entry_id = array_search('/', $page_uris);

        if ($home_entry_id !== FALSE)
        {
            unset($page_uris[$home_entry_id]);
        }

        sort($page_uris);

        $options = array_combine($page_uris, $page_uris);

        if ($home_entry_id !== FALSE)
        {
            $options = array('' => '/') + $options;
        }

        return form_dropdown($this->field_name, $options, $data, 'class="auto-pages-uri-parent-page"');
    }

    /**
     * Save Settings
     *
     * @param   array  Any settings $_POST'ed with the $field_name.'_' prefix
     * @return  mixed  Settings to store
     */
    public function save_settings($data)
    {
        $data['field_fmt'] = 'none';
        $data['field_show_fmt'] = 'n';

        return $data;
    }
}

/* End of file ft.auto_pages_uri.php */
/* Location: /system/expressionengine/third_party/auto_pages_uri/ft.auto_pages_uri.php */