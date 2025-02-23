<?php
namespace app\index\validate;
use think\Validate;
class Resaleinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['room', 'require|integer', '倉庫データは空にできません！|倉庫データが正しくありません！'],
        ['goods', 'require|integer', '商品データは空にできません！|商品データが正しくありません！'],
        ['warehouse', 'require|integer', '倉庫データは空にできません！|倉庫データが正しくありません！'],
        ['nums', 'require|number', '数量データは空にできません！|数量データが正しくありません！'],
        ['price', 'require|number', '単価データは空にできません！|単価データが正しくありません！'],
        ['total', 'require|number', '総額データは空にできません！|総額データが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}