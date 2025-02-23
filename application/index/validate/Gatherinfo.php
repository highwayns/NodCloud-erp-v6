<?php
namespace app\index\validate;
use think\Validate;
class Gatherinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['account', 'require|integer', '資金口座データは空にできません！|資金口座データが正しくありません！'],
        ['total', 'require|number', '決済金額データは空にできません！|決済金額データが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}