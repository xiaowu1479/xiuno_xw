# Xiuno_xw

基于 [Xiuno BBS 4.1.1](https://bbs.xiuno.com/) 二次开发的分支版本（fork），国产轻量论坛系统。

> 上游 Xiuno BBS 4.1.1 兼容 PHP 8.0 - 8.4，修复了 SMTP 邮件系统，升级了缓存机制（文件缓存 / Redis 认证）。Xiuno_xw 在此基础上继续演进。

## 特性

- 轻量：仅 20 多个表，源码压缩后 1M 左右，单次请求处理在 0.01 秒级别
- 响应式：前端 BootStrap 4.5 + jQuery 3.5.1，自适应手机 / 平板 / PC
- 高性能：静态语言编程风格，充分发挥 PHP7+ OPCache 威力；Session + Token 双登录
- 插件机制：hook + overwrite，方便二次开发，不影响性能
- 多语言：自带简中 / 繁中 / 英文 / 俄语 / 泰语
- 强扩展：内置火车头免登录发布接口（loco.php）

## 与原版的差异

- 安全加固：loco 接口密码配置化；登录 Token 恢复 IP 绑定与过期校验；安装器移除已废弃的 `mysql_*` 代码；附件上传移除 exe/bin/swf 危险类型
- 存储引擎：全表 InnoDB
- 其他改动见 [UPGRADE.txt](UPGRADE.txt)

## 环境要求

- PHP >= 7.2（推荐 7.4 / 8.x）
- MySQL 5.7+（需要 pdo_mysql）
- 可选：Redis / Memcached（缓存加速）

## 安装

1. 上传所有文件到网站根目录，确保以下目录可写：`./upload`、`./plugin`、`./tmp`、`./log`、`./conf`
2. 浏览器访问 `http://www.domain.com/install/`，按提示完成安装
3. 安装完成后删除 `install` 目录

详细步骤见 [INSTALL.txt](INSTALL.txt)。

## 目录结构

```
├── admin/          后台
├── conf/           配置目录
├── index.inc.php   入口路由
├── lang/           语言包
├── model/          模型层（编译缓存到 tmp/）
├── plugin/         插件目录
├── route/          路由层
├── view/           模板与静态资源
├── xiunophp/       自研 PHP 框架（已压缩合并为 xiunophp.min.php）
└── install/        安装向导
```

## 二次开发

- 插件机制：`hook`（模板钩子）+ `overwrite`（类覆盖），参考 `plugin/` 下插件示例
- 编译缓存：源码改动后清空 `tmp/` 目录，或设置 `DEBUG=1` 开发模式
- 版本号在 `index.php` 中统一定义，避免手工修改 conf

## 授权

MIT 协议。基于 Xiuno BBS 4.1.1（MIT）二次开发，保留了原作者的版权信息。

上游作者：axiuno@gmail.com · [bbs.xiuno.com](https://bbs.xiuno.com/)