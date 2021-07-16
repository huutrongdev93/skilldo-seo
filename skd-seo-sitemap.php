<?php
function skd_seo_sitemap($ci , $model) {
	header('Content-type: application/xml');
	$data = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	$headers = get_headers(Url::base(SKD_SEO_PATH.'assets/main-sitemap.xsl'));
	if( $headers[0] == 'HTTP/1.1 200 OK' ) {
		$data .= '<?xml-stylesheet type="text/xsl" href="'.Url::base().SKD_SEO_PATH.'assets/main-sitemap.xsl"?>'."\n";
	}
	$p 	= $ci->input->get('p');
	if( $p == '' ) {

		$data .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

		$data .= '<sitemap>'."\n";
		$data .= '<loc>'.Url::base('sitemap.xml?p=page').'</loc>'."\n";
		$data .= '<lastmod>'.date(DATE_ATOM).'</lastmod>'."\n";
		$data .= '</sitemap>'."\n";

		$data .= '<sitemap>'."\n";
		$data .= '<loc>'.Url::base('sitemap.xml?p=category').'</loc>'."\n";
		$data .= '<lastmod>'.date(DATE_ATOM).'</lastmod>'."\n";
		$data .= '</sitemap>'."\n";

		$data .= '<sitemap>'."\n";
		$data .= '<loc>'.Url::base('sitemap.xml?p=post').'</loc>'."\n";
		$data .= '<lastmod>'.date(DATE_ATOM).'</lastmod>'."\n";
		$data .= '</sitemap>'."\n";

		if( class_exists('sicommerce') ) {
			$data .= '<sitemap>'."\n";
			$data .= '<loc>'.Url::base('sitemap.xml?p=product-category').'</loc>'."\n";
			$data .= '<lastmod>'.date(DATE_ATOM).'</lastmod>'."\n";
			$data .= '</sitemap>'."\n";

			$data .= '<sitemap>'."\n";
			$data .= '<loc>'.Url::base('sitemap.xml?p=product').'</loc>'."\n";
			$data .= '<lastmod>'.date(DATE_ATOM).'</lastmod>'."\n";
			$data .= '</sitemap>'."\n";
		}

		$data .= '</sitemapindex>'."\n";
	}
	if( $p == 'page' ) 		$data .= skd_seo_sitemap_page( $ci, $model );
	if( $p == 'category' ) 	$data .= skd_seo_sitemap_category( $ci, $model );
	if( $p == 'post' ) 		$data .= skd_seo_sitemap_post( $ci, $model );
	if( class_exists('sicommerce') ) {
		if( $p == 'product-category' ) 	$data .=skd_seo_sitemap_product_category( $ci, $model );
		if( $p == 'product' ) 			$data .=skd_seo_sitemap_product( $ci, $model );
	}
	echo $data;
}

function skd_seo_sitemap_page($ci , $model) {
	$data = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
	$object = Pages::gets();
	$data .= '<url><loc>'.Url::base().'</loc><lastmod>'.date(DATE_ATOM).'</lastmod><priority>1</priority></url>'."\n";
	$data .= '<url><loc>'.Url::base('trang-chu').'</loc><lastmod>'.date(DATE_ATOM).'</lastmod><priority>1</priority></url>'."\n";
	foreach ($object as $key => $value) {
		$data .= '<url><loc>'.Url::base().$value->slug.'</loc><lastmod>'.date(DATE_ATOM).'</lastmod><priority>0.5</priority></url>'."\n";
	}
	$data .= '</urlset>';
	return $data;
}

function skd_seo_sitemap_category($ci , $model) {
	$data = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
	$object = PostCategory::gets(array('where' => array('public' => 1, 'cate_type' => 'post_category')));
	foreach ($object as $key => $value) {
		$data .= '<url><loc>'.Url::base().$value->slug.'</loc><lastmod>'.date(DATE_ATOM).'</lastmod><priority>0.5</priority></url>'."\n";
	}
	$data .= '</urlset>';
	return $data;
}

function skd_seo_sitemap_post($ci , $model) {
	$data = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
	$object = Posts::gets( array('where' => array('public' => 1, 'trash' => 0, 'post_type' => 'post')) );
	foreach ($object as $key => $value) {
		$data .= '<url><loc>'.Url::base().$value->slug.'</loc><lastmod>'.date(DATE_ATOM).'</lastmod><priority>0.5</priority></url>'."\n";
	}
	$data .= '</urlset>';
	return $data;
}

function skd_seo_sitemap_product_category($ci , $model) {
	$data = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
	$object = ProductCategory::gets( array('where' => array('public' => 1)) );
	foreach ($object as $key => $value) {
		$data .= '<url><loc>'.Url::base().$value->slug.'</loc><lastmod>'.date(DATE_ATOM).'</lastmod><priority>0.5</priority></url>'."\n";
	}
	$data .= '</urlset>';
	return $data;
}

function skd_seo_sitemap_product($ci , $model) {
	$data = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
	$object = Product::gets( array('where' => array('public' => 1, 'trash' => 0)) );
	foreach ($object as $key => $value) {
		$data .= '<url><loc>'.Url::base().$value->slug.'</loc><lastmod>'.date(DATE_ATOM).'</lastmod><priority>0.5</priority></url>'."\n";
	}
	$data .= '</urlset>';
	return $data;
}
