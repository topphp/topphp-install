/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for topphp_admin
-- ----------------------------
DROP TABLE IF EXISTS `topphp_admin`;
CREATE TABLE `topphp_admin`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `admin_name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '管理员用户名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '管理员登录密码',
  `email` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '管理员邮箱',
  `tel` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '管理员手机号',
  `is_super_admin` tinyint(2) UNSIGNED NOT NULL DEFAULT 2 COMMENT '是否是超级管理员 2 否 1 是',
  `login_time` timestamp(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '最近一次登录时间',
  `login_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '最近一次登录IP',
  `create_time` timestamp(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '更新时间',
  `delete_time` timestamp(0) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '管理员表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for topphp_user
-- ----------------------------
DROP TABLE IF EXISTS `topphp_user`;
CREATE TABLE `topphp_user`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `open_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT 'open_id',
  `username` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户密码',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '昵称',
  `avatar_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '头像',
  `email` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '用户邮箱',
  `tel` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '用户手机号',
  `sex` tinyint(2) UNSIGNED NULL DEFAULT 2 COMMENT '性别 0 女 1 男 2 保密',
  `personal_brief` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '个性签名',
  `integral` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '可用积分',
  `freeze_integral` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '冻结积分',
  `balance` decimal(10, 2) UNSIGNED NULL DEFAULT 0.00 COMMENT '用户余额',
  `freeze_balance` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '冻结余额',
  `pay_pwd` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '支付密码',
  `pay_money` decimal(10, 2) UNSIGNED NULL DEFAULT 0.00 COMMENT '用户总消费',
  `real_money` decimal(10, 2) UNSIGNED NULL DEFAULT 0.00 COMMENT '实际消费（排除退款的）',
  `vip_level` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '会员等级',
  `qrcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '个人二维码',
  `login_time` timestamp(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '最近一次登录时间',
  `login_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '最近一次登录IP',
  `state` tinyint(2) NULL DEFAULT 1 COMMENT '用户状态 1 正常 2 锁定 -1 拉黑',
  `create_time` timestamp(0) NULL DEFAULT NULL COMMENT '注册时间',
  `update_time` timestamp(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0) COMMENT '更新时间',
  `delete_time` timestamp(0) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for topphp_version
-- ----------------------------
DROP TABLE IF EXISTS `topphp_version`;
CREATE TABLE `topphp_version`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
  `app_id` int(11) NOT NULL DEFAULT -1 COMMENT '应用id -1 无',
  `app_name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'topphp' COMMENT '应用名称',
  `app_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'server' COMMENT '应用类型 server, android, ios, web, wap, applet',
  `client_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户端版本号',
  `allow_lowest_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '允许的最低版本，低于这个版本，强制更新',
  `server_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '服务端版本号',
  `channels` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '各发布渠道版本，以逗号分割（或json数据）',
  `update_type` tinyint(3) UNSIGNED NULL DEFAULT 10 COMMENT '更新类型 10：强制更新 20：一般更新 30：静默更新 40：可忽略更新 50：静默可忽略更新',
  `update_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '更新描述',
  `update_log` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '更新日志',
  `download_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '客户端升级下载地址',
  `state` tinyint(3) UNSIGNED NULL DEFAULT 2 COMMENT '发布状态 1 已上架 2 未上架',
  `gray_released` tinyint(3) NULL DEFAULT NULL COMMENT '灰度发布 -1 无 1 白名单发布 2 IP发布',
  `white_list_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '白名单ID集合，以逗号分割',
  `ip_list` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'IP发布集合，以逗号分割',
  `create_time` timestamp(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '版本管理表' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
