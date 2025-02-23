<?php
namespace app\index\validate;
use think\Validate;
class Attr extends Validate{
    //正则规则
    protected $regex;
    public function __construct() {
        $this->regex= ['plus' => get_regex('plus')];
    }
    //默认创建规则
    protected $rule = [
        ['name', 'require', '属性名は空にできません！'],
        ['buy', 'require|regex:plus', '購入価格は空にできません！|購入価格が正しくありません！'],
        ['sell', 'require|regex:plus', '販売価格は空にできません！|販売価格が正しくありません！'],
        ['retail', 'require|regex:plus', '小売価格は空にできません！|小売価格が正しくありません！'],
        ['code', 'alphaDash', 'バーコードが正しくありません！'],
        ['enable', 'require|in:0,1', '属性の有効状態が正しくありません！']
    ];
}