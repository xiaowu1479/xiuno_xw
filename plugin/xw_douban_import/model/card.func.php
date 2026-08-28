<?php

/*
	豆瓣影视批量导入 - 卡片渲染与数据规整
	========================================
	卡片 HTML 结构与 huux_tinymce proxy.php 的 douban_render_html() 完全一致，
	前台展示样式由 huux_tinymce 的 style.css 提供（依赖该插件启用）。
	函数统一加 xwdi_ 前缀。

	v1.1 起豆瓣抓取移至本地 Python 客户端（tools/xwdi_client），
	服务端只负责：接收采集数据 → 规整 → 渲染卡片 → 发布。
*/

// 空白 payload 模板（字段与 Python 客户端推送的数据结构一一对应）
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

function xwdi_douban_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

// 将客户端推送的字段并入 payload，仅填充空白字段
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

// 将 API 推送的 data 数组规整为标准 payload；title 为空视为无效数据
function xwdi_payload_from_api(array $data): array
{
    $id = '';
    if (!empty($data['url']) && preg_match('~/subject/(\d+)~', (string) $data['url'], $m)) {
        $id = $m[1];
    } elseif (!empty($data['id']) && preg_match('/^\d+$/', (string) $data['id'])) {
        $id = (string) $data['id'];
    }
    $payload = xwdi_payload_empty($id);
    $map = array(
        'title', 'cover', 'rating', 'ratingVotes', 'subtitle', 'intro', 'hotComment',
        'year', 'imdb', 'aka', 'director', 'writer', 'actor', 'class', 'area',
        'pubdate', 'lang', 'duration',
    );
    $clean = array();
    foreach ($map as $key) {
        if (isset($data[$key])) {
            $clean[$key] = xwdi_stringify($data[$key]);
        }
    }
    if (!empty($data['url']) && preg_match('~^https?://~i', (string) $data['url'])) {
        $clean['url'] = xwdi_clean_text((string) $data['url']);
    }
    if (!empty($data['stills']) && is_array($data['stills'])) {
        $clean['stills'] = array();
        foreach ($data['stills'] as $still) {
            $still = xwdi_clean_text((string) $still);
            if ($still !== '' && preg_match('~^https?://~i', $still)) {
                $clean['stills'][] = $still;
            }
            if (count($clean['stills']) >= 6) {
                break;
            }
        }
    }
    xwdi_merge($payload, $clean);

    // 兼容客户端直接推豆瓣列表字段的情况（directors/actors/genres...）
    $alias = array(
        'director' => array('directors'),
        'actor' => array('actors'),
        'class' => array('types', 'genres'),
        'area' => array('countries', 'region'),
        'pubdate' => array('pubdates'),
        'duration' => array('durations'),
        'aka' => array('aka_list'),
    );
    foreach ($alias as $target => $keys) {
        if (xwdi_clean_text((string) $payload[$target]) !== '') {
            continue;
        }
        foreach ($keys as $k) {
            if (!empty($data[$k])) {
                $text = xwdi_list_text($data[$k]);
                if ($text !== '') {
                    $payload[$target] = $text;
                    break;
                }
            }
        }
    }

    if ($payload['title'] === '') {
        throw new RuntimeException('采集数据缺少影视标题');
    }
    if ($payload['cover'] === '') {
        throw new RuntimeException('采集数据缺少封面');
    }
    if ($payload['rating'] === '') {
        $payload['rating'] = '暂无评分';
    }
    if ($payload['subtitle'] === '') {
        $payload['subtitle'] = $payload['year'];
    }
    return $payload;
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

?>
