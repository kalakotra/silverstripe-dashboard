<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Forms;

use SilverStripe\Forms\Form;
use SilverStripe\Model\ArrayData;

/**
 * DashboardForm
 *
 * Form wrapper that exposes arbitrary template data as $FormData,
 * keeping DashboardForm.ss compatible with custom dashboard context.
 */
class DashboardForm extends Form
{
    private ArrayData $formData;

    public function setFormData(array $data): self
    {
        $this->formData = ArrayData::create($data);

        return $this;
    }

    public function getFormData(): ArrayData
    {
        if (!isset($this->formData)) {
            $this->formData = ArrayData::create([]);
        }

        return $this->formData;
    }
}
