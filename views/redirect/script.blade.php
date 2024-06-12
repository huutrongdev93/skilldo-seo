<div class="modal fade" id="js_redirect_modal__edit">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Chỉnh sửa chuyển hướng</h4>
            </div>
            {!! $form->open() !!}
            <div class="modal-body" style="position: relative">
                {!! Admin::loading() !!}
                <div class="form-group">
                    <label class="col-sm-4 control-label">Nguồn chuyển hướng:</label>
                    <div class="col-sm-8"><label class="badge text-bg-success" id="js_redirect_modal_path__label"></label></div>
                </div>
                {!! $form->html() !!}
            </div>
            <div class="modal-footer">
                <button class="btn btn-green" type="submit">{!! Admin::icon('save') !!} Lưu</button>
            </div>
            {!! $form->close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
	let item, box;

	function seo_redirect_submit(element) {

		element.find('.loading').show();

		let data 		= element.serializeJSON();

		data.action =  'AjaxAdminSeoRedirect::save';

		data.id     =  item.id;

		request.post(ajax, data).then(function(response) {

			SkilldoHelper.message.response(response);

			element.find('.loading').hide();

			if(response.status === 'success') {

				box.find('.js_redirect_btn__edit').attr('data-item', JSON.stringify(response.data));

				$('#js_redirect_modal__edit').modal('hide');
			}
		});

		return false;
	}

	$(function () {

		$(document).on('click', '.js_redirect_btn__edit', function () {

			item = JSON.parse($(this).attr('data-item'));

			box  = $(this).closest('tr');

			$('#js_redirect_modal_path__label').html(item.path);

			$('#redirect_to').val(item.to);

			$("input[name=redirect][value='"+item.redirect+"']").iCheck('check');

			$('#js_redirect_modal__edit').modal('show');

			return false;
		});
	})
</script>