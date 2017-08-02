<?php

/**
 * @category    Modules
 *
 * @author      Jerry Price
 *
 * @link        https://github.com/jjpmann
 */

require_once('config.php');
require_once('HubSpot.php');

class Hubspot
{
    public $return_data;

    public function __construct()
    {
        //ee()->load->model('subscribe_model');
    }

    public function latestBlogs()
    {
        $limit      = ee()->TMPL->fetch_param('limit', 10);
        $topic      = ee()->TMPL->fetch_param('topic');
        $tagdata    = ee()->TMPL->tagdata;
        $hubspot    = new \jjpmann\EE\HubSpot();
        $all        = $hubspot->blogs(); 

        if ($topic) {
            $all = $all->filter(function($item, $key) use ($topic) {
                return in_array($topic, $item['topics']);
            });
        }

        $blogs = $all->take($limit);
        $count = 0;
        $total = $blogs->count();

        $blogs = $blogs->map(function($item, $key) use (&$count, $total){
            $count++;
            $item["count"] = $count;
            $item["total_results"] = $total;
            return $item;
        });

        $html = ee()->TMPL->parse_variables($tagdata, $blogs->values()->all());

        return $html;
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
