<?php
Class Seo_Redirect extends \SkillDo\Model\Model {

    protected string $table = 'redirect';

    protected array $columns = [
        'path'          => ['string'],
        'to'            => ['string'],
        'type'          => ['string', '301'],
        'redirect'      => ['string', 0],
    ];
}