/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : wxdc

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2018-05-27 23:39:31
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `api_ad`
-- ----------------------------
DROP TABLE IF EXISTS `api_ad`;
CREATE TABLE `api_ad` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '广告ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '广告描述标题',
  `flag` varchar(20) NOT NULL DEFAULT '' COMMENT '广告位置标识',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '广告类型 1图文 2文字',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '广告状态 0停放 1正常',
  `img` varchar(255) NOT NULL DEFAULT '' COMMENT '广告图片',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '链接地址',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '广告开始投放时间',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '广告结束投放时间',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '广告创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '广告修改时间',
  `sort` int(10) unsigned NOT NULL DEFAULT '99' COMMENT '广告排序',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '广告位单价 元/月',
  `client` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '用户操作端 1： 小程序  2：  Android  3：  iOS',
  PRIMARY KEY (`id`),
  KEY `type` (`type`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `flag` (`flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='广告表';

-- ----------------------------
-- Records of api_ad
-- ----------------------------

-- ----------------------------
-- Table structure for `api_auth_group`
-- ----------------------------
DROP TABLE IF EXISTS `api_auth_group`;
CREATE TABLE `api_auth_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '组名称',
  `description` varchar(50) NOT NULL COMMENT '组描述',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '组状态：为1正常，为0禁用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='权限组';

-- ----------------------------
-- Records of api_auth_group
-- ----------------------------
INSERT INTO `api_auth_group` VALUES ('1', '市场部', '想去哪就去哪儿', '1');

-- ----------------------------
-- Table structure for `api_auth_group_access`
-- ----------------------------
DROP TABLE IF EXISTS `api_auth_group_access`;
CREATE TABLE `api_auth_group_access` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL,
  `groupId` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='用户和组的对应关系';

-- ----------------------------
-- Records of api_auth_group_access
-- ----------------------------
INSERT INTO `api_auth_group_access` VALUES ('1', '2', '1');

-- ----------------------------
-- Table structure for `api_auth_rule`
-- ----------------------------
DROP TABLE IF EXISTS `api_auth_rule`;
CREATE TABLE `api_auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `url` char(80) NOT NULL DEFAULT '' COMMENT '规则唯一标识',
  `groupId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '权限所属组的ID',
  `auth` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '权限数值',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：为1正常，为0禁用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='权限细节';

-- ----------------------------
-- Records of api_auth_rule
-- ----------------------------
INSERT INTO `api_auth_rule` VALUES ('1', 'Index/index', '1', '0', '1');
INSERT INTO `api_auth_rule` VALUES ('2', 'Order/index', '1', '0', '1');
INSERT INTO `api_auth_rule` VALUES ('3', 'Member/index', '1', '0', '1');
INSERT INTO `api_auth_rule` VALUES ('4', 'Merchant/index', '1', '0', '1');

-- ----------------------------
-- Table structure for `api_coupon`
-- ----------------------------
DROP TABLE IF EXISTS `api_coupon`;
CREATE TABLE `api_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `card_no` varchar(32) NOT NULL DEFAULT '' COMMENT '卡券编号',
  `card_name` varchar(255) NOT NULL DEFAULT '' COMMENT '优惠券名称',
  `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户ID 0 表示所有',
  `deductible` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '可抵扣金额',
  `high_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '达到金额',
  `card_type` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '卡券类型: 1满减 2无门槛',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '卡券状态  0删除  1正常可领取 2不可领取',
  `effective_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有效时间 单位:天',
  `is_sex` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别券 0不分 1男 2女',
  `marks` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠券开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '优惠券结束时间',
  `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '卡券创建时间',
  `flag` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '领券方式 1领券中心 2首页领取 3全平台老用户回馈券 4全平台新人注册送券 5全平台首单消费返利',
  `total` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '优惠券总数',
  `get_start_time` int(11) NOT NULL DEFAULT '0' COMMENT '领取开始时间',
  `get_end_time` int(11) NOT NULL DEFAULT '0' COMMENT '领取结束时间',
  `merchant_type` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '酒吧类型 0不限制 1,2,3,4,5',
  `goods_type` int(255) unsigned NOT NULL DEFAULT '0' COMMENT '套餐限制 0不限制 1散客套餐 2卡座套餐 3 单点酒水',
  `attach_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '附加时间',
  PRIMARY KEY (`id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `card_type` (`card_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='优惠券表';

-- ----------------------------
-- Records of api_coupon
-- ----------------------------

-- ----------------------------
-- Table structure for `api_coupon_member`
-- ----------------------------
DROP TABLE IF EXISTS `api_coupon_member`;
CREATE TABLE `api_coupon_member` (
  `member_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `card_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '卡券ID',
  `card_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '使用状态 0未使用 1已使用 2已过期',
  `get_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  KEY `member_id` (`member_id`),
  KEY `card_id` (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户优惠券表';

-- ----------------------------
-- Records of api_coupon_member
-- ----------------------------

-- ----------------------------
-- Table structure for `api_goods_pack`
-- ----------------------------
DROP TABLE IF EXISTS `api_goods_pack`;
CREATE TABLE `api_goods_pack` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '酒水(套餐)id',
  `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属商户ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '酒水(套餐名称)',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '商品类型 1 ',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '线上销售价格',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '酒水图片',
  `description` text COMMENT '酒水(套餐描述)',
  `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `stock` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品总库存（正常购买）',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '商品状态：0下架状态 1上架状态',
  `market_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场价格',
  `limit_buy` int(4) unsigned DEFAULT NULL COMMENT '每单限制购买量',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `merchant_id` (`merchant_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='酒水(套餐)商品表';

-- ----------------------------
-- Records of api_goods_pack
-- ----------------------------
INSERT INTO `api_goods_pack` VALUES ('1', '2', '开江豆腐皮', '1', '5.00', '/goods_20180527_vcx1527412692feba946.jpg', '独一无二，美食冠绝天下', '1527412738', '100', '1', '10.00', '0');
INSERT INTO `api_goods_pack` VALUES ('2', '2', '开酱板鸭', '1', '20.00', '/goods_20180527_zob1527413165kiwg164.jpg', '测试时啦啦啦啦啦啦啦算了算了算了算了', '1527413199', '100', '1', '30.00', '10');
INSERT INTO `api_goods_pack` VALUES ('3', '2', '开江小吃', '1', '30.00', '/goods_20180527_bxq1527413214rvxt755.jpg', '啦啦啦实力老师的发生类似啦啦啦', '1527413230', '100', '1', '50.00', '0');
INSERT INTO `api_goods_pack` VALUES ('4', '2', '蛋花汤', '2', '8.00', '/goods_20180527_wcs1527413412fple086.jpg', '拉拉爱啦啦阿拉拉了劳斯莱斯带来福利', '1527413473', '100', '1', '10.00', '0');
INSERT INTO `api_goods_pack` VALUES ('5', '2', '骨头汤', '2', '25.00', '/goods_20180527_pgj1527413493trph549.jpg', '费拉拉了死啦死啦地理发师拉力赛浪费粮食分类', '1527413523', '100', '0', '30.00', '10');
INSERT INTO `api_goods_pack` VALUES ('6', '2', '水煮肉片', '1', '38.00', '/goods_20180527_pjg1527413564zuwr172.jpg', '啦啦啦啦商量商量商量了商量商量商量大乱斗', '1527413586', '100', '1', '45.00', '0');
INSERT INTO `api_goods_pack` VALUES ('7', '2', '由孟大虾', '3', '58.00', '/goods_20180527_kyo1527413605fdwk119.jpg', '是是是扩大开放开始看的福克斯', '1527413630', '100', '1', '70.00', '0');

-- ----------------------------
-- Table structure for `api_member`
-- ----------------------------
DROP TABLE IF EXISTS `api_member`;
CREATE TABLE `api_member` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `tel` bigint(11) unsigned DEFAULT '0' COMMENT '手机号码',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '用户登录密码',
  `salt` char(6) NOT NULL DEFAULT '' COMMENT '密码盐',
  `nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `realname` varchar(18) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '性别: 1男 2女',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '用户头像',
  `coin` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分（K币）',
  `growth` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '经验/成长值',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '0封禁 1正常',
  `unionid` varchar(28) NOT NULL DEFAULT '' COMMENT '微信用户唯一识别码',
  `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间',
  `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `invite_code` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推广码',
  `level` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '1普通会员 2银卡会员、3金卡会员、4白金会员、5钻石会员、6黑金会员',
  `wx_openid` varchar(28) NOT NULL DEFAULT '' COMMENT '用户微信公众号openID',
  `xcx_openid` varchar(28) NOT NULL DEFAULT '' COMMENT '小程序unionid',
  `promoter_code` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推广人的推广码 前缀为1:用户端 2:商户端',
  `used_card` int(10) NOT NULL DEFAULT '0' COMMENT '已使用逾期卡',
  `age` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '年龄',
  `signature` varchar(255) NOT NULL DEFAULT '' COMMENT '个性签名',
  `image` text COMMENT '用户相册',
  `is_auth` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已认证为派对大使 0未认证 1已认证',
  `is_edit_sex` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否修改过性别 0未修改 1已修改过',
  `alipay_account` varchar(255) NOT NULL DEFAULT '' COMMENT '提现账户',
  `channel_id` int(5) unsigned NOT NULL DEFAULT '0' COMMENT '推广渠道ID',
  PRIMARY KEY (`id`),
  KEY `status` (`status`) USING BTREE,
  KEY `tel` (`tel`),
  KEY `unionid` (`unionid`),
  KEY `level` (`level`),
  KEY `wx_openid` (`wx_openid`),
  KEY `channel_id` (`channel_id`),
  KEY `promoter_code` (`promoter_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='普通用户表';

-- ----------------------------
-- Records of api_member
-- ----------------------------

-- ----------------------------
-- Table structure for `api_member_capital`
-- ----------------------------
DROP TABLE IF EXISTS `api_member_capital`;
CREATE TABLE `api_member_capital` (
  `member_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后一次余额变动时间',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '支付密码',
  `salt` varchar(6) NOT NULL DEFAULT '' COMMENT '余额支付密码加密字符串',
  `consume_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '消费额度',
  `give_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '充值赠送金额',
  `recharge_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户资金余额表';

-- ----------------------------
-- Records of api_member_capital
-- ----------------------------

-- ----------------------------
-- Table structure for `api_member_contacts`
-- ----------------------------
DROP TABLE IF EXISTS `api_member_contacts`;
CREATE TABLE `api_member_contacts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '联系人ID',
  `realname` varchar(10) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '性别: 1男 2女',
  `tel` bigint(11) unsigned NOT NULL COMMENT '联系人手机号码',
  `member_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否为默认联系人 0否 1是',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '具体地址',
  `lat` double(10,7) NOT NULL DEFAULT '0.0000000' COMMENT '经度',
  `lng` double(10,7) unsigned NOT NULL DEFAULT '0.0000000' COMMENT '纬度',
  PRIMARY KEY (`id`),
  KEY `tel` (`tel`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of api_member_contacts
-- ----------------------------

-- ----------------------------
-- Table structure for `api_member_order`
-- ----------------------------
DROP TABLE IF EXISTS `api_member_order`;
CREATE TABLE `api_member_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '充值订单id',
  `member_id` int(11) unsigned NOT NULL COMMENT '用户id',
  `recharge_money` decimal(10,2) unsigned NOT NULL COMMENT '充值金额',
  `give_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '充值赠送金额',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0未支付 1支付完成 2支付失败',
  `order_no` bigint(16) unsigned NOT NULL DEFAULT '0' COMMENT '充值订单号',
  `create_time` int(1) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `member_id` (`member_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户充值订单表';

-- ----------------------------
-- Records of api_member_order
-- ----------------------------

-- ----------------------------
-- Table structure for `api_member_privilege`
-- ----------------------------
DROP TABLE IF EXISTS `api_member_privilege`;
CREATE TABLE `api_member_privilege` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '特权id',
  `level` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '1普通会员 2银卡会员、3金卡会员、4白金会员、5钻石会员、6黑金会员',
  `overdue` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '逾期次数',
  `delayed` int(3) unsigned NOT NULL DEFAULT '30' COMMENT '卡套延期天数',
  `birthday` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有生日特权',
  `birthday_content` varchar(255) NOT NULL DEFAULT '' COMMENT '生日特权赠送内容',
  `coin` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '赠送K币数量',
  `free_seat` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '免预定金特权',
  `quota` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '累计消费额度',
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT '会员等级名称',
  PRIMARY KEY (`id`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='vip等级特权表';

-- ----------------------------
-- Records of api_member_privilege
-- ----------------------------

-- ----------------------------
-- Table structure for `api_member_record`
-- ----------------------------
DROP TABLE IF EXISTS `api_member_record`;
CREATE TABLE `api_member_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '交易记录ID',
  `member_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` tinyint(1) unsigned NOT NULL COMMENT '交易类型 1正常购买消费 2充值 3退款 4续酒购买',
  `change_money` decimal(10,2) unsigned NOT NULL COMMENT '交易金额',
  `trade_time` int(11) unsigned NOT NULL COMMENT '资金变动时间',
  `source` varchar(20) NOT NULL DEFAULT '' COMMENT '来源',
  `terminal` varchar(10) NOT NULL COMMENT '数据变动终端(微信小程序,ios,android)',
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT '交易名称',
  `order_no` bigint(16) unsigned NOT NULL DEFAULT '0' COMMENT '订单号',
  `before_recharge_money` decimal(10,2) unsigned NOT NULL COMMENT '变动之前的账户充值金额',
  `after_recharge_money` decimal(10,2) unsigned NOT NULL COMMENT '变动后的账户充值余额',
  `before_give_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '变动之前的赠送金额',
  `after_give_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '变动之后的赠送金额',
  `order_id` int(11) unsigned DEFAULT '0' COMMENT '订单ID',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户充值消费记录表';

-- ----------------------------
-- Records of api_member_record
-- ----------------------------

-- ----------------------------
-- Table structure for `api_menu`
-- ----------------------------
DROP TABLE IF EXISTS `api_menu`;
CREATE TABLE `api_menu` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '菜单名',
  `fid` int(11) NOT NULL COMMENT '父级菜单ID',
  `url` varchar(50) NOT NULL DEFAULT '' COMMENT '链接',
  `auth` tinyint(2) NOT NULL DEFAULT '0' COMMENT '访客权限',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `hide` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否显示',
  `icon` varchar(50) NOT NULL DEFAULT '' COMMENT '菜单图标',
  `level` tinyint(2) NOT NULL DEFAULT '0' COMMENT '菜单认证等级',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COMMENT='目录信息';

-- ----------------------------
-- Records of api_menu
-- ----------------------------
INSERT INTO `api_menu` VALUES ('1', '后台首页', '0', 'Index/index', '0', '0', '1', '', '0');
INSERT INTO `api_menu` VALUES ('2', '欢迎页', '0', 'Index/welcome', '0', '0', '0', '', '0');
INSERT INTO `api_menu` VALUES ('3', '系统配置', '0', '', '0', '1', '0', '', '0');
INSERT INTO `api_menu` VALUES ('4', '菜单维护', '3', 'Menu/index', '0', '0', '0', '', '0');
INSERT INTO `api_menu` VALUES ('5', '用户管理', '3', 'User/index', '0', '1', '0', '', '0');
INSERT INTO `api_menu` VALUES ('6', '权限管理', '3', 'Permission/index', '0', '2', '0', '', '0');
INSERT INTO `api_menu` VALUES ('7', '新增菜单', '4', 'Menu/add', '0', '0', '1', '', '0');
INSERT INTO `api_menu` VALUES ('8', '订单管理', '0', '', '0', '2', '0', '', '0');
INSERT INTO `api_menu` VALUES ('9', '会员管理', '0', '', '0', '3', '0', '', '0');
INSERT INTO `api_menu` VALUES ('10', '商户管理', '0', '', '0', '4', '0', '', '0');
INSERT INTO `api_menu` VALUES ('11', '订单列表', '8', 'Order/index', '0', '0', '0', '', '0');
INSERT INTO `api_menu` VALUES ('12', '会员列表', '9', 'Member/index', '0', '0', '0', '', '0');
INSERT INTO `api_menu` VALUES ('13', '商户列表', '10', 'Merchant/index', '0', '0', '0', '', '0');

-- ----------------------------
-- Table structure for `api_merchant`
-- ----------------------------
DROP TABLE IF EXISTS `api_merchant`;
CREATE TABLE `api_merchant` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '商户ID',
  `title` varchar(250) NOT NULL DEFAULT '' COMMENT '商户名称',
  `logo` varchar(255) NOT NULL DEFAULT '' COMMENT '商户标志',
  `description` text COMMENT '商户门店介绍',
  `tel` varchar(100) NOT NULL DEFAULT '' COMMENT '商户电话号码',
  `image` text COMMENT '商家展示图片，最多5张',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0封禁 1待审核 2正常',
  `province` varchar(255) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(255) NOT NULL DEFAULT '' COMMENT '市',
  `area` varchar(255) NOT NULL DEFAULT '' COMMENT '区/县',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '具体地址',
  `begin_time` time NOT NULL DEFAULT '00:00:00' COMMENT '开门时间',
  `end_time` time NOT NULL DEFAULT '00:00:00' COMMENT '打烊时间',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '商户标签',
  `lat` double(10,7) unsigned NOT NULL DEFAULT '0.0000000' COMMENT '纬度',
  `lng` double(10,7) unsigned NOT NULL DEFAULT '0.0000000' COMMENT '经度',
  `absadd` double(15,12) DEFAULT '0.000000000000' COMMENT '经纬度和',
  `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户创建时间',
  `avg_consume` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '平均消费',
  `notice` varchar(255) NOT NULL DEFAULT '' COMMENT '店铺公告',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `lat` (`lat`,`lng`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='商户(店铺)表';

-- ----------------------------
-- Records of api_merchant
-- ----------------------------
INSERT INTO `api_merchant` VALUES ('2', '开江', '/merchant_20180527_tlw1527411371xqih861.jpg', '地理位置太好了，欢迎大家来品尝', '023-12345678', '/_merchant_2018-05-27_ita1527411517yrte917.jpg', '2', '四川省', '达州市', '开江县', '开江县新宁镇好吃街1号', '09:30:00', '21:30:00', '', '30.9051070', '107.7921010', '0.000000000000', '1527411552', '60.00', '现在店铺刚刚开业，清新老顾客光临本店消费');

-- ----------------------------
-- Table structure for `api_merchant_balance_day`
-- ----------------------------
DROP TABLE IF EXISTS `api_merchant_balance_day`;
CREATE TABLE `api_merchant_balance_day` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日结数据ID',
  `merchant_id` int(11) unsigned DEFAULT '0' COMMENT '商户ID',
  `order_total` int(11) unsigned DEFAULT '0' COMMENT '结算订单数量',
  `purchase_money` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '结算订单总金额(进货结算总金额)',
  `date` int(11) unsigned DEFAULT '0' COMMENT '日期',
  `created_time` int(11) unsigned DEFAULT '0' COMMENT '结算记录创建时间',
  PRIMARY KEY (`id`),
  KEY `merchant_date` (`merchant_id`,`date`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of api_merchant_balance_day
-- ----------------------------

-- ----------------------------
-- Table structure for `api_merchant_balance_month`
-- ----------------------------
DROP TABLE IF EXISTS `api_merchant_balance_month`;
CREATE TABLE `api_merchant_balance_month` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日结数据ID',
  `merchant_id` int(11) unsigned DEFAULT '0' COMMENT '商户ID',
  `order_total` int(11) unsigned DEFAULT '0' COMMENT '结算订单数量',
  `purchase_money` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '结算订单总金额(进货结算总金额)',
  `month` int(11) unsigned DEFAULT '0' COMMENT '日期',
  `created_time` int(11) unsigned DEFAULT '0' COMMENT '结算记录创建时间',
  PRIMARY KEY (`id`),
  KEY `month` (`merchant_id`,`month`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of api_merchant_balance_month
-- ----------------------------

-- ----------------------------
-- Table structure for `api_merchant_balance_total`
-- ----------------------------
DROP TABLE IF EXISTS `api_merchant_balance_total`;
CREATE TABLE `api_merchant_balance_total` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日结数据ID',
  `merchant_id` int(11) unsigned DEFAULT '0' COMMENT '商户ID',
  `order_total` int(11) unsigned DEFAULT '0' COMMENT '结算订单数量',
  `purchase_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `created_time` int(11) unsigned DEFAULT '0' COMMENT '结算记录创建时间',
  `last_time` int(11) unsigned DEFAULT '0' COMMENT '最后统计时间',
  PRIMARY KEY (`id`),
  KEY `merchant` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of api_merchant_balance_total
-- ----------------------------

-- ----------------------------
-- Table structure for `api_merchant_balance_year`
-- ----------------------------
DROP TABLE IF EXISTS `api_merchant_balance_year`;
CREATE TABLE `api_merchant_balance_year` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日结数据ID',
  `merchant_id` int(11) unsigned DEFAULT '0' COMMENT '商户ID',
  `order_total` int(11) unsigned DEFAULT '0' COMMENT '结算订单数量',
  `purchase_money` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '结算订单总金额(进货结算总金额)',
  `created_time` int(11) unsigned DEFAULT '0' COMMENT '结算记录创建时间',
  `year` int(10) DEFAULT '0' COMMENT '年',
  PRIMARY KEY (`id`),
  KEY `year` (`merchant_id`,`year`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of api_merchant_balance_year
-- ----------------------------

-- ----------------------------
-- Table structure for `api_message`
-- ----------------------------
DROP TABLE IF EXISTS `api_message`;
CREATE TABLE `api_message` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '消息ID',
  `title` varchar(255) NOT NULL COMMENT '消息标题',
  `content` text NOT NULL COMMENT '消息内容',
  `toclient` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '消息发送终端 1所有 2用户端 3商户端',
  `mode` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '发送方式: 1全局广播 0指定用户',
  `member_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '指定发送的用户id',
  `created_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消息内容表';

-- ----------------------------
-- Records of api_message
-- ----------------------------

-- ----------------------------
-- Table structure for `api_order`
-- ----------------------------
DROP TABLE IF EXISTS `api_order`;
CREATE TABLE `api_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `order_no` bigint(16) unsigned NOT NULL DEFAULT '0' COMMENT '订单号',
  `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `member_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `contacts_realname` varchar(20) DEFAULT '' COMMENT '会员真实姓名',
  `contacts_tel` bigint(11) unsigned DEFAULT '0' COMMENT '会员手机号码',
  `contacts_sex` tinyint(1) unsigned DEFAULT '1' COMMENT '性别: 1男 2女',
  `total_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单总价',
  `pay_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态 0已取消 1待支付 2已支付  3已接单 4已拒绝 5 已完成',
  `order_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '订单类型：1 堂食 2 外卖',
  `payment` tinyint(1) unsigned DEFAULT '0' COMMENT '支付方式 1余额支付 2微信支付 3支付宝 4银联支付',
  `description` varchar(255) DEFAULT '' COMMENT '订单备注',
  `created_time` int(11) unsigned DEFAULT '0' COMMENT '订单创建时间',
  `updated_time` int(11) unsigned DEFAULT '0' COMMENT '状态更新时间',
  `cancel_reason` varchar(255) DEFAULT '' COMMENT '订单拒绝理由',
  `is_evaluate` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否评论,0未评价,1已评价',
  `desk_number` varchar(20) DEFAULT '' COMMENT '桌号',
  `card_id` int(11) unsigned DEFAULT '0' COMMENT '优惠券ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`) USING BTREE,
  KEY `status` (`status`),
  KEY `order_type` (`order_type`),
  KEY `merchant_id` (`merchant_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单主表';

-- ----------------------------
-- Records of api_order
-- ----------------------------

-- ----------------------------
-- Table structure for `api_order_everyday`
-- ----------------------------
DROP TABLE IF EXISTS `api_order_everyday`;
CREATE TABLE `api_order_everyday` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `merchant_id` int(11) unsigned NOT NULL COMMENT '商户id',
  `amount` int(11) unsigned NOT NULL COMMENT '订单数量',
  `time` int(11) unsigned NOT NULL COMMENT '时间,只存入年月日,用于检索',
  PRIMARY KEY (`id`),
  KEY `merchant_id` (`merchant_id`,`time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='每日订单数量统计表';

-- ----------------------------
-- Records of api_order_everyday
-- ----------------------------

-- ----------------------------
-- Table structure for `api_order_operate_record`
-- ----------------------------
DROP TABLE IF EXISTS `api_order_operate_record`;
CREATE TABLE `api_order_operate_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '退款记录ID',
  `user_id` int(10) unsigned NOT NULL,
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_no` varchar(20) NOT NULL DEFAULT '' COMMENT '订单编号',
  `content` varchar(200) NOT NULL DEFAULT '0' COMMENT '操作类型: 1退款 2',
  `created_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of api_order_operate_record
-- ----------------------------

-- ----------------------------
-- Table structure for `api_order_pack`
-- ----------------------------
DROP TABLE IF EXISTS `api_order_pack`;
CREATE TABLE `api_order_pack` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `goods_pack_id` int(11) unsigned NOT NULL COMMENT '酒水商品id',
  `title` varchar(255) DEFAULT '' COMMENT '商品标题(酒水标题)',
  `amount` int(11) unsigned DEFAULT '1' COMMENT '购买数量',
  `price` decimal(10,2) unsigned DEFAULT NULL COMMENT '酒水价格',
  `image` varchar(255) DEFAULT '' COMMENT '商品图片',
  `merchant_id` int(11) unsigned NOT NULL COMMENT '商户ID',
  `member_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `pack_description` text COMMENT '套餐描述',
  `purchase_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '进货价格(结算价格)',
  `market_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场价格',
  `goods_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '商品类型 1散客套餐 2卡座套餐 3单点酒水	',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `goods_pack_id` (`goods_pack_id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单商品详情表-套餐表';

-- ----------------------------
-- Records of api_order_pack
-- ----------------------------

-- ----------------------------
-- Table structure for `api_order_total`
-- ----------------------------
DROP TABLE IF EXISTS `api_order_total`;
CREATE TABLE `api_order_total` (
  `merchant_id` int(11) unsigned NOT NULL COMMENT '商户ID',
  `order_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单总数',
  PRIMARY KEY (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of api_order_total
-- ----------------------------
INSERT INTO `api_order_total` VALUES ('2', '0');

-- ----------------------------
-- Table structure for `api_payment_record`
-- ----------------------------
DROP TABLE IF EXISTS `api_payment_record`;
CREATE TABLE `api_payment_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户ID',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单ID',
  `order_no` bigint(22) NOT NULL COMMENT '订单编号',
  `appid` varchar(32) NOT NULL DEFAULT '' COMMENT '应用ID',
  `mch_id` varchar(64) NOT NULL DEFAULT '' COMMENT '支付平台商户号',
  `trade_type` varchar(32) NOT NULL DEFAULT '' COMMENT '交易类型 JSAPI, APP或其他',
  `order_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单应支付金额',
  `receipt_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '收到支付金额',
  `trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '支付平台订单号(微信支付订单号/支付宝交易号)',
  `end_time` int(10) NOT NULL DEFAULT '0' COMMENT '支付完成时间',
  `pay_type` int(10) NOT NULL DEFAULT '1' COMMENT '支付类型 1订单消费 2充值',
  `buy_type` int(10) NOT NULL DEFAULT '0' COMMENT '购买类型 1正常下单支付 2正常续酒下单支付 3拼吧支付 4拼吧续酒支付',
  `payment` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付方式 1余额支付 2微信支付 3支付宝 4银联支付',
  `created_time` int(10) NOT NULL DEFAULT '0' COMMENT '记录创建时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `order_no` (`order_no`),
  KEY `trade_no` (`trade_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of api_payment_record
-- ----------------------------

-- ----------------------------
-- Table structure for `api_refund_record`
-- ----------------------------
DROP TABLE IF EXISTS `api_refund_record`;
CREATE TABLE `api_refund_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `app_id` varchar(32) NOT NULL COMMENT '支付宝分配给开发者的应用Id',
  `trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '支付平台交易订单号',
  `order_no` bigint(22) NOT NULL DEFAULT '0' COMMENT '商户订单号 订单前缀1正常购买订单 2线下订座订单 3拼吧订单 9充值订单',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `trade_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '退款状态 1退款中 2退款成功',
  `receipt_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商家在交易中实际收到的款项',
  `refund_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总退款金额',
  `pay_type` int(10) NOT NULL DEFAULT '1' COMMENT '消费类型 1订单消费 2充值',
  `buy_type` int(10) NOT NULL DEFAULT '0' COMMENT '购买类型 1正常下单支付 2正常续酒下单支付 3拼吧支付 4拼吧续酒支付',
  `refund_no` bigint(22) NOT NULL,
  `refund_time` int(10) NOT NULL DEFAULT '0' COMMENT '微信支付退款成功时间/支付宝交易退款时间',
  `created_time` int(10) NOT NULL DEFAULT '0' COMMENT '数据写入时间',
  `refund_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '退款原因',
  `payment` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '支付方式 1余额支付 2微信支付 3支付宝 4银联支付',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of api_refund_record
-- ----------------------------

-- ----------------------------
-- Table structure for `api_union`
-- ----------------------------
DROP TABLE IF EXISTS `api_union`;
CREATE TABLE `api_union` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '商户名称',
  `city` varchar(100) NOT NULL COMMENT '所在城市',
  `phone` bigint(11) NOT NULL COMMENT '联系电话',
  `contacter` varchar(10) NOT NULL COMMENT '联系人',
  `created_time` int(10) NOT NULL DEFAULT '0' COMMENT '提交信息时间',
  `is_ok` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1未审核 2已审核',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商家加盟信息提交表';

-- ----------------------------
-- Records of api_union
-- ----------------------------

-- ----------------------------
-- Table structure for `api_user`
-- ----------------------------
DROP TABLE IF EXISTS `api_user`;
CREATE TABLE `api_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL DEFAULT '' COMMENT '用户名',
  `nickname` varchar(64) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `password` char(32) NOT NULL DEFAULT '' COMMENT '用户密码',
  `regTime` int(10) NOT NULL DEFAULT '0' COMMENT '注册时间',
  `regIp` varchar(11) NOT NULL DEFAULT '' COMMENT '注册IP',
  `updateTime` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '账号状态 0封号 1正常',
  `openId` varchar(100) DEFAULT NULL COMMENT '微信唯一ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='管理员认证信息';

-- ----------------------------
-- Records of api_user
-- ----------------------------
INSERT INTO `api_user` VALUES ('1', 'admin', '凌风', '912601e4ad1b308c9ae41877cf6ca754', '1492004246', '3682992231', '1516170574', '1', null);
INSERT INTO `api_user` VALUES ('2', 'Mrwang', '王悦', '912601e4ad1b308c9ae41877cf6ca754', '1527141880', '2130706433', '0', '1', null);

-- ----------------------------
-- Table structure for `api_user_action`
-- ----------------------------
DROP TABLE IF EXISTS `api_user_action`;
CREATE TABLE `api_user_action` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `actionName` varchar(50) NOT NULL DEFAULT '' COMMENT '行为名称',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '操作用户ID',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `addTime` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  `data` text COMMENT '用户提交的数据',
  `url` varchar(200) NOT NULL DEFAULT '' COMMENT '操作URL',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户操作日志';

-- ----------------------------
-- Records of api_user_action
-- ----------------------------

-- ----------------------------
-- Table structure for `api_user_data`
-- ----------------------------
DROP TABLE IF EXISTS `api_user_data`;
CREATE TABLE `api_user_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `loginTimes` int(11) NOT NULL COMMENT '账号登录次数',
  `lastLoginIp` varchar(11) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `lastLoginTime` int(11) NOT NULL COMMENT '最后登录时间',
  `uid` varchar(11) NOT NULL DEFAULT '' COMMENT '用户ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='管理员数据表';

-- ----------------------------
-- Records of api_user_data
-- ----------------------------
INSERT INTO `api_user_data` VALUES ('1', '8', '2130706433', '1527327863', '1');
INSERT INTO `api_user_data` VALUES ('2', '1', '2130706433', '1527141912', '2');
