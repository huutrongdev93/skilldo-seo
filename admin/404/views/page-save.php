<form method="post" accept-charset="utf-8" id="js_redirect_form_save">
    <?php
    if(isset($object) && have_posts($object)) {
        ?>
        <script>
            let object_id = <?php echo $object->id;?>;
        </script>
        <?php
        echo '<input type="hidden" name="id" class="form-control" value="'.$object->id.'">';
    }
    Template::partial('include/form/form', ['form' => $form, 'object' => (isset($object) && have_posts($object)) ? $object : []]);
    ?>
</form>
<script type="text/javascript">
    $(function() {
        $('#js_redirect_form_save').submit(function() {

            $('.loading').show();

            let data 		= $(this).serializeJSON();

            $(this).find('textarea').each(function(index, el) {
                let textareaid 	= $(this).attr('id');
                let value 		= $(this).val();
                if($(this).hasClass('tinymce') === true || $(this).hasClass('tinymce-shortcut') === true){
                    value 	= document.getElementById(textareaid+'_ifr').contentWindow.document.body.innerHTML;
                }
                data[$(this).attr('name')] = value;
            });

            data.action     =  'admin_ajax_redirect_save';

            let $jqxhr = $.post(ajax, data, function () {}, 'json');

            $jqxhr.done(function( data ) {
                show_message(data.message, data.status);
                $('.loading').hide();
                if(data.status === 'success') {
                    window.location.href = '<?php echo admin_url('plugins?page=redirect');?>';
                }
            });

            return false;
        });
    })
</script>