<?php
// Set the content type to XML
header('Content-Type: application/xml; charset=utf-8');

// Base URL of your website
$baseUrl = 'https://play.thetrue.in';

// Start the XML output
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// --- 1. Static Pages ---
// List all your static pages here
$staticPages = [
    ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => '/about.html', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/privacy-policy.html', 'priority' => '0.5', 'changefreq' => 'yearly'],
    ['loc' => '/terms.html', 'priority' => '0.5', 'changefreq' => 'yearly']
];

foreach ($staticPages as $page) {
    echo '<url>';
    echo '<loc>' . $baseUrl . $page['loc'] . '</loc>';
    echo '<priority>' . $page['priority'] . '</priority>';
    echo '<changefreq>' . $page['changefreq'] . '</changefreq>';
    echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
    echo '</url>';
}

// --- 2. Dynamic Audio Track Pages ---
$audio_data_file = 'data/audio_data.json';
if (file_exists($audio_data_file)) {
    $audioList = json_decode(file_get_contents($audio_data_file), true);

    if (is_array($audioList)) {
        foreach ($audioList as $track) {
            echo '<url>';
            // We are creating a unique URL for each track using its ID
            echo '<loc>' . $baseUrl . '/?track_id=' . htmlspecialchars($track['id']) . '</loc>';
            echo '<priority>0.8</priority>';
            echo '<changefreq>monthly</changefreq>';
            echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
            echo '</url>';
        }
    }
}

echo '</urlset>';
?>
