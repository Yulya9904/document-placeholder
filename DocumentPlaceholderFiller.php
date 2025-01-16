<?php

declare(strict_types=1);

namespace App\domains\documentTemplate;

use App\domains\contract\enums\OrganizerDocumentTemplatePlaceholderEnum;
use App\domains\contract\exceptions\NotEnoughDataForContractException;
use App\domains\contract\services\OrganizerDocumentUnfilledPlaceholdersMapper;
use App\domains\documentTemplate\exception\PlaceholderNotExistsException;
use App\domains\documentTemplate\placeholders\PlaceholderFiller;
use Yii;

abstract class DocumentPlaceholderFiller
{
    /**
     * @var PlaceholderFiller[]
     */
    protected array $placeholders = [];

    /**
     * @throws NotEnoughDataForContractException
     */
    public function render(string $template, bool $useStamp): string
    {
        $unfilledPlaceholders = [];
        $mapper = new OrganizerDocumentUnfilledPlaceholdersMapper();
        foreach ($this->hiddenPlaceholders() as $hiddenPlaceholder) {
            $template = str_replace($hiddenPlaceholder->value, PlaceholderFiller::EMPTY_VALUE, $template);
        }
        foreach ($this->getPlaceholderParams($template) as $placeholder) {
            if (!in_array($placeholder, $this->getSupportedPlaceholders())) {
                $template = str_replace($placeholder->value, PlaceholderFiller::EMPTY_VALUE, $template);
                continue;
            }
            if (!$useStamp && in_array($placeholder, [
                OrganizerDocumentTemplatePlaceholderEnum::SEAL_AND_SIGNATURE,
                OrganizerDocumentTemplatePlaceholderEnum::AUTHORIZED_SIGNATURE])
            ) {
                $template = str_replace($placeholder->value, '', $template);
                continue;
            }
            $handler = $this->getPlaceholderFiller($placeholder);
            $value = $handler->getValue($placeholder);
            //Получаем значение для договора, чтобы вставить вместо переменной шаблона
            //Заменяем значение переменной шаблона
            $template = str_replace($placeholder->value, (string)$value, $template);
            if (!$this->isRequiredPlaceholder($placeholder)) {
                continue;
            }
            if (!$value) {
                $type = $mapper->getErrorTypeByPlaceholderName($placeholder->name, Yii::$app->i18n->languageId);
                //Если обезательная для заполнения переменная не заполнена, то выводим ошибку об этом
                if ($type) {
                    $unfilledPlaceholders[] = [
                        'name' => mb_strtolower($placeholder->name),
                        'type' => $type,
                    ];
                }
            }
            //заменяем все теги {if tour_itinerary}Программа тура{endif}, которые выводятся по условию заполнения атрибута
            $template = preg_replace('/\{if ' . $placeholder->value . '\}(.*?)\{endif\}/', ($value ? '${1}' : ''), $template);
        }
        if ($unfilledPlaceholders) {
            //Прокидываем исключение с незаполенными переменными, чтобы  вывести их на фронте
            throw new NotEnoughDataForContractException(unfilledPlaceholders: $unfilledPlaceholders);
        }
        return $template;
    }

    /**
     * @param  OrganizerDocumentTemplatePlaceholderEnum $placeholder
     * @return PlaceholderFiller
     * @throws \Exception
     */
    protected function getPlaceholderFiller(OrganizerDocumentTemplatePlaceholderEnum $placeholder): PlaceholderFiller
    {
        $all = $this->getAllPlaceholderFillers();
        foreach ($all as $placeholderFiller) {
            if ($placeholderFiller->canReplacePlaceholder($placeholder)) {
                return $placeholderFiller;
            }
        }

        throw new PlaceholderNotExistsException("Placeholder $placeholder->value not found");
    }


    /**
     * @return array
     */
    protected function getAllPlaceholderFillers(): array
    {
        return $this->placeholders;
    }

    /**
     * Проверяет: явлется ли переменная шаблона обязательной к заполнению для генерации файла
     * @param  OrganizerDocumentTemplatePlaceholderEnum $placeholder
     * @return bool
     */
    protected function isRequiredPlaceholder(OrganizerDocumentTemplatePlaceholderEnum $placeholder): bool
    {
        if (in_array($placeholder, [
            OrganizerDocumentTemplatePlaceholderEnum::COMPANY_EXECUTIVE_MIDDLE_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::CLIENT_MIDDLE_NAME,
            OrganizerDocumentTemplatePlaceholderEnum::TOUR_PROVIDER_MIDDLE_NAME])
        ) {
            return false;
        }
        return true;
    }

    protected function hiddenPlaceholders(): array
    {
        return [];
    }

    /**
     * @param string $template
     *
     * @return OrganizerDocumentTemplatePlaceholderEnum[]
     */
    private function getPlaceholderParams(string $template): array
    {
        preg_match_all('/(\{.*?\})/', $template, $matches);
        $result = [];
        foreach ($matches[1] as $param) {
            if ($placeholder = OrganizerDocumentTemplatePlaceholderEnum::tryFrom($param)) {
                $result[] = $placeholder;
            }
        }

        return $result;
    }

    /**
     * @return OrganizerDocumentTemplatePlaceholderEnum[]
     */
    abstract protected function getSupportedPlaceholders(): array;
}
