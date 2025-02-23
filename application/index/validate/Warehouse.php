<?php
namespace app\index\validate;
use think\Validate;
class Warehouse extends Validate{
    //默认创建规则
    protected $rule = [
        ['name', 'require|RepeatName:create', '倉庫名は空にできません！|フィールドデータが重複しています！'],
        ['number', 'RepeatNumber:create', 'フィールドデータが重複しています！'],
        ['tel', 'CheckTel', 'フィールド形式が正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'name'=>'require|RepeatName:update',
            'number'=>'RepeatNumber:update',
            'tel',
            'more'
        ]
    ];
    //仓库名称重复性判断
    protected function RepeatName($val,$rule,$data){
        $sql['name']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('warehouse')->where($sql)->find();
        return empty($nod)?true:'倉庫名[ '.$val.' ]存在する!';
    }
    //仓库编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('warehouse')->where($sql)->find();
        return empty($nod)?true:'倉庫番号[ '.$val.' ]存在する!';
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