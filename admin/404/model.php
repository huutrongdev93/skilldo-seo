<?php
Class Log404 extends \SkillDo\Model\Model {

    protected string $table = 'log404';

    protected array $columns = [
        'path' => ['string'],
        'to' => ['string'],
        'type' => ['string', '301'],
        'redirect' => ['string'],
        'ip' => ['string'],
        'hit' => ['int', 0],
    ];
}