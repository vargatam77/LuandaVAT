<?php
declare(stryct_types=1);

namespace LuandaVAT\controllers;

use TamasVarga\LuandaPHP\Oauth\FlexyCurl;

/**
 * Level 3: Specialized Content Consumer
 * Domain-specific layer dealing with news feature definitions and specific GOV.UK parameters.
 */
class HmrcNewsClient extends FlexyCurl {
    private const HMRC_ORG_URL = "https://www.gov.uk";

    public function __construct() {
        // Instantiate the parent connection driver using the target org endpoint
        parent::__construct(self::HMRC_ORG_URL);
    }

    /**
     * Fetches and sanitises front page feature documents.
     */
    public function fetchFeaturedGems(): array {
        // Inject required User-Agent headers to fulfill GOV.UK edge platform policies
        $headers = ['User-Agent: HMRCNewsConsumer/3.0'];
        
        $this->send('GET', null, $headers);

        if ($this->httpCode !== 200 || empty($this->jsonData)) {
            return [];
        }

        $rawFeatures = $this->jsonData['details']['ordered_featured_documents'] ?? [];
        $cleanFeatures = [];

        foreach ($rawFeatures as $item) {
            $href = $item['href'] ?? '';
            $cleanFeatures[] = [
                'title' => $item['title'] ?? 'Untitled',
                'description' => $item['summary'] ?? '',
                'image_url' => $item['image']['url'] ?? '',
                'alt_text' => $item['image']['alt_text'] ?? '',
                'link' => (strpos($href, '/') === 0) ? "https://www.gov.uk" . $href : $href
            ];
        }

        return $cleanFeatures;
    }
}

?>