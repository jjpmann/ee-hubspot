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

    protected $hubspot;

    public function __construct()
    {
    }

    public function blogPosts()
    {
        $tagdata    = ee()->TMPL->tagdata;
        $limit      = ee()->TMPL->fetch_param('limit', 10);

        // Filters
        $filters = [
            'blog'      => explode('|', ee()->TMPL->fetch_param('blog')),
            'topics'    => explode('|', ee()->TMPL->fetch_param('topic')),
            'author'    => explode('|', ee()->TMPL->fetch_param('author')),
            'status'    => explode('|', ee()->TMPL->fetch_param('status'))
        ];

        $blogs = $this->hubspot()->posts($filters)->take($limit);
// echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $blogs ); exit;

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
     * Helper funciton for template logging.
     */
    protected function _error_log($msg)
    {
        ee()->TMPL->log_item('Hubspot ERROR: '.$msg);
    }

    protected function hubspot()
    {
        if (!$this->hubspot) {
            $this->hubspot = new \jjpmann\EE\HubSpot();
        }
        return $this->hubspot;
    }
}
