<?php
namespace app\index\validate;
use think\Validate;
class Attribute extends Validate{
    //默认创建规则
    protected $rule = [
        ['name', 'require|RepeatName:create', '補助属性名は空にできません！|フィールドデータが重複しています！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'name'=>'require|RepeatName:update',
            'more'
        ]
    ];
    //辅助属性重复性判断
    protected function RepeatName($val,$rule,$data){
        $sql['name']=$val;
        $sql['pid']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('attribute')->where($sql)->find();
        return empty($nod)?true:'補助属性名[ '.$val.' ]已存在!';
    }
}