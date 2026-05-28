```php id="f7d2qm"
<?php

include 'app/functions.php';

if (!file_exists($loginFilePath)) {
    http_response_code(401);
    echo 'Login required.';
    exit;
}

header('Content-Type: audio/x-mpegurl');
header('Content-Disposition: attachment; filename="playlist.m3u"');

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $origin_api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

curl_close($ch);

$data = json_decode($response, true);

$channels = $data['data']['list'] ?? [];

if (!is_array($channels)) {

    http_response_code(500);

    echo "# Error loading channel list\n";

    exit;
}

$skip_ids_json = @file_get_contents($stb_only);

$skip_ids = json_decode($skip_ids_json, true);

if (!is_array($skip_ids)) {
    $skip_ids = [];
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ? "https"
    : "http";

$host = $_SERVER['HTTP_HOST'];

$request_uri = $_SERVER['REQUEST_URI'];

$path = dirname($request_uri);

$base_url = "{$protocol}://{$host}{$path}";

foreach ($channels as $channel) {

    $channel_id = $channel['id'];

    if (in_array($channel_id, $skip_ids, true)) {
        continue;
    }

    if (
        isset($channel['provider']) &&
        $channel['provider'] === 'DistroTV'
    ) {
        continue;
    }

    $channel_name = $channel['title'];

    $channel_logo = $channel['transparentImageUrl'];

    $genres = array_values(
        array_diff($channel['genres'] ?? [], ['HD'])
    );

    $channel_genre = $genres[0] ?? 'General';

    $license_url =
        "https://tp.drmlive-01.workers.dev?id={$channel_id}";

    $channel_live =
        "{$base_url}/get-mpd.php?id={$channel_id}";

    echo "#EXTINF:-1 tvg-id=\"ts{$channel_id}\" tvg-logo=\"{$channel_logo}\" group-title=\"{$channel_genre}\",{$channel_name}\n";

    echo "#KODIPROP:inputstreamaddon=inputstream.adaptive\n";

    echo "#KODIPROP:inputstream.adaptive.manifest_type=mpd\n";

    echo "#KODIPROP:inputstream.adaptive.license_type=clearkey\n";

    echo "#KODIPROP:inputstream.adaptive.license_key={$license_url}\n";

    echo "#KODIPROP:inputstream.adaptive.license_flags=persistent_storage\n";

    echo "#EXTVLCOPT:http-user-agent=Mozilla/5.0\n";

    echo "#EXTHTTP:{\"User-Agent\":\"Mozilla/5.0\",\"Origin\":\"https://watch.tataplay.com\",\"Referer\":\"https://watch.tataplay.com/\"}\n";

    echo "{$channel_live}\n\n";
}

?>
```

