<?php
namespace app\index\validate;
use think\Validate;
class Otpaymentinfo extends Validate{
    //默认创建规则
    protected $rule = [
        ['account', 'require|integer', '資金アカウントのデータは空にできません！|資金アカウントのデータが正しくありません！'],
        ['total', 'require|number', '決済金額のデータは空にできません！|決済金額のデータが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
}