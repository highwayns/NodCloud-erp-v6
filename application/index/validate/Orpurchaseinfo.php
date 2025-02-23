<?php
namespace app\index\validate;
use think\Validate;
class Orpurchaseinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['oid', 'require|integer', '所属する注文詳細IDは空にできません！|所属する注文詳細IDデータが正しくありません！'],
        ['warehouse', 'require|integer', '倉庫データは空にできません！|倉庫データが正しくありません！'],
        ['nums', 'require|number', '数量データは空にできません！|数量データが正しくありません！'],
        ['price', 'require|number', '単価データは空にできません！|単価データが正しくありません！'],
        ['total', 'require|number', '総額データは空にできません！|総額データが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}