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

class Hubspot_ft extends EE_Fieldtype
{

    /**
    * Info array
    *
    * @var  array
    */
    public $info = array(
        'name'      => HUBSPOT_MOD_NAME . ' Field',
        'version'   => HUBSPOT_VERSION
    );

    public $has_array_data = TRUE;


    protected $hubspot;

    /**
     * Replace tag
     *
     * @access  public
     * @param   field contents
     * @return  replacement text
     *
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        
        // ignore if no tagdata
        if (! $tagdata) return;

        $data   = json_decode(htmlspecialchars_decode($data), true);
        $limit  = (isset($params['limit']) && $params['limit'] <= $this->settings['limit'])? $params['limit'] : $this->settings['limit'];
        
        ee()->load->model('field_model');
        $field_name = ee()->field_model->get_field($this->id)->row()->field_name;

        if (!$data['data']) {
            return ;
        }

        $all = collect($this->hubspot()->posts());



        $ids = $data['data'];

        $blogs = $all->filter(function($value, $key) use ($ids){
            return in_array($value['id'], $ids);
        })->take($limit);

        $count = 0;
        $total = $blogs->count();

        $blogs = $blogs->map(function($item, $key) use (&$count, $total, $field_name){
            $count++;
            $new_item = [];
            collect($item)->each(function($v, $k) use (&$new_item, &$count, $total, $field_name){
                $new_item["{$field_name}:{$k}"] = $v;
            });
            $new_item["{$field_name}:count"] = $count;
            $new_item["{$field_name}:total_results"] = $total;
                        
            return $new_item;
        });

        $html = ee()->TMPL->parse_variables($tagdata, $blogs->all() );


        return $html;
    }

    protected function hubspot()
    {
        if (!$this->hubspot) {
            $this->hubspot = new \jjpmann\EE\HubSpot();
        }
        return $this->hubspot;
    }

    /**
    * Displays the field in publish form
    *
    * @param    string
    * @param    bool
    * @return   string
    */
    public function display_field($data)
    {

        $data = json_decode(htmlspecialchars_decode($data), true);

        $field_name = $this->field_name;
        $entry_id = ee()->input->get('entry_id');

        $order = array();
        $selected = array();

        if (is_array($data) && isset($data['data']) && ! empty($data['data'])) // autosave
        {
            foreach ($data['data'] as $k => $id)
            {
                $selected[$k] = $id;
                $order[$id] = isset($data['sort'][$k]) ? $data['sort'][$k] : 0;
            }
        }

        // Filters
        $filters = [
            'blog'      => $this->settings['blog'],
            'topics'    => $this->settings['topics'],
            'author'    => $this->settings['authors'],
            'status'    => $this->settings['statuses'],
        ];

        $blogs = $this->hubspot()->posts($filters);


        if ($this->settings['multiple'] != 'y')
        {
            $options[''] = '--';

            foreach ($blogs as $blog)
            {
                $options[$blog['id']] = $blog['title'];
            }
            return form_dropdown($field_name.'[data][]', $options, current($selected));
        }

        ee()->cp->add_js_script(array(
            'plugin' => 'ee_interact.event',
            'file' => 'cp/relationships',
            'ui' => 'sortable'
        ));

        ee()->javascript->output("EE.setup_relationship_field('".$this->field_name."');");
        $css_link = ee()->view->head_link('css/relationship.css');
        ee()->cp->add_to_head($css_link);

        return ee()->load->view('publish', compact('field_name', 'blogs', 'selected', 'order'), TRUE);

    }


    /**
    * Displays the field in matrix
    *
    * @param    string
    * @return   string
    */
    public function display_cell($cell_data)
    {
        return $this->display_field($cell_data, true);
    }

    
    /**
    * Displays the field in Low Variables
    *
    * @param    string
    * @return   string
    */
    public function display_var_field($var_data)
    {
        return $this->display_field($var_data);
    }

    /**
     * Display Settings Screen
     *
     * @access  public
     * @return  default global settings
     *
     */
    public function display_settings($data)
    {
        // load the language file
        ee()->lang->loadfile('hubspot');

        //$options = array('off', 'on');
        $blog       = isset($data['blog']) ? $data['blog'] : $this->settings['blog'];
        $topics      = isset($data['topics']) ? $data['topics'] : $this->settings['topics'];
        $multiple   = isset($data['multiple']) ? $data['multiple'] : $this->settings['multiple'];
        $statuses     = isset($data['statuses']) ? $data['statuses'] : $this->settings['statuses'];
        $authors     = isset($data['authors']) ? $data['authors'] : $this->settings['authors'];
      
        ee()->table->set_template(array(
            'table_open'    => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
            'row_start'     => '<tr class="even">',
            'row_alt_start' => '<tr class="odd">'
        ));

        // "Preference" and "Setting" table headings
        ee()->table->set_heading(array('data' => lang('preference'), 'style' => 'width: 50%'), lang('setting'));

        ee()->table->add_row(
            lang('blog_label', 'blog'),
            form_dropdown('blog', compressByNameId($this->hubspot()->blogs()->all()), $blog, 'id="blog"')
        );

        ee()->table->add_row(
            lang('statuses_label', 'statuses'),
            form_multiselect('statuses[]', $this->hubspot()->statuses(), $statuses, 'id="statuses" style="width:60%; min-height: 40px;"')
        );

        $allTopics = ['' => '-- All --'] + compressByNameId($this->hubspot()->topics()->all())->all();

        ee()->table->add_row(
            lang('topics_label', 'topics_label'),
            form_multiselect('topics[]', $allTopics, $topics, 'id="topics" style="width:60%; min-height: 80px;"')
        );

        $allAuthors = ['' => '-- All --'] + compressByNameId($this->hubspot()->authors()->all())->all();

        ee()->table->add_row(
            lang('authors_label', 'authors'),
            form_multiselect('authors[]', $allAuthors, $authors, 'id="authors" style="width:60%; min-height: 80px;"')
        );

        ee()->table->add_row(
            lang('multiple_label', 'multiple'),
            form_checkbox('multiple', 'y', $multiple, 'id="multiple"')
        );

        // function form_dropdown($name = '', $options = array(), $selected = array(), $extra = '')
    }

    /**
     * Save Field
     *
     * In our case the actual field entry will be blank, so we'll simply
     * cache some data for the post_save method.
     *
     * @param   field data
     * @return  column data
     */
    public function save($data)
    {
        $data = json_encode($data);
        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $data ); exit;
        return $data;
    }

    /**
     * Save Settings
     *
     * @access  public
     * @return  field settings
     *
     */
    public function save_settings($data)
    {
        return array(
            'blog'      => ee()->input->post('blog'),
            'topics'    => array_filter(ee()->input->post('topics')) ?: [''],
            'multiple'  => ee()->input->post('multiple'),
            'statuses'  => array_filter(ee()->input->post('statuses')) ?: [''],
            'authors'   => array_filter(ee()->input->post('authors')) ?: [''],
        );
    }

    /**
     * Install Fieldtype
     *
     * @access  public
     * @return  default global settings
     *
     */
    public function install()
    {
        return array(
            'blog'      => '',
            'topics'    => '',
            'multiple'  => '',
            'statuses'  => '',
            'authors'   => '', 
        );
    }

}
