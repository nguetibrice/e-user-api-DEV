<?php

namespace App\Services\Contracts;

use App\Models\UserLevel;

interface IUserLevelsService extends IBaseService
{
    /**
     * Gets the list of supported payment methods.
     *
     * @return UserLevel[]
     */
    public function getUserLevels();

    /**
     * Adds a payment method to the list of supported payment methods.
     */
    public function addUserLevel(array $data): UserLevel;

    // /**
    //  * Updates the specified supported payment method.
    //  */
    // public function updateUserLevel(string $id, array $data): UserLevel;

    // /**
    //  * Removes the specified payment method from the list of supported payment methods.
    //  */
    // public function deleteUserLevel(string $id);

    // /**
    //  * Get the specified payment method.
    //  */
    // public function findUserLevel(string $id);
}
