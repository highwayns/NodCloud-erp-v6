<?php
namespace app\index\validate;
use think\Validate;
class Cashierinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['room', 'require|integer', '倉庫データは空にできません！|倉庫データが不正です！'],
        ['goods', 'require|integer', '商品データは空にできません！|商品データが不正です！'],
        ['warehouse', 'require|integer', '倉庫データは空にできません！|倉庫データが不正です！'],
        ['nums', 'require|number', '数量データは空にできません！|数量データが不正です！'],
        ['price', 'require|number', '単価データは空にできません！|単価データが不正です！'],
        ['discount', 'require|between:0.01,1', '割引データは空にできません！|割引データが不正です！'],
        ['total', 'require|number', '合計金額データは空にできません！|合計金額データが不正です！'],
        ['more', 'array', '拡張情報の形式が不正です！']
    ];
}