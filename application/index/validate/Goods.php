<?php
namespace app\index\validate;
use think\Validate;
class Goods extends Validate{
    //正则规则
    protected $regex;
    public function __construct() {
        $this->regex= ['plus' => get_regex('plus')];
    }
    //默认创建规则
    protected $rule = [
        ['name', 'require|RepeatName:create', '商品名は空にできません！|フィールドデータが重複しています！'],
        ['number', 'RepeatNumber:create', 'フィールドデータが重複しています！'],
        ['class', 'require', '商品分類は空にできません！'],
        ['buy', 'require|regex:plus', '購入価格は空にできません！|購入価格が正しくありません！'],
        ['sell', 'require|regex:plus', '販売価格は空にできません！|販売価格が正しくありません！'],
        ['retail', 'require|regex:plus', '小売価格は空にできません！|小売価格が正しくありません！'],
        ['integral', 'regex:plus', '交換ポイントが正しくありません！'],
        ['code', 'alphaDash', 'バーコードが正しくありません！'],
        ['stocktip', 'regex:plus', '在庫しきい値が正しくありません！'],
        ['more', 'array', '拡張情報の形式が正しくありません！']
    ];
    //场景规则
    protected $scene = [
        'update'  =>  [
            'name'=>'require|RepeatName:update',
            'number'=>'RepeatNumber:update',
            'class',
            'buy',
            'sell',
            'retail',
            'integral',
            'code',
            'stocktip',
            'more'
        ]
    ];
    //商品名称重复性判断
    protected function RepeatName($val,$rule,$data){
        $sql['name']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('goods')->where($sql)->find();
        return empty($nod)?true:'製品名[ '.$val.' ]存在する!';
    }
    //商品编号重复性判断
    protected function RepeatNumber($val,$rule,$data){
        $sql['number']=$val;
        $rule=='update'&&($sql['id']=['neq',$data['id']]);
        $nod=db('goods')->where($sql)->find();
        return empty($nod)?true:'商品番号[ '.$val.' ]存在する!';
    }
}