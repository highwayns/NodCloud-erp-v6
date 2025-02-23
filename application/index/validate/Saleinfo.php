<?php
namespace app\index\validate;
use think\Validate;
class Saleinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['room', 'require|integer', '倉庫データは空にできません！|倉庫データが正しくありません！'],
        ['goods', 'require|integer', '商品データは空にできません！|商品データが正しくありません！'],
        ['warehouse', 'require|integer', '倉庫データは空にできません！|倉庫データが正しくありません！'],
        ['nums', 'require|number', '数量データは空にできません！|数量データが正しくありません！'],
        ['price', 'require|number', '単価データは空にできません！|単価データが正しくありません！'],
        ['discount', 'require|between:0.01,1', '割引データは空にできません！|割引データが正しくありません！'],
        ['total', 'require|number', '合計金額データは空にできません！|合計金額データが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}