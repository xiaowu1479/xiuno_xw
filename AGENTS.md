# AGENTS.md — XIUNO XW 开发 / 发行流程速查

仓库：`https://github.com/xiaowu1479/xiuno_xw.git`（本地在 `D:\local_php_build\phpstudy_pro\WWW\xiuluo11`，Windows + PowerShell 5.1）

## 一、改完代码/插件后要清编译缓存

Xiuno 会把带插件钩子的文件编译到 `tmp/`。源码或插件改动后，**不清理不生效**：

- 后台：其他 → 清理缓存 → 勾选「清空临时文件」提交
- 或直接删除对应编译文件，下次请求自动重编译：
  - `tmp/lang_zh-cn_bbs.php`（语言钩子）
  - `tmp/view_htm_header.inc.htm`（前台 header 覆盖）
  - `tmp/plugin_zaesky_theme_light_setting.php`、`tmp/_plugin_zaesky_theme_light_view_htm_settingItem_*.htm`（主题设置页）
- 主题/插件文件用 `php -l` 验证语法（本机 PHP：`D:\local_php_build\phpstudy_pro\Extensions\php\php7.4.3nts\php.exe`）

## 二、发行版发布流程（vX.Y.Z）

1. **改版本号**：`index.php` 第 40 行 `$conf['version'] = 'X.Y.Z';`
2. **README.md**：更新日志顶部新增 `### vX.Y.Z (日期)` 条目，格式见现有记录
3. **暂存/提交**（只加相关文件）：
   - `git add <改动文件>`
   - `git commit -m "vX.Y.Z: 一句话摘要"`（沿用历史风格）
4. **推送**：`git push origin main`
5. **打 tag 并推送**：
   - `git tag -a vX.Y.Z -m "vX.Y.Z: 摘要"`
   - `git push origin vX.Y.Z`
6. **打包发行版**（只含已跟踪文件，自动排除本地 conf、tmp、log、upload 内容及未跟踪文件）：
   - `git archive --format=tar.gz --output=xiuno_xw_vX.Y.Z.tar.gz HEAD`
   - 产物约 9-10MB，位于仓库根目录（已被 .gitignore 忽略，不会误提交）
7. **创建 GitHub Release 并上传附件**（见下节）

## 三、创建 GitHub Release（gh 未安装，用 REST API）

Token 从 Windows 凭据管理器读取（无需用户再登录）：

```powershell
$cred = "protocol=https`nhost=github.com`n`n" | git credential fill
$token = (($cred -split "`n") | Where-Object { $_ -like "password=*" }) -replace "^password=",""
$headers = @{ Authorization = "Bearer $token"; "User-Agent" = "XIUNO-XW-release"; "Accept" = "application/vnd.github+json" }
```

1. 建 Release：

```powershell
$body = @{ tag_name="vX.Y.Z"; target_commitish="main"; name="vX.Y.Z"; body=<见下方编码注意>; draft=$false; prerelease=$false } | ConvertTo-Json
Invoke-RestMethod -Method Post -Uri "https://api.github.com/repos/xiaowu1479/xiuno_xw/releases" -Headers $headers -Body $body -ContentType "application/json"
# 记下返回的 id（release_id）和 upload_url
```

2. 上传附件：

```powershell
$uploadUrl = "https://uploads.github.com/repos/xiaowu1479/xiuno_xw/releases/<release_id>/assets?name=xiuno_xw_vX.Y.Z.tar.gz"
Invoke-RestMethod -Method Post -Uri $uploadUrl -Headers $headers -InFile "<tar.gz绝对路径>" -ContentType "application/gzip"
```

3. 改正文（如需修正）：

```powershell
Invoke-RestMethod -Method Patch -Uri "https://api.github.com/repos/xiaowu1479/xiuno_xw/releases/<release_id>" -Headers $headers -Body $json -ContentType "application/json; charset=utf-8"
```

### ⚠️ 编码坑（重要）

- **PowerShell 命令行里直接写中文会变乱码**（`?`/`????????`）。Release body 含中文时：
  1. 先用文本工具（Write/Edit）把 body 写进 UTF-8 JSON 文件，如 `{"body":"...中文..."}`
  2. 再用 `[System.IO.File]::ReadAllText(路径, [System.Text.Encoding]::UTF8)` 读出作为 `-Body`
- 控制台回显中文乱码**不代表数据错误**，可能是控制台代码页问题；用 API GET 取回正文写文件即可验证是否正常。

## 四、注意点

- 未跟踪文件默认不纳入发行版（git archive 只含已提交文件）；如用户新增图标等资源，打包前确认是否 `git add`
- 后台「检测更新」的自动更新逻辑：下载 Release 附件（zip/tar.gz）→ 解压定位 index.php 根目录 → 覆盖主程序（备份 conf/upload/log），详见 `admin/admin.func.php`
- 修改主题（如 zaesky_theme_light）后，`plugin/zaesky_theme_light/conf.json` 保持 `enable:1`，新增覆盖模板放 `overwrite/` 下、设置项在 `setting.php` + `view/htm/settingItem/*.htm` + `hook/lang_zh_cn_bbs.php`
