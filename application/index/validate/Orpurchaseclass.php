<?php
namespace app\index\validate;
use think\Validate;
class Orpurchaseclass extends Validate{
    //默认创建规则
    protected $rule = [
        ['oid', 'require|integer', '所属する注文IDは空にできません！|所属する注文IDデータが正しくありません！'],
        ['merchant', 'require|integer', '所属する商店は空にできません！|所属する商店データが正しくありません！'],
        ['supplier', 'require|integer', '供給業者は空にできません！|供給業者データが正しくありません！'],
        ['time', 'require|date', '伝票日時は空にできません！|伝票日時が正しくありません！'],
        ['number', 'require|RepeatNumber:create', '伝票番号は空にできません！|フィールドデータが重複しています！'],
        ['total', 'require|number', '伝票金額は空にできません！|伝票金額データが正しくありません！'],
        ['actual', 'require|number', '実際の金額は空にできません！|実際の金額データが正しくありません！'],
        ['money', 'require|number', '実際に支払った金額は空にできません！|実際に支払った金額データが正しくありません！'],
        ['user', 'require|integer', '作成者は空にできません！|作成者データが正しくありません！'],
        ['account', 'require|integer', '決済口座は空にできません！|決済口座データが正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //单据编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $nod=db('rpurchaseclass')->where($sql)->find();
        return empty($nod)?true:'ドキュメント番号[ '.$val.' ]存在する!';
    }
}