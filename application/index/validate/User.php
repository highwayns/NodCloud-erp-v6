<?php
namespace app\index\validate;
use think\Validate;
class User extends Validate{
    //默认创建规则
    protected $rule = [
        ['user', 'require|RepeatUser:create', 'スタッフアカウントは空にできません！|フィールドデータが重複しています！'],
        ['pwd', 'require', 'スタッフパスワードは空にできません！'],
        ['merchant', 'require', '所属する商人は空にできません！'],
        ['name', 'require', 'スタッフ名は空にできません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'user'=>'require|RepeatUser:update',
            'merchant',
            'name',
            'more'
        ]
    ];
    //职员账号重复性判断
    protected function RepeatUser($val,$rule,$data){
        $sql['user']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('user')->where($sql)->find();
        return empty($nod)?true:'従業員アカウント[ '.$val.' ]存在する!';
    }
}