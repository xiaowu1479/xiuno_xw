# Nginx 安全配置（可选加固）

> 本文是**可选**加固项。v1.8.21 起，`nginx.htaccess` 已随发行版自动提供基础防护
> （`conf/log/tmp/install` 目录 403 + 编译产物防护头），**不需要任何服务器改动**。
> 下面这些规则做的是 nginx.htaccess 做不到的部分，愿意手改服务器的用户可以加上。

## 1. 基础防护（v1.8.21 已自动提供，无需手工配置）

发行包内的 `nginx.htaccess` 会在你的站点 `location /` 内生效（前提：vhost 里
`include .../nginx.htaccess;`，Xiuno 标准配置默认如此）：

```nginx
if ($request_uri ~* "^/(conf|log|tmp|install)/") { return 403; }
```

同时所有编译产物（`tmp/*.php`、`tmp/model.min.php`）开头带防护头，直接经 URL
访问返回 403。**如果更新后 `/tmp/model.min.php` 仍返回 200，说明 `tmp/` 未重编译**，
到后台「其他 → 清理缓存」勾选「清空临时文件」提交一次即可（后台自动更新会顺带清理）。

## 2. upload 目录禁执行 PHP（需手改，本仓无法自动完成）

附件上传机制本身会把非法后缀重命名（`1.php` → `1.x_php`，见 `route/attach.php:34-38`），
但如果愿意在服务器层再加一道，可在站点 vhost 的 `server { }` 内、
**原有 `location ~ \.php(.*)$` 之前**插入：

```nginx
location ~ ^/upload/.*\.(php|phps|phtml|phar)$ { return 404; }
```

> 正则 location 按书写顺序匹配，必须放在原有 `.php` 规则之前才会优先生效。
> 这条只影响 `upload/` 下的 PHP 文件，图片 / 附件下载不受影响。

## 3. 敏感目录 deny（等价于第 1 节，适合不 include nginx.htaccess 的配置）

如果你的 vhost 没有 `include nginx.htaccess`，在 `server { }` 内、原有
`location /` 之前加入（**注意必须带 `^~`**，否则会被 `location ~ \.php` 正则盖过）：

```nginx
location ^~ /conf/    { deny all; }
location ^~ /log/     { deny all; }
location ^~ /tmp/     { deny all; }
location ^~ /install/ { deny all; }
```

## 4. 验证命令

改完 `nginx -t` + `nginx -s reload` 后执行（域名换成你的）：

```bash
for p in /tmp/model.min.php /tmp/view_htm_header.inc.htm /log/php_error.log /conf/conf.php; do
  printf "%-40s %s\n" "$p" "$(curl -s -o /dev/null -w '%{http_code}' https://你的域名$p)"
done
```

期望：前三项为 403，`/conf/conf.php` 为 200 但响应体为空（只 return 数组，不泄露内容）。
再确认首页 / 帖子页 / 登录页仍为 200。

## 5. 常见环境操作路径

| 环境 | 位置 |
|---|---|
| 宝塔面板 | 网站 → 设置 → **配置文件**（不要用「伪静态」，那里写不了 location 块） |
| lnmp / 自建 | `/usr/local/nginx/conf/vhost/你的域名.conf` |
| phpstudy | `Extensions/Nginx1.15.11/conf/vhosts/你的站点.conf` |

回滚：还原改前的配置文件 + `nginx -s reload`，立即恢复原状。
