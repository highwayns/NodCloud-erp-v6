<?php
namespace app\index\validate;
use think\Validate;
class Cashierclass extends Validate{
    //默认创建规则
    protected $rule = [
        ['merchant', 'require|integer', '所属商店は空にできません！|所属商店のデータが不正です！'],
        ['customer', 'require|integer', '顧客は空にできません！|顧客のデータが不正です！'],
        ['time', 'require|date', '伝票の時間は空にできません！|伝票の時間が不正です！'],
        ['number', 'require|RepeatNumber:create', '伝票番号は空にできません！|フィールドデータが重複しています！'],
        ['total', 'require|number', '伝票金額は空にできません！|伝票金額のデータが不正です！'],
        ['actual', 'require|number', '実際の金額は空にできません！|実際の金額のデータが不正です！'],
        ['money', 'require|number', '実際に受け取った金額は空にできません！|実際に受け取った金額のデータが不正です！'],
        ['user', 'require|integer', '作成者は空にできません！|作成者のデータが不正です！'],
        ['account', 'integer', '決済口座のデータが不正です！'],
        ['integral', 'number', 'プレゼントされたポイントのデータが不正です！'],
        ['payinfo', 'array', '支払い口座のデータが不正です！'],
        ['paytype', 'require|in:0,1|Valipaytype', '支払い方法は空にできません！|支払いタイプが不正です！|合法性検証に合格していません'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'customer',
            'time',
            'number'=>'require|RepeatNumber:update',
            'total',
            'actual',
            'money',
            'user',
            'account',
            'integral',
            'payinfo',
            'paytype',
            'more'
        ]
    ];
    //付款类型合法性判断
    protected function Valipaytype($val,$rule,$data){
        if($data['paytype']==0 && empty($data['account'])){
            $vali='個別の支払い[ 決済口座 ]正しくない!';
        }elseif($data['paytype']==1 && empty($data['payinfo'])){
            $vali='ポートフォリオ[ 支払いデータ ]正しくない!';
        }else{
            $vali=true;
        }
        return $vali;
    }
    //组合支付数据合法性判断
    protected function Valipayinfo($val,$rule,$data){
        if($data['paytype']==1 && empty($data['account'])){
            $vali='個別の支払い[ 決済口座 ]正しくない!';
        }else{
            $vali=true;
        }
        return $vali;
    }
    //单据编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('cashierclass')->where($sql)->find();
        return empty($nod)?true:'ドキュメント番号[ '.$val.' ]存在する!';
    }
}