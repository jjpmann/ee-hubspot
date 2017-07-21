<?php

/**
 * @category    Modules
 *
 * @author      Jerry Price
 *
 * @link        https://github.com/jjpmann
 */

require_once('config.php');

class Hubspot
{
    public $return_data;

    public function __construct()
    {
        //ee()->load->model('subscribe_model');
    }

    /**
     * Helper function for getting a parameter.
     */
    protected function _get_param($key, $default_value = '')
    {
        $val = ee()->TMPL->fetch_param($key);

        if ($val == '') {
            return $default_value;
        }

        return $val;
    }

    /**
     * Helper funciton for template logging.
     */
    protected function _error_log($msg)
    {
        ee()->TMPL->log_item('Hubspot ERROR: '.$msg);
    }
}
