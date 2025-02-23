<?php
namespace app\index\validate;
use think\Validate;
class Unit extends Validate{
    //默认创建规则
    protected $rule = [
        ['name', 'require|RepeatName:create', '計量単位名称は空にできません！|フィールドデータが重複しています！'],
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
    //计量单位名称重复性判断
    protected function RepeatName($val,$rule,$data){
        $sql['name']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('unit')->where($sql)->find();
        return empty($nod)?true:'測定ユニットの名前[ '.$val.' ]存在する!';
    }
    //计量单位编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('unit')->where($sql)->find();
        return empty($nod)?true:'計量ユニット数[ '.$val.' ]存在する!';
    }
}