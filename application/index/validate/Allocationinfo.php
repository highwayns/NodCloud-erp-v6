<?php
namespace app\index\validate;
use think\Validate;
class Allocationinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['room', 'require|integer', '倉庫データは空白にできません！|倉庫データが正しくありません！'],
        ['goods', 'require|integer', '商品データは空白にできません！|商品データが正しくありません！'],
        ['warehouse', 'require|integer', '倉庫データは空白にできません！|倉庫データが正しくありません！'],
        ['nums', 'require|number', '数量データは空白にできません！|数量データが正しくありません！'],
        ['towarehouse', 'require|integer', '調整倉庫は空白にできません！|調整倉庫データが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}