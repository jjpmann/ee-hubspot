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

class Hubspot_mcp
{
    public $base;           // the base url for this module
    public $form_base;      // base url for forms
    public $module_name = HUBSPOT_MOD_NAME;

    public $settings = [];

    public $settings_exist = 'y';

    protected $hubspot;

    public function __construct($switch = true)
    {
        // Make a local reference to the ExpressionEngine super object

        $this->base = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.strtolower($this->module_name);
        $this->form_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.strtolower($this->module_name);
        ee()->cp->set_right_nav(
            [
                    'settings'               => $this->base.AMP.'method=settings',
                    // 'hubspot_list_manage'  => $this->base.AMP.'method=hubspot_lists',
                   // 'hubspot_stats'    => $this->base.AMP.'method=hubspot_stats',
            ]
        );
        ee()->load->model('hubspot_model');
        // uncomment this if you want navigation buttons at the top
        /*      ee()->cp->set_right_nav(array(
                'home'          => $this->base,
                'some_language_key' => $this->base.AMP.'method=some_method_here',
            ));
        */
    }

    public function index()
    {
        return $this->settings();
    }

    public function settings()
    {
        //$this->_permissions_check();
        ee()->load->library('table');

        $vars = [
            'action_url' => $this->base.AMP.'method=save_settings',
        ];

        ee()->view->cp_page_title = lang('hubspot_settings');

        ee()->view->cp_breadcrumbs = [$this->base => lang('Hubspot')];

        // $blogs = $this->hubspot()->blogs();        
        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $blogs ); exit;

        // $authors = $this->hubspot()->authors();
        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $authors->pluck('id')->unique()->sort() ); exit;
        
        // $topics = $this->hubspot()->topics();        
        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $topics ); exit;


        // $posts = $this->hubspot()->posts();        
        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $posts ); exit;
        
        // $topic = 5225874677;
        // $blogs = $blogs->filter(function($item, $key) use ($topic) {
        //     return in_array($topic, $item['topics']);
        // });

        
        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $blogs->toArray() ); exit;
        
        // // $topics = $hubspot->topics();
        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $topics ); exit;

        

        return ee()->load->view('index', $vars, true);
    }

    protected function hubspot()
    {
        if (!$this->hubspot) {
            $this->hubspot = new \jjpmann\EE\HubSpot();
        }
        return $this->hubspot;
    }

    public function hubspot_settings_validation()
    {
        ee()->load->library('form_validation');

        $valid_form = ee()->form_validation->run();
        if ($valid_form) {
            echo 1;
            exit();
        } else {
            echo json_encode(ee()->form_validation->_error_array);
            exit();
        }
    }

    public function save_settings()
    {
        $insert['hubspot_api_key'] = ee()->input->post('hubspot_api_key');
        $insert['hubspot_username'] = ee()->input->post('hubspot_username');
        $insert['hubspot_password'] = ee()->input->post('hubspot_password');

        //ee()->config->_update_config($insert);

        ee()->session->set_flashdata('message_success', lang('settings_updated'));

        ee()->functions->redirect($this->base.AMP.'method=settings');
    }


}
