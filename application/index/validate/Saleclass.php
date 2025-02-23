<?php
namespace app\index\validate;
use think\Validate;
class Saleclass extends Validate{
    //默认创建规则
    protected $rule = [
        ['merchant', 'require|integer', '所属商店は空にできません！|商店データが正しくありません！'],
        ['customer', 'require|integer', '顧客は空にできません！|顧客データが正しくありません！'],
        ['time', 'require|date', '伝票の時間は空にできません！|伝票の時間が正しくありません！'],
        ['number', 'require|RepeatNumber:create', '伝票番号は空にできません！|フィールドデータが重複しています！'],
        ['total', 'require|number', '伝票金額は空にできません！|伝票金額データが正しくありません！'],
        ['actual', 'require|number', '実際の金額は空にできません！|実際の金額データが正しくありません！'],
        ['money', 'require|number', '実際に受け取った金額は空にできません！|実際の金額データが正しくありません！'],
        ['user', 'require|integer', '作成者は空にできません！|作成者データが正しくありません！'],
        ['account', 'require|integer', '決済口座は空にできません！|決済口座データが正しくありません！'],
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
            'more'
        ]
    ];
    //单据编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('saleclass')->where($sql)->find();
        return empty($nod)?true:'ドキュメント番号[ '.$val.' ]存在する!';
    }
}