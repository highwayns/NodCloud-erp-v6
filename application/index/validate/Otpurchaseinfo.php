<?php
namespace app\index\validate;
use think\Validate;
class Otpurchaseinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['goods', 'require|integer', '商品データは空にできません！|商品データが正しくありません！'],
        ['warehouse', 'require|integer', '倉庫データは空にできません！|倉庫データが正しくありません！'],
        ['nums', 'require|number', '数量データは空にできません！|数量データが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}