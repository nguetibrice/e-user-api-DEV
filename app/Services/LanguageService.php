<?php

namespace App\Services;

use App\Events\LanguageCreatedOrUpdated;
use Stripe\Stripe;
use Stripe\Product;
use App\Dtos\Language;
use Illuminate\Support\Facades\Cache;
use App\Services\Contracts\ILanguageService;
use Request;

class LanguageService implements ILanguageService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function getLanguage(string $id): Language
    {
        $results = array_filter($this->getProducts(), function ($product) use ($id) {
            return $product->id === $id;
        });

        if (empty($results)) {
            throw new \RuntimeException("The language referred to by '$id' does not exist.");
        }

        $product = array_shift($results);

        return new Language(
            $product->id,
            $product->metadata['code'],
            $product->name,
            $product->description,
            (int) $product->active
        );
    }

    public function getLanguages($page = 1): array
    {
        $products = $this->getProducts($page);

        return array_map(function ($product) {
            return new Language(
                $product->id,
                $product->metadata['code'],
                $product->name,
                $product->description,
                (int) $product->active
            );
        }, $products);
    }

    public function createLanguage(Language $language): Language
    {
        $product = Product::create([
            'name' => $language->getName(),
            'description' => $language->getDescription(),
            'metadata' => ['code' => $language->getCode()],
            'active' => (bool) $language->getStatus()
        ]);

        $this->refreshCache();
        event(new LanguageCreatedOrUpdated($language, "created"));

        return $language->setId($product->id);
    }

    public function updateLanguage(string $id, array $updates): Language
    {
        Product::update($id, $updates);
        $this->refreshCache();

        $language = $this->getLanguage($id);
        event(new LanguageCreatedOrUpdated($language, "updated"));
        return  $language;
    }

    public function deleteLanguage(string $id): void
    {
        Product::update($id, ["active" => false]);

        $this->refreshCache();
    }

    protected function getProducts(int $page = 1): array
    {
        $data = [
            'query' => "active:'true'",
            "limit"=>env('PRODUCT_QUERY_LIMIT'),
            "expand[]" => "data.prices.data.currency_options"
        ];
        if ($page > 1) {
            $data["next_page"] = $page;
        }
        return Cache::remember("languages::$page", now()->addMinutes(5), function () use ($data) {
            return Product::search($data)->data;
        });
    }

    protected function refreshCache()
    {
        Cache::forget('languages');
        $this->getProducts();
    }
}
