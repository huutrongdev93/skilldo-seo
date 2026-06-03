<?php
namespace SkdSeo\Models;

Class Redirect extends \SkillDo\Database\Eloquent\Model
{
    protected string $table = 'redirect';

    protected array $columns = [
        'path'          => ['string'],
        'to'            => ['string'],
        'type'          => ['string', '301'],
        'redirect'      => ['string', 0],
    ];
}