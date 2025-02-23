<?php
namespace app\index\validate;
use think\Validate;
class Purchaseclass extends Validate{
    //默认创建规则
    protected $rule = [
        ['merchant', 'require|integer', '商店データは空にできません！|商店データが正しくありません！'],
        ['supplier', 'require|integer', 'サプライヤーデータは空にできません！|サプライヤーデータが正しくありません！'],
        ['time', 'require|date', '伝票日時は空にできません！|伝票日時が正しくありません！'],
        ['number', 'require|RepeatNumber:create', '伝票番号は空にできません！|フィールドデータが重複しています！'],
        ['total', 'require|number', '伝票金額は空にできません！|伝票金額が正しくありません！'],
        ['actual', 'require|number', '実際の金額は空にできません！|実際の金額が正しくありません！'],
        ['money', 'require|number', '支払金額は空にできません！|支払金額が正しくありません！'],
        ['user', 'require|integer', '作成者データは空にできません！|作成者データが正しくありません！'],
        ['account', 'require|integer', '決済アカウントは空にできません！|決済アカウントが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'supplier',
            'time',
            'number'=>'require|RepeatNumber:update',
            'total',
            'actual',
            'money',
            'user',
            'account',
            'more'
        ]
    ];
    //单据编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('purchaseclass')->where($sql)->find();
        return empty($nod)?true:'ドキュメント番号[ '.$val.' ]存在する!';
    }
}