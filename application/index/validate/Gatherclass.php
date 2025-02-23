<?php
namespace app\index\validate;
use think\Validate;
class Gatherclass extends Validate{
    //默认创建规则
    protected $rule = [
        ['merchant', 'require|integer', '所属商店は空にできません！|所属商店のデータが正しくありません！'],
        ['customer', 'require|integer', '顧客は空にできません！|顧客のデータが正しくありません！'],
        ['time', 'require|date', '書類の日付は空にできません！|書類の日付が正しくありません！'],
        ['number', 'require|RepeatNumber:create', '書類番号は空にできません！|フィールドデータが重複しています！'],
        ['user', 'require|integer', '作成者は空にできません！|作成者のデータが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'customer',
            'time',
            'number'=>'require|RepeatNumber:update',
            'user',
            'more'
        ]
    ];
    //单据编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('gatherclass')->where($sql)->find();
        return empty($nod)?true:'ドキュメント番号[ '.$val.' ]存在する!';
    }
}