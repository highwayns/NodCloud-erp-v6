<?php
namespace app\index\controller;
use think\Hook;
use app\index\controller\Acl;
use app\index\model\Opurchaseclass;
use app\index\model\Opurchaseinfo;
use app\index\model\Rpurchaseclass;
class Opurchase extends Acl {
    //采购订单模块
    //---------------(^_^)---------------//
    //主视图
    public function main(){
        return $this->fetch();
    }
    //新增|更新信息
    public function set(){
        $input=input('post.');
        if(isset($input['id'])){
            //验证采购订单详情
            if(isset_full($input,'tab')){
                foreach ($input['tab'] as $tab_key=>$tab_vo) {
                    $tab_vali = $this->validate($tab_vo,'Opurchaseinfo');//详情验证
                    if($tab_vali!==true){
                        return json(['state'=>'error','info'=>'[ データフォーム ]第'.($tab_key+1).'行'.$tab_vali]);
                        exit;
                    }
                }
            }else{
                return json(['state'=>'error','info'=>'データテーブルを空にすることはできません!']);
                exit;
            }
            //验证操作类型
            if(empty($input['id'])){
                //新增
                $input['merchant']=Session('is_merchant_id');//补充商户信息
                $vali = $this->validate($input,'Opurchaseclass');
                if($vali===true){
                    $create_info=Opurchaseclass::create(syn_sql($input,'purchaseclass'));
                    Hook::listen('create_opurchase',$create_info);//采购订单新增行为
                    push_log('新しい発注書[ '.$create_info['number'].' ]');//日志
                    $resule=['state'=>'success'];
                }else{
                    $resule=['state'=>'error','info'=>$vali];
                }
            }else{
                //更新
                $vali = $this->validate($input,'Opurchaseclass.update');
                if($vali===true){
                    $update_info=Opurchaseclass::update(syn_sql($input,'purchaseclass'));
                    Hook::listen('update_opurchase',$update_info);//采购订单更新行为
                    push_log('発注書を更新します[ '.$update_info['number'].' ]');//日志
                    Opurchaseinfo::where(['pid'=>$update_info['id']])->delete();
                    $resule=['state'=>'success'];
                }else{
                    $resule=['state'=>'error','info'=>$vali];
                }
            }
            //添加采购订单详情
            if($resule['state']=='success'){
                $info_pid=empty($input['id'])?$create_info['id']:$update_info['id'];
                foreach ($input['tab'] as $info_vo) {
                    $info_vo['pid']=$info_pid;
                    (isset_full($info_vo,'attr') && $info_vo['attr']=='-1')&&($info_vo['attr']='');//兼容辅助属性
                    Opurchaseinfo::create(syn_sql($info_vo,'purchaseinfo'));
                }
            }
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        //兼容自动审核[新增操作]
        if($resule['state']=='success'&&empty($input['id'])){
            empty(get_sys(['auto_auditing']))||($this->auditing([$create_info['id']],true));
        }
        return json($resule);
    }
    //报表视图
    public function form(){
        return $this->fetch();
    }
    //报表列表
    public function form_list(){
        $input=input('post.');
        //数据完整性判断
        if(isset_full($input,'page') && isset_full($input,'limit')){
            $sql=get_sql($input,[
                'name'=>'continue',
                'number'=>'full_like',
                'type'=>'full_dec_1',
                'start_time'=>'stime',
                'end_time'=>'etime',
                'user'=>'full_division_in',
                'data'=>'full_like'
            ],'opurchaseclass');//构造SQL
            //处理名称搜索
            if(isset_full($input,'name')){
                $goods=get_db_field('goods',['name|py'=>['like','%'.$input['name'].'%']],'id');//取出商品表信息
                if(empty($goods)){
                    sql_assign($sql,'id',[]);//多表查询赋值处理
                }else{
                    $info=get_db_field('opurchaseinfo',['goods'=>['in',$goods]],'pid');//取出详情表数据
                    sql_assign($sql,'id',$info);//多表查询赋值处理
                }
            }
            $sql['merchant']=['in',get_auth_merchant()];//补全授权商户数据
            $sql=auth('opurchaseclass',$sql);//数据鉴权
            $count = Opurchaseclass::where ($sql)->count();//获取总条数
            $arr = Opurchaseclass::with('merchantinfo,userinfo')->where($sql)->page($input['page'],$input['limit'])->order('id desc')->select();//查询分页数据
            $resule=[
                'code'=>0,
                'msg'=>'取得成功',
                'count'=>$count,
                'data'=>$arr
            ];//返回数据
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //详情
    public function info(){
        $input=input('get.');
        //数据完整性判断
        if(isset_full($input,'id')){
            $class=Opurchaseclass::where(['id'=>$input['id']])->find();
            $info=Opurchaseinfo::with('goodsinfo')->where(['pid'=>$input['id']])->select();
            $this->assign('class',$class);
            $this->assign('info',$info);
            return $this->fetch('main');
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //审核
    public function auditing($arr=[],$auto=false){
        (empty($arr))&&($arr=input('post.arr'));//兼容多态审核
        if(empty($arr)){
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }else{
            $class_data=[];//初始化CLASS数据
            $info_data=[];//初始化INFO数据
            //数据检验
            foreach ($arr as $arr_vo) {
                $class=Opurchaseclass::where(['id'=>$arr_vo])->find();
                $info=Opurchaseinfo::where(['pid'=>$arr_vo])->select();
                //判断操作类型
                if(!empty($class['type']['nod'])){
                    //反审核操作
                    $rpurchase=Rpurchaseclass::where(['oid'=>$class['id']])->find();//查找关联采购入库订单
                    if(!empty($rpurchase)){
                        $auto&&(push_log('自動監査注文[ '.$class['number'].' ]失敗,理由:この注文には出庫操作があります!'));//日志
                        return json(['state'=>'error','info'=>'レビュー-注文書[ '.$class['number'].' ]失敗,理由:この注文には出庫操作があります!']);
                        exit;
                    }
                }
                $class_data[$arr_vo]=$class;//转存CLASS数据
                $info_data[$arr_vo]=$info;//转存INFO数据
            }
            //实际操作
            foreach ($arr as $arr_vo) {
                $class=$class_data[$arr_vo];//读取CLASS数据
                $info=$info_data[$arr_vo];//读取INFO数据
                //判断操作类型
                if(empty($class['type']['nod'])){
                    //审核操作
                    Opurchaseclass::update(['id'=>$arr_vo,'type'=>1,'auditinguser'=>Session('is_user_id'),'auditingtime'=>time()]);//更新CLASS数据
                    push_log(($auto?'自動':'').'購買注文の承認[ '.$class['number'].' ]');
                }else{
                    //反审核操作
                    Opurchaseclass::update(['id'=>$arr_vo,'type'=>0,'auditinguser'=>0,'auditingtime'=>0]);//更新CLASS数据
        			push_log ('反購買注文の承認[ '.$class['number'].' ]');
                }
            }
            $resule=['state'=>'success'];
        }
        return $auto?true:json($resule);
    }
    //删除信息
    public function del(){
        $input=input('post.');
        if(isset_full($input,'arr') && is_array($input['arr'])){
            $class=db('purchaseclass')->where(['id'=>['in',$input['arr']]])->select()->ToArray();//获取CLASS数据
            $data = searchdata($class,['type'=>['eq',1]]);//查询已审核单据
            //数据检验
            if(empty($data)){
                foreach ($class as $class_vo) {
                    push_log('発注書を削除します[ '.$class_vo['number'].' ]');//日志
                    Hook::listen('del_opurchase',$class_vo['id']);//采购订单删除行为
                }
                Opurchaseclass::where(['id'=>['in',$input['arr']]])->delete();
                Opurchaseinfo::where(['id'=>['in',$input['arr']]])->delete();
                $resule=['state'=>'success'];
            }else{
                $resule=['state'=>'error','info'=>'注文書[ '.$data[0]['number'].' ]レビュー,削除されていません!'];
            }
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //导出报表信息
    public function exports(){
        $input=input('get.');
        if(isset($input['mode'])){
            push_log('発注書データをエクスポートします');//日志
            $sql=get_sql($input,[
                'name'=>'continue',
                'number'=>'full_like',
                'type'=>'full_dec_1',
                'start_time'=>'stime',
                'end_time'=>'etime',
                'user'=>'full_division_in',
                'data'=>'full_like',
            ],'opurchaseclass');//构造SQL
            //处理名称搜索
            if(isset_full($input,'name')){
                $goods=get_db_field('goods',['name|py'=>['like','%'.$input['name'].'%']],'id');//取出商品表信息
                if(empty($goods)){
                    sql_assign($sql,'id',[]);//多表查询赋值处理
                }else{
                    $info=get_db_field('opurchaseinfo',['goods'=>['in',$goods]],'pid');//取出详情表数据
                    sql_assign($sql,'id',$info);//多表查询赋值处理
                }
            }
            $sql['merchant']=['in',get_auth_merchant()];//补全授权商户数据
            $sql=auth('opurchaseclass',$sql);//数据鉴权
            $arr = Opurchaseclass::with('merchantinfo,userinfo')->where($sql)->order('id desc')->select();//查询数据
            //判断报表类型
            if(empty($input['mode'])){
                //简易报表
                $formfield=get_formfield('opurchase_export','array');//获取字段配置
                //开始构造导出数据
                $excel=[];//初始化导出数据
                //1.填充标题数据
                array_push($excel,['type'=>'title','info'=>'采购订单列表']);
                //2.构造表格数据
                $table_cell=[];//初始化表头数据
                //构造表头数据
                foreach ($formfield as $formfield_vo) {
                    $table_cell[$formfield_vo['key']]=$formfield_vo['text'];
                }
                $table_data=[];//初始化表内数据
                //构造表内数据
                foreach ($arr as $arr_vo) {
                    $row_data=[];
                    //循环字段配置
                    foreach ($formfield as $formfield_vo) {
                        $val='nod_initial';//初始化数据
                        //循环匹配数据源
                        foreach (explode('|',$formfield_vo['data']) as $source) {
                            $val=$val=='nod_initial'?$arr_vo[$source]:(isset($val[$source])?$val[$source]:'');
                        }
                        $row_data[$formfield_vo['key']]=$val;//数据赋值
                    }
                    array_push($table_data,$row_data);//加入行数据
                }
                array_push($excel,['type'=>'table','info'=>['cell'=>$table_cell,'data'=>$table_data]]);//填充表内数据
                //3.导出execl
                export_excel('購入注文リスト',$excel);
            }else{
                //详细报表
                $files=[];//初始化文件列表
                $formfield=get_formfield('opurchase_exports','array');//获取字段配置
                //循环CLASS数据
                foreach ($arr as $arr_vo) {
                    $excel=[];//初始化导出数据
                    //1.填充标题数据
                    array_push($excel,['type'=>'title','info'=>'注文書']);
                    //2.添加基础字段
                    array_push($excel,['type'=>'node','info'=>[
                        'ドキュメント日:'.$arr_vo['time'],
                        '',
                        'ドキュメント番号:'.$arr_vo['number'],
                    ]]);
                    //3.构造表格数据
                    $info=Opurchaseinfo::where(['pid'=>$arr_vo['id']])->select();
                    $table_cell=[];//初始化表头数据
                    //构造表头数据
                    foreach ($formfield as $formfield_vo) {
                        $table_cell[$formfield_vo['key']]=$formfield_vo['text'];
                    }
                    $table_data=[];//初始化表内数据
                    //构造表内数据
                    foreach ($info as $info_vo) {
                        $row_data=[];
                        //循环字段配置
                        foreach ($formfield as $formfield_vo) {
                            $val='nod_initial';//初始化数据
                            //循环匹配数据源
                            foreach (explode('|',$formfield_vo['data']) as $source) {
                                $val=$val=='nod_initial'?$info_vo[$source]:(isset($val[$source])?$val[$source]:'');
                            }
                            $row_data[$formfield_vo['key']]=$val;//数据赋值
                        }
                        array_push($table_data,$row_data);//加入行数据
                    }
                    array_push($excel,['type'=>'table','info'=>['cell'=>$table_cell,'data'=>$table_data]]);//填充表内数据
                    //4.添加基础字段
                    array_push($excel,['type'=>'node','info'=>[
                        'シングルハンドの人:'.$arr_vo['userinfo']['name'],
                        '',
                        '備考情報:'.$arr_vo['data'],
                    ]]);
                    $path=export_excel($arr_vo['number'],$excel,false);//生成文件
                    array_push($files,$path);//添加文件路径数据
                }
                file_to_zip('購入注文の詳細',$files);//打包输出数据
            }
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
    //打印
    public function prints(){
        $input=input('get.');
        if(isset_full($input,'id')){
            $print_name='opurchase';//模板标识
            $class=Opurchaseclass::where(['id'=>$input['id']])->find();
            $info=Opurchaseinfo::where(['pid'=>$input['id']])->select();
            $print_paper=get_sys(['print_paper']);
            //1.获取字段信息
            $formfield=get_formfield('opurchase_print','array');//获取字段配置
            //2.构造表格数据
            $tab_html=get_print_tab($formfield,$info);
            //3.获取模板代码
            $print=get_print($print_name);
            $print_text=$print[empty($print_paper)?'paper4':'paper2'];
            //4.赋值数据
            $this->assign('class',$class);
            $this->assign('tab_html',$tab_html);
            $this->assign('print_name',$print_name);
            $this->assign('paper_type',$print_paper);
            $this->assign('print_text',$print_text);
            return $this->fetch();
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
}