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

        $all = collect($this->hubspot()->blogs());
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
        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $data, $json ); exit;
        
        $field_name = $this->field_name;
        $entry_id = ee()->input->get('entry_id');

        $order = array();
        $selected = array();

       // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $data ); exit;
        

        if (is_array($data) && isset($data['data']) && ! empty($data['data'])) // autosave
        {
            foreach ($data['data'] as $k => $id)
            {
                $selected[$k] = $id;
                $order[$id] = isset($data['sort'][$k]) ? $data['sort'][$k] : 0;
            }
        }

        $blogs = $this->hubspot()->blogs();

        ee()->cp->add_js_script(array(
            'plugin' => 'ee_interact.event',
            'file' => 'cp/relationships',
            'ui' => 'sortable'
        ));
        ee()->javascript->output("EE.setup_relationship_field('".$this->field_name."');");
        $css_link = ee()->view->head_link('css/relationship.css');
        ee()->cp->add_to_head($css_link);

        // $options[''] = '--';

        // foreach ($blogs as $blog)
        // {
        //     $options[$blog['id']] = $blog['title'];
        // }

        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $options ); exit;
        
        // echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( $field_name, $blogs, $selected, $order ); exit;
        
        // $html = '<div class="json-field '.$klass.'">'.$textarea.'<pre class="result"></pre></div>';
        return ee()->load->view('publish', compact('field_name', 'blogs', 'selected', 'order'), TRUE);
        // return form_dropdown($field_name.'[]', $options, $selected, 'multiple');
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
        $topic      = isset($data['topic']) ? $data['topic'] : $this->settings['topic'];
        $multiple   = isset($data['multiple']) ? $data['multiple'] : $this->settings['multiple'];
        $status     = isset($data['status']) ? $data['status'] : $this->settings['status'];
        $author     = isset($data['author']) ? $data['author'] : $this->settings['author'];
      
        ee()->table->set_template(array(
            'table_open'    => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
            'row_start'     => '<tr class="even">',
            'row_alt_start' => '<tr class="odd">'
        ));

        echo "<pre>".__FILE__.'<br>'.__METHOD__.' : '.__LINE__."<br><br>"; var_dump( collect($this->hubspot()->topics()) ); exit;
        

        // "Preference" and "Setting" table headings
        ee()->table->set_heading(array('data' => lang('preference'), 'style' => 'width: 50%'), lang('setting'));

        ee()->table->add_row(
            lang('blog', 'blog'),
            form_dropdown('blog', 'LMO', $blog, 'id="blog"')
        );

        ee()->table->add_row(
            lang('status', 'status'),
            form_dropdown('status', $this->hubspot()->statuses(), $status, 'id="status"')
        );

        ee()->table->add_row(
            lang('topic', 'topic'),
            form_multiselect('topic', collect($this->hubspot()->topics())->pluck('name'), $topic, 'id="topic"')
        );

        ee()->table->add_row(
            lang('multiple', 'multiple'),
            form_checkbox('multiple', 'y', $multiple, 'id="multiple"')
        );

        ee()->table->add_row(
            lang('author', 'author'),
            form_multiselect('author', collect($this->hubspot()->topics())->pluck('name'), $author, 'id="author"')
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
            'blog'          => ee()->input->post('blog'),
            'topic'          => ee()->input->post('topic'),
            'multiple'      => ee()->input->post('multiple'),
            'status'        => ee()->input->post('status'),
            'author'        => ee()->input->post('author'),
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
            'topic'      => '',
            'multiple'  => '',
            'status'    => '',
            'author'    => '', 
        );
    }

}
