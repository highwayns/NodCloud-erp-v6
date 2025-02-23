<?php
namespace app\index\validate;
use think\Validate;
class Account extends Validate{
    //默认创建规则
    protected $rule = [
        ['name', 'require|RepeatName:create', '資金アカウント名は空白にできません！|フィールドデータが重複しています'],
        ['number', 'RepeatNumber:create', 'フィールドデータの繰り返し'],
        ['initial', 'number', '期間の初期期間のエラー形式!'],
        ['createtime', 'date', 'アカウント開設の時間形式!'],
        ['more', 'array', '誤った拡張情報形式!']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'name'=>'require|RepeatName:update',
            'number'=>'RepeatNumber:update',
            'initial',
            'createtime',
            'more'
        ]
    ];
    //资金账户名称重复性判断
    protected function RepeatName($val,$rule,$data){
        $sql['name']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('account')->where($sql)->find();
        return empty($nod)?true:'ファンドアカウント名[ '.$val.' ]存在する!';
    }
    //资金账户编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('account')->where($sql)->find();
        return empty($nod)?true:'資本口座番号[ '.$val.' ]存在する!';
    }
}