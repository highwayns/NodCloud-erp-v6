<?php
namespace app\index\controller;
use think\Hook;
use app\index\controller\Acl;
use app\index\model\Recashierclass;
use app\index\model\Recashierinfo;
use app\index\model\Room;
use app\index\model\Roominfo;
use app\index\model\Account;
use app\index\model\Accountinfo;
use app\index\model\Serial;
use app\index\model\Serialinfo;
use app\index\model\Customer;
use app\index\model\Integral;
class Recashier extends Acl {
    //零售退货模块
    //---------------(^_^)---------------//
    //主视图
    public function main(){
        return $this->fetch();
    }
    //新增|更新信息
    public function set(){
        $input=input('post.');
        if(isset($input['id'])){
            //验证零售退货单详情
            if(isset_full($input,'tab')){
                foreach ($input['tab'] as $tab_key=>$tab_vo) {
                    $tab_vali = $this->validate($tab_vo,'Recashierinfo');//详情验证
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
                $vali = $this->validate($input,'Recashierclass');
                if($vali===true){
                    $create_info=Recashierclass::create(syn_sql($input,'recashierclass'));
                    Hook::listen('create_recashier',$create_info);//零售退货单新增行为
                    push_log('小売返品請求書を追加しました[ '.$create_info['number'].' ]');//日志
                    $resule=['state'=>'success'];
                }else{
                    $resule=['state'=>'error','info'=>$vali];
                }
            }else{
                //更新
                $vali = $this->validate($input,'Recashierclass.update');
                if($vali===true){
                    $update_info=Recashierclass::update(syn_sql($input,'recashierclass'));
                    Hook::listen('update_recashier',$update_info);//零售退货单更新行为
                    push_log('小売リターンを更新します[ '.$update_info['number'].' ]');//日志
                    Recashierinfo::where(['pid'=>$update_info['id']])->delete();
                    $resule=['state'=>'success'];
                }else{
                    $resule=['state'=>'error','info'=>$vali];
                }
            }
            //添加零售退货单详情
            if($resule['state']=='success'){
                $info_pid=empty($input['id'])?$create_info['id']:$update_info['id'];
                foreach ($input['tab'] as $info_vo) {
                    $info_vo['pid']=$info_pid;
                    (isset_full($info_vo,'serial')&&$info_vo['serial']=='&amp;nbsp;')&&($info_vo['serial']='');//兼容串码
                    Recashierinfo::create(syn_sql($info_vo,'recashierinfo'));
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
                'customer'=>'full_division_in',
                'start_time'=>'stime',
                'end_time'=>'etime',
                'type'=>'full_dec_1',
                'warehouse'=>'continue',
                'user'=>'full_division_in',
                'account'=>'full_division_in',
                'data'=>'full_like',
            ],'recashierclass');//构造SQL
            //处理名称搜索
            if(isset_full($input,'name')){
                $goods=get_db_field('goods',['name|py'=>['like','%'.$input['name'].'%']],'id');//取出商品表信息
                if(empty($goods)){
                    sql_assign($sql,'id',[]);//多表查询赋值处理
                }else{
                    $info=get_db_field('recashierinfo',['goods'=>['in',$goods]],'pid');//取出详情表数据
                    sql_assign($sql,'id',$info);//多表查询赋值处理
                }
            }
            //处理仓库搜索
            if(isset_full($input,'warehouse')){
                $info=get_db_field('recashierinfo',['warehouse'=>['in',explode(",",$input['warehouse'])]],'pid');//取出详情表数据
                sql_assign($sql,'id',$info,'intersect');//多表查询赋值处理
            }
            $sql['merchant']=['in',get_auth_merchant()];//补全授权商户数据
            $sql=auth('recashierclass',$sql);//数据鉴权
            $count = Recashierclass::where ($sql)->count();//获取总条数
            $arr = Recashierclass::with('merchantinfo,customerinfo,userinfo,accountinfo')->where($sql)->page($input['page'],$input['limit'])->order('id desc')->select();//查询分页数据
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
            $class=Recashierclass::where(['id'=>$input['id']])->find();
            $info=Recashierinfo::with('roominfo,goodsinfo,warehouseinfo')->where(['pid'=>$input['id']])->select()->toarray();
            foreach ($info as $info_key=>$info_vo) {
                //改造串码数据
                $info[$info_key]['roominfo']['serialinfo']=implode(",",arraychange(searchdata($info_vo['roominfo']['serialinfo'],['type|nod'=>['eq',1]]),'code'));
            }
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
                $class=Recashierclass::where(['id'=>$arr_vo])->find();
                $info=Recashierinfo::where(['pid'=>$arr_vo])->select();
                //判断操作类型
                if(empty($class['type']['nod'])){
                    //审核操作
                    foreach ($info as $info_key=>$info_vo) {
                        if(!empty($info_vo['serial'])){
                            $serial_sql=['code'=>['in',explode(',',$info_vo['serial'])],'type'=>['neq',1]];
                            $serial=Serial::where($serial_sql)->find();//查找串码状态为非未销售
                            if(!empty($serial)){
                                $auto&&(push_log('小売返品リストを自動的に確認します[ '.$class['number'].' ]失敗,理由:第'.($info_key+1).'文字列コードのステータスが正しくありません!'));//日志
                                return json(['state'=>'error','info'=>'レビュー-小売返品リスト[ '.$class['number'].' ]失敗,理由:第'.($info_key+1).'文字列コードのステータスが正しくありません!']);
                                exit;
                            }
                        }
                    }
                }else{
                    //反审核操作
                    foreach ($info as $info_key=>$info_vo) {
                        if(!empty($info_vo['serial'])){
                            $serial_sql=['code'=>['in',explode(',',$info_vo['serial'])],'type'=>['neq',0]];
                            $serial=Serial::where($serial_sql)->find();//查找串码状态为非已销售
                            if(!empty($serial)){
                                return json(['state'=>'error','info'=>'反レビュー-小売返品リスト[ '.$class['number'].' ]第'.($info_key+1).'文字列コードのステータスが正しくありません!']);
                                exit;
                            }
                        }
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
                    foreach ($info as $info_vo) {
                        //设置仓储信息
                        Room::where(['id'=>$info_vo['room']])->setInc('nums',$info_vo['nums']);//更新仓储数据[+]
                        //新增仓储详情
                        $roominfo_sql['pid']=$info_vo['room'];
                        $roominfo_sql['type']=10;
                        $roominfo_sql['class']=$arr_vo;
                        $roominfo_sql['info']=$info_vo['id'];
                        $roominfo_sql['nums']=$info_vo['nums'];
                        Roominfo::create($roominfo_sql);
                        //操作串码信息
                        if (!empty($info_vo['serial'])){
                            $serial_arr=explode(',',$info_vo['serial']);//分割串码信息
                            foreach ($serial_arr as $serial_arr_vo) {
                                //设置串码信息
                                $serial=Serial::where(['code'=>$serial_arr_vo])->find();//获取串码信息
                                Serial::update(['id'=>$serial['id'],'type'=>0]);
                                //新增串码详情
                                Serialinfo::create (['pid'=>$serial['id'],'type'=>9,'class'=>$arr_vo]);
                            }
                        }
                    }
                    //处理资金数据
                    Account::where (['id'=>$class['account']])->setDec('balance',$class['money']);//操作资金账户[-]
                    Accountinfo::create (['pid'=>$class['account'],'set'=>0,'money'=>$class['money'],'type'=>10,'time'=>time(),'user'=>Session('is_user_id'),'class'=>$arr_vo]);//新增资金详情
                    //处理客户积分
                    if(!empty($class['integral'])){
                        Customer::where (['id'=>$class['customer']])->setDec('integral',$class['integral']);//操作客户积分[-]
                        Integral::create(['pid'=>$class['customer'],'set'=>0,'integral'=>$class['integral'],'type'=>2,'time'=>time(),'user'=>Session('is_user_id'),'class'=>$arr_vo]);//新增积分详情
                    }
                    Recashierclass::update(['id'=>$arr_vo,'type'=>1,'auditinguser'=>Session('is_user_id'),'auditingtime'=>time()]);//更新CLASS数据
                    set_summary('recashier',$arr_vo,true);//更新统计表
                    push_log(($auto?'自動':'').'小売返品リストを確認します[ '.$class['number'].' ]');
                }else{
                    //反审核操作
                    foreach ($info as $info_vo){
                        Room::where (['id'=>$info_vo['room']])->setDec('nums',$info_vo['nums']);//更新仓储数据[-]
                        if(!empty($info_vo['serial'])){
                            $serial=Serial::where(['code'=>['in',explode(',',$info_vo['serial'])]])->select();//获取串码数据
                            foreach ($serial as $serial_vo) {
                                //设置串码数据
                                Serial::update(['id'=>$serial_vo['id'],'type'=>1]);
                                Serialinfo::where(['pid'=>$serial_vo['id'],'type'=>9,'class'=>$arr_vo])->delete();//删除串码详情
                            }
                        }
                    }
                    Roominfo::where(['type'=>10,'class'=>$arr_vo])->delete();//删除仓储详情
                    //处理资金数据
                    Account::where (['id'=>$class['account']])->setInc('balance',$class['money']);//操作资金账户[+]
                    Accountinfo::destroy (['type'=>10,'class'=>$arr_vo]);//删除资金详情
                    //处理客户积分
                    if(!empty($class['integral'])){
                        Customer::where (['id'=>$class['customer']])->setInc('integral',$class['integral']);//操作客户积分[+]
                        Integral::destroy(['type'=>2,'class'=>$arr_vo]);//删除积分详情
                    }
                    Recashierclass::update(['id'=>$arr_vo,'type'=>0,'auditinguser'=>0,'auditingtime'=>0]);//更新CLASS数据
                    set_summary('recashier',$arr_vo,false);//更新统计表
                    push_log ('反レビュー小売返品リスト[ '.$class['number'].' ]');
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
            $class=db('recashierclass')->where(['id'=>['in',$input['arr']]])->select()->ToArray();//获取CLASS数据
            $data = searchdata($class,['type'=>['eq',1]]);//查询已审核单据
            //数据检验
            if(empty($data)){
                foreach ($class as $class_vo) {
                    push_log('小売返品リストを削除します[ '.$class_vo['number'].' ]');//日志
                    Hook::listen('del_recashier',$class_vo['id']);//零售退货单删除行为
                }
                Recashierclass::where(['id'=>['in',$input['arr']]])->delete();
                Recashierinfo::where(['pid'=>['in',$input['arr']]])->delete();
                $resule=['state'=>'success'];
            }else{
                $resule=['state'=>'error','info'=>'小売返品リスト[ '.$data[0]['number'].' ]レビュー、削除できません!'];
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
            push_log('小売返品リストデータをエクスポートします');//日志
            $sql=get_sql($input,[
                'name'=>'continue',
                'number'=>'full_like',
                'customer'=>'full_division_in',
                'start_time'=>'stime',
                'end_time'=>'etime',
                'type'=>'full_dec_1',
                'warehouse'=>'continue',
                'user'=>'full_division_in',
                'account'=>'full_division_in',
                'data'=>'full_like',
            ],'recashierclass');//构造SQL
            //处理名称搜索
            if(isset_full($input,'name')){
                $goods=get_db_field('goods',['name|py'=>['like','%'.$input['name'].'%']],'id');//取出商品表信息
                if(empty($goods)){
                    sql_assign($sql,'id',[]);//多表查询赋值处理
                }else{
                    $info=get_db_field('recashierinfo',['goods'=>['in',$goods]],'pid');//取出详情表数据
                    sql_assign($sql,'id',$info);//多表查询赋值处理
                }
            }
            //处理仓库搜索
            if(isset_full($input,'warehouse')){
                $info=get_db_field('recashierinfo',['warehouse'=>['in',explode(",",$input['warehouse'])]],'pid');//取出详情表数据
                sql_assign($sql,'id',$info,'intersect');//多表查询赋值处理
            }
            $sql['merchant']=['in',get_auth_merchant()];//补全授权商户数据
            $sql=auth('recashierclass',$sql);//数据鉴权
            $arr = Recashierclass::with('merchantinfo,customerinfo,userinfo,accountinfo')->where($sql)->order('id desc')->select();//查询数据
            //判断报表类型
            if(empty($input['mode'])){
                //简易报表
                $formfield=get_formfield('recashier_export','array');//获取字段配置
                //开始构造导出数据
                $excel=[];//初始化导出数据
                //1.填充标题数据
                array_push($excel,['type'=>'title','info'=>'小売返品リストのリスト']);
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
                //3.添加汇总信息
                $sum_arr=get_sums($table_data,['total','actual','money']);
                array_push($excel,['type'=>'node','info'=>[
                    'ドキュメントの総量:'.$sum_arr['total'],
                    '実際の総額:'.$sum_arr['actual'],
                    '実際の支払いの総額:'.$sum_arr['money'],
                ]]);//填充汇总信息
                //4.导出execl
                export_excel('小売返品リストのリスト',$excel);
            }else{
                //详细报表
                $files=[];//初始化文件列表
                $formfield=get_formfield('recashier_exports','array');//获取字段配置
                //配置字段
                $sys=get_sys(['enable_batch','enable_serial']);
                empty($sys['enable_batch'])&&(arrs_key_del($formfield,['key','batch']));
                empty($sys['enable_serial'])&&(arrs_key_del($formfield,['key','serial']));
                //循环CLASS数据
                foreach ($arr as $arr_vo) {
                    $excel=[];//初始化导出数据
                    //1.填充标题数据
                    array_push($excel,['type'=>'title','info'=>'小売返品リスト']);
                    //2.添加基础字段
                    array_push($excel,['type'=>'node','info'=>[
                        'クライアント:'.$arr_vo['customerinfo']['name'],
                        '',
                        'ドキュメント日:'.$arr_vo['time'],
                        '',
                        'ドキュメント番号:'.$arr_vo['number'],
                    ]]);
                    //3.构造表格数据
                    $info=Recashierinfo::where(['pid'=>$arr_vo['id']])->select();
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
                        'ドキュメント量:'.$arr_vo['total'],
                        '',
                        '実際の金額:'.$arr_vo['actual'],
                        '',
                        '実際の支払い額:'.$arr_vo['money'],
                    ]]);
                    //5.添加基础字段
                    array_push($excel,['type'=>'node','info'=>[
                        'シングルハンドの人:'.$arr_vo['userinfo']['name'],
                        '',
                        '決済口座:'.$arr_vo['accountinfo']['name'],
                        '',
                        'ポイントを差し引く:'.$arr_vo['integral'],
                    ]]);
                    //6.添加基础字段
                    array_push($excel,['type'=>'node','info'=>[
                        '備考情報:'.$arr_vo['data'],
                    ]]);
                    $path=export_excel($arr_vo['number'],$excel,false);//生成文件
                    array_push($files,$path);//添加文件路径数据
                }
                file_to_zip('小売返品注文の詳細',$files);//打包输出数据
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
            $print_name='recashier';//模板标识
            $class=Recashierclass::where(['id'=>$input['id']])->find();
            $info=Recashierinfo::where(['pid'=>$input['id']])->select();
            $sys=get_sys(['enable_batch','enable_serial','print_paper']);
            //1.获取字段信息
            $formfield=get_formfield('recashier_print','array');//获取字段配置
            //2.配置字段
            empty($sys['enable_batch'])&&(arrs_key_del($formfield,['key','batch']));
            empty($sys['enable_serial'])&&(arrs_key_del($formfield,['key','serial']));
            //3.构造表格数据
            $tab_html=get_print_tab($formfield,$info);
            //4.获取模板代码
            $print=get_print($print_name);
            $print_text=$print[empty($sys['print_paper'])?'paper4':'paper2'];
            //5.赋值数据
            $this->assign('class',$class);
            $this->assign('tab_html',$tab_html);
            $this->assign('print_name',$print_name);
            $this->assign('paper_type',$sys['print_paper']);
            $this->assign('print_text',$print_text);
            return $this->fetch();
        }else{
            $resule=['state'=>'error','info'=>'入力されたパラメーターが不完全です!'];
        }
        return json($resule);
    }
}