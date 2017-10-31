<?php

return [
    '<controller:\w+>/<id:\d+>'=>'<controller>/view',
    '<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
    '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
    /*内容展示*/
    'index'=>'wap141/default/index',                                       //首页
    'event-<advert_id:\d+>'=>'wap141/advert/index',                        //促销专题
    'categories'=>'wap141/cate',                                           //分类列表
    'category/list-<s:\d+>'=>'wap141/cate/list',                           //分类商品
    'brands'=>'wap141/brand/index',                                              //品牌列表
    'brand/list-<b:\d+>'=>'wap141/brand/list',                             //品牌商品
    'product/<goods:\d+>'=>'wap141/detail/index',
    'top' => 'wap141/top/index',

    /*登录注册*/
    'login'=>'wap141/user/login',                                          //登录
    'signup'=>'wap141/user/register',                                      //注册
    'forget'=>'wap141/user/findpw',                                        //找回密码
    
    /*订单流程*/
    'empty'=>'wap141/cart/empty',                                          //购物车为空
    'order/checkout'=>'wap141/order/confirm',                              //订单确认

    /*会员中心*/
    'member/home'=>'wap141/member/index',                                  //会员中心
    'member/orders'=>'wap141/member/ordercenter',                          //我的订单
    'member/order-<order_id:\d+>'=>'wap141/member/orderinfo',              //订单明细
    'member/address'=>'wap141/addr/index',                                 //我的地址

    'detail/special-<info_id:\d+>-<goods_id:\d+>' => 'wap141/detail/special', //特卖会链接

    //试穿活动
    'try/tryon-<tryon_id:\w+|(\w+[-]\w+)+>' => 'wap141/tryevent/tryon',
    'try/activity-<act_id:\w+|(\w+[-]\w+)+>' => 'wap141/tryevent/activity',
    'try/tryreport-<report_id:\w+|(\w+[-]\w+)+>' => 'wap141/tryevent/tryreport',
    'try/apply-<info_id:\d+>' => 'wap141/tryevent/apply',
    'try/mylist' => 'wap141/tryevent/mylist',
    'try/trylist' => 'wap141/tryevent/trylist',
    'details/<info_id:\d+>-<goods_id:\d+>'=>'wap141/tryevent/details',//线上
    'details2/<info_id:\d+>'=>'wap141/tryevent/details',//线下
    'picdetails/<goods_id:\d+>'=>'wap141/tryevent/picdetails',

//    退货流程
    'return'  => '/wap141/return/index',
    'record-<order_id:\d+>' =>'wap141/return/record',
    'returngoodsinfo'  => '/wap141/return/returngoodsinfo',
    'returncheck'  => '/wap141/return/returncheck',
//    'returnGoodsInfo'  => '/wap141/return/returnGoodsInfo',
    'return/uploadreturnimg' => '/wap141/return/uploadreturnimg',
    'aspolicy'=>'/wap141/page/aspolicy',
    'aspolicer'=>'/wap141/page/aspolicer',
    /*会员日活动*/
    'vip-day'=>'wap141/vip/day',
    'vip-join'=>'wap141/vip/join',
    'c/<access:\w+|(\w+[-]\w+)+>'=>'wap141/activity/index',
    'c-j-<access:\w+|(\w+[-]\w+)+>'=>'wap141/activitylogin/join',

    'geolocation'=>'wap141/store/geolocation',
    'position-<lat:[\d|.]*>-<long:[\d|.]*>(-bus=<bus:\d*>)?(-reg=)?<reg:\d*>'        =>'wap141/store/position',//门店定位
    'wchatstores'=>'wap141/wchatstores/index',
    'review/index-<product_id:\d+>-<order_id:\d+>'=> 'wap141/review/index',  //进行评论
    'showcomments/<goods:\d+>-<type:\w+>'=>'wap141/review/showcomments',  //评论展示页
    'oauthlogin-<type:\w+>'=>'wap141/callback/oauthlogin',                        //第三方登录
    'member/collect'=>'wap141/member/collect', //收藏
    'ShareOauth-<type:\w+>-<goods:\d+>'=>'wap141/share/shareoauth',                      //分享登录
    'bindp' =>'wap141/callback/bindp',
    'download'=>'wap141/default/download',


    #微信code跳板页面
    'get_weixin_code'=>'wap141/default/getWeixinCode',

    //年会秒杀
    'lottery' => 'wap141/fastfood/index',
    'fastfood/detail-<goods:\d+>' => 'wap141/fastfood/detail',
    'test/detail-<goods:\d+>' => 'wap141/test/detail',
    'fastfood/failed' => 'wap141/fastfood/failed',
    'festival/order' => 'wap141/festival/OrderCheck',
    'festival/addr' => 'wap141/festival/addrh',

    //wap1.4新路由
    'member/mybaby' => 'wap141/babyinfo/index',
    'member/babyinfo-<baby_id:\d+>' => 'wap141/babyinfo/info',
    'logistics-<order_id:\d+>' => 'wap141/order/logistics',


    'wxstores'=>'member/wchatstores/index',


    //大转盘
    'event/lottery/index' => 'wap141/lottery/index',
    'event/lottery/revolve' => 'wap141/lottery/revolve',
    'event/lottery/list' => 'wap141/lottery/list',
    'user/piccode' => 'wap141/user/piccode',


    //OCTMAMI 666 线下会员路径
    //注册
    'ipos/register' => 'member/user/Register',
    'ipos/doregister' => 'member/user/doregister',
    //登录
    'ipos/login' => 'member/user/login',
    'ipos/dologin' => 'member/user/dologin',

    'ipos/check' => 'member/user/check',
    'ipos/center' => 'member/default/index', //用户中心
    'ipos/order' => 'member/info/orderinfo', //线下订单
    'ipos/info' => 'member/info/index',  //个人信息
    'ipos/saveinfo' => 'member/info/saveinfo', //保存个人信息
    'ipos/rights' => 'member/info/rights', //会员权益


    //OCTMAMI 888 线下会员路径
    //注册
    'ipos8888/register' => 'member/user8888/Register',
    'ipos8888/doregister' => 'member/user8888/doregister',
    'ipos8888/center' => 'member/default8888/index',
    'ipos8888/info' => 'member/info8888/index',
    'ipos8888/rights' => 'member/info8888/rights',
    'ipos8888/order' => 'member/info8888/orderinfo',
    //登录
    'ipos8888/login' => 'member/user8888/login',
    'ipos8888/dologin' => 'member/user8888/dologin',
    'ipos8888/check' => 'member/user8888/check',
    'ipos8888/saveinfo' => 'member/info8888/saveinfo',

    //微信号666 获取open_id、union_id
    'login/wechat/getopenid' => 'wchat/default/wechatgetopenid',
    'login/wechat/getopenidcallback' => 'wchat/default/wxredirecturlmain',

    //微信号octmami888仅获取union_id
    'login/wechat/get888openid' => 'wchat/service/wechatget888openid',
    'login/wechat/get888openidcallback' => 'wchat/service/wx888redirecturlmain',
    'ipos/gettoken' => 'wchat/service/getoctmami888token',
    'getregion' => 'member/region/index',
    
    //ipos抽奖
    'happy/lottery' => 'member/events/lottery',
    'happy/lotteryrevolve' => 'member/events/revolve',
    'happy/insert' => 'member/events/insert',
     'ipos/clear' => 'member/point/clear',
     'ipos/test' => 'member/point/test',

    '<controller:\w+>/<action:\w+>'=>'wap141/<controller>/<action>',
    '<controller:\w+>'=>'wap141/<controller>/index',
];