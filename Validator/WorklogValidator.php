<?php

namespace Kanboard\Plugin\TimeLog\Validator;

use Kanboard\Validator\BaseValidator;
use SimpleValidator\Validator;
use SimpleValidator\Validators;

class WorklogValidator extends BaseValidator
{
    public function validateCreation(array &$values)
    {
        return $this->validate($values, true);
    }

    public function validateModification(array &$values)
    {
        return $this->validate($values, false);
    }

    private function validate(array &$values, $creation)
    {
        $this->normalizeDate($values);

        $rules = array(
            new Validators\Required('task_id', t('The task id is required')),
            new Validators\Required('user_id', t('The user id is required')),
            new Validators\Required('work_date', t('The date is required')),
            new Validators\Required('hours', t('The time is required')),
            new Validators\Integer('task_id', t('The task id must be an integer')),
            new Validators\Integer('user_id', t('The user id must be an integer')),
            new Validators\Date('work_date', t('The date format is invalid'), $this->dateParser->getDateFormats(true)),
            new Validators\Numeric('hours', t('The time must be a numeric value')),
            new Validators\MaxLength('comment', t('The maximum length is %d characters', 65535), 65535),
        );

        if (! $creation) {
            $rules[] = new Validators\Required('id', t('The worklog id is required'));
            $rules[] = new Validators\Integer('id', t('The worklog id must be an integer'));
        }

        $validator = new Validator($values, $rules);
        $valid = $validator->execute();

        if ($valid && (float) $values['hours'] <= 0) {
            $validator->addError('hours', t('The time must be greater than zero'));
            $valid = false;
        }

        if ($valid) {
            $values['hours'] = round((float) $values['hours'], 2);
            $values['work_date'] = $this->dateParser->getIsoDate($values['work_date']);
        }

        return array($valid, $validator->getErrors());
    }

    private function normalizeDate(array &$values)
    {
        if (! isset($values['work_date'])) {
            return;
        }

        $value = trim((string) $values['work_date']);

        if (ctype_digit($value) && strlen($value) > 4) {
            $value = $this->dateParser->getIsoDate($value);
        }

        $values['work_date'] = $value;
    }
}
