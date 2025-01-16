<?php

declare(strict_types=1);

namespace App\domains\documentTemplate;

use App\domains\contract\enums\OrganizerDocumentTemplatePlaceholderEnum;
use App\domains\documentTemplate\placeholders\ContractPlaceholderFiller;
use App\domains\documentTemplate\placeholders\OrganizerPlaceholderFiller;
use App\domains\documentTemplate\placeholders\RequestPlaceholderFiller;
use App\domains\documentTemplate\placeholders\VoucherPlaceholderFiller;
use App\domains\localization\services\LocalizationService;
use App\Models\Voucher;
use Yii;

class VoucherDocumentPlaceholderFiller extends DocumentPlaceholderFiller
{
    private Voucher $voucher;

    public function __construct(Voucher $voucher)
    {
        $this->voucher = $voucher;
        if ($this->voucher->contract->isOwnContract()) {
            $localizationService = Yii::$container->get(LocalizationService::class);
            $localization = $localizationService->getLocalizationForOrganizer($this->voucher->contract->tour_organizer_id);
        } else {
            $localization = $this->voucher->contract->request->sourceLocalization;
        }
        $this->placeholders = [
            new OrganizerPlaceholderFiller($this->voucher->contract->getOrgWithFixedData(), $localization),
            new ContractPlaceholderFiller($this->voucher->contract, $localization),
            new RequestPlaceholderFiller($this->voucher->contract->request, $localization),
            new VoucherPlaceholderFiller($this->voucher, $localization),
        ];
    }

    /**
     * Проверяет: явлется ли переменная шаблона обязательной к заполнению для генерации файла
     * @param  string $placeholder
     * @return bool
     */
    protected function isRequiredPlaceholder(OrganizerDocumentTemplatePlaceholderEnum $placeholder): bool
    {
        if (parent::isRequiredPlaceholder($placeholder)) {
            return !in_array($placeholder, [
                OrganizerDocumentTemplatePlaceholderEnum::TOUR_PACKING_LIST,
                OrganizerDocumentTemplatePlaceholderEnum::TOUR_FAQ,
            ]);
        }
        return false;
    }

    protected function getSupportedPlaceholders(): array
    {
        return [
            OrganizerDocumentTemplatePlaceholderEnum::AUTHORIZED_SIGNATURE,
            OrganizerDocumentTemplatePlaceholderEnum::SEAL_AND_SIGNATURE,
            OrganizerDocumentTemplatePlaceholderEnum::COMPANY_LEGAL_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::REGISTRATION_NUMBER_OF_TOUR_OPERATOR,
            OrganizerDocumentTemplatePlaceholderEnum::CONTRACT_NUMBER,
            OrganizerDocumentTemplatePlaceholderEnum::CREATION_DATE,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_DATES,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::THE_LIST_OF_TOURIST,
            OrganizerDocumentTemplatePlaceholderEnum::VOUCHER_TEMPLATE_TEXT,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_PACKING_LIST,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_FAQ,
            OrganizerDocumentTemplatePlaceholderEnum::OGRN,
            OrganizerDocumentTemplatePlaceholderEnum::LEGAL_ENTITY_IDENTIFIER,
            OrganizerDocumentTemplatePlaceholderEnum::KPP,
            OrganizerDocumentTemplatePlaceholderEnum::COMPANY_EXECUTIVE_POSITION_TITLE,
            OrganizerDocumentTemplatePlaceholderEnum::COMPANY_EXECUTIVE_LAST_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::COMPANY_EXECUTIVE_FIRST_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::COMPANY_EXECUTIVE_MIDDLE_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_PROVIDER_LAST_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_PROVIDER_FIRST_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_PROVIDER_MIDDLE_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_PROVIDER_PHONE_NUMBER,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_PROVIDER_EMAIL,
        ];
    }
}
