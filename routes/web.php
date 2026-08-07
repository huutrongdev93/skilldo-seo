<?php

use SkillDo\Support\Facades\Route;

Route::get('/sitemap.xml', 'SkdSeo\Controllers\Web\SeoController@sitemap')->name('sitemap');
Route::get('/robots.txt', 'SkdSeo\Controllers\Web\SeoController@robots')->name('robots');
Route::get('/llms.txt', 'SkdSeo\Controllers\Web\SeoController@llms')->name('llms');
Route::get('/llms-full.txt', 'SkdSeo\Controllers\Web\SeoController@llmsFull')->name('llmsFull');