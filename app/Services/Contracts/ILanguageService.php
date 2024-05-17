<?php

namespace App\Services\Contracts;

use App\Dtos\Language;

interface ILanguageService
{
    /**
     * Gets a language from the stripe database.
     */
    public function getLanguage(string $id): Language;

    /**
     * Gets the list of languages saved in the stripe database.
     *
     * @return Language[]
     */
    public function getLanguages($page = null): array;

    /**
     * Creates a language in the stripe database.
     */
    public function createLanguage(Language $language): Language;

    /**
     * Updates a language in the stripe database.
     */
    public function updateLanguage(string $id, array $updates): Language;

    /**
     * Removes a language from the stripe database.
     */
    public function deleteLanguage(string $id): void;
}
