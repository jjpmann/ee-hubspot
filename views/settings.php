

<?php
$attributes = ['id' => 'subscribe_settings_form'];
//echo form_open($action_url, $attributes);

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    ['data' => lang('preference'), 'style' => 'width:50%;'],
    lang('setting')
);
    $this->table->add_row([
            '<strong>Real Magnent Settings</strong>',
            'These settings should be set in your <strong>.env</strong> file.',
        ]);
    $this->table->add_row([
            lang('realmagnet_username', 'realmagnet_username'),
            form_input('realmagnet_username', $realmagnet_username, 'class="field" disabled'),
        ]);
        $this->table->add_row([
            lang('realmagnet_password', 'realmagnet_password'),
            form_password('realmagnet_password', $realmagnet_password, 'class="field" disabled'),
        ]);

    // $this->table->add_row(array(
    // 		lang('subscribe_api_key', 'subscribe_api_key'),
    // 		form_input('subscribe_api_key', $subscribe_api_key, 'class="field"')
    // 	)
    // );
 //    	$this->table->add_row(array(
    // 		lang('subscribe_username', 'subscribe_username'),
    // 		form_input('subscribe_username', $subscribe_username, 'class="field"')
    // 	)
    // );
    // $this->table->add_row(array(
    // 		lang('subscribe_password', 'subscribe_password'),
    // 		form_password('subscribe_password', $subscribe_password, 'class="password"')
    // 	)
    // );

        echo $this->table->generate();

/*

?>

    <?=form_submit(['name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'])?>

<?=form_close()?>
*/

?>
        <script>$("#subscribe_settings_form").submit(function () {
        var return_value;
        fv_url=(("<?php echo htmlspecialchars_decode($fv_url)?>"));
         $.ajax({
                type:'POST',
                url:fv_url,
                async:false,
                data:$("#subscribe_settings_form").serialize(),
                beforeSend: function() {
                    $("#subscribe_settings_form input.submit").after('<span id="submit_loading">&nbsp&nbsp;&nbsp&nbsp;<img alt="Loading" src="themes/cp_themes/default/images/indicator.gif"></span>');

                },      
                complete: function() {
                   $('#submit_loading').remove();
                },                   
                success: function(msg){         
                    if(msg==1)
                    {
                        return_value=true;
                    }
                    else
                    {
                        var data = jQuery.parseJSON(msg);
                        var error_msg='';
                        $.each(data, function(key, value){
                            error_msg +=value+"<br>";
                           // $("#"+key).addClass('errorInput');
                        });                        
                        $.ee_notice(error_msg,{"type" : "error"});
                        return_value=false;
                    }
                }
          });         
          return return_value;      

        });
        </script>