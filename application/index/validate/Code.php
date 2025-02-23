<?php
namespace app\index\validate;
use think\Validate;
class Code extends Validate{
    //默认创建规则
    protected $rule = [
        ['name', 'require|RepeatName:create', 'バーコード名は空にできません！|フィールドデータが重複しています！'],
        ['code', 'require', 'バーコード内容は空にできません！'],
        ['more', 'array', '拡張情報の形式が不正です！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'name'=>'require|RepeatName:update',
            'code'=>'require',
            'more'
        ]
    ];
    //条码名称重复性判断
    protected function RepeatName($val,$rule,$data){
        $sql['name']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('code')->where($sql)->find();
        return empty($nod)?true:'バーコード名[ '.$val.' ]存在する!';
    }
}