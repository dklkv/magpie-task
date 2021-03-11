<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\DomCrawler\Crawler;

require 'vendor/autoload.php';

class Scrape
{
    private const DOCUMENT_LOCATION = 'https://www.magpiehq.com/developer-challenge/smartphones';
    private const OUTPUT_FILE_PATH = 'output.json';

    private array $products = [];

    public function run(): void
    {
        $pageHtml = ScrapeHelper::fetchDocument(self::DOCUMENT_LOCATION . '?page=1');
        $maxPage = $this->getMaxPage($pageHtml);
        $this->parsePageHtml($pageHtml);

        for ($i = 2; $i <= $maxPage; $i++) {
            $this->parsePageHtml(ScrapeHelper::fetchDocument(self::DOCUMENT_LOCATION . '?page=' . $i));
        }

        file_put_contents(self::OUTPUT_FILE_PATH, json_encode(array_unique($this->products)));
    }

    private function getMaxPage(Crawler $pageHtml): int
    {
        $maxPage = $pageHtml->filter('#products')->children('p')->text();
        $maxPage = explode(' ', $maxPage);

        return (int)end($maxPage);
    }

    private function parsePageHtml(Crawler $pageHtml): void
    {
        $pageHtml
            ->filter('#products .product > div')
            ->each(fn(Crawler $productHtml) => $this->parseProductHtml($productHtml));
    }

    private function parseProductHtml(Crawler $productHtml): void
    {
        $divs = $productHtml->children('div');
        $colours = $this->parseColors($divs->eq(0));

        foreach ($colours as $colour) {
            $product = new Product();

            $header = $productHtml->filter('h3');
            $product->setTitle($header->filter('.product-name')->text());
            $product->setCapacityMb(
                $this->parseCapacityInMb($header->filter('.product-capacity')->text())
            );

            $product->setImageUrl($productHtml->filter('img')->image()->getUri());

            $product->setColor($colour);
            $product->setPrice($this->parsePrice($divs->eq(1)->text()));
            $product->setAvailabilityText($this->parseAvailabilityString($divs->eq(2)->text()));
            if ($divs->count() === 4) {
                $product->setShippingText($divs->eq(3)->text());
            }

            $this->products[] = $product;
        }
    }

    private function parseCapacityInMb(string $capacityString): int
    {
        $matches = [];
        preg_match('/(\d+)\s*(MB|GB)/', $capacityString, $matches);

        return strtoupper($matches[2]) === 'GB' ? $matches[1] * 1000 : (int)$matches[1];
    }

    private function parseAvailabilityString(string $availabilityString): string
    {
        return trim(explode(':', $availabilityString)[1]);
    }

    private function parseColors(Crawler $colorDiv): array
    {
        $colors = [];

        $colorDiv->filter('span')
            ->each(function (Crawler $span) use (&$colors) {
                $colors[] = $span->attr('data-colour');
            });

        return $colors;
    }

    private function parsePrice(string $moneyString): float
    {
        $matches = [];
        preg_match('/[\d.]+/', $moneyString, $matches);

        return (float)$matches[0];
    }
}

$scrape = new Scrape();
$scrape->run();
