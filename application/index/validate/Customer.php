<?php
namespace app\index\validate;
use think\Validate;
class Customer extends Validate{
    //默认创建规则
    protected $rule = [
        ['name', 'require|RepeatName:create', '顧客名は空にできません！|フィールドデータが重複しています'],
        ['number', 'RepeatNumber:create', 'フィールドデータが重複しています'],
        ['tel', 'CheckTel', 'フィールド形式が正しくありません'],
        ['birthday', 'date', '顧客の誕生日形式が正しくありません！'],
        ['account', 'number', '銀行口座番号形式が正しくありません！'],
        ['tax', 'alphaNum|length:15,20', '顧客の税番号形式が正しくありません！|顧客の税番号の長さが正しくありません！'],
        ['email', 'email', 'メールアドレス形式が正しくありません！'],
        ['more', 'array', '拡張情報形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'name'=>'require|RepeatName:update',
            'number'=>'RepeatNumber:update',
            'tel',
            'birthday',
            'account',
            'tax',
            'email',
            'more'
        ]
    ];
    //客户名称重复性判断
    protected function RepeatName($val,$rule,$data){
        $sql['name']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('customer')->where($sql)->find();
        return empty($nod)?true:'顧客名[ '.$val.' ]已存在!';
    }
    //客户编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('customer')->where($sql)->find();
        return empty($nod)?true:'顧客番号[ '.$val.' ]已存在!';
    }
    //联系电话格式判断
    protected function CheckTel($val,$rule,$data){
        preg_match(get_regex('tel'), $val, $tel, PREG_OFFSET_CAPTURE, 0);//手机号正则判断
        preg_match(get_regex('phone'), $val, $phone, PREG_OFFSET_CAPTURE, 0);//座机正则判断
        if(empty($tel) && empty($phone)){
            return '電話番号に連絡してください[ '.$val.' ]誤った形式!';
        }else{
            return true;
        }
    }
}