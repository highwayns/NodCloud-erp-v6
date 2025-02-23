<?php
namespace app\index\validate;
use think\Validate;
class Eftclass extends Validate{
    //默认创建规则
    protected $rule = [
        ['merchant', 'require|integer', '商店名は空にできません！|商店データが正しくありません！'],
        ['time', 'require|date', '伝票の日時は空にできません！|伝票の日時が正しくありません！'],
        ['number', 'require|RepeatNumber:create', '伝票番号は空にできません！|フィールドデータが重複しています'],
        ['user', 'require|integer', '作成者は空にできません！|作成者データが正しくありません！'],
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
        $nod=db('eftclass')->where($sql)->find();
        return empty($nod)?true:'ドキュメント番号[ '.$val.' ]存在する!';
    }
}