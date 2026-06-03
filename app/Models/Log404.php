<?php
namespace SkdSeo\Models;

Class Log404 extends \SkillDo\Database\Eloquent\Model
{
    protected string $table = 'log404';

    protected array $columns = [
        'path' => ['string'],
        'to' => ['string'],
        'type' => ['string', '301'],
        'hit' => ['int', 0],
    ];
}