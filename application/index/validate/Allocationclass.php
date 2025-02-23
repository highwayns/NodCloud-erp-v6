<?php
namespace app\index\validate;
use think\Validate;
class Allocationclass extends Validate{
    //默认创建规则
    protected $rule = [
        ['merchant', 'require|integer', '所属商戶は空白にできません！|所属商戶のデータが正しくありません！'],
        ['time', 'require|date', '伝票の時間は空白にできません！|伝票の時間が正しくありません！'],
        ['number', 'require|RepeatNumber:create', '伝票番号は空白にできません！|フィールドデータが重複しています'],
        ['user', 'require|integer', '作成者は空白にできません！|作成者のデータが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
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
        $nod=db('allocationclass')->where($sql)->find();
        return empty($nod)?true:'ドキュメント番号[ '.$val.' ]存在する!';
    }
}