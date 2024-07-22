<?php
// fetch_images.php

class IstockImageFetcher {
    private $phrase;
    private $mediatype;
    private $number_of_people;
    private $orientations;
    private $page;
    private $api;
    private $userAgents;

    public function __construct($phrase, $mediatype = 'photography', $number_of_people = 'none', $orientations = 'undefined', $page = 1) {
        $this->phrase = urlencode($phrase);
        $this->mediatype = $mediatype;
        $this->number_of_people = $number_of_people;
        $this->orientations = $orientations;
        $this->page = $page;
        $this->api = 'https://www.istockphoto.com/search/2/image';

        $this->userAgents = [
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36 Edg/116.0.1938.76",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15",
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36"
        ];
    }

    private function getRandomUserAgent() {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    private function fetchHtml($url) {
        $options = [
            "http" => [
                "header" => "User-Agent: " . $this->getRandomUserAgent()
            ]
        ];
        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }

    private function buildQuery() {
        $params = [
            "phrase" => $this->phrase,
            "page" => $this->page
        ];

        if ($this->mediatype !== 'undefined') {
            $params["mediatype"] = $this->mediatype;
        }
        if ($this->number_of_people !== 'undefined') {
            $params["numberofpeople"] = $this->number_of_people;
        }
        if ($this->orientations !== 'undefined') {
            $params["orientations"] = $this->orientations;
        }

        return http_build_query($params);
    }

    public function getImageUrls() {
        $query = $this->buildQuery();
        $url = "{$this->api}?{$query}";
        $html = $this->fetchHtml($url);
        $imageUrls = [];

        if ($html !== false) {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            $gallery = $xpath->query("//div[@data-testid='gallery-items-container']");

            if ($gallery->length > 0) {
                $imgTags = $gallery->item(0)->getElementsByTagName("img");

                foreach ($imgTags as $img) {
                    $src = $img->getAttribute("src");

                    if (strpos($src, "https://media.istockphoto.com/") === 0) {
                        $imageUrls[] = $src;
                    }
                }
            }
        }

        return $imageUrls;
    }
}

// Handle request
if (isset($_GET['query'])) {
    $phrase = $_GET['query'];
    $fetcher = new IstockImageFetcher($phrase);
    $imageUrls = $fetcher->getImageUrls();
    header('Content-Type: application/json');
    echo json_encode($imageUrls);
}
?>
