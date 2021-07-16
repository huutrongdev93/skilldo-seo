<?php
function skd_add_input_seo($input) {
	$input[] = array('field' => 'skd_seo_robots', 'type'	=> 'textarea', 'label' => 'Nội dung file robots');
	$input[] = array('field' => 'facebook_app_id', 'type'	=> 'text', 'label' => 'Facebook app id');
	$input[] = array('field' => 'facebook_admins', 'type'	=> 'text', 'label' => 'Facebook admins');
	$input[] = array('field' => 'seo_point', 'type'	=> 'select', 'label' => 'Chấm điểm seo', 'options' => [0 => 'không sử dụng', 1 => 'Sử dụng']);
	return $input;
}
add_filter('get_theme_seo_input', 'skd_add_input_seo');