<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

return new class () extends Migration {

    public function up(): void
    {
        if(!schema()->hasTable('redirect')) {
            schema()->create('redirect', function (Blueprint $table) {
                $table->increments('id');
                $table->string('path', 255)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('to', 255)->collation('utf8mb4_unicode_ci');
                $table->string('type', 100)->collation('utf8mb4_unicode_ci')->default('301');
                $table->integer('redirect')->default(0);
                $table->integer('order')->default(0);
                $table->integer('user_created')->default(0);
                $table->integer('user_updated')->default(0);
                $table->datetime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->datetime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('log404')) {
            schema()->create('log404', function (Blueprint $table) {
                $table->increments('id');
                $table->string('path', 255)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('to', 255)->collation('utf8mb4_unicode_ci');
                $table->string('type', 100)->collation('utf8mb4_unicode_ci')->default('301');
                $table->integer('redirect')->default(0);
                $table->string('ip')->nullable();
                $table->integer('hit')->default(0);
                $table->datetime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->datetime('updated')->nullable();
            });
        }
    }

    public function down(): void
    {
        if(schema()->hasTable('redirect')) {
            schema()->drop('redirect');
        }
        if(schema()->hasTable('redirect')) {
            schema()->drop('log404');
        }
    }
};