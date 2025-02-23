<?php
namespace app\index\validate;
use think\Validate;
class Itemorderinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['serve', 'require|integer', 'サービスデータは空にできません！|サービスデータが正しくありません！'],
        ['nums', 'require|number', '数量データは空にできません！|数量データが正しくありません！'],
        ['price', 'require|number', '単価データは空にできません！|単価データが正しくありません！'],
        ['total', 'require|number', '総額データは空にできません！|総額データが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}