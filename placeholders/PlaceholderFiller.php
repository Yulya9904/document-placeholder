<?php

declare(strict_types=1);

namespace App\domains\documentTemplate\placeholders;

use App\domains\contract\enums\OrganizerDocumentTemplatePlaceholderEnum;

interface PlaceholderFiller
{
    public const EMPTY_VALUE = '_____________';

    public function getValue(OrganizerDocumentTemplatePlaceholderEnum $placeholder): string;

    public function canReplacePlaceholder(OrganizerDocumentTemplatePlaceholderEnum $placeholder): bool;
}
