# XIUNO XW

基于 [Xiuno BBS 4.1.1](https://bbs.xiuno.com/) 二次开发的开源轻量论坛程序，MIT 协议。

- **后端**：PHP 7.2+（推荐 7.4 / 8.x）+ MySQL 5.7+，使用 Xiuno 自研的轻量框架 `xiunophp`（路由 / 模型 / 模板 / 插件 hook）
- **前端**：Bootstrap 4.5 + jQuery 3.5.1，响应式，自适应手机 / 平板 / PC
- **缓存**：文件缓存，可选 Redis / Memcached 加速

## 特点

- **轻量**：仅 20 多个表，源码压缩后 1M 左右，单次请求处理在 0.01 秒级别
- **高性能**：静态语言编程风格，充分发挥 PHP7+ OPCache 威力；Session + Token 双登录
- **插件机制**：`hook`（模板钩子）+ `overwrite`（文件覆盖），二次开发不改核心，不影响性能
- **多语言**：自带简中 / 繁中 / 英文 / 俄语 / 泰语
- **开箱可用**：图形验证码、昼夜主题、站点统计、火车头免登录发布接口（`loco.php`）均已内置
- **安全加固**：相比原版补齐了登录 Token 的 IP 绑定与过期校验、loco 接口密码配置化、危险附件类型拦截

## 快速开始

环境要求：PHP >= 7.2（需 pdo_mysql）、MySQL 5.7+。

1. 上传全部文件到网站根目录，确保 `./upload`、`./plugin`、`./tmp`、`./log`、`./conf` 可写
2. 浏览器访问 `http://你的域名/install/`，按向导完成安装
3. 安装完成后删除 `install` 目录

详细步骤见 [INSTALL.txt](INSTALL.txt)。

### 使用

- 前台直接访问域名即可浏览、注册、发帖
- 后台地址 `/admin/`，前台右上角「后台管理」入口会在新标签页打开（管理员登录后可见）
- 修改模板 / 插件后，到后台「其他 → 清理缓存」勾选「清空临时文件」提交，改动才会生效
- 升级：后台首页「检测更新」可在线升级；也可下载 [Releases](https://github.com/xiaowu1479/xiuno_xw/releases) 中的压缩包覆盖（保留 `conf/`、`upload/`、`log/`）

## 目录结构

```
├── admin/          后台
├── conf/           配置目录（安装时生成）
├── model/          模型层（编译缓存到 tmp/）
├── route/          路由层
├── view/           模板与静态资源
├── lang/           语言包
├── plugin/         插件目录
├── xiunophp/       自研 PHP 框架（发布版合并为 xiunophp.min.php）
└── install/        安装向导
```

## 二次开发

- 插件：`hook` 注入模板、`overwrite` 覆盖文件，参考 [docs/插件开发指南.md](docs/插件开发指南.md)
- 源码改动后需清空 `tmp/` 编译缓存，开发时可设 `DEBUG=1` 关闭缓存
- 版本号统一在 `index.php` 中定义，不要手工改 `conf/`

## 更新日志

完整历史见 [CHANGELOG.md](CHANGELOG.md)，每个版本的发行包见 [Releases](https://github.com/xiaowu1479/xiuno_xw/releases)。

## 授权

MIT 协议。基于 Xiuno BBS 4.1.1（MIT）二次开发，保留原作者版权信息。

上游作者：axiuno@gmail.com · [bbs.xiuno.com](https://bbs.xiuno.com/)
