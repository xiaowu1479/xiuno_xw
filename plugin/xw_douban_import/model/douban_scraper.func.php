<?php

/*
	豆瓣影视批量导入 - 豆瓣抓取与卡片渲染
	========================================
	抓取与渲染逻辑移植自 huux_tinymce/tinymce/plugins/douban/proxy.php，
	卡片 HTML 结构与其 douban_render_html() 完全一致，
	前台展示样式由 huux_tinymce 的 style.css 提供（依赖该插件启用）。
	函数统一加 xwdi_ 前缀，避免与 proxy.php 冲突。
*/

define('XWDI_DOUBAN_TIMEOUT', 18);
define('XWDI_DOUBAN_CACHE_TTL', 3600);

function xwdi_douban_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function xwdi_cache_dir(): string
{
    $dir = APP_PATH . 'plugin/xw_douban_import/cache/douban';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function xwdi_cache_get(string $key): ?string
{
    $file = xwdi_cache_dir() . '/' . sha1($key) . '.json';
    if (!is_file($file) || time() - filemtime($file) > XWDI_DOUBAN_CACHE_TTL) {
        return null;
    }
    $body = @file_get_contents($file);
    return $body === false ? null : $body;
}

function xwdi_cache_set(string $key, string $body): void
{
    $dir = xwdi_cache_dir();
    if (is_dir($dir) && is_writable($dir)) {
        @file_put_contents($dir . '/' . sha1($key) . '.json', $body, LOCK_EX);
    }
}

function xwdi_http_get(string $url, string $referer = 'https://movie.douban.com/', bool $useCache = true): string
{
    if ($useCache && ($cached = xwdi_cache_get($url)) !== null) {
        return $cached;
    }

    $headers = array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: application/json,text/plain,*/*,text/html;q=0.9',
        'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        'Referer: ' . ($referer !== '' ? $referer : 'https://movie.douban.com/'),
        'Cache-Control: no-cache',
        'Pragma: no-cache',
    );

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => XWDI_DOUBAN_TIMEOUT,
            CURLOPT_TIMEOUT => XWDI_DOUBAN_TIMEOUT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ));
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException($error ?: '豆瓣接口响应异常：HTTP ' . $status);
        }
        if ($useCache) {
            xwdi_cache_set($url, (string) $body);
        }
        return (string) $body;
    }

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'timeout' => XWDI_DOUBAN_TIMEOUT,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
        ),
    ));
    $body = @file_get_contents($url, false, $context);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $status = (int) $matches[1];
    }
    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('豆瓣接口请求失败' . ($status ? '：HTTP ' . $status : ''));
    }
    if ($useCache) {
        xwdi_cache_set($url, $body);
    }
    return $body;
}

function xwdi_http_json(string $url, string $referer = 'https://movie.douban.com/'): array
{
    $body = xwdi_http_get($url, $referer);
    $json = json_decode($body, true);
    if (!is_array($json)) {
        throw new RuntimeException('豆瓣接口数据解析失败');
    }
    return $json;
}

function xwdi_extract_id(string $query): string
{
    $query = trim($query);
    if (preg_match('~/subject/(\d+)~', $query, $matches)) {
        return $matches[1];
    }
    if (preg_match('/^\d+$/', $query)) {
        return $query;
    }
    return '';
}

function xwdi_clean_text(?string $value): string
{
    $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    $value = str_replace("\xc2\xa0", ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
}

function xwdi_first_non_empty(...$values): string
{
    foreach ($values as $value) {
        $value = xwdi_clean_text((string) $value);
        if ($value !== '') {
            return $value;
        }
    }
    return '';
}

function xwdi_stringify($value): string
{
    if ($value === null) {
        return '';
    }
    if (is_float($value)) {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
    if (is_int($value)) {
        return (string) $value;
    }
    return xwdi_clean_text((string) $value);
}

function xwdi_find_year(string $value): string
{
    return preg_match('/(?:19|20)\d{2}/', $value, $matches) ? $matches[0] : '';
}

function xwdi_display_title(string $title, string $year = ''): string
{
    $title = xwdi_clean_text(str_replace(["\xe2\x80\x8e", "\xe2\x80\x8f"], '', $title));
    if ($year !== '') {
        $title = preg_replace('/\s*[\(（]\s*' . preg_quote($year, '/') . '\s*[\)）]\s*$/u', '', $title) ?? $title;
    }
    return xwdi_clean_text($title);
}

function xwdi_list_text($value, string $sep = ' / ', int $limit = 20): string
{
    if (!is_array($value)) {
        return xwdi_stringify($value);
    }
    $out = array();
    foreach ($value as $item) {
        if (is_string($item) || is_numeric($item)) {
            $text = xwdi_clean_text((string) $item);
        } elseif (is_array($item)) {
            $text = xwdi_first_non_empty($item['name'] ?? '', $item['title'] ?? '', $item['text'] ?? '');
        } else {
            $text = '';
        }
        if ($text !== '' && !in_array($text, $out, true)) {
            $out[] = $text;
        }
        if (count($out) >= $limit) {
            break;
        }
    }
    return implode($sep, $out);
}

function xwdi_info_label(string $html, string $label): string
{
    if (preg_match('/<span[^>]*class=["\']pl["\'][^>]*>\s*' . preg_quote($label, '/') . '\s*[:：]\s*<\/span>(.*?)(?:<br\s*\/?>|<\/div>)/isu', $html, $matches)) {
        return xwdi_clean_text(strip_tags($matches[1]));
    }
    return '';
}

function xwdi_search(string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return array();
    }
    $items = xwdi_http_json('https://movie.douban.com/j/subject_suggest?q=' . rawurlencode($query), 'https://movie.douban.com/');
    $results = array();
    foreach ((array) $items as $item) {
        if (!is_array($item) || empty($item['id'])) {
            continue;
        }
        $id = (string) $item['id'];
        $results[] = array(
            'id' => $id,
            'title' => xwdi_first_non_empty($item['title'] ?? '', $item['sub_title'] ?? '', $id),
            'year' => xwdi_stringify($item['year'] ?? ''),
            'type' => xwdi_stringify($item['type'] ?? 'movie'),
            'rating' => '',
            'img' => xwdi_stringify($item['img'] ?? ''),
        );
        if (count($results) >= 12) {
            break;
        }
    }
    return $results;
}

function xwdi_payload_empty(string $id): array
{
    return array(
        'id' => $id,
        'url' => 'https://movie.douban.com/subject/' . rawurlencode($id) . '/',
        'title' => '',
        'cover' => '',
        'rating' => '',
        'ratingVotes' => '',
        'subtitle' => '',
        'intro' => '',
        'hotComment' => '',
        'year' => '',
        'imdb' => '',
        'aka' => '',
        'director' => '',
        'writer' => '',
        'actor' => '',
        'class' => '',
        'area' => '',
        'pubdate' => '',
        'lang' => '',
        'duration' => '',
        'stills' => array(),
    );
}

function xwdi_merge(array &$base, array $extra): void
{
    foreach ($extra as $key => $value) {
        if ($key === 'stills') {
            $base['stills'] = array_values(array_unique(array_merge((array) $base['stills'], (array) $value)));
            continue;
        }
        if (array_key_exists($key, $base) && xwdi_clean_text((string) $base[$key]) === '' && xwdi_clean_text((string) $value) !== '') {
            $base[$key] = $value;
        }
    }
}

function xwdi_fetch_subject_abstract(string $id): array
{
    $out = array();
    try {
        $json = xwdi_http_json('https://movie.douban.com/j/subject_abstract?subject_id=' . rawurlencode($id), 'https://movie.douban.com/');
    } catch (Throwable $e) {
        return $out;
    }
    $subject = is_array($json['subject'] ?? null) ? $json['subject'] : array();
    $rating = '';
    if (isset($subject['rating']) && is_array($subject['rating'])) {
        $rating = xwdi_stringify($subject['rating']['value'] ?? '');
        $out['ratingVotes'] = xwdi_stringify($subject['rating']['count'] ?? '');
    }
    $out['title'] = xwdi_stringify($subject['title'] ?? '');
    $out['cover'] = xwdi_first_non_empty($subject['cover_url'] ?? '', $subject['pic'] ?? '');
    $out['rating'] = xwdi_first_non_empty($rating, $subject['rate'] ?? '');
    $out['subtitle'] = xwdi_stringify($subject['card_subtitle'] ?? '');
    $out['intro'] = xwdi_stringify($subject['intro'] ?? '');
    $out['year'] = xwdi_first_non_empty($subject['release_year'] ?? '', xwdi_find_year($out['subtitle']));
    $out['director'] = xwdi_list_text($subject['directors'] ?? array(), ' / ');
    $out['actor'] = xwdi_list_text($subject['actors'] ?? array(), ' / ');
    $out['class'] = xwdi_list_text($subject['types'] ?? array(), ' / ');
    $out['area'] = xwdi_stringify($subject['region'] ?? '');
    $out['duration'] = xwdi_stringify($subject['duration'] ?? '');
    return $out;
}

function xwdi_fetch_mobile_meta(string $id): array
{
    $out = array();
    try {
        $json = xwdi_http_json('https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '?for_mobile=1', 'https://m.douban.com/movie/subject/' . rawurlencode($id) . '/');
    } catch (Throwable $e) {
        return $out;
    }
    $rating = is_array($json['rating'] ?? null) ? $json['rating'] : array();
    $out['title'] = xwdi_stringify($json['title'] ?? '');
    $out['cover'] = xwdi_first_non_empty($json['cover_url'] ?? '', $json['pic']['large'] ?? '');
    $out['subtitle'] = xwdi_stringify($json['card_subtitle'] ?? '');
    $out['intro'] = xwdi_stringify($json['intro'] ?? '');
    $out['year'] = xwdi_stringify($json['year'] ?? '');
    $out['rating'] = xwdi_stringify($rating['value'] ?? '');
    $out['ratingVotes'] = xwdi_stringify($rating['count'] ?? '');
    $out['director'] = xwdi_list_text($json['directors'] ?? array(), ' / ');
    $out['actor'] = xwdi_list_text($json['actors'] ?? array(), ' / ');
    $out['class'] = xwdi_list_text($json['genres'] ?? array(), ' / ');
    $out['area'] = xwdi_list_text($json['countries'] ?? array(), ' / ');
    $out['lang'] = xwdi_list_text($json['languages'] ?? array(), ' / ');
    $out['pubdate'] = xwdi_list_text($json['pubdate'] ?? array(), ' / ');
    $out['duration'] = xwdi_list_text($json['durations'] ?? array(), ' / ');
    $out['aka'] = xwdi_list_text($json['aka'] ?? array(), ' / ');
    return $out;
}

function xwdi_fetch_credits_meta(string $id): array
{
    $out = array();
    try {
        $json = xwdi_http_json('https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/credits?start=0&count=100', 'https://m.douban.com/movie/subject/' . rawurlencode($id) . '/');
    } catch (Throwable $e) {
        return $out;
    }
    $writers = array();
    foreach ((array) ($json['items'] ?? array()) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $roleText = xwdi_list_text($item['roles'] ?? array(), ' ') . xwdi_stringify($item['category'] ?? '') . xwdi_stringify($item['character'] ?? '');
        if (mb_strpos($roleText, '编剧') === false) {
            continue;
        }
        $name = xwdi_stringify($item['name'] ?? '');
        if ($name !== '' && !in_array($name, $writers, true)) {
            $writers[] = $name;
        }
    }
    $out['writer'] = implode(' / ', $writers);
    return $out;
}

function xwdi_fetch_page_meta(string $id): array
{
    $out = array();
    try {
        $body = xwdi_http_get('https://movie.douban.com/subject/' . rawurlencode($id) . '/', 'https://movie.douban.com/');
    } catch (Throwable $e) {
        return $out;
    }
    if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $body, $matches)) {
        $out['cover'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('/<span[^>]*property=["\']v:summary["\'][^>]*>(.*?)<\/span>/isu', $body, $matches)) {
        $out['intro'] = xwdi_clean_text(strip_tags($matches[1]));
    } elseif (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $body, $matches)) {
        $out['intro'] = xwdi_clean_text($matches[1]);
    }
    foreach (array(
        'director' => '导演',
        'writer' => '编剧',
        'actor' => '主演',
        'class' => '类型',
        'area' => '制片国家/地区',
        'lang' => '语言',
        'pubdate' => '上映日期',
        'duration' => '片长',
        'aka' => '又名',
        'imdb' => 'IMDb',
    ) as $key => $label) {
        $out[$key] = xwdi_info_label($body, $label);
    }
    if (($out['imdb'] ?? '') === '' && preg_match('/tt\d{6,10}/i', $body, $matches)) {
        $out['imdb'] = strtolower($matches[0]);
    }
    if (preg_match_all('/https?:\/\/img\d+\.doubanio\.com\/view\/photo\/[^"\']+\/public\/p\d+\.(?:jpg|webp|png)/i', $body, $matches)) {
        $out['stills'] = array_values(array_unique(array_slice($matches[0], 0, 3)));
    }
    return $out;
}

function xwdi_fetch_hot_reviews(string $id): array
{
    $out = array();
    try {
        $json = xwdi_http_json('https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/interests?count=3&order_by=hot&start=0', 'https://m.douban.com/movie/subject/' . rawurlencode($id) . '/');
    } catch (Throwable $e) {
        return $out;
    }
    $lines = array();
    foreach ((array) ($json['interests'] ?? array()) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $comment = xwdi_clean_text($item['comment'] ?? '');
        if ($comment === '') {
            continue;
        }
        $author = xwdi_first_non_empty($item['user']['name'] ?? '', '匿名用户');
        $lines[] = $comment . ' —— ' . $author;
    }
    $out['hotComment'] = implode("\n\n", $lines);
    return $out;
}

function xwdi_photo_url(array $photo): string
{
    return xwdi_first_non_empty(
        $photo['image']['large']['url'] ?? '',
        $photo['image']['normal']['url'] ?? '',
        $photo['image']['small']['url'] ?? '',
        $photo['cover'] ?? '',
        $photo['url'] ?? ''
    );
}

function xwdi_fetch_photos(string $id): array
{
    $out = array('stills' => array());
    $referer = 'https://m.douban.com/movie/subject/' . rawurlencode($id) . '/';
    $urls = array(
        'https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/photos?type=S&start=0&count=6',
        'https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/photos?type=R&start=0&count=3',
        'https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/photos?start=0&count=6',
    );

    foreach ($urls as $url) {
        try {
            $json = xwdi_http_json($url, $referer);
        } catch (Throwable $e) {
            continue;
        }
        foreach ((array) ($json['photos'] ?? array()) as $photo) {
            if (!is_array($photo)) {
                continue;
            }
            $image = xwdi_photo_url($photo);
            if ($image !== '' && preg_match('/^https?:\/\/img\d+\.doubanio\.com\//i', $image)) {
                $out['stills'][] = $image;
            }
            if (count(array_unique($out['stills'])) >= 6) {
                break 2;
            }
        }
    }

    $out['stills'] = array_values(array_unique($out['stills']));
    return $out;
}

// 封面改写为 huux_tinymce 的图片代理地址（与编辑器卡片行为一致）
function xwdi_cover_url(string $url, string $referer = 'https://movie.douban.com/'): string
{
    if ($url === '') {
        return '';
    }
    return 'plugin/huux_tinymce/tinymce/plugins/douban/proxy.php?action=cover&url=' . rawurlencode($url) . '&referer=' . rawurlencode($referer);
}

// 与 huux_tinymce/proxy.php douban_render_html() 保持一致，勿改动结构
function xwdi_render_html(array $data): string
{
    $year = (string) $data['year'];
    $title = xwdi_display_title((string) $data['title'], $year);
    $cover = xwdi_cover_url((string) $data['cover'], (string) $data['url']);
    $rating = (string) ($data['rating'] !== '' ? $data['rating'] : '暂无评分');
    $ratingVotes = (string) $data['ratingVotes'];
    if ($ratingVotes !== '' && preg_match('/^\d+$/', $ratingVotes)) {
        $ratingVotes = number_format((int) $ratingVotes) . '人评';
    }
    $subtitleParts = array_filter([$year, $data['area'], $data['class'], $data['director'], $data['actor']]);
    $subtitle = xwdi_first_non_empty((string) $data['subtitle'], implode(' / ', $subtitleParts));
    $metaRows = array(
        array('导演', (string) $data['director']),
        array('编剧', (string) $data['writer']),
        array('主演', (string) $data['actor']),
        array('类型', (string) $data['class']),
        array('国家/语言', trim((string) $data['area'] . ' / ' . (string) $data['lang'], ' /')),
        array('上映/首播', (string) $data['pubdate']),
        array('单集片长', (string) $data['duration']),
        array('又名', (string) $data['aka']),
        array('IMDb', (string) $data['imdb']),
    );

    $html = '<div class="chuan-douban-card movie-card">';
    $html .= '<div class="movie-card__hero">';
    if ($cover !== '') {
        $html .= '<div class="movie-card__poster"><img src="' . xwdi_douban_e($cover) . '" alt="' . xwdi_douban_e($title) . '"></div>';
    }
    $html .= '<div class="movie-card__main"><div class="movie-card__head">';
    $html .= '<h2 class="movie-card__title"><a href="' . xwdi_douban_e((string) $data['url']) . '" target="_blank" rel="noopener">' . xwdi_douban_e($title) . '</a>';
    if ($year !== '') {
        $html .= '<span>（' . xwdi_douban_e($year) . '）</span>';
    }
    $html .= '</h2><div class="movie-card__rating"><strong>' . xwdi_douban_e($rating) . '</strong><span></span>';
    if ($ratingVotes !== '') {
        $html .= '<em>' . xwdi_douban_e($ratingVotes) . '</em>';
    }
    $html .= '</div></div>';
    if ($subtitle !== '') {
        $html .= '<p class="movie-card__subtitle">' . xwdi_douban_e($subtitle) . '</p>';
    }
    $html .= '<div class="movie-card__meta">';
    foreach ($metaRows as $row) {
        if ($row[1] === '') {
            continue;
        }
        $class = $row[0] === '主演' ? ' movie-card__meta--clamp' : '';
        $html .= '<p class="' . trim('movie-card__meta-row' . $class) . '"><strong>' . xwdi_douban_e($row[0]) . '：</strong><span>' . xwdi_douban_e($row[1]) . '</span></p>';
    }
    $html .= '</div></div></div>';
    if ($data['intro'] !== '') {
        $html .= '<div class="movie-card__section"><h3>剧情简介</h3><p>' . xwdi_douban_e((string) $data['intro']) . '</p></div>';
    }
    if ($data['hotComment'] !== '') {
        $html .= '<div class="movie-card__quote"><strong>豆瓣热评</strong><p>“' . nl2br(xwdi_douban_e((string) $data['hotComment'])) . '”</p></div>';
    }
    $stills = array_values(array_filter((array) $data['stills']));
    if ($stills) {
        $html .= '<div class="movie-card__section movie-card__section--photos"><h3>剧照/海报</h3><div class="movie-card__stills">';
        foreach (array_slice($stills, 0, 3) as $still) {
            $html .= '<img src="' . xwdi_douban_e(xwdi_cover_url((string) $still, (string) $data['url'])) . '" alt="' . xwdi_douban_e($title) . '">';
        }
        $html .= '</div></div>';
    }
    $html .= '</div><p></p>';
    return $html;
}

function xwdi_payload_subject(array $payload): string
{
    $title = xwdi_display_title((string) $payload['title'], (string) $payload['year']);
    $year = (string) $payload['year'];
    return $year === '' ? $title : $title . ' (' . $year . ')';
}

// 按名称/ID/链接抓取详情：先识别 ID，再搜索取第一个结果（全自动模式）
function xwdi_resolve_detail(string $query): array
{
    $query = trim($query);
    if ($query === '') {
        throw new InvalidArgumentException('影视名为空');
    }
    $len = function_exists('mb_strlen') ? mb_strlen($query, 'UTF-8') : strlen($query);
    if ($len > 100) {
        throw new InvalidArgumentException('影视名超过100字');
    }
    $id = xwdi_extract_id($query);
    if ($id === '') {
        $results = xwdi_search($query);
        if (!$results) {
            throw new RuntimeException('豆瓣未找到相关条目');
        }
        // 多个结果自动选第一个（评分权重由豆瓣 suggest 接口决定）
        $id = (string) $results[0]['id'];
    }
    if (!preg_match('/^\d+$/', $id)) {
        throw new InvalidArgumentException('豆瓣ID格式不正确');
    }

    $payload = xwdi_payload_empty($id);
    xwdi_merge($payload, xwdi_fetch_subject_abstract($id));
    xwdi_merge($payload, xwdi_fetch_mobile_meta($id));
    xwdi_merge($payload, xwdi_fetch_credits_meta($id));
    xwdi_merge($payload, xwdi_fetch_page_meta($id));
    xwdi_merge($payload, xwdi_fetch_hot_reviews($id));
    xwdi_merge($payload, xwdi_fetch_photos($id));
    if ($payload['title'] === '') {
        throw new RuntimeException('未获取到豆瓣影视信息，请稍后重试');
    }
    if ($payload['cover'] === '') {
        $candidates = xwdi_search($payload['title']);
        if ($candidates) {
            $payload['cover'] = isset($candidates[0]['img']) ? (string) $candidates[0]['img'] : '';
        }
    }
    if ($payload['rating'] === '') {
        $payload['rating'] = '暂无评分';
    }
    if ($payload['subtitle'] === '') {
        $payload['subtitle'] = $payload['year'];
    }
    $payload['html'] = xwdi_render_html($payload);
    $payload['subject'] = xwdi_payload_subject($payload);
    unset($payload['hotComment']);
    return $payload;
}

?>
