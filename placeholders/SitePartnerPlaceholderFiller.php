<?php

declare(strict_types=1);

namespace App\domains\documentTemplate\placeholders;

use App\domains\contract\enums\OrganizerDocumentTemplatePlaceholderEnum;
use App\domains\language\models\Language;
use App\domains\localization\models\Localization;
use App\domains\site\models\Site;
use yii\helpers\Html;

class SitePartnerPlaceholderFiller implements PlaceholderFiller
{
    protected Site $site;
    protected Localization $localization;

    public function __construct(Site $site, Localization $localization)
    {
        $this->site = $site;
        $this->localization = $localization;
    }

    public function getValue(
        OrganizerDocumentTemplatePlaceholderEnum $placeholder,
    ): string {
        return match ($placeholder->value) {
            OrganizerDocumentTemplatePlaceholderEnum::PARTNER_COMPANY_SHORT_LEGAL_NAME->value => Html::encode($this->site->contract_short_title) ?: '',
            OrganizerDocumentTemplatePlaceholderEnum::PARTNER_LEGAL_ENTITY_IDENTIFIER->value => Html::encode($this->site->contract_inn ?: ''),
            OrganizerDocumentTemplatePlaceholderEnum::PARTNER_KPP->value => Html::encode($this->site->contract_kpp ?: ''),
            OrganizerDocumentTemplatePlaceholderEnum::PARTNER_LEGAL_ADDRESS->value => Html::encode($this->getLegalAddress()),
        };
    }

    public function canReplacePlaceholder(OrganizerDocumentTemplatePlaceholderEnum $placeholder): bool
    {
        return in_array($placeholder, [
            OrganizerDocumentTemplatePlaceholderEnum::PARTNER_COMPANY_SHORT_LEGAL_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::PARTNER_LEGAL_ENTITY_IDENTIFIER,
            OrganizerDocumentTemplatePlaceholderEnum::PARTNER_KPP,
            OrganizerDocumentTemplatePlaceholderEnum::PARTNER_LEGAL_ADDRESS,
        ]);
    }

    private function getLegalAddress(): ?string
    {
        $res = [];
        $countryName = $this->site->countryOfLegalAddress?->getLanguageValue($this->localization->language_id, 'name');
        if (!$countryName || !$this->site->legal_address_city) {
            return null;
        }
        if ($this->localization->language_id === Language::RU) {
            if ($this->site->legal_address_postal_code) {
                $res[] = $this->site->legal_address_postal_code;
            }
            if ($this->site->countryOfLegalAddress) {
                $res[] = $this->site->countryOfLegalAddress->name;
            }
            if ($this->site->legal_address_region) {
                $res[] = $this->site->legal_address_region;
            }
            if ($this->site->legal_address_city) {
                $res[] = $this->site->legal_address_city;
            }
            if ($this->site->legal_address_street) {
                $res[] = $this->site->legal_address_street;
            }
        } else {
            if ($this->site->legal_address_street) {
                $res[] = $this->site->legal_address_street;
            }
            $res[] = $this->site->legal_address_city;
            if ($this->site->legal_address_region) {
                $res[] = $this->site->legal_address_region;
            }
            if ($this->site->legal_address_postal_code) {
                $res[] = $this->site->legal_address_postal_code;
            }
            $res[] = $countryName;
        }

        return $res ? implode(', ', $res) : '';
    }

}
