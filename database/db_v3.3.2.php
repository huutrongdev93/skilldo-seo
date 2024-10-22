<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class () extends Migration {

    public function up(): void
    {
        Option::update('seo_point_support', [
            'post_post', 'post_categories_post_categories', 'page', 'products', 'products_categories'
        ]);
    }

    public function down(): void
    {
        Option::delete('seo_point_support');
    }
};