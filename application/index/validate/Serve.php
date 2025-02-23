<?php
namespace app\index\validate;
use think\Validate;
class Serve extends Validate{
    //默认创建规则
    protected $rule = [
        ['name', 'require|RepeatName:create', 'サービス項目名は空にできません！|フィールドデータが重複しています！'],
        ['number', 'RepeatNumber:create', 'フィールドデータが重複しています！'],
        ['price', 'number', '期初残高の形式が正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'name'=>'require|RepeatName:update',
            'number'=>'RepeatNumber:update',
            'price',
            'more'
        ]
    ];
    //服务项目名称重复性判断
    protected function RepeatName($val,$rule,$data){
        $sql['name']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('serve')->where($sql)->find();
        return empty($nod)?true:'サービスアイテム名[ '.$val.' ]存在する!';
    }
    //服务项目编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('serve')->where($sql)->find();
        return empty($nod)?true:'サービスアイテム番号[ '.$val.' ]存在する!';
    }
}