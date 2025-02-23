<?php
namespace app\index\validate;
use think\Validate;
class Formfield extends Validate{
    //默认创建规则
    protected $rule = [
        ['name', 'require', 'フォーム名は空にできません！'],
        ['key', 'require|RepeatKey:create', 'フォーム識別子は空にできません！|フィールドデータが重複しています！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'key'=>'require|RepeatKey:update'
        ],
    ];
    //表单标识重复性判断
    protected function RepeatKey($val,$rule,$data){
        $sql['key']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('formfield')->where($sql)->find();
        return empty($nod)?true:'フォーム識別[ '.$val.' ]存在する!';
    }
    
    
}