<?php
namespace app\index\validate;
use think\Validate;
class Brand extends Validate{
    //默认创建规则
    protected $rule = [
        ['name', 'require|RepeatName:create', 'ブランド名は空にできません！|フィールドデータが重複しています！'],
        ['number', 'RepeatNumber:create', 'フィールドデータが重複しています！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'name'=>'require|RepeatName:update',
            'number'=>'RepeatNumber:update',
            'more'
        ]
    ];
    //品牌名称重复性判断
    protected function RepeatName($val,$rule,$data){
        $sql['name']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('brand')->where($sql)->find();
        return empty($nod)?true:'ブランド名[ '.$val.' ]存在する!';
    }
    //品牌编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('brand')->where($sql)->find();
        return empty($nod)?true:'ブランド番号[ '.$val.' ]存在する!';
    }
}