<?php

/**
 * @category    Modules
 *
 * @author      Jerry Price
 *
 * @link        https://github.com/jjpmann
 */

require_once('config.php');

class Hubspot_upd
{
    public $version     = HUBSPOT_VERSION;
    public $module_name = HUBSPOT_MOD_NAME;

    public function __construct($switch = true)
    {
    }

    public function install()
    {
        $data = [
            'module_name'    => $this->module_name,
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
        ];

        ee()->db->insert('modules', $data);

        //
        // Add additional stuff needed on module install here
        //

        return true;
    }

    public function uninstall()
    {
        ee()->db->select('module_id');
        $query = ee()->db->get_where('modules', ['module_name' => $this->module_name]);

        ee()->db->where('module_id', $query->row('module_id'));
        ee()->db->delete('module_member_groups');

        ee()->db->where('module_name', $this->module_name);
        ee()->db->delete('modules');

        ee()->db->where('class', $this->module_name);
        ee()->db->delete('actions');

        ee()->db->where('class', $this->module_name.'_mcp');
        ee()->db->delete('actions');

        return true;
    }

    public function update($current = '')
    {
        return false;
    }
}
