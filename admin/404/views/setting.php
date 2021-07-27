<div class="col-xs-12 col-md-12">
    <div class="box">
        <div class="header"><h4>Cấu hình chuyển hướng 404</h4></div>
        <div class="box-content" style="padding:10px;">
            <?php
                $form = new FormBuilder();
                $form
                    ->add('seo_404[redirect_to]', 'select', [
                        'label' => 'Chuyển hướng đến',
                        'options' => [
                            '' => 'Không chuyển hướng',
                            'home' => 'Trang chủ website',
                            'link' => 'Url tùy chỉnh'
                        ],
                        'note' => "<p></p><strong>Không chuyển hướng:</strong> Để tắt chuyển hướng.</p>
                        <p></p><strong>Trang chủ website:</strong> Chuyển hướng trang 404 đến trang chủ website.</p>
                        <p></p><strong>URL tùy chỉnh:</strong> Chuyển hướng yêu cầu 404 đến một URL cụ thể.</p>"
                    ], Seo_404::config('redirect_to'))
                    ->add('seo_404[redirect_link]', 'text', [
                        'label' => 'URL tùy chỉnh',
                        'note'  => "Nhập bất kỳ url nào (bao gồm cả http://) để sử dụng tùy chọn URL tùy chỉnh"
                    ], Seo_404::config('redirect_link'))
                    ->add('seo_404[redirect_logs]', 'switch', [
                        'label' => 'Nhật ký 404 lỗi',
                        'note'  => "Bật/Tắt Ghi nhật ký",
                        'single'=> true,
                    ], Seo_404::config('redirect_logs'))
                    ->html(false);
            ?>
        </div>
    </div>
</div>
