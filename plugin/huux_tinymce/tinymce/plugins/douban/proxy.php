<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

const DOUBAN_TIMEOUT = 18;
const DOUBAN_CACHE_TTL = 3600;

function douban_json(int $code, string $message, array $extra = []): void
{
    echo json_encode(array_merge([
        'code' => $code,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function douban_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function douban_cache_dir(): string
{
    $dir = dirname(__DIR__, 3) . '/cache/douban';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function douban_cache_get(string $key): ?string
{
    $file = douban_cache_dir() . '/' . sha1($key) . '.json';
    if (!is_file($file) || time() - filemtime($file) > DOUBAN_CACHE_TTL) {
        return null;
    }
    $body = @file_get_contents($file);
    return $body === false ? null : $body;
}

function douban_cache_set(string $key, string $body): void
{
    $dir = douban_cache_dir();
    if (is_dir($dir) && is_writable($dir)) {
        @file_put_contents($dir . '/' . sha1($key) . '.json', $body, LOCK_EX);
    }
}

function douban_http_get(string $url, string $referer = 'https://movie.douban.com/', bool $useCache = true): string
{
    if ($useCache && ($cached = douban_cache_get($url)) !== null) {
        return $cached;
    }

    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: application/json,text/plain,*/*,text/html;q=0.9',
        'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        'Referer: ' . ($referer !== '' ? $referer : 'https://movie.douban.com/'),
        'Cache-Control: no-cache',
        'Pragma: no-cache',
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => DOUBAN_TIMEOUT,
            CURLOPT_TIMEOUT => DOUBAN_TIMEOUT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException($error ?: '豆瓣接口响应异常：HTTP ' . $status);
        }
        if ($useCache) {
            douban_cache_set($url, (string) $body);
        }
        return (string) $body;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => DOUBAN_TIMEOUT,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $status = (int) $matches[1];
    }
    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('豆瓣接口请求失败' . ($status ? '：HTTP ' . $status : ''));
    }
    if ($useCache) {
        douban_cache_set($url, $body);
    }
    return $body;
}

function douban_http_json(string $url, string $referer = 'https://movie.douban.com/'): array
{
    $body = douban_http_get($url, $referer);
    $json = json_decode($body, true);
    if (!is_array($json)) {
        throw new RuntimeException('豆瓣接口数据解析失败');
    }
    return $json;
}

function douban_extract_id(string $query): string
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

function douban_clean_text(?string $value): string
{
    $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    $value = str_replace("\xc2\xa0", ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
}

function douban_first_non_empty(...$values): string
{
    foreach ($values as $value) {
        $value = douban_clean_text((string) $value);
        if ($value !== '') {
            return $value;
        }
    }
    return '';
}

function douban_stringify($value): string
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
    return douban_clean_text((string) $value);
}

function douban_find_year(string $value): string
{
    return preg_match('/(?:19|20)\d{2}/', $value, $matches) ? $matches[0] : '';
}

function douban_display_title(string $title, string $year = ''): string
{
    $title = douban_clean_text(str_replace(["\xe2\x80\x8e", "\xe2\x80\x8f"], '', $title));
    if ($year !== '') {
        $title = preg_replace('/\s*[\(（]\s*' . preg_quote($year, '/') . '\s*[\)）]\s*$/u', '', $title) ?? $title;
    }
    return douban_clean_text($title);
}

function douban_list_text($value, string $sep = ' / ', int $limit = 20): string
{
    if (!is_array($value)) {
        return douban_stringify($value);
    }
    $out = [];
    foreach ($value as $item) {
        if (is_string($item) || is_numeric($item)) {
            $text = douban_clean_text((string) $item);
        } elseif (is_array($item)) {
            $text = douban_first_non_empty($item['name'] ?? '', $item['title'] ?? '', $item['text'] ?? '');
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

function douban_info_label(string $html, string $label): string
{
    if (preg_match('/<span[^>]*class=["\']pl["\'][^>]*>\s*' . preg_quote($label, '/') . '\s*[:：]\s*<\/span>(.*?)(?:<br\s*\/?>|<\/div>)/isu', $html, $matches)) {
        return douban_clean_text(strip_tags($matches[1]));
    }
    return '';
}

function douban_search(string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }
    $items = douban_http_json('https://movie.douban.com/j/subject_suggest?q=' . rawurlencode($query), 'https://movie.douban.com/');
    $results = [];
    foreach ($items as $item) {
        if (!is_array($item) || empty($item['id'])) {
            continue;
        }
        $id = (string) $item['id'];
        $results[] = [
            'id' => $id,
            'title' => douban_first_non_empty($item['title'] ?? '', $item['sub_title'] ?? '', $id),
            'year' => douban_stringify($item['year'] ?? ''),
            'type' => douban_stringify($item['type'] ?? 'movie'),
            'rating' => '',
            'img' => douban_stringify($item['img'] ?? ''),
        ];
        if (count($results) >= 12) {
            break;
        }
    }
    return $results;
}

function douban_payload_empty(string $id): array
{
    return [
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
        'stills' => [],
    ];
}

function douban_merge(array &$base, array $extra): void
{
    foreach ($extra as $key => $value) {
        if ($key === 'stills') {
            $base['stills'] = array_values(array_unique(array_merge((array) $base['stills'], (array) $value)));
            continue;
        }
        if (array_key_exists($key, $base) && douban_clean_text((string) $base[$key]) === '' && douban_clean_text((string) $value) !== '') {
            $base[$key] = $value;
        }
    }
}

function douban_fetch_subject_abstract(string $id): array
{
    $out = [];
    try {
        $json = douban_http_json('https://movie.douban.com/j/subject_abstract?subject_id=' . rawurlencode($id), 'https://movie.douban.com/');
    } catch (Throwable $e) {
        return $out;
    }
    $subject = is_array($json['subject'] ?? null) ? $json['subject'] : [];
    $rating = '';
    if (isset($subject['rating']) && is_array($subject['rating'])) {
        $rating = douban_stringify($subject['rating']['value'] ?? '');
        $out['ratingVotes'] = douban_stringify($subject['rating']['count'] ?? '');
    }
    $out['title'] = douban_stringify($subject['title'] ?? '');
    $out['cover'] = douban_first_non_empty($subject['cover_url'] ?? '', $subject['pic'] ?? '');
    $out['rating'] = douban_first_non_empty($rating, $subject['rate'] ?? '');
    $out['subtitle'] = douban_stringify($subject['card_subtitle'] ?? '');
    $out['intro'] = douban_stringify($subject['intro'] ?? '');
    $out['year'] = douban_first_non_empty($subject['release_year'] ?? '', douban_find_year($out['subtitle']));
    $out['director'] = douban_list_text($subject['directors'] ?? [], ' / ');
    $out['actor'] = douban_list_text($subject['actors'] ?? [], ' / ');
    $out['class'] = douban_list_text($subject['types'] ?? [], ' / ');
    $out['area'] = douban_stringify($subject['region'] ?? '');
    $out['duration'] = douban_stringify($subject['duration'] ?? '');
    return $out;
}

function douban_fetch_mobile_meta(string $id): array
{
    $out = [];
    try {
        $json = douban_http_json('https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '?for_mobile=1', 'https://m.douban.com/movie/subject/' . rawurlencode($id) . '/');
    } catch (Throwable $e) {
        return $out;
    }
    $rating = is_array($json['rating'] ?? null) ? $json['rating'] : [];
    $out['title'] = douban_stringify($json['title'] ?? '');
    $out['cover'] = douban_first_non_empty($json['cover_url'] ?? '', $json['pic']['large'] ?? '');
    $out['subtitle'] = douban_stringify($json['card_subtitle'] ?? '');
    $out['intro'] = douban_stringify($json['intro'] ?? '');
    $out['year'] = douban_stringify($json['year'] ?? '');
    $out['rating'] = douban_stringify($rating['value'] ?? '');
    $out['ratingVotes'] = douban_stringify($rating['count'] ?? '');
    $out['director'] = douban_list_text($json['directors'] ?? [], ' / ');
    $out['actor'] = douban_list_text($json['actors'] ?? [], ' / ');
    $out['class'] = douban_list_text($json['genres'] ?? [], ' / ');
    $out['area'] = douban_list_text($json['countries'] ?? [], ' / ');
    $out['lang'] = douban_list_text($json['languages'] ?? [], ' / ');
    $out['pubdate'] = douban_list_text($json['pubdate'] ?? [], ' / ');
    $out['duration'] = douban_list_text($json['durations'] ?? [], ' / ');
    $out['aka'] = douban_list_text($json['aka'] ?? [], ' / ');
    return $out;
}

function douban_fetch_imdb_by_title(string $title, string $year = ''): string
{
    $title = douban_clean_text($title);
    if ($title === '') {
        return '';
    }
    $search = $title . ($year !== '' ? ' ' . $year : '');
    try {
        $json = douban_http_json(
            'https://v2.sg.media-imdb.com/suggestion/x/' . rawurlencode($search) . '.json',
            'https://www.imdb.com/'
        );
    } catch (Throwable $e) {
        return '';
    }
    $items = array_values(array_filter((array) ($json['d'] ?? []), function ($item) {
        return is_array($item) && preg_match('/^tt\d{6,10}$/', (string) ($item['id'] ?? ''));
    }));
    if (!$items) {
        return '';
    }
    $titleNorm = function (string $s): string {
        return strtolower(trim(preg_replace('/\W+/u', '', $s) ?? ''));
    };
    $target = $titleNorm($title);
    foreach ($items as $item) {
        $name = $titleNorm(douban_clean_text((string) ($item['l'] ?? '')));
        if ($target !== '' && $name === $target) {
            return strtolower((string) $item['id']);
        }
    }
    if ($year !== '') {
        foreach ($items as $item) {
            $matchYear = douban_clean_text((string) ($item['y'] ?? ''));
            $matchTl = douban_clean_text((string) ($item['tl'] ?? ''));
            if ($matchYear === $year || strpos($matchTl, $year) !== false) {
                return strtolower((string) $item['id']);
            }
        }
    }
    return strtolower((string) $items[0]['id']);
}

function douban_fetch_credits_meta(string $id): array
{
    $out = [];
    try {
        $json = douban_http_json('https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/credits?start=0&count=100', 'https://m.douban.com/movie/subject/' . rawurlencode($id) . '/');
    } catch (Throwable $e) {
        return $out;
    }
    $writers = [];
    foreach ((array) ($json['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $roleText = douban_list_text($item['roles'] ?? [], ' ') . douban_stringify($item['category'] ?? '') . douban_stringify($item['character'] ?? '');
        if (mb_strpos($roleText, '编剧') === false) {
            continue;
        }
        $name = douban_stringify($item['name'] ?? '');
        if ($name !== '' && !in_array($name, $writers, true)) {
            $writers[] = $name;
        }
    }
    $out['writer'] = implode(' / ', $writers);
    return $out;
}

function douban_fetch_page_meta(string $id): array
{
    $out = [];
    try {
        $body = douban_http_get('https://movie.douban.com/subject/' . rawurlencode($id) . '/', 'https://movie.douban.com/');
    } catch (Throwable $e) {
        return $out;
    }
    if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $body, $matches)) {
        $out['cover'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('/<span[^>]*property=["\']v:summary["\'][^>]*>(.*?)<\/span>/isu', $body, $matches)) {
        $out['intro'] = douban_clean_text(strip_tags($matches[1]));
    } elseif (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $body, $matches)) {
        $out['intro'] = douban_clean_text($matches[1]);
    }
    foreach ([
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
    ] as $key => $label) {
        $out[$key] = douban_info_label($body, $label);
    }
    if (($out['imdb'] ?? '') === '' && preg_match('/tt\d{6,10}/i', $body, $matches)) {
        $out['imdb'] = strtolower($matches[0]);
    }
    if (preg_match_all('/https?:\/\/img\d+\.doubanio\.com\/view\/photo\/[^"\']+\/public\/p\d+\.(?:jpg|webp|png)/i', $body, $matches)) {
        $out['stills'] = array_values(array_unique(array_slice($matches[0], 0, 3)));
    }
    return $out;
}

function douban_fetch_hot_reviews(string $id): array
{
    $out = [];
    try {
        $json = douban_http_json('https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/interests?count=3&order_by=hot&start=0', 'https://m.douban.com/movie/subject/' . rawurlencode($id) . '/');
    } catch (Throwable $e) {
        return $out;
    }
    $lines = [];
    foreach ((array) ($json['interests'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $comment = douban_clean_text($item['comment'] ?? '');
        if ($comment === '') {
            continue;
        }
        $author = douban_first_non_empty($item['user']['name'] ?? '', '匿名用户');
        $lines[] = $comment . ' —— ' . $author;
    }
    $out['hotComment'] = implode("\n\n", $lines);
    return $out;
}

function douban_photo_url(array $photo): string
{
    return douban_first_non_empty(
        $photo['image']['large']['url'] ?? '',
        $photo['image']['normal']['url'] ?? '',
        $photo['image']['small']['url'] ?? '',
        $photo['cover'] ?? '',
        $photo['url'] ?? ''
    );
}

function douban_fetch_photos(string $id): array
{
    $out = ['stills' => []];
    $referer = 'https://m.douban.com/movie/subject/' . rawurlencode($id) . '/';
    $urls = [
        'https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/photos?type=S&start=0&count=6',
        'https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/photos?type=R&start=0&count=3',
        'https://m.douban.com/rexxar/api/v2/movie/' . rawurlencode($id) . '/photos?start=0&count=6',
    ];

    foreach ($urls as $url) {
        try {
            $json = douban_http_json($url, $referer);
        } catch (Throwable $e) {
            continue;
        }
        foreach ((array) ($json['photos'] ?? []) as $photo) {
            if (!is_array($photo)) {
                continue;
            }
            $image = douban_photo_url($photo);
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

function douban_cover_url(string $url, string $referer = 'https://movie.douban.com/'): string
{
    if ($url === '') {
        return '';
    }
    return 'plugin/huux_tinymce/tinymce/plugins/douban/proxy.php?action=cover&url=' . rawurlencode($url) . '&referer=' . rawurlencode($referer);
}

function douban_render_html(array $data): string
{
    $year = (string) $data['year'];
    $title = douban_display_title((string) $data['title'], $year);
    $cover = douban_cover_url((string) $data['cover'], (string) $data['url']);
    $rating = (string) ($data['rating'] !== '' ? $data['rating'] : '暂无评分');
    $ratingVotes = (string) $data['ratingVotes'];
    if ($ratingVotes !== '' && preg_match('/^\d+$/', $ratingVotes)) {
        $ratingVotes = number_format((int) $ratingVotes) . '人评';
    }
    $subtitleParts = array_filter([$year, $data['area'], $data['class'], $data['director'], $data['actor']]);
    $subtitle = douban_first_non_empty((string) $data['subtitle'], implode(' / ', $subtitleParts));
    $metaRows = [
        ['导演', (string) $data['director']],
        ['编剧', (string) $data['writer']],
        ['主演', (string) $data['actor']],
        ['类型', (string) $data['class']],
        ['国家/语言', trim((string) $data['area'] . ' / ' . (string) $data['lang'], ' /')],
        ['上映/首播', (string) $data['pubdate']],
        ['单集片长', (string) $data['duration']],
        ['又名', (string) $data['aka']],
        ['IMDb', (string) $data['imdb']],
        ['豆瓣ID', (string) $data['id']],
    ];

    $html = '<div class="chuan-douban-card movie-card">';
    $html .= '<div class="movie-card__hero">';
    if ($cover !== '') {
        $html .= '<div class="movie-card__poster"><img src="' . douban_e($cover) . '" alt="' . douban_e($title) . '"></div>';
    }
    $html .= '<div class="movie-card__main"><div class="movie-card__head">';
    $html .= '<h2 class="movie-card__title">' . douban_e($title);
    if ($year !== '') {
        $html .= '<span>（' . douban_e($year) . '）</span>';
    }
    $html .= '</h2><div class="movie-card__rating"><strong>' . douban_e($rating) . '</strong><span></span>';
    if ($ratingVotes !== '') {
        $html .= '<em>' . douban_e($ratingVotes) . '</em>';
    }
    $html .= '</div></div>';
    if ($subtitle !== '') {
        $html .= '<p class="movie-card__subtitle">' . douban_e($subtitle) . '</p>';
    }
    $html .= '<div class="movie-card__meta">';
    foreach ($metaRows as $row) {
        if ($row[1] === '') {
            continue;
        }
        $class = $row[0] === '主演' ? ' movie-card__meta--clamp' : '';
        $html .= '<p class="' . trim('movie-card__meta-row' . $class) . '"><strong>' . douban_e($row[0]) . '：</strong><span>' . douban_e($row[1]) . '</span></p>';
    }
    $html .= '</div></div></div>';
    if ($data['intro'] !== '') {
        $html .= '<div class="movie-card__section"><h3>剧情简介</h3><p>' . douban_e((string) $data['intro']) . '</p></div>';
    }
    if ($data['hotComment'] !== '') {
        $html .= '<div class="movie-card__section movie-card__quote"><h3>影视热评</h3><p>“' . nl2br(douban_e((string) $data['hotComment'])) . '”</p></div>';
    }
    $html .= '<div class="movie-card__section"><h3>下载地址：</h3></div>';
    $html .= '</div><p></p>';
    return $html;
}

function douban_payload_subject(array $payload): string
{
    $title = douban_display_title((string) $payload['title'], (string) $payload['year']);
    $year = (string) $payload['year'];
    return $year === '' ? $title : $title . ' (' . $year . ')';
}

function douban_payload_tagline(array $payload): string
{
    return douban_first_non_empty((string) $payload['subtitle'], implode(' / ', array_filter([
        $payload['year'],
        $payload['area'],
        $payload['class'],
        $payload['director'],
        $payload['actor'],
    ])));
}

function douban_payload_tags(array $payload): array
{
    $line = douban_payload_tagline($payload);
    $tags = [];
    foreach (preg_split('/[\s\/,，、]+/u', $line) ?: [] as $tag) {
        $tag = douban_clean_text($tag);
        if ($tag !== '' && !in_array($tag, $tags, true)) {
            $tags[] = $tag;
        }
    }
    return array_slice($tags, 0, 12);
}

function douban_detail(string $id): array
{
    if (!preg_match('/^\d+$/', $id)) {
        throw new InvalidArgumentException('豆瓣ID格式不正确');
    }
    $payload = douban_payload_empty($id);
    douban_merge($payload, douban_fetch_subject_abstract($id));
    douban_merge($payload, douban_fetch_mobile_meta($id));
    douban_merge($payload, douban_fetch_credits_meta($id));
    douban_merge($payload, douban_fetch_page_meta($id));
    douban_merge($payload, douban_fetch_hot_reviews($id));
    douban_merge($payload, douban_fetch_photos($id));
    if ($payload['title'] === '') {
        throw new RuntimeException('未获取到豆瓣影视信息，请稍后重试');
    }
    if ($payload['cover'] === '') {
        $candidates = douban_search($payload['title']);
        if ($candidates) {
            $payload['cover'] = $candidates[0]['img'] ?? '';
        }
    }
    if ($payload['rating'] === '') {
        $payload['rating'] = '暂无评分';
    }
    if ($payload['imdb'] === '') {
        $payload['imdb'] = douban_fetch_imdb_by_title($payload['title'], $payload['year']);
    }
    if ($payload['subtitle'] === '') {
        $payload['subtitle'] = $payload['year'];
    }
    $payload['html'] = douban_render_html($payload);
    $subject = douban_payload_subject($payload);
    return [
        'id' => $payload['id'],
        'title' => douban_display_title((string) $payload['title'], (string) $payload['year']),
        'subject' => $subject,
        'tagline' => douban_payload_tagline($payload),
        'tags' => douban_payload_tags($payload),
        'year' => $payload['year'],
        'rating' => $payload['rating'],
        'url' => $payload['url'],
        'cover' => $payload['cover'],
        'source' => 'Douban',
        'html' => $payload['html'],
    ];
}

function douban_cover_candidates(string $url): array
{
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        return [];
    }
    $hosts = [$parts['host']];
    if (preg_match('/^img\d+\.doubanio\.com$/', $parts['host'])) {
        $hosts = ['img1.doubanio.com', 'img2.doubanio.com', 'img3.doubanio.com', 'img9.doubanio.com'];
    }
    $path = $parts['path'] ?? '';
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    $schemes = [$parts['scheme'] ?? 'https'];
    $sizes = ['', 'l', 'm', 's_ratio_poster'];
    $out = [];
    foreach ($hosts as $host) {
        foreach ($sizes as $size) {
            $nextPath = $path;
            if ($size !== '') {
                $nextPath = preg_replace('~/view/photo/[^/]+/public/~', '/view/photo/' . $size . '/public/', $path);
                if ($nextPath === $path) {
                    continue;
                }
            }
            foreach ($schemes as $scheme) {
                $out[] = $scheme . '://' . $host . $nextPath . $query;
            }
        }
    }
    return array_values(array_unique($out));
}

function douban_output_cover(): void
{
    $url = trim((string) ($_GET['url'] ?? ''));
    $referer = trim((string) ($_GET['referer'] ?? 'https://movie.douban.com/'));
    $host = parse_url($url, PHP_URL_HOST);
    if ($url === '' || !is_string($host) || !preg_match('/(^|\.)douban(io)?\.com$/', $host)) {
        http_response_code(400);
        exit('bad url');
    }
    foreach (douban_cover_candidates($url) as $candidate) {
        try {
            $body = douban_http_get($candidate, $referer, true);
        } catch (Throwable $e) {
            continue;
        }
        if (strlen($body) < 1024) {
            continue;
        }
        $type = function_exists('finfo_open') ? (new finfo(FILEINFO_MIME_TYPE))->buffer($body) : '';
        if (!is_string($type) || strpos($type, 'image/') !== 0) {
            $type = 'image/jpeg';
        }
        header('Content-Type: ' . $type);
        header('Cache-Control: public, max-age=86400');
        echo $body;
        exit;
    }
    header('Content-Type: image/svg+xml; charset=utf-8');
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 450"><rect width="300" height="450" rx="18" fill="#edf2ff"/><text x="150" y="225" text-anchor="middle" fill="#60708a" font-size="26" font-family="Arial">暂无封面</text></svg>';
    exit;
}

try {
    $action = trim((string) ($_GET['action'] ?? ''));
    if ($action === 'cover') {
        douban_output_cover();
    }
    if ($action === 'resolve') {
        $query = trim((string) ($_GET['q'] ?? ''));
        $queryLength = function_exists('mb_strlen') ? mb_strlen($query, 'UTF-8') : strlen($query);
        if ($query === '' || $queryLength > 100) {
            douban_json(1, '请输入100字以内的豆瓣ID、链接或名称');
        }
        $id = douban_extract_id($query);
        if ($id !== '') {
            douban_json(0, 'ok', [
                'type' => 'detail',
                'data' => douban_detail($id),
            ]);
        }
        $results = douban_search($query);
        if (!$results) {
            douban_json(1, '未找到相关豆瓣条目');
        }
        if (count($results) === 1) {
            douban_json(0, 'ok', [
                'type' => 'detail',
                'data' => douban_detail($results[0]['id']),
            ]);
        }
        douban_json(0, 'ok', [
            'type' => 'list',
            'data' => $results,
        ]);
    }
    if ($action === 'detail') {
        $id = trim((string) ($_GET['id'] ?? ''));
        douban_json(0, 'ok', [
            'type' => 'detail',
            'data' => douban_detail($id),
        ]);
    }
    douban_json(1, '未知操作');
} catch (Throwable $e) {
    error_log('[huux_tinymce_douban] ' . $e->getMessage());
    douban_json(1, $e->getMessage());
}
