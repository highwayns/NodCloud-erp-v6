<?php
namespace app\index\validate;
use think\Validate;
class Rpurchaseclass extends Validate{
    //默认创建规则
    protected $rule = [
        ['supplier', 'require|integer', '仕入先データは空にできません！|仕入先データが正しくありません！'],
        ['time', 'require|date', '伝票日時は空にできません！|伝票日時が正しくありません！'],
        ['number', 'require|RepeatNumber:create', '伝票番号は空にできません！|フィールドデータが重複しています！'],
        ['total', 'require|number', '伝票金額は空にできません！|伝票金額データが正しくありません！'],
        ['actual', 'require|number', '実際の金額は空にできません！|実際の金額データが正しくありません！'],
        ['money', 'require|number', '支払い金額は空にできません！|支払い金額データが正しくありません！'],
        ['user', 'require|integer', '作成者データは空にできません！|作成者データが正しくありません！'],
        ['account', 'require|integer', '決済アカウントは空にできません！|決済アカウントデータが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //单据编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $sql['id']=['neq',$data['id']];
        $nod=db('rpurchaseclass')->where($sql)->find();
        return empty($nod)?true:'ドキュメント番号[ '.$val.' ]存在する!';
    }
}