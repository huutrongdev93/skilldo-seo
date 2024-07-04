<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class () extends Migration {

    public function up(): void
    {
        model('routes')->delete(Qr::set('slug', 'sitemap.xml'));

        model('routes')->delete(Qr::set('slug', 'robots.txt'));

        if(!schema()->hasTable('log404')) {
            schema()->create('log404', function (Blueprint $table) {
                $table->increments('id');
                $table->string('path', 255)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('to', 255)->collation('utf8mb4_unicode_ci');
                $table->string('type', 100)->collation('utf8mb4_unicode_ci')->default('301');
                $table->integer('redirect')->default(0);
                $table->string('ip')->nullable();
                $table->integer('hit')->default(0);
                $table->datetime('created')->default('CURRENT_TIMESTAMP');
                $table->datetime('updated')->nullable();
            });
        }

        if(schema()->hasTable('redirect')) {
            schema()->table('redirect', function (Blueprint $table) {
                $table->dateTime('created')->default('CURRENT_TIMESTAMP')->change();
            });
        }

        $seo404 = Option::get('seo_404');

        $seo404 = (is_array($seo404)) ? $seo404 : [];

        if(!isset($seo404['log404'])) {

            $seo404['redirect_404']  = (!empty($seo404['redirect_to'])) ? $seo404['redirect_to'] : '';

            $seo404['redirect_404_link'] = (!empty($seo404['redirect_link'])) ? $seo404['redirect_link'] : '';

            $seo404['log404']           = (!empty($seo404['redirect_logs'])) ? $seo404['redirect_logs'] : 0;

            Option::update('seo_404', $seo404);
        }
    }

    public function down(): void
    {
        if(schema()->hasTable('redirect')) {
            schema()->drop('redirect');
        }
        if(schema()->hasTable('log404')) {
            schema()->drop('log404');
        }
    }
};