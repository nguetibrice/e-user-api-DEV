<?php

namespace App\Events;

use App\Dtos\Language;

class LanguageCreatedOrUpdated extends BaseDjedSyncEvent
{

    /**
     * Create a new event instance.
     *s
     * @return void
     */
    protected $language;
    protected string $action;
    public function __construct(
        $language,
        $action
    ) {
        parent::__construct($language);
        parent::setAction($action);
    }

}
