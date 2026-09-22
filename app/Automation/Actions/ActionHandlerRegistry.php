<?php

namespace App\Automation\Actions;

use App\Automation\ActionType;
use InvalidArgumentException;

final class ActionHandlerRegistry
{
    public function __construct(private readonly EmailActionHandler $email)
    {
    }

    public function for(ActionType $type): ActionHandler
    {
        return match ($type) {
            ActionType::Email => $this->email,
            default => throw new InvalidArgumentException('Bu aksiyon tipi henüz uygulanmadı: '.$type->value),
        };
    }
}
