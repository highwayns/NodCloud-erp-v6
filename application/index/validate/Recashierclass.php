<?php
namespace app\index\validate;
use think\Validate;
class Recashierclass extends Validate{
    //默认创建规则
    protected $rule = [
        ['merchant', 'require|integer', '所属商店データは空にできません！|所属商店データが正しくありません！'],
        ['customer', 'require|integer', '顧客データは空にできません！|顧客データが正しくありません！'],
        ['time', 'require|date', '書類の日付は空にできません！|書類の日付が正しくありません！'],
        ['number', 'require|RepeatNumber:create', '書類番号は空にできません！|フィールドデータが重複しています！'],
        ['total', 'require|number', '書類金額は空にできません！|書類金額が正しくありません！'],
        ['actual', 'require|number', '実際の金額は空にできません！|実際の金額が正しくありません！'],
        ['money', 'require|number', '支払金額は空にできません！|支払金額が正しくありません！'],
        ['user', 'require|integer', '作成者データは空にできません！|作成者データが正しくありません！'],
        ['account', 'require|integer', '決済口座データは空にできません！|決済口座データが正しくありません！'],
        ['integral', 'number', 'ポイントデータが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'customer',
            'time',
            'number'=>'require|RepeatNumber:update',
            'total',
            'actual',
            'money',
            'user',
            'account',
            'integral',
            'more'
        ]
    ];
    //单据编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('recashierclass')->where($sql)->find();
        return empty($nod)?true:'ドキュメント番号[ '.$val.' ]存在する!';
    }
}