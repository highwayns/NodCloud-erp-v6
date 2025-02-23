<?php
namespace app\index\validate;
use think\Validate;
class Eftinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['account', 'require|integer', '出金資金アカウントデータは空にできません！|出金資金アカウントデータが正しくありません！'],
        ['toaccount', 'require|integer', '入金資金アカウントデータは空にできません！|入金資金アカウントデータが正しくありません！'],
        ['total', 'require|number', '決済金額データは空にできません！|決済金額データが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}