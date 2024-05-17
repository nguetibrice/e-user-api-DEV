<?php

namespace App\Http\Controllers;

use App\Dtos\Language;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use App\Services\Contracts\IPriceService;
use App\Services\Contracts\ILanguageService;
class LanguageController extends Controller
{
    protected ILanguageService $languageService;
    protected IPriceService $priceService;

    public function __construct(ILanguageService $languageService, IPriceService $priceService)
    {
        $this->languageService = $languageService;
        $this->priceService = $priceService;
    }

    /**
     * Display a listing of languages.
     */
    public function index(Request $request): JsonResponse
    {
        $languages = $this->languageService->getLanguages($request->page == null?1:(int)$request->page);

        return Response::success(['languages' => $languages]);
    }

    /**
     * Display the specified language.
     *
     * @param  int  $id The language's identifier
     */
    public function show(string $id): JsonResponse
    {
        $language = $this->languageService->getLanguage($id);

        $prices = $this->priceService->getPrices(['language' => $language->getId()]);
        $language->setPrices($prices);

        return Response::success(['language' => $language]);
    }

    /**
     * Store a newly created language in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|size:4',
            'description' => 'required|string',
            'status' => 'required|integer|between:0,1'
        ]);

        $language = $this->languageService->createLanguage(new Language(
            null,
            $input['code'],
            $input['name'],
            $input['description'],
            $input['status']
        ));
        return Response::success(['language' => $language]);
    }

    /**
     * Update the specified language in storage.
     *
     * @param  int  $id The language's identifier
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'code' => 'sometimes|string|size:4',
            'description' => 'sometimes|string',
            'status' => 'sometimes|integer|between:0,1',
        ]);

        if (array_key_exists('status', $data)) {
            $data['active'] = $data['status'] == '1' ? true : false;
            unset($data['status']);
        }
        if (isset($data['code'])) {
            $code = $data['code'];
            unset($data['code']);
            $data['metadata'] = ['code' => $code];
        }

        $language = $this->languageService->updateLanguage($id, $data);
        return Response::success(['language' => $language]);
    }

    /**
     * Remove the specified language from storage.
     *
     * @param string $id The language's identifier
     */
    public function delete(string $id): JsonResponse
    {
        $this->languageService->deleteLanguage($id);
        return Response::success(['message' => "The language referred by '$id' was deleted"]);
    }
}
