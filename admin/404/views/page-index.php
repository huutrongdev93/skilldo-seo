<div class="ui-layout">
    <div class="col-md-12">
        <div class="ui-title-bar__group">
            <h1 class="ui-title-bar__title">Redirect</h1>
            <div class="ui-title-bar__action">
                <?php do_action('admin_redirect_action_bar_heading');?>
            </div>
        </div>
        <div class="box">
            <!-- .box-content -->
            <div class="box-content">
                <!-- search box -->
                <!-- /search box -->
                <form method="post" id="form-action" class="table-responsive">
                    <?php $table_list->display();?>
                </form>
                <!-- paging -->
                <div class="paging">
                    <div class="pull-right"><?= (isset($pagination))?$pagination->html():'';?></div>
                </div>
                <!-- paging -->
            </div>
            <!-- /.box-content -->
        </div>
    </div>
</div>

<div class="modal fade" id="js_redirect_modal__edit">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Chỉnh sửa chuyển hướng</h4>
            </div>
            <div class="modal-body">
                <form action="" method="post" class="form-horizontal" role="form" id="js_redirect_form_save">
                    <?php echo Admin::loading();?>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Nguồn chuyển hướng:</label>
                        <div class="col-sm-8"><b id="js_redirect_modal_path__label"></b></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Chuyển hướng:</label>
                        <div class="col-sm-8">
                            <div class="radio">
                                <label style="margin-right: 10px;"><input type="radio" name="redirect" value="0" checked>Mặc định</label>
                                <label style="margin-right: 10px;"><input type="radio" name="redirect" value="1">Bật</label>
                                <label><input type="radio" name="redirect" value="2">Tắt</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Chuyển hướng đến:</label>
                        <div class="col-sm-8">
                            <input type="text" name="redirect_to" class="form-control" value="" id="js_redirect_to_input">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-4"></div>
                        <div class="col-sm-8">
                            <button class="btn btn-green"><?php echo Admin::icon('save');?> Lưu</button>
                        </div>
                    </div>
                </form>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    $(function () {
        let item, box;
        $('.js_redirect_btn__edit').click(function () {
            item = JSON.parse($(this).attr('data-item'));
            box  = $(this).closest('tr');
            $('#js_redirect_modal_path__label').html(item.path);
            $('#js_redirect_to_input').val(item.to);
            $("input[name=redirect][value='"+item.redirect+"']").prop("checked",true);
            $('#js_redirect_modal__edit').modal('show');
            return false;
        });
        $('#js_redirect_form_save').submit(function() {

            $('.loading').show();

            let data 		= $(this).serializeJSON();

            data.action =  'Seo_Redirect_Admin::save';
            data.id     =  item.id;

            let $jqxhr = $.post(ajax, data, function () {}, 'json');

            $jqxhr.done(function(response) {
                show_message(response.message, response.status);
                $('.loading').hide();
                if(response.status === 'success') {
                    box.find('.js_redirect_btn__edit').attr('data-item', JSON.stringify(response.item));
                    $('#js_redirect_modal__edit').modal('hide');
                }
            });

            return false;
        });
    })
</script>