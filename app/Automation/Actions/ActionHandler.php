<?php

namespace App\Automation\Actions;

use App\Models\AutomationJob;

interface ActionHandler
{
    public function handle(AutomationJob $job): ActionResult;
}
