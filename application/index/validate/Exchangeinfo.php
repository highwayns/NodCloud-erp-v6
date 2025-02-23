<?php
namespace app\index\validate;
use think\Validate;
class Exchangeinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['room', 'require|integer', '倉庫データは空にできません！|倉庫データが正しくありません！'],
        ['goods', 'require|integer', '商品データは空にできません！|商品データが正しくありません！'],
        ['warehouse', 'require|integer', '倉庫データは空にできません！|倉庫データが正しくありません！'],
        ['nums', 'require|number', '数量データは空にできません！|数量データが正しくありません！'],
        ['integral', 'require|number', '交換ポイントデータは空にできません！|交換ポイントデータが正しくありません！'],
        ['allintegral', 'require|number', '総ポイントデータは空にできません！|総ポイントデータが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}